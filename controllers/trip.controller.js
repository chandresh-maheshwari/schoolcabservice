const { Op } = require('sequelize');
const Child = require('../models/Child');
const Driver = require('../models/Driver');
const Trip = require('../models/Trip');
const {
  buildStopsNearestFirst,
  calculateRoute,
  calculateRouteWithWaypoints,
} = require('../services/route.service');
const { ensureTripsTable } = require('../services/runtime-schema.service');
const {
  findUserByLogin,
  getDriverProfileForUser,
  getAssignedChildrenForDriverUser,
  getChildForParentUser,
  getChildRecordById,
  getParentUserIdForChild,
  getRouteStopsByRouteId,
  isLegacyNodeUserSchema,
  updateSharedDriverStateForUser,
} = require('../services/schema-compat.service');
const {
  sendEventToUsers,
  sendChildEvent,
} = require('../services/push-notification.service');

function parseMaybeJson(value) {
  if (typeof value !== 'string') return value;
  try {
    return JSON.parse(value);
  } catch (_) {
    return value;
  }
}

function parseCoordinate(value) {
  if (value === null || value === undefined) return null;
  const num = typeof value === 'string' ? Number(value.trim()) : Number(value);
  return Number.isFinite(num) ? num : null;
}

function distanceInMeters(lat1, lng1, lat2, lng2) {
  const toRadians = (degrees) => (degrees * Math.PI) / 180;
  const earthRadius = 6371000;
  const dLat = toRadians(lat2 - lat1);
  const dLng = toRadians(lng2 - lng1);
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(toRadians(lat1)) *
      Math.cos(toRadians(lat2)) *
      Math.sin(dLng / 2) *
      Math.sin(dLng / 2);

  return earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function normalizeId(value) {
  const num = Number(value);
  return Number.isFinite(num) && num > 0 ? Math.trunc(num) : null;
}

function getLastCompletedStopIndex(stops) {
  if (!Array.isArray(stops)) return -1;

  for (let index = stops.length - 1; index >= 0; index -= 1) {
    if (stops[index]?.status === 'completed') {
      return index;
    }
  }

  return -1;
}

async function resolveParentIdFromChildId(childId) {
  const normalizedChildId = normalizeId(childId);
  if (!normalizedChildId) return null;
  return normalizeId(await getParentUserIdForChild(normalizedChildId));
}

async function resolveParentUserIdsForChildren(children = []) {
  const childIds = children
    .map((child) => normalizeId(child?.id ?? child?.childId))
    .filter(Boolean);

  const parentIds = await Promise.all(childIds.map((childId) => resolveParentIdFromChildId(childId)));
  return [...new Set(parentIds.filter(Boolean))];
}

function getProximityEventKey(stop, tripType, stage) {
  if (!stop?.type || !tripType) return null;

  if (stage === 'near') {
    if (stop.type === 'pickup') {
      return tripType === 'morning' ? 'vehicle_near_pickup' : 'vehicle_near_school';
    }

    if (stop.type === 'dropoff') {
      return tripType === 'morning' ? 'vehicle_near_school' : 'vehicle_near_dropoff';
    }
  }

  if (stage === 'arrived') {
    if (stop.type === 'pickup') {
      return tripType === 'morning' ? 'vehicle_arrived_pickup' : 'vehicle_arrived_school';
    }

    if (stop.type === 'dropoff') {
      return tripType === 'morning' ? 'vehicle_arrived_school' : 'vehicle_arrived_dropoff';
    }
  }

  return null;
}

async function notifyTripProgressIfNeeded(trip, driverLat, driverLng) {
  const normalizedTrip = normalizeTripRecord(trip);
  const stops = Array.isArray(normalizedTrip?.stops) ? [...normalizedTrip.stops] : [];
  const nextIndex = stops.findIndex((stop) => stop?.status === 'pending');
  if (nextIndex === -1) {
    return;
  }

  const stop = { ...stops[nextIndex] };
  const stopLat = parseCoordinate(stop.lat);
  const stopLng = parseCoordinate(stop.lng);
  if (!stop.childId || stopLat === null || stopLng === null) {
    return;
  }

  const distance = distanceInMeters(driverLat, driverLng, stopLat, stopLng);
  let changed = false;

  if (distance <= 250 && !stop.nearNotifiedAt) {
    const eventKey = getProximityEventKey(stop, normalizedTrip.tripType, 'near');
    if (eventKey) {
      await sendChildEvent(
        eventKey,
        stop.childId,
        {
          childName: stop.name || undefined,
          stopLabel: stop.name || 'stop',
          tripType: normalizedTrip.tripType,
        },
        {
          tripId: trip.id,
          tripType: normalizedTrip.tripType,
          stopType: stop.type,
          stopId: stop.stopId ?? null,
          distanceMeters: Math.round(distance),
        }
      );
      stop.nearNotifiedAt = new Date().toISOString();
      changed = true;
    }
  }

  if (distance <= 60 && !stop.arrivedNotifiedAt) {
    const eventKey = getProximityEventKey(stop, normalizedTrip.tripType, 'arrived');
    if (eventKey) {
      await sendChildEvent(
        eventKey,
        stop.childId,
        {
          childName: stop.name || undefined,
          stopLabel: stop.name || 'stop',
          tripType: normalizedTrip.tripType,
        },
        {
          tripId: trip.id,
          tripType: normalizedTrip.tripType,
          stopType: stop.type,
          stopId: stop.stopId ?? null,
          distanceMeters: Math.round(distance),
        }
      );
      stop.arrivedNotifiedAt = new Date().toISOString();
      changed = true;
    }
  }

  if (!changed) {
    return;
  }

  stops[nextIndex] = stop;
  await trip.update({
    stops,
    nextStop: stop,
  });
}

function emitToRooms(io, rooms, eventName, payload) {
  const uniqueRooms = [...new Set(rooms.filter(Boolean))];
  uniqueRooms.forEach((room) => io.to(room).emit(eventName, payload));
}

async function emitTripScopedEvent(req, eventName, payload = {}, options = {}) {
  const io = req.app.get('io');
  if (!io) return;

  const tripId = normalizeId(options.tripId ?? payload.tripId);
  const childId = normalizeId(options.childId ?? payload.childId);
  let parentId = normalizeId(options.parentId ?? payload.parentId);

  if (!parentId && childId) {
    parentId = await resolveParentIdFromChildId(childId);
  }

  const eventPayload = {
    ...payload,
    tripId: tripId ?? null,
    childId: childId ?? payload.childId ?? null,
    parentId: parentId ?? payload.parentId ?? null,
    emittedAt: new Date().toISOString(),
  };

  const rooms = [];
  if (tripId) rooms.push(`trip:${tripId}`);
  if (parentId) rooms.push(`parent:${parentId}`);
  if (childId) rooms.push(`child:${childId}`);

  if (options.broadcastParentRole) rooms.push('role:parent');
  if (options.broadcastDriverRole) rooms.push('role:driver');

  if (rooms.length === 0) {
    io.emit(eventName, eventPayload);
    return;
  }

  emitToRooms(io, rooms, eventName, eventPayload);
}

function normalizeTripRecord(trip) {
  if (!trip) return null;
  const raw = trip.toJSON ? trip.toJSON() : trip;

  const rawStops = parseMaybeJson(raw.stops);
  const stops = Array.isArray(rawStops)
    ? rawStops
        .map((s) => parseMaybeJson(s))
        .filter((s) => s && typeof s === 'object')
        .map((s) => ({
          ...s,
          lat: parseCoordinate(s.lat) ?? s.lat,
          lng: parseCoordinate(s.lng) ?? s.lng,
        }))
    : [];

  const rawNextStop = parseMaybeJson(raw.nextStop);
  const nextStop =
    rawNextStop && typeof rawNextStop === 'object'
      ? {
          ...rawNextStop,
          lat: parseCoordinate(rawNextStop.lat) ?? rawNextStop.lat,
          lng: parseCoordinate(rawNextStop.lng) ?? rawNextStop.lng,
        }
      : null;

  const rawRoute = parseMaybeJson(raw.currentRoute);
  let currentRoute = null;
  if (rawRoute && typeof rawRoute === 'object') {
    const rawPoints = parseMaybeJson(rawRoute.points);
    const points = Array.isArray(rawPoints)
      ? rawPoints
          .map((p) => parseMaybeJson(p))
          .filter((p) => p && typeof p === 'object')
          .map((p) => ({
            ...p,
            lat: parseCoordinate(p.lat) ?? p.lat,
            lng: parseCoordinate(p.lng) ?? p.lng,
          }))
      : [];
    currentRoute = { ...rawRoute, points };
  }

  return {
    ...raw,
    driverLat: parseCoordinate(raw.driverLat) ?? raw.driverLat,
    driverLng: parseCoordinate(raw.driverLng) ?? raw.driverLng,
    routeId: raw.routeId ?? null,
    driverUserId: raw.driverUserId ?? null,
    stops,
    nextStop,
    currentRoute,
  };
}

async function computeChildRoutePreviewFromTrip(normalizedTrip, childId) {
  const normalizedChildId = normalizeId(childId);
  if (!normalizedTrip || !normalizedChildId) {
    return { points: [], distance: 0, duration: 0 };
  }

  const stops = normalizedTrip.stops || [];
  const nextStop = normalizedTrip.nextStop;
  if (!nextStop) {
    return { points: [], distance: 0, duration: 0 };
  }

  const pickupPendingIndex = stops.findIndex(
    (stop) =>
      String(stop.childId) === String(normalizedChildId) &&
      stop.type === 'pickup' &&
      stop.status === 'pending'
  );
  const dropoffPendingIndex = stops.findIndex(
    (stop) =>
      String(stop.childId) === String(normalizedChildId) &&
      stop.type === 'dropoff' &&
      stop.status === 'pending'
  );

  const targetType = pickupPendingIndex !== -1 ? 'pickup' : 'dropoff';
  if (targetType === 'dropoff' && dropoffPendingIndex === -1) {
    return { points: [], distance: 0, duration: 0 };
  }

  const nextStopIndex = stops.findIndex(
    (stop) =>
      String(stop.childId) === String(nextStop.childId) &&
      stop.type === nextStop.type &&
      stop.status === 'pending'
  );
  const targetStopIndex = stops.findIndex(
    (stop) =>
      String(stop.childId) === String(normalizedChildId) &&
      stop.type === targetType &&
      stop.status === 'pending'
  );

  if (nextStopIndex === -1 || targetStopIndex === -1 || targetStopIndex < nextStopIndex) {
    return { points: [], distance: 0, duration: 0 };
  }

  const waypoints = [
    { lat: normalizedTrip.driverLat, lng: normalizedTrip.driverLng },
    ...stops.slice(nextStopIndex, targetStopIndex + 1).map((stop) => ({
      lat: stop.lat,
      lng: stop.lng,
    })),
  ];

  return calculateRouteWithWaypoints(waypoints);
}

function sortStopsBySequence(stops) {
  return [...stops].sort((left, right) => {
    const leftSeq = Number.isFinite(Number(left.sequenceOrder)) ? Number(left.sequenceOrder) : Number.MAX_SAFE_INTEGER;
    const rightSeq = Number.isFinite(Number(right.sequenceOrder)) ? Number(right.sequenceOrder) : Number.MAX_SAFE_INTEGER;
    if (leftSeq !== rightSeq) return leftSeq - rightSeq;
    return normalizeId(left.childId) - normalizeId(right.childId);
  });
}

function normalizeStopKey(value) {
  const trimmed = String(value ?? '').trim();
  return trimmed ? trimmed.toLowerCase() : null;
}

function getTodayDateKey() {
  const today = new Date();
  return [
    today.getFullYear(),
    String(today.getMonth() + 1).padStart(2, '0'),
    String(today.getDate()).padStart(2, '0'),
  ].join('-');
}

function resolveTodayPickupOverride(child, raw) {
  const todayPickupName = String(child?.todayPickupName ?? raw?.today_pickup_name ?? '').trim();
  const todayPickupDate = String(child?.todayPickupDate ?? raw?.today_pickup_date ?? '').trim();
  if (!todayPickupName || !todayPickupDate) {
    return null;
  }

  return todayPickupDate === getTodayDateKey() ? todayPickupName : null;
}

function buildStopMap(routeStops) {
  const stopMap = new Map();

  for (const stop of routeStops) {
    const normalizedStop = {
      id: normalizeId(stop.id),
      pickupName: stop.pickup_name || null,
      stopName: stop.stop_name || null,
      lat: parseCoordinate(stop.latitude),
      lng: parseCoordinate(stop.longitude),
      sequenceOrder: normalizeId(stop.sequence_order) ?? Number(stop.sequence_order) ?? null,
    };

    const idKey = normalizeStopKey(stop.id);
    if (idKey) stopMap.set(idKey, normalizedStop);

    const sequenceKey = normalizeStopKey(stop.sequence_order ?? stop.sequenceOrder);
    if (sequenceKey) stopMap.set(sequenceKey, normalizedStop);

    const pickupKey = normalizeStopKey(stop.pickup_name);
    if (pickupKey) stopMap.set(pickupKey, normalizedStop);

    const stopKey = normalizeStopKey(stop.stop_name);
    if (stopKey) stopMap.set(stopKey, normalizedStop);
  }

  return stopMap;
}

function buildStopsFromSharedRoute(children, routeStops, tripType = 'morning') {
  const stopMap = buildStopMap(routeStops);

  const isMorning = tripType === 'morning';
  const generatedStops = [];

  for (const child of children) {
    const raw = child.raw || child;
    const overridePickupStopId = resolveTodayPickupOverride(child, raw);
    const pickupStopId = isMorning ? (overridePickupStopId || raw.pickup_name) : raw.stop_name;
    const dropStopId = isMorning ? raw.stop_name : raw.pickup_name;
    const pickupRouteStop = stopMap.get(normalizeStopKey(pickupStopId));
    const dropRouteStop = stopMap.get(normalizeStopKey(dropStopId));
    const childId = normalizeId(child.id ?? raw.id);
    const childName = child.name || child.child_name || 'Child';

    if (pickupRouteStop && pickupRouteStop.lat !== null && pickupRouteStop.lng !== null) {
      generatedStops.push({
        childId,
        name: childName,
        type: 'pickup',
        lat: pickupRouteStop.lat,
        lng: pickupRouteStop.lng,
        status: 'pending',
        stopId: pickupRouteStop.id,
        sequenceOrder: pickupRouteStop.sequenceOrder,
      });
    }

    if (dropRouteStop && dropRouteStop.lat !== null && dropRouteStop.lng !== null) {
      generatedStops.push({
        childId,
        name: childName,
        type: 'dropoff',
        lat: dropRouteStop.lat,
        lng: dropRouteStop.lng,
        status: 'pending',
        stopId: dropRouteStop.id,
        sequenceOrder: dropRouteStop.sequenceOrder,
      });
    }
  }

  return sortStopsBySequence(generatedStops);
}

function diagnoseSharedStops(children, routeStops, tripType = 'morning') {
  const stopMap = buildStopMap(routeStops);
  const isMorning = tripType === 'morning';
  const missingStops = [];
  const invalidCoordinates = [];

  for (const child of children) {
    const raw = child.raw || child;
    const overridePickupStopId = resolveTodayPickupOverride(child, raw);
    const pickupStopId = isMorning ? (overridePickupStopId || raw.pickup_name) : raw.stop_name;
    const dropStopId = isMorning ? raw.stop_name : raw.pickup_name;
    const childId = normalizeId(child.id ?? raw.id);
    const childName = child.name || child.child_name || 'Child';

    const pickupRouteStop = stopMap.get(normalizeStopKey(pickupStopId));
    if (!pickupRouteStop) {
      missingStops.push({ childId, childName, type: 'pickup', value: pickupStopId ?? null });
    } else if (pickupRouteStop.lat === null || pickupRouteStop.lng === null) {
      invalidCoordinates.push({ childId, childName, type: 'pickup', value: pickupStopId ?? null });
    }

    const dropRouteStop = stopMap.get(normalizeStopKey(dropStopId));
    if (!dropRouteStop) {
      missingStops.push({ childId, childName, type: 'dropoff', value: dropStopId ?? null });
    } else if (dropRouteStop.lat === null || dropRouteStop.lng === null) {
      invalidCoordinates.push({ childId, childName, type: 'dropoff', value: dropStopId ?? null });
    }
  }

  const hasUsableRouteStops = routeStops.some((stop) => {
    const lat = parseCoordinate(stop.latitude);
    const lng = parseCoordinate(stop.longitude);
    return lat !== null && lng !== null;
  });

  return {
    hasUsableRouteStops,
    missingStops,
    invalidCoordinates,
  };
}

function buildStopsFromRouteStopsOnly(routeStops) {
  const normalizedStops = routeStops
    .map((stop, index) => {
      const lat = parseCoordinate(stop.latitude);
      const lng = parseCoordinate(stop.longitude);
      if (lat === null || lng === null) return null;

      return {
        childId: null,
        name: stop.pickup_name || stop.stop_name || `Stop ${index + 1}`,
        type: 'stop',
        lat,
        lng,
        status: 'pending',
        stopId: normalizeId(stop.id) ?? null,
        sequenceOrder: normalizeId(stop.sequence_order) ?? Number(stop.sequence_order) ?? index + 1,
      };
    })
    .filter(Boolean);

  return sortStopsBySequence(normalizedStops);
}

async function buildSharedTripContext(loginValue) {
  const user = await findUserByLogin(loginValue);
  if (!user) {
    return { error: { status: 404, body: { message: 'Driver user not found' } } };
  }

  const driver = await getDriverProfileForUser(user.id);
  if (!driver) {
    return { error: { status: 404, body: { message: 'Driver profile not found' } } };
  }

  if (!driver.routeId) {
    return { error: { status: 409, body: { message: 'No active route is assigned to this driver' } } };
  }

  const routeStops = await getRouteStopsByRouteId(driver.routeId);
  if (!routeStops.length) {
    return { error: { status: 409, body: { message: 'No stop coordinates are configured for the assigned route' } } };
  }

  const children = await getAssignedChildrenForDriverUser(user.id);
  return { user, driver, routeStops, children };
}

async function getRunningTrip() {
  await ensureTripsTable();
  return Trip.findOne({ where: { status: 'running' } });
}

async function computeNextRoute(driverLat, driverLng, nextStop) {
  if (!nextStop) return null;
  return calculateRoute(
    { lat: parseCoordinate(driverLat), lng: parseCoordinate(driverLng) },
    nextStop
  );
}

function isSameCoordinate(left, right) {
  if (!left || !right) return false;
  const leftLat = parseCoordinate(left.lat);
  const leftLng = parseCoordinate(left.lng);
  const rightLat = parseCoordinate(right.lat);
  const rightLng = parseCoordinate(right.lng);
  return leftLat !== null && leftLng !== null && leftLat === rightLat && leftLng === rightLng;
}

function calculateDistanceKm(left, right) {
  const leftLat = parseCoordinate(left?.lat);
  const leftLng = parseCoordinate(left?.lng);
  const rightLat = parseCoordinate(right?.lat);
  const rightLng = parseCoordinate(right?.lng);
  if (leftLat === null || leftLng === null || rightLat === null || rightLng === null) {
    return null;
  }

  const toRadians = (degrees) => (degrees * Math.PI) / 180;
  const earthRadiusKm = 6371;
  const deltaLat = toRadians(rightLat - leftLat);
  const deltaLng = toRadians(rightLng - leftLng);
  const a =
    Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2) +
    Math.cos(toRadians(leftLat)) *
      Math.cos(toRadians(rightLat)) *
      Math.sin(deltaLng / 2) *
      Math.sin(deltaLng / 2);

  return earthRadiusKm * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function buildStopLabel(stop, tripType) {
  if (!stop) return 'the stop';
  if (stop.type === 'pickup') return stop.name ? `${stop.name}'s pickup stop` : 'the pickup stop';
  if (stop.type === 'dropoff' && tripType === 'morning') return 'school';
  if (stop.type === 'dropoff') return stop.name ? `${stop.name}'s drop-off stop` : 'the drop-off stop';
  return stop.name || 'the stop';
}

function buildStopMetaMap(route = null) {
  const metaMap = new Map();
  const routeStops = Array.isArray(route?.stopsMeta) ? route.stopsMeta : [];

  for (const stop of routeStops) {
    const stopId = normalizeId(stop.id);
    if (!stopId) continue;

    metaMap.set(stopId, {
      id: stopId,
      name: stop.name || null,
      pickupName: stop.pickupName || null,
      stopName: stop.stopName || null,
      lat: parseCoordinate(stop.lat) ?? null,
      lng: parseCoordinate(stop.lng) ?? null,
      sequenceOrder: normalizeId(stop.sequenceOrder) ?? Number(stop.sequenceOrder) ?? null,
    });
  }

  return metaMap;
}

function resolveGroupedStopLabel(stop, meta = null, tripType = 'morning') {
  if (stop?.type === 'pickup') {
    return meta?.pickupName || meta?.name || stop?.pickupName || stop?.stopName || stop?.name || 'Pickup stop';
  }

  if (stop?.type === 'dropoff') {
    if (tripType === 'morning') {
      return meta?.stopName || meta?.name || stop?.stopName || stop?.name || 'School';
    }
    return meta?.pickupName || meta?.name || stop?.pickupName || stop?.name || 'Drop-off stop';
  }

  return meta?.name || stop?.name || 'Stop';
}

function buildStopGroupsFromTrip(normalizedTrip) {
  const stops = Array.isArray(normalizedTrip?.stops) ? normalizedTrip.stops : [];
  const tripType = normalizedTrip?.tripType || 'morning';
  const stopMetaMap = buildStopMetaMap(normalizedTrip?.currentRoute);
  const groups = [];
  const groupMap = new Map();
  const nextStop = normalizedTrip?.nextStop || null;

  const activeGroupKey = nextStop
    ? [
        nextStop.type || 'stop',
        normalizeId(nextStop.stopId) ?? 'na',
        Number.isFinite(Number(nextStop.sequenceOrder)) ? Number(nextStop.sequenceOrder) : 'na',
        parseCoordinate(nextStop.lat) ?? 'na',
        parseCoordinate(nextStop.lng) ?? 'na',
      ].join(':')
    : null;

  for (const stop of stops) {
    if (!stop || !stop.type || !['pickup', 'dropoff'].includes(stop.type)) continue;

    const stopId = normalizeId(stop.stopId);
    const sequenceOrder = Number.isFinite(Number(stop.sequenceOrder)) ? Number(stop.sequenceOrder) : null;
    const lat = parseCoordinate(stop.lat);
    const lng = parseCoordinate(stop.lng);
    const groupKey = [
      stop.type,
      stopId ?? 'na',
      sequenceOrder ?? 'na',
      lat ?? 'na',
      lng ?? 'na',
    ].join(':');

    let group = groupMap.get(groupKey);
    if (!group) {
      const meta = stopId ? stopMetaMap.get(stopId) || null : null;
      group = {
        key: groupKey,
        stopId,
        sequenceOrder,
        type: stop.type,
        tripType,
        stopLabel: resolveGroupedStopLabel(stop, meta, tripType),
        lat: lat ?? meta?.lat ?? null,
        lng: lng ?? meta?.lng ?? null,
        totalChildren: 0,
        pendingChildren: 0,
        completedChildren: 0,
        isActive: activeGroupKey === groupKey,
        children: [],
      };
      groupMap.set(groupKey, group);
      groups.push(group);
    }

    const childId = normalizeId(stop.childId);
    const childStatus = String(stop.status || 'pending');
    group.totalChildren += 1;
    if (childStatus === 'completed') {
      group.completedChildren += 1;
    } else {
      group.pendingChildren += 1;
    }

    group.children.push({
      childId,
      name: stop.name || 'Child',
      status: childStatus,
      type: stop.type,
      stopId,
      sequenceOrder,
      isNextStop:
        !!nextStop &&
        String(nextStop.childId) === String(stop.childId) &&
        String(nextStop.type) === String(stop.type) &&
        String(nextStop.status || 'pending') === String(stop.status || 'pending'),
      canVerifyPickup: stop.type === 'pickup' && childStatus === 'pending',
      canConfirmDropoff: stop.type === 'dropoff' && childStatus === 'pending',
    });
  }

  return groups.sort((left, right) => {
    const leftSeq = Number.isFinite(Number(left.sequenceOrder)) ? Number(left.sequenceOrder) : Number.MAX_SAFE_INTEGER;
    const rightSeq = Number.isFinite(Number(right.sequenceOrder)) ? Number(right.sequenceOrder) : Number.MAX_SAFE_INTEGER;
    if (leftSeq !== rightSeq) return leftSeq - rightSeq;
    return String(left.stopLabel || '').localeCompare(String(right.stopLabel || ''));
  });
}

function buildTripEventKey(stop, tripType, thresholdType) {
  if (!stop) return null;
  if (stop.type === 'pickup') {
    return thresholdType === 'arrived' ? 'vehicle_arrived_pickup' : 'vehicle_near_pickup';
  }
  if (stop.type === 'dropoff' && tripType === 'morning') {
    return thresholdType === 'arrived' ? 'vehicle_arrived_school' : 'vehicle_near_school';
  }
  if (stop.type === 'dropoff') {
    return thresholdType === 'arrived' ? 'vehicle_arrived_dropoff' : 'vehicle_near_dropoff';
  }
  return null;
}

async function maybeSendProximityNotification(req, trip, normalizedTrip) {
  if (!trip || !normalizedTrip?.nextStop?.childId) return;

  const nextStop = normalizedTrip.nextStop;
  const distanceKm = calculateDistanceKm(
    { lat: normalizedTrip.driverLat, lng: normalizedTrip.driverLng },
    nextStop
  );
  if (distanceKm == null) return;

  const nearThresholdKm = Number(process.env.PUSH_NOTIFY_NEAR_STOP_KM || 0.4);
  const arrivedThresholdKm = Number(process.env.PUSH_NOTIFY_ARRIVED_STOP_KM || 0.1);

  const stopIndex = normalizedTrip.stops.findIndex(
    (stop) =>
      String(stop.childId) === String(nextStop.childId) &&
      stop.type === nextStop.type &&
      stop.status === 'pending'
  );
  if (stopIndex === -1) return;

  const stop = normalizedTrip.stops[stopIndex];
  const notifications = { ...(stop.notifications || {}) };
  const parentUserId = await resolveParentIdFromChildId(stop.childId);
  if (!parentUserId) return;

  const context = {
    childName: stop.name || 'Child',
    stopLabel: buildStopLabel(stop, normalizedTrip.tripType),
    distanceKm: distanceKm.toFixed(2),
    tripType: normalizedTrip.tripType || 'morning',
  };

  let changed = false;

  if (!notifications.nearSent && distanceKm <= nearThresholdKm) {
    const eventKey = buildTripEventKey(stop, normalizedTrip.tripType, 'near');
    if (eventKey) {
      await sendEventNotification({
        eventKey,
        userIds: [parentUserId],
        context,
        data: {
          tripId: normalizedTrip.id,
          childId: stop.childId,
          stopType: stop.type,
          distanceKm,
        },
      });
      notifications.nearSent = true;
      notifications.nearSentAt = new Date().toISOString();
      changed = true;
    }
  }

  if (!notifications.arrivedSent && distanceKm <= arrivedThresholdKm) {
    const eventKey = buildTripEventKey(stop, normalizedTrip.tripType, 'arrived');
    if (eventKey) {
      await sendEventNotification({
        eventKey,
        userIds: [parentUserId],
        context,
        data: {
          tripId: normalizedTrip.id,
          childId: stop.childId,
          stopType: stop.type,
          distanceKm,
        },
      });
      notifications.arrivedSent = true;
      notifications.arrivedSentAt = new Date().toISOString();
      changed = true;
    }
  }

  if (changed) {
    normalizedTrip.stops[stopIndex] = { ...stop, notifications };
    await trip.update({ stops: normalizedTrip.stops });
  }
}

function buildPendingWaypoints(driverLat, driverLng, stops) {
  const waypoints = [];
  const origin = { lat: parseCoordinate(driverLat), lng: parseCoordinate(driverLng) };
  if (origin.lat === null || origin.lng === null) return [];
  waypoints.push(origin);

  const pendingStops = Array.isArray(stops) ? stops.filter((stop) => stop?.status === 'pending') : [];
  for (const stop of pendingStops) {
    const lat = parseCoordinate(stop.lat);
    const lng = parseCoordinate(stop.lng);
    if (lat === null || lng === null) continue;

    const nextPoint = { lat, lng };
    const lastPoint = waypoints[waypoints.length - 1];
    if (isSameCoordinate(lastPoint, nextPoint)) continue;
    waypoints.push(nextPoint);

    // Keep waypoint count reasonable for OSRM public endpoint.
    if (waypoints.length >= 50) break;
  }

  return waypoints.length >= 2 ? waypoints : [];
}

function buildWaypointsFromRouteStops(driverLat, driverLng, routeStops) {
  const waypoints = [];
  const origin = { lat: parseCoordinate(driverLat), lng: parseCoordinate(driverLng) };
  if (origin.lat === null || origin.lng === null) return [];
  waypoints.push(origin);

  const normalizedStops = Array.isArray(routeStops) ? routeStops : [];
  for (const stop of normalizedStops) {
    const lat = parseCoordinate(stop.latitude ?? stop.lat);
    const lng = parseCoordinate(stop.longitude ?? stop.lng);
    if (lat === null || lng === null) continue;

    const nextPoint = { lat, lng };
    const lastPoint = waypoints[waypoints.length - 1];
    if (isSameCoordinate(lastPoint, nextPoint)) continue;
    waypoints.push(nextPoint);

    if (waypoints.length >= 50) break;
  }

  return waypoints.length >= 2 ? waypoints : [];
}

function buildWaypointsFromTail(driverLat, driverLng, tailPoints) {
  const waypoints = [];
  const origin = { lat: parseCoordinate(driverLat), lng: parseCoordinate(driverLng) };
  if (origin.lat === null || origin.lng === null) return [];
  waypoints.push(origin);

  const tail = Array.isArray(tailPoints) ? tailPoints : [];
  for (const point of tail) {
    const lat = parseCoordinate(point?.lat);
    const lng = parseCoordinate(point?.lng);
    if (lat === null || lng === null) continue;

    const nextPoint = { lat, lng };
    const lastPoint = waypoints[waypoints.length - 1];
    if (isSameCoordinate(lastPoint, nextPoint)) continue;
    waypoints.push(nextPoint);

    if (waypoints.length >= 50) break;
  }

  return waypoints.length >= 2 ? waypoints : [];
}

async function computeTripRoute(driverLat, driverLng, stops, options = {}) {
  const waypointsTail = options.waypointsTail;
  const routeStops = options.routeStops;

  const waypoints =
    Array.isArray(waypointsTail) && waypointsTail.length
      ? buildWaypointsFromTail(driverLat, driverLng, waypointsTail)
      : Array.isArray(routeStops) && routeStops.length
          ? buildWaypointsFromRouteStops(driverLat, driverLng, routeStops)
          : buildPendingWaypoints(driverLat, driverLng, stops);

  if (!waypoints.length) return null;
  const route = await calculateRouteWithWaypoints(waypoints);

  const stopsMeta = Array.isArray(routeStops) && routeStops.length
    ? routeStops
        .map((stop) => ({
          id: normalizeId(stop.id) ?? stop.id ?? null,
          name: stop.pickup_name ?? stop.stop_name ?? stop.name ?? null,
          pickupName: stop.pickup_name ?? null,
          stopName: stop.stop_name ?? null,
          lat: parseCoordinate(stop.latitude ?? stop.lat),
          lng: parseCoordinate(stop.longitude ?? stop.lng),
          sequenceOrder: normalizeId(stop.sequence_order) ?? Number(stop.sequence_order) ?? null,
        }))
        .filter((stop) => stop.lat !== null && stop.lng !== null)
    : null;

  return { ...route, waypoints, stopsMeta };
}

async function refreshLiveTripSnapshot(trip, driverLat, driverLng) {
  const normalizedTrip = normalizeTripRecord(trip);
  if (!normalizedTrip) {
    return null;
  }

  const nextStop = Array.isArray(normalizedTrip.stops)
    ? normalizedTrip.stops.find((stop) => stop?.status === 'pending') || null
    : null;

  let routeStops = [];
  if (normalizedTrip.routeId) {
    routeStops = await getRouteStopsByRouteId(normalizedTrip.routeId);
  }

  const nextRoute = nextStop
    ? await computeTripRoute(driverLat, driverLng, normalizedTrip.stops, {
        waypointsTail: normalizedTrip.currentRoute?.waypoints?.slice(1),
        routeStops,
      })
    : null;

  await trip.update({
    driverLat,
    driverLng,
    nextStop,
    currentRoute: nextRoute,
    status: nextStop ? normalizedTrip.status : 'completed',
  });

  await updateSharedDriverStateForUser(normalizedTrip.driverUserId, {
    currentLat: driverLat,
    currentLng: driverLng,
    stops: normalizedTrip.stops,
    currentRoute: nextRoute,
    lastCompletedStopIndex: getLastCompletedStopIndex(normalizedTrip.stops),
  });

  return normalizeTripRecord(trip);
}

exports.startTrip = async (req, res) => {
  try {
    await ensureTripsTable();

    const { lat, lng, tripType = 'morning' } = req.body;
    const parsedLat = parseCoordinate(lat);
    const parsedLng = parseCoordinate(lng);

    if (parsedLat === null || parsedLng === null) {
      return res.status(400).json({ message: 'Valid lat and lng are required' });
    }

    if (await isLegacyNodeUserSchema()) {
      const query = { subscriptionStatus: 'active' };
      if (tripType === 'morning') {
        query.tripStatus = 'pending';
      } else {
        query.tripStatus = 'dropped';
      }

      const children = await Child.findAll({
        where: query,
        order: [['routeOrder', 'ASC'], ['name', 'DESC']],
      });

      if (!children.length) {
        return res.json({ message: `No children found for ${tripType} trip` });
      }

      if (tripType === 'afternoon') {
        const childIds = children.map((child) => child.id);
        await Child.update({ tripStatus: 'pending' }, { where: { id: childIds } });
      }

      const stops = buildStopsNearestFirst(children, parsedLat, parsedLng, tripType);
      const nextStop = stops[0];
      const route = await computeTripRoute(parsedLat, parsedLng, stops);

      await Trip.destroy({ where: {} });
      const trip = await Trip.create({
        driverLat: parsedLat,
        driverLng: parsedLng,
        stops,
        nextStop,
        currentRoute: route,
        status: 'running',
        tripType,
        direction: tripType === 'morning' ? 'FORWARD' : 'REVERSE',
      });

      await emitTripScopedEvent(req, 'trip_started', normalizeTripRecord(trip), {
        tripId: trip.id,
        broadcastParentRole: true,
        broadcastDriverRole: true,
      });

      const tripParentUserIds = await resolveParentUserIdsForChildren(children);
      await sendEventToUsers(
        'trip_started',
        tripParentUserIds,
        { tripType },
        { tripId: trip.id, tripType }
      );

      return res.json(trip);
    }

    const loginValue = req.body.email || req.query.email;
    if (!loginValue) {
      return res.status(400).json({ message: 'Driver email is required in shared-database mode' });
    }

    const sharedContext = await buildSharedTripContext(loginValue);
    if (sharedContext.error) {
      return res.status(sharedContext.error.status).json(sharedContext.error.body);
    }

    let stops = [];
    if (sharedContext.children.length) {
      stops = buildStopsFromSharedRoute(sharedContext.children, sharedContext.routeStops, tripType);
    }
    if (!stops.length) {
      // Fall back to route stops even when children are assigned, because some schemas
      // store child pickup/stop references that cannot be matched reliably.
      stops = buildStopsFromRouteStopsOnly(sharedContext.routeStops);
    }
    if (!stops.length) {
      if (sharedContext.children.length) {
        const diagnostics = diagnoseSharedStops(sharedContext.children, sharedContext.routeStops, tripType);
        return res.status(409).json({
          message: 'Assigned route is missing usable stop coordinates',
          details: {
            hasUsableRouteStops: diagnostics.hasUsableRouteStops,
            missingStops: diagnostics.missingStops.slice(0, 10),
            invalidCoordinates: diagnostics.invalidCoordinates.slice(0, 10),
          },
        });
      }

      return res.status(409).json({
        message: 'Assigned route is missing usable stop coordinates',
        details: { hasUsableRouteStops: false },
      });
    }

    const nextStop = stops[0];
    const route = await computeTripRoute(parsedLat, parsedLng, stops, { routeStops: sharedContext.routeStops });

    await Trip.destroy({ where: {} });
    const trip = await Trip.create({
      driverLat: parsedLat,
      driverLng: parsedLng,
      routeId: sharedContext.driver.routeId ?? null,
      driverUserId: sharedContext.user.id ?? null,
      stops,
      nextStop,
      currentRoute: route,
      status: 'running',
      tripType,
      direction: tripType === 'morning' ? 'FORWARD' : 'REVERSE',
    });

    await updateSharedDriverStateForUser(sharedContext.user.id, {
      currentLat: parsedLat,
      currentLng: parsedLng,
      vehicleNumber: sharedContext.driver.vehicleNumber,
      vehicleModel: sharedContext.driver.vehicleModel,
      vehicleCapacity: sharedContext.driver.vehicleCapacity,
      stops,
      currentRoute: route,
      lastCompletedStopIndex: -1,
    });

    await emitTripScopedEvent(req, 'trip_started', normalizeTripRecord(trip), {
      tripId: trip.id,
      broadcastParentRole: true,
      broadcastDriverRole: true,
    });

    const tripParentUserIds = await resolveParentUserIdsForChildren(sharedContext.children);
    await sendEventToUsers(
      'trip_started',
      tripParentUserIds,
      { tripType },
      { tripId: trip.id, tripType }
    );

    return res.json(trip);
  } catch (error) {
    console.error('Trip start error:', error);
    return res.status(500).json({
      message: error?.message || 'Trip start failed due to server error',
    });
  }
};

exports.completeStop = async (req, res) => {
  await ensureTripsTable();

  const trip = await getRunningTrip();
  if (!trip) {
    return res.status(404).json({ message: 'No running trip found' });
  }

  const normalizedTrip = normalizeTripRecord(trip);
  if (normalizedTrip?.nextStop?.type && normalizedTrip.nextStop.type !== 'stop') {
    return res.status(409).json({ message: 'complete-stop is only available for generic route stops' });
  }
  const stops = Array.isArray(normalizedTrip.stops) ? [...normalizedTrip.stops] : [];
  const nextIndex = stops.findIndex((stop) => stop.status === 'pending');

  if (nextIndex === -1) {
    await trip.update({ status: 'completed', nextStop: null, currentRoute: null });
    await updateSharedDriverStateForUser(normalizedTrip.driverUserId, {
      currentLat: normalizedTrip.driverLat,
      currentLng: normalizedTrip.driverLng,
      stops,
      currentRoute: null,
      lastCompletedStopIndex: getLastCompletedStopIndex(stops),
    });
    await emitTripScopedEvent(req, 'trip_completed', normalizeTripRecord(trip), {
      tripId: trip.id,
      broadcastParentRole: true,
      broadcastDriverRole: true,
    });
    return res.json({ message: 'Trip already completed' });
  }

  stops[nextIndex].status = 'completed';
  const nextStop = stops.find((stop) => stop.status === 'pending') || null;
  const nextRoute = nextStop
    ? await computeTripRoute(normalizedTrip.driverLat, normalizedTrip.driverLng, stops, {
        waypointsTail: normalizedTrip.currentRoute?.waypoints?.slice(1),
      })
    : null;

  await trip.update({
    stops,
    nextStop,
    currentRoute: nextStop ? nextRoute : null,
    status: nextStop ? normalizedTrip.status : 'completed',
  });

  await updateSharedDriverStateForUser(
    normalizedTrip.driverUserId,
    {
      currentLat: normalizedTrip.driverLat,
      currentLng: normalizedTrip.driverLng,
      stops,
      currentRoute: nextStop ? nextRoute : null,
      lastCompletedStopIndex: getLastCompletedStopIndex(stops),
    }
  );

  await emitTripScopedEvent(req, 'stop_completed', { trip: normalizeTripRecord(trip) }, {
    tripId: trip.id,
    broadcastParentRole: true,
    broadcastDriverRole: true,
  });

  return res.json({ message: 'Stop completed', trip: normalizeTripRecord(trip) });
};

exports.getTripData = async (req, res) => {
  const trip = await getRunningTrip();
  const normalizedTrip = normalizeTripRecord(trip);
  if (!normalizedTrip) {
    return res.json(normalizedTrip);
  }

  return res.json({
    ...normalizedTrip,
    stopGroups: buildStopGroupsFromTrip(normalizedTrip),
  });
};

exports.verifyPickup = async (req, res) => {
  await ensureTripsTable();

  const { childId, pin } = req.body;
  const normalizedChildId = normalizeId(childId);
  if (!normalizedChildId) {
    return res.status(400).json({ message: 'Valid childId is required' });
  }

  if (await isLegacyNodeUserSchema()) {
    const child = await Child.findByPk(normalizedChildId);
    if (!child) return res.status(404).json({ message: 'Child not found' });

    if (child.secretPin !== pin) {
      return res.status(400).json({ message: 'Invalid PIN' });
    }

    await child.update({ tripStatus: 'picked_up' });
  } else {
    const child = await getChildRecordById(normalizedChildId);
    if (!child) return res.status(404).json({ message: 'Child not found' });

    const expectedPin = child.secretPin ? String(child.secretPin) : '';
    const providedPin = pin != null ? String(pin).trim() : '';

    if (!providedPin) {
      return res.status(400).json({ message: 'PIN is required' });
    }

    if (!expectedPin) {
      return res.status(409).json({ message: 'PIN is not set for this child' });
    }

    if (expectedPin !== providedPin) {
      return res.status(400).json({ message: 'Invalid PIN' });
    }
  }

  const trip = await getRunningTrip();
  if (trip) {
    const normalizedTrip = normalizeTripRecord(trip);
    const stops = [...normalizedTrip.stops];
    const stopIndex = stops.findIndex(
      (stop) =>
        String(stop.childId) === String(normalizedChildId) &&
        stop.type === 'pickup' &&
        stop.status === 'pending'
    );

    if (stopIndex === -1) {
      return res.status(409).json({ message: 'Pickup stop is not pending for this child' });
    }

    stops[stopIndex].status = 'completed';
    const nextStop = stops.find((stop) => stop.status === 'pending') || null;
    const route = nextStop
      ? await computeTripRoute(normalizedTrip.driverLat, normalizedTrip.driverLng, stops, {
          waypointsTail: normalizedTrip.currentRoute?.waypoints?.slice(1),
        })
      : null;

    await trip.update({
      stops,
      nextStop,
      currentRoute: route,
      status: nextStop ? normalizedTrip.status : 'completed',
    });

    await updateSharedDriverStateForUser(
      normalizedTrip.driverUserId,
      {
        currentLat: normalizedTrip.driverLat,
        currentLng: normalizedTrip.driverLng,
        stops,
        currentRoute: route,
        lastCompletedStopIndex: getLastCompletedStopIndex(stops),
      }
    );

    await emitTripScopedEvent(
      req,
      'pickup_completed',
      { childId: normalizedChildId, trip: normalizeTripRecord(trip) },
      { tripId: trip.id, childId: normalizedChildId }
    );

    await sendChildEvent(
      'child_picked_up',
      normalizedChildId,
      { tripType: normalizedTrip.tripType },
      { tripId: trip.id, tripType: normalizedTrip.tripType }
    );
  }

  return res.json({ message: 'Pickup verified' });
};

exports.dropChild = async (req, res) => {
  await ensureTripsTable();

  const normalizedChildId = normalizeId(req.body.childId);
  if (!normalizedChildId) {
    return res.status(400).json({ message: 'Valid childId is required' });
  }

  if (await isLegacyNodeUserSchema()) {
    await Child.update({ tripStatus: 'dropped' }, { where: { id: normalizedChildId } });
  } else {
    const child = await getChildRecordById(normalizedChildId);
    if (!child) return res.status(404).json({ message: 'Child not found' });
  }

  const trip = await getRunningTrip();
  if (trip) {
    const normalizedTrip = normalizeTripRecord(trip);
    const stops = [...normalizedTrip.stops];
    const stopIndex = stops.findIndex(
      (stop) =>
        String(stop.childId) === String(normalizedChildId) &&
        stop.type === 'dropoff' &&
        stop.status === 'pending'
    );

    if (stopIndex === -1) {
      return res.status(409).json({ message: 'Drop-off stop is not pending for this child' });
    }

    stops[stopIndex].status = 'completed';
    const nextStop = stops.find((stop) => stop.status === 'pending') || null;
    const nextRoute = nextStop
      ? await computeTripRoute(normalizedTrip.driverLat, normalizedTrip.driverLng, stops, {
          waypointsTail: normalizedTrip.currentRoute?.waypoints?.slice(1),
        })
      : null;

    await trip.update({
      stops,
      nextStop,
      currentRoute: nextRoute,
      status: nextStop ? normalizedTrip.status : 'completed',
    });

    await updateSharedDriverStateForUser(
      normalizedTrip.driverUserId,
      {
        currentLat: normalizedTrip.driverLat,
        currentLng: normalizedTrip.driverLng,
        stops,
        currentRoute: nextRoute,
        lastCompletedStopIndex: getLastCompletedStopIndex(stops),
      }
    );

    await emitTripScopedEvent(
      req,
      'drop_completed',
      { childId: normalizedChildId, trip: normalizeTripRecord(trip) },
      { tripId: trip.id, childId: normalizedChildId }
    );

    await sendChildEvent(
      normalizedTrip.tripType === 'morning' ? 'child_arrived_school' : 'child_dropped_home',
      normalizedChildId,
      { tripType: normalizedTrip.tripType },
      { tripId: trip.id, tripType: normalizedTrip.tripType }
    );
  }

  return res.json({ message: 'Child dropped' });
};

exports.updateDriverLocation = async (req, res) => {
  await ensureTripsTable();

  const { lat, lng } = req.body;
  const parsedLat = parseCoordinate(lat);
  const parsedLng = parseCoordinate(lng);

  if (parsedLat === null || parsedLng === null) {
    return res.status(400).json({ message: 'Valid lat and lng are required' });
  }

  await Trip.update(
    { driverLat: parsedLat, driverLng: parsedLng },
    { where: { status: 'running' } }
  );

  if (await isLegacyNodeUserSchema()) {
    await Driver.update({ currentLat: parsedLat, currentLng: parsedLng }, { where: {} });
    await Child.update(
      { driverCurrentLat: parsedLat, driverCurrentLng: parsedLng },
      { where: { tripStatus: { [Op.in]: ['pending', 'picked_up'] } } }
    );
  } else {
    const loginValue = req.body.email || req.query.email;
    if (loginValue) {
      const user = await findUserByLogin(loginValue);
      if (user) {
        await updateSharedDriverStateForUser(user.id, {
          currentLat: parsedLat,
          currentLng: parsedLng,
        });
      }
    }
  }

  const runningTrip = await getRunningTrip();
  let refreshedTrip = null;
  if (runningTrip) {
    refreshedTrip = await refreshLiveTripSnapshot(runningTrip, parsedLat, parsedLng);
    await notifyTripProgressIfNeeded(runningTrip, parsedLat, parsedLng);
    refreshedTrip = normalizeTripRecord(runningTrip);
  }

  await emitTripScopedEvent(
    req,
    'driver_moved',
    {
      lat: parsedLat,
      lng: parsedLng,
      ...(refreshedTrip
        ? {
            trip: refreshedTrip,
            nextStop: refreshedTrip.nextStop,
            currentRoute: refreshedTrip.currentRoute,
            stops: refreshedTrip.stops,
          }
        : {}),
    },
    { tripId: runningTrip?.id, broadcastParentRole: !runningTrip?.id }
  );

  res.json({ success: true, live: true, trip: refreshedTrip });
};

exports.resetTrip = async (req, res) => {
  await ensureTripsTable();
  await Trip.destroy({ where: {} });

  if (await isLegacyNodeUserSchema()) {
    await Child.update(
      {
        tripStatus: 'pending',
        driverCurrentLat: 23.02431,
        driverCurrentLng: 72.53016,
      },
      { where: {} }
    );

    await Driver.update(
      {
        stops: [],
        currentRoute: null,
        lastCompletedStopIndex: -1,
        currentLat: 23.02431,
        currentLng: 72.53016,
      },
      { where: {} }
    );
  } else {
    const loginValue = req.body.email || req.query.email;
    let driverUserId = null;

    if (loginValue) {
      const user = await findUserByLogin(loginValue);
      driverUserId = user?.id || null;
    }

    await updateSharedDriverStateForUser(driverUserId, {
      stops: [],
      currentRoute: null,
      lastCompletedStopIndex: -1,
      currentLat: 23.02431,
      currentLng: 72.53016,
    });
  }

  await emitTripScopedEvent(req, 'trip_reset', {}, {
    broadcastParentRole: true,
    broadcastDriverRole: true,
  });

  res.json({ message: 'Trip reset' });
};

exports.getChildRoutePreview = async (req, res) => {
  await ensureTripsTable();

  const normalizedChildId = normalizeId(req.query.childId);
  if (!normalizedChildId) {
    return res.status(400).json({ message: 'childId is required' });
  }

  const trip = await getRunningTrip();
  const normalizedTrip = normalizeTripRecord(trip);
  if (!normalizedTrip) {
    return res.json({ points: [], distance: 0, duration: 0 });
  }

  const child = await getChildRecordById(normalizedChildId);
  if (!child) {
    return res.status(404).json({ message: 'Child not found' });
  }

  const route = await computeChildRoutePreviewFromTrip(normalizedTrip, normalizedChildId);
  return res.json(route);
};

exports.getChildTracking = async (req, res) => {
  await ensureTripsTable();

  const normalizedChildId = normalizeId(req.query.childId);
  if (!normalizedChildId) {
    return res.status(400).json({ message: 'childId is required' });
  }

  const email = String(req.query.email || '').trim();
  let child = null;

  if (email) {
    const user = await findUserByLogin(email);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    child = await getChildForParentUser(normalizedChildId, user.id);
    if (!child) {
      return res.status(403).json({ message: 'Child not found for this parent' });
    }
  } else {
    child = await getChildRecordById(normalizedChildId);
    if (!child) {
      return res.status(404).json({ message: 'Child not found' });
    }
  }

  const trip = await getRunningTrip();
  const normalizedTrip = normalizeTripRecord(trip);
  if (!normalizedTrip) {
    return res.json({
      active: false,
      trip: null,
      child,
      routeStops: child.routeId ? await getRouteStopsByRouteId(child.routeId) : [],
      routePreview: { points: [], distance: 0, duration: 0 },
    });
  }

  if (normalizedTrip.routeId && child.routeId && String(normalizedTrip.routeId) !== String(child.routeId)) {
    return res.json({
      active: false,
      trip: null,
      child,
      routeStops: child.routeId ? await getRouteStopsByRouteId(child.routeId) : [],
      routePreview: { points: [], distance: 0, duration: 0 },
    });
  }

  const routeStops = child.routeId ? await getRouteStopsByRouteId(child.routeId) : [];
  const routePreview = await computeChildRoutePreviewFromTrip(normalizedTrip, normalizedChildId);

  return res.json({
    active: true,
    trip: normalizedTrip,
    child,
    routeStops,
    routePreview,
  });
};

const { Op, QueryTypes } = require('sequelize');
const Child = require('../models/Child');
const Driver = require('../models/Driver');
const Trip = require('../models/Trip');
const { sequelize } = require('../config/db.config');
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
  getRouteGeometryPointsByRouteId,
  isLegacyNodeUserSchema,
  tableHasColumn,
  updateSharedDriverStateForUser,
} = require('../services/schema-compat.service');
const {
  sendChildEvent,
} = require('../services/push-notification.service');
const {
  sendEventNotification,
} = require('../services/mobile-notification.service');
const {
  generateTripPinsForChildren,
  getActiveTripPinForChild,
  deleteExistingPinsForChildren,
} = require('../services/child-trip-pin.service');

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

function projectPointOnRoute(lat, lng, points) {
  let bestProjection = null;

  for (let index = 0; index < points.length - 1; index += 1) {
    const start = points[index];
    const end = points[index + 1];
    const avgLat = ((lat + start.lat + end.lat) / 3) * Math.PI / 180;
    const metersPerDegreeLat = 111320;
    const metersPerDegreeLng = 111320 * Math.cos(avgLat);

    const sx = start.lng * metersPerDegreeLng;
    const sy = start.lat * metersPerDegreeLat;
    const ex = end.lng * metersPerDegreeLng;
    const ey = end.lat * metersPerDegreeLat;
    const px = lng * metersPerDegreeLng;
    const py = lat * metersPerDegreeLat;

    const dx = ex - sx;
    const dy = ey - sy;
    const lenSq = dx * dx + dy * dy;
    const t = lenSq === 0
      ? 0
      : Math.min(1, Math.max(0, ((px - sx) * dx + (py - sy) * dy) / lenSq));
    const projectedX = sx + dx * t;
    const projectedY = sy + dy * t;
    const distanceMeters = Math.sqrt(
      ((px - projectedX) ** 2) + ((py - projectedY) ** 2)
    );
    const projectedLat = start.lat + ((end.lat - start.lat) * t);
    const projectedLng = start.lng + ((end.lng - start.lng) * t);

    if (!bestProjection || distanceMeters < bestProjection.distanceMeters) {
      bestProjection = {
        segmentIndex: index,
        distanceMeters,
        point: { lat: projectedLat, lng: projectedLng },
      };
    }
  }

  return bestProjection;
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

async function notifyTripStartedForChildren(children = [], tripType, tripId) {
  const childIds = [...new Set(
    children
      .map((child) => normalizeId(child?.id ?? child?.childId ?? child?.raw?.id))
      .filter(Boolean)
  )];

  await Promise.all(
    childIds.map((childId) =>
      sendChildEvent(
        'trip_started',
        childId,
        { tripType },
        { tripId, tripType }
      )
    )
  );
}

async function updateTripStatusForChildren(childIds = [], tripStatus) {
  const ids = [...new Set(
    (Array.isArray(childIds) ? childIds : [childIds])
      .map((id) => normalizeId(id))
      .filter(Boolean)
  )];
  if (!ids.length || !tripStatus) return;

  if (await isLegacyNodeUserSchema()) {
    await Child.update({ tripStatus }, { where: { id: ids } });
    return;
  }

  const statusColumn = (await tableHasColumn('children', 'tripStatus'))
    ? 'tripStatus'
    : (await tableHasColumn('children', 'trip_status'))
      ? 'trip_status'
      : null;
  if (!statusColumn) return;

  await sequelize.query(
    `
      UPDATE children
      SET \`${statusColumn}\` = :tripStatus
      WHERE id IN (:ids)
    `,
    {
      replacements: { tripStatus, ids },
      type: QueryTypes.UPDATE,
    }
  );
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

function applyTripPinsToStops(stops = [], createdPins = []) {
  if (!Array.isArray(stops) || !stops.length || !Array.isArray(createdPins) || !createdPins.length) {
    return stops;
  }

  const pinByChildId = new Map(
    createdPins
      .map((row) => [String(normalizeId(row.childId) ?? ''), row.pin == null ? '' : String(row.pin).trim()])
      .filter(([childId, pin]) => childId && pin)
  );

  return stops.map((stop) => {
    const childId = String(normalizeId(stop?.childId) ?? '');
    const pin = pinByChildId.get(childId);
    if (!pin) return stop;

    return {
      ...stop,
      secretPin: pin,
      secret_pin: pin,
    };
  });
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

function stripPinFieldsFromStop(stop) {
  if (!stop || typeof stop !== 'object') return stop;
  const sanitized = { ...stop };
  delete sanitized.secretPin;
  delete sanitized.secret_pin;
  return sanitized;
}

function isSameStopIdentity(left, right) {
  if (!left || !right) return false;
  if (String(left.type || '').trim().toLowerCase() !== String(right.type || '').trim().toLowerCase()) {
    return false;
  }

  const leftStopId = normalizeId(left.stopId);
  const rightStopId = normalizeId(right.stopId);
  if (leftStopId || rightStopId) {
    return String(leftStopId || '') === String(rightStopId || '');
  }

  const leftSequence = normalizeId(left.sequenceOrder);
  const rightSequence = normalizeId(right.sequenceOrder);
  if (leftSequence || rightSequence) {
    return String(leftSequence || '') === String(rightSequence || '');
  }

  const leftLat = parseCoordinate(left.lat);
  const leftLng = parseCoordinate(left.lng);
  const rightLat = parseCoordinate(right.lat);
  const rightLng = parseCoordinate(right.lng);
  return leftLat !== null &&
    leftLng !== null &&
    rightLat !== null &&
    rightLng !== null &&
    Math.abs(leftLat - rightLat) < 0.000001 &&
    Math.abs(leftLng - rightLng) < 0.000001;
}

async function buildPickupPinRowsForParent(normalizedTrip) {
  const nextStop = normalizedTrip?.nextStop;
  if (!nextStop || String(nextStop.type || '').trim().toLowerCase() !== 'pickup') {
    return [];
  }

  const matchingStops = (Array.isArray(normalizedTrip?.stops) ? normalizedTrip.stops : [])
    .filter((stop) => {
      const status = String(stop?.status || '').trim().toLowerCase();
      if (status === 'completed' || status === 'picked_up' || status === 'dropped') {
        return false;
      }
      return isSameStopIdentity(stop, nextStop);
    });

  const rows = await Promise.all(
    matchingStops.map(async (stop) => {
      const childId = normalizeId(stop?.childId);
      if (!childId) return null;
      const activePin = await getActiveTripPinForChild(childId, normalizedTrip?.id || null);
      const pin = activePin?.pin ? String(activePin.pin).trim() : '';
      if (!pin) return null;
      return {
        childId,
        name: stop?.name || 'Child',
        pin,
        stopId: normalizeId(stop?.stopId),
        sequenceOrder: normalizeId(stop?.sequenceOrder),
        stopLabel: stop?.stopLabel || stop?.pickupName || stop?.stopName || '',
      };
    })
  );

  return rows.filter(Boolean);
}

async function buildTripPinRowsForParent(normalizedTrip) {
  const pickupStops = (Array.isArray(normalizedTrip?.stops) ? normalizedTrip.stops : [])
    .filter((stop) => String(stop?.type || '').trim().toLowerCase() === 'pickup')
    .filter((stop) => stop?.skipped !== true);

  const uniqueStops = [];
  const seenChildIds = new Set();
  for (const stop of pickupStops) {
    const childId = normalizeId(stop?.childId);
    if (!childId || seenChildIds.has(childId)) continue;
    seenChildIds.add(childId);
    uniqueStops.push(stop);
  }

  const rows = await Promise.all(
    uniqueStops.map(async (stop) => {
      const childId = normalizeId(stop?.childId);
      if (!childId) return null;
      const activePin = await getActiveTripPinForChild(childId, normalizedTrip?.id || null);
      const pin = activePin?.pin
        ? String(activePin.pin).trim()
        : String(stop?.secretPin || stop?.secret_pin || '').trim();
      if (!pin) return null;
      return {
        childId,
        name: stop?.name || 'Child',
        pin,
        stopId: normalizeId(stop?.stopId),
        sequenceOrder: normalizeId(stop?.sequenceOrder),
        stopLabel: stop?.stopLabel || stop?.pickupName || stop?.stopName || '',
        status: String(stop?.status || '').trim().toLowerCase(),
        skipped: stop?.skipped === true,
        skippedReason: stop?.skippedReason || null,
      };
    })
  );

  return rows
    .filter(Boolean)
    .sort((left, right) => {
      const leftSeq = normalizeId(left.sequenceOrder) ?? Number.MAX_SAFE_INTEGER;
      const rightSeq = normalizeId(right.sequenceOrder) ?? Number.MAX_SAFE_INTEGER;
      if (leftSeq !== rightSeq) return leftSeq - rightSeq;
      return normalizeId(left.childId) - normalizeId(right.childId);
    });
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

function sortStopsBySequence(stops, tripType = 'morning') {
  return [...stops].sort((left, right) => {
    const typeOrder = tripType === 'afternoon'
      ? { dropoff: 0, stop: 1, place: 1, end: 1, pickup: 2 }
      : { pickup: 0, stop: 1, place: 1, end: 2, dropoff: 2 };
    const leftTypeOrder = typeOrder[left.type] ?? 1;
    const rightTypeOrder = typeOrder[right.type] ?? 1;
    if (leftTypeOrder !== rightTypeOrder) return leftTypeOrder - rightTypeOrder;

    const leftSeq = Number.isFinite(Number(left.sequenceOrder)) ? Number(left.sequenceOrder) : Number.MAX_SAFE_INTEGER;
    const rightSeq = Number.isFinite(Number(right.sequenceOrder)) ? Number(right.sequenceOrder) : Number.MAX_SAFE_INTEGER;
    if (leftSeq !== rightSeq) {
      return tripType === 'afternoon' && left.type === 'dropoff' && right.type === 'dropoff'
        ? rightSeq - leftSeq
        : leftSeq - rightSeq;
    }
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

function normalizeRouteStop(stop) {
  if (!stop) return null;

  return {
    id: normalizeId(stop.id),
    pickupName: stop.pickup_name ?? stop.pickupName ?? stop.name ?? null,
    stopName: stop.stop_name ?? stop.stopName ?? stop.name ?? null,
    name:
      stop.name ??
      stop.stop_name ??
      stop.stopName ??
      stop.pickup_name ??
      stop.pickupName ??
      null,
    lat: parseCoordinate(stop.latitude ?? stop.lat),
    lng: parseCoordinate(stop.longitude ?? stop.lng),
    sequenceOrder:
      normalizeId(stop.sequence_order ?? stop.sequenceOrder) ??
      Number(stop.sequence_order ?? stop.sequenceOrder) ??
      null,
    type: String(stop.type || '').trim().toLowerCase(),
  };
}

function getRouteEndpointStop(routeStops, tripType = 'morning') {
  const normalized = (Array.isArray(routeStops) ? routeStops : [])
    .map(normalizeRouteStop)
    .filter((stop) => stop && stop.lat !== null && stop.lng !== null);

  if (!normalized.length) return null;

  const preferredTypes = ['end', 'dropoff', 'school'];

  for (const type of preferredTypes) {
    const found = normalized.find((stop) => stop.type === type);
    if (found) return found;
  }

  return normalized[normalized.length - 1];
}

function getRouteStartStop(routeStops) {
  const normalized = (Array.isArray(routeStops) ? routeStops : [])
    .map(normalizeRouteStop)
    .filter((stop) => stop && stop.lat !== null && stop.lng !== null);

  if (!normalized.length) return null;

  const explicitStart = normalized.find((stop) => stop.type === 'start');
  return explicitStart || normalized[0];
}

function buildStopsFromSharedRoute(children, routeStops, tripType = 'morning') {
  const stopMap = buildStopMap(routeStops);
  const routeEndpointStop = getRouteEndpointStop(routeStops, tripType);

  const isMorning = tripType === 'morning';
  const generatedStops = [];

  for (const child of children) {
    const raw = child.raw || child;
    const overridePickupStopId = resolveTodayPickupOverride(child, raw);
    const pickupStopId = isMorning ? (overridePickupStopId || raw.pickup_name) : raw.stop_name;
    const dropStopId = isMorning ? raw.stop_name : raw.pickup_name;
    const childPickupRouteStop = stopMap.get(normalizeStopKey(pickupStopId));
    const pickupRouteStop = isMorning ? childPickupRouteStop : (routeEndpointStop || childPickupRouteStop);
    const childDropRouteStop = stopMap.get(normalizeStopKey(dropStopId));
    const dropRouteStop = isMorning ? (routeEndpointStop || childDropRouteStop) : childDropRouteStop;
    const childId = normalizeId(child.id ?? raw.id);
    const childName = child.name || child.child_name || 'Child';

    if (isMorning && pickupRouteStop && pickupRouteStop.lat !== null && pickupRouteStop.lng !== null) {
      generatedStops.push({
        childId,
        name: childName,
        type: 'pickup',
        lat: pickupRouteStop.lat,
        lng: pickupRouteStop.lng,
        status: 'pending',
        stopId: pickupRouteStop.id,
        sequenceOrder: pickupRouteStop.sequenceOrder,
        stopName: pickupRouteStop.stopName ?? pickupRouteStop.name,
        pickupName: pickupRouteStop.pickupName ?? pickupRouteStop.name,
        stopLabel: isMorning
          ? (pickupRouteStop.name ?? pickupRouteStop.pickupName ?? pickupRouteStop.stopName)
          : (pickupRouteStop.name ?? pickupRouteStop.stopName ?? pickupRouteStop.pickupName ?? 'School pickup'),
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
        stopName: dropRouteStop.stopName ?? dropRouteStop.name,
        pickupName: dropRouteStop.pickupName ?? dropRouteStop.name,
        stopLabel: dropRouteStop.name ?? dropRouteStop.stopName ?? dropRouteStop.pickupName,
      });
    }
  }

  return sortStopsBySequence(generatedStops, tripType);
}

function buildMorningRouteContinuationStop(routeStops, existingStops = []) {
  const routeEndpointStop = getRouteEndpointStop(routeStops, 'morning');
  if (!routeEndpointStop || routeEndpointStop.lat === null || routeEndpointStop.lng === null) {
    return null;
  }

  const alreadyHasEndpointStop = (Array.isArray(existingStops) ? existingStops : []).some((stop) => {
    if (!stop) return false;
    const stopType = String(stop.type || '').trim().toLowerCase();
    if (!['stop', 'dropoff'].includes(stopType)) return false;
    if (String(stop.status || '').trim().toLowerCase() === 'completed') return false;
    return isSameCoordinate(stop, routeEndpointStop);
  });
  if (alreadyHasEndpointStop) {
    return null;
  }

  const sequenceOrder = Number.isFinite(Number(routeEndpointStop.sequenceOrder))
    ? Number(routeEndpointStop.sequenceOrder)
    : ((Array.isArray(existingStops) ? existingStops : [])
        .map((stop) => Number(stop?.sequenceOrder))
        .filter((value) => Number.isFinite(value))
        .reduce((max, value) => Math.max(max, value), 0) + 1);

  return {
    childId: null,
    name:
      routeEndpointStop.stopName ??
      routeEndpointStop.name ??
      routeEndpointStop.pickupName ??
      'School',
    type: 'dropoff',
    lat: routeEndpointStop.lat,
    lng: routeEndpointStop.lng,
    status: 'pending',
    stopId: routeEndpointStop.id,
    sequenceOrder,
    stopName: routeEndpointStop.stopName ?? routeEndpointStop.name ?? null,
    pickupName: routeEndpointStop.pickupName ?? routeEndpointStop.name ?? null,
    stopLabel:
      routeEndpointStop.stopName ??
      routeEndpointStop.name ??
      routeEndpointStop.pickupName ??
      'School',
    syntheticEndpoint: true,
  };
}

function buildAfternoonRouteContinuationStop(routeStops, existingStops = []) {
  const routeEndpointStop = getRouteEndpointStop(routeStops, 'afternoon');
  if (!routeEndpointStop || routeEndpointStop.lat === null || routeEndpointStop.lng === null) {
    return null;
  }

  const alreadyHasEndpointStop = (Array.isArray(existingStops) ? existingStops : []).some((stop) => {
    if (!stop) return false;
    const stopType = String(stop.type || '').trim().toLowerCase();
    if (!['stop', 'dropoff', 'end', 'school'].includes(stopType)) return false;
    if (String(stop.status || '').trim().toLowerCase() === 'completed') return false;
    return isSameCoordinate(stop, routeEndpointStop);
  });
  if (alreadyHasEndpointStop) {
    return null;
  }

  const sequenceOrder = Number.isFinite(Number(routeEndpointStop.sequenceOrder))
    ? Number(routeEndpointStop.sequenceOrder)
    : ((Array.isArray(existingStops) ? existingStops : [])
        .map((stop) => Number(stop?.sequenceOrder))
        .filter((value) => Number.isFinite(value))
        .reduce((max, value) => Math.max(max, value), 0) + 1);

  return {
    childId: null,
    name:
      routeEndpointStop.stopName ??
      routeEndpointStop.name ??
      routeEndpointStop.pickupName ??
      'Route End',
    type: 'dropoff',
    lat: routeEndpointStop.lat,
    lng: routeEndpointStop.lng,
    status: 'pending',
    stopId: routeEndpointStop.id,
    sequenceOrder,
    stopName: routeEndpointStop.stopName ?? routeEndpointStop.name ?? null,
    pickupName: routeEndpointStop.pickupName ?? routeEndpointStop.name ?? null,
    stopLabel:
      routeEndpointStop.stopName ??
      routeEndpointStop.name ??
      routeEndpointStop.pickupName ??
      'Route End',
    syntheticEndpoint: true,
  };
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

function buildStopsFromRouteStopsOnly(routeStops, tripType = 'morning') {
  if (tripType === 'afternoon') {
    const routeEndpointStop = getRouteEndpointStop(routeStops, tripType);
    if (!routeEndpointStop) {
      return [];
    }

    return [{
      childId: null,
      name:
        routeEndpointStop.stopName ??
        routeEndpointStop.name ??
        routeEndpointStop.pickupName ??
        'Route End',
      type: 'dropoff',
      lat: routeEndpointStop.lat,
      lng: routeEndpointStop.lng,
      status: 'pending',
      stopId: routeEndpointStop.id,
      sequenceOrder: routeEndpointStop.sequenceOrder,
      stopName: routeEndpointStop.stopName ?? routeEndpointStop.name,
      pickupName: routeEndpointStop.pickupName ?? routeEndpointStop.name,
      stopLabel:
        routeEndpointStop.stopName ??
        routeEndpointStop.name ??
        routeEndpointStop.pickupName ??
        'Route End',
    }];
  }

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

  return sortStopsBySequence(normalizedStops, tripType);
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

function collectCancelledChildIdsFromStops(stops = []) {
  const cancelledChildIds = new Set();

  for (const stop of Array.isArray(stops) ? stops : []) {
    const childId = normalizeId(stop?.childId ?? stop?.child_id);
    if (!childId) continue;

    const skippedReason = String(stop?.skippedReason || '').trim().toLowerCase();
    const isCancelled = stop?.skipped === true && (
      skippedReason === 'child_absent' ||
      skippedReason === 'pickup_cancelled'
    );

    if (isCancelled) {
      cancelledChildIds.add(childId);
    }
  }

  return cancelledChildIds;
}

function collectMorningPickedChildIdsFromStops(stops = []) {
  const pickedChildIds = new Set();

  for (const stop of Array.isArray(stops) ? stops : []) {
    const childId = normalizeId(stop?.childId ?? stop?.child_id);
    if (!childId) continue;

    const stopType = String(stop?.type || '').trim().toLowerCase();
    const stopStatus = String(stop?.status || '').trim().toLowerCase();
    if (stopType !== 'pickup') continue;
    if (stopStatus !== 'completed') continue;
    if (stop?.skipped === true) continue;

    pickedChildIds.add(childId);
  }

  return pickedChildIds;
}

async function getMorningCancelledChildIdsForAfternoonTrip(routeId, driverUserId) {
  const normalizedRouteId = normalizeId(routeId);
  const normalizedDriverUserId = normalizeId(driverUserId);
  if (!normalizedRouteId && !normalizedDriverUserId) {
    return new Set();
  }

  const predicates = [`tripType = 'morning'`];
  const replacements = {};

  if (normalizedRouteId) {
    predicates.push('routeId = :routeId');
    replacements.routeId = normalizedRouteId;
  }

  if (normalizedDriverUserId) {
    predicates.push('driverUserId = :driverUserId');
    replacements.driverUserId = normalizedDriverUserId;
  }

  const rows = await sequelize.query(
    `
      SELECT stops
      FROM trips
      WHERE ${predicates.join(' AND ')}
      ORDER BY id DESC
      LIMIT 1
    `,
    {
      replacements,
      type: QueryTypes.SELECT,
    }
  );

  const stops = parseMaybeJson(rows[0]?.stops);
  return collectCancelledChildIdsFromStops(stops);
}

async function getMorningPickedChildIdsForAfternoonTrip(routeId, driverUserId) {
  const normalizedRouteId = normalizeId(routeId);
  const normalizedDriverUserId = normalizeId(driverUserId);
  if (!normalizedRouteId && !normalizedDriverUserId) {
    return new Set();
  }

  const predicates = [`tripType = 'morning'`];
  const replacements = {};

  if (normalizedRouteId) {
    predicates.push('routeId = :routeId');
    replacements.routeId = normalizedRouteId;
  }

  if (normalizedDriverUserId) {
    predicates.push('driverUserId = :driverUserId');
    replacements.driverUserId = normalizedDriverUserId;
  }

  const rows = await sequelize.query(
    `
      SELECT stops
      FROM trips
      WHERE ${predicates.join(' AND ')}
      ORDER BY id DESC
      LIMIT 1
    `,
    {
      replacements,
      type: QueryTypes.SELECT,
    }
  );

  const stops = parseMaybeJson(rows[0]?.stops);
  return collectMorningPickedChildIdsFromStops(stops);
}

async function getRunningTrip() {
  await ensureTripsTable();
  return Trip.findOne({
    where: { status: 'running' },
    order: [['id', 'DESC']],
  });
}

let tripColumnCache = null;

async function getTripColumns() {
  if (tripColumnCache) return tripColumnCache;

  const rows = await sequelize.query(
    `
      SELECT COLUMN_NAME
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'trips'
    `,
    { type: QueryTypes.SELECT }
  );

  tripColumnCache = new Set(rows.map((row) => row.COLUMN_NAME));
  return tripColumnCache;
}

async function persistTripLocationSnapshot(tripId, payload) {
  const columns = await getTripColumns();
  const updates = [];
  const replacements = { tripId };

  const assign = (column, key, value, json = false) => {
    if (!columns.has(column)) return;
    updates.push(`\`${column}\` = :${key}`);
    replacements[key] = json ? JSON.stringify(value ?? null) : value;
  };

  assign('driverLat', 'driverLat', payload.driverLat);
  assign('driver_lat', 'driverLatSnake', payload.driverLat);
  assign('driverLng', 'driverLng', payload.driverLng);
  assign('driver_lng', 'driverLngSnake', payload.driverLng);
  assign('nextStop', 'nextStop', payload.nextStop, true);
  assign('next_stop', 'nextStopSnake', payload.nextStop, true);
  assign('currentRoute', 'currentRoute', payload.currentRoute, true);
  assign('current_route', 'currentRouteSnake', payload.currentRoute, true);
  assign('updated_at', 'updatedAtSnake', new Date());
  assign('updatedAt', 'updatedAtCamel', new Date());

  if (!updates.length) return;

  await sequelize.query(
    `
      UPDATE trips
      SET ${updates.join(', ')}
      WHERE id = :tripId
      LIMIT 1
    `,
    {
      replacements,
      type: QueryTypes.UPDATE,
    }
  );
}

function normalizeRouteStopsPayload(routeStops = []) {
  const normalized = (Array.isArray(routeStops) ? routeStops : [])
    .map((stop, index, source) => {
      const id = normalizeId(stop.id) ?? stop.id ?? null;
      const lat = parseCoordinate(stop.latitude ?? stop.lat);
      const lng = parseCoordinate(stop.longitude ?? stop.lng);
      if (lat === null || lng === null) return null;

      const inferredType =
        stop.type ||
        (index === 0 ? 'start' : index === source.length - 1 ? 'end' : 'pickup');

      return {
        id,
        name: stop.pickup_name ?? stop.stop_name ?? stop.name ?? `Stop ${index + 1}`,
        pickupName: stop.pickup_name ?? null,
        stopName: stop.stop_name ?? null,
        lat,
        lng,
        sequenceOrder: normalizeId(stop.sequence_order) ?? Number(stop.sequence_order) ?? index + 1,
        type: inferredType,
      };
    })
    .filter(Boolean);

  return normalized.sort((left, right) => {
    const leftSeq = Number.isFinite(Number(left.sequenceOrder))
      ? Number(left.sequenceOrder)
      : Number.MAX_SAFE_INTEGER;
    const rightSeq = Number.isFinite(Number(right.sequenceOrder))
      ? Number(right.sequenceOrder)
      : Number.MAX_SAFE_INTEGER;
    if (leftSeq !== rightSeq) return leftSeq - rightSeq;
    return String(left.name || '').localeCompare(String(right.name || ''));
  });
}

function enrichStopWithRouteMeta(stop, routeStops = [], tripType = 'morning') {
  if (!stop || typeof stop !== 'object') return stop;

  const stopId = normalizeId(stop.stopId);
  const sequenceOrder = Number.isFinite(Number(stop.sequenceOrder))
    ? Number(stop.sequenceOrder)
    : null;

  const routeMeta =
    routeStops.find((routeStop) => stopId && normalizeId(routeStop.id) === stopId) ||
    routeStops.find(
      (routeStop) =>
        sequenceOrder !== null &&
        Number.isFinite(Number(routeStop.sequenceOrder)) &&
        Number(routeStop.sequenceOrder) === sequenceOrder
    ) ||
    null;

  if (!routeMeta) {
    return {
      ...stop,
      lat: parseCoordinate(stop.lat) ?? stop.lat,
      lng: parseCoordinate(stop.lng) ?? stop.lng,
    };
  }

  const normalizedType =
    stop.type === 'stop'
      ? routeMeta.type || stop.type
      : stop.type || routeMeta.type || 'pickup';

  return {
    ...stop,
    stopId: stopId ?? routeMeta.id ?? null,
    sequenceOrder: sequenceOrder ?? routeMeta.sequenceOrder ?? null,
    type: normalizedType,
    lat: parseCoordinate(stop.lat) ?? routeMeta.lat ?? stop.lat,
    lng: parseCoordinate(stop.lng) ?? routeMeta.lng ?? stop.lng,
    pickupName:
      stop.pickupName ??
      routeMeta.pickupName ??
      (normalizedType === 'pickup' ? routeMeta.name : null) ??
      null,
    stopName:
      stop.stopName ??
      routeMeta.stopName ??
      (normalizedType === 'dropoff' ? routeMeta.name : null) ??
      null,
    stopLabel:
      resolveGroupedStopLabel(
        {
          ...stop,
          type: normalizedType,
          pickupName: stop.pickupName ?? routeMeta.pickupName ?? null,
          stopName: stop.stopName ?? routeMeta.stopName ?? null,
          name: routeMeta.name ?? stop.name ?? null,
        },
        routeMeta,
        tripType
      ) ??
      stop.stopLabel,
  };
}

async function buildTripResponsePayload(trip) {
  const normalizedTrip = normalizeTripRecord(trip);
  if (!normalizedTrip) return null;

  let driver = null;
  if (normalizedTrip.driverUserId) {
    const driverProfile = await getDriverProfileForUser(normalizedTrip.driverUserId);
    if (driverProfile) {
      driver = {
        id: driverProfile.id ?? null,
        userId: driverProfile.userId ?? normalizedTrip.driverUserId,
        fullName: driverProfile.fullName || null,
        phoneNumber: driverProfile.phoneNumber || null,
        emergencyPhone: driverProfile.emergencyPhone || null,
        vehicleNumber: driverProfile.vehicleNumber || null,
        vehicleModel: driverProfile.vehicleModel || null,
        vehicleCapacity: driverProfile.vehicleCapacity || null,
        routeId: driverProfile.routeId || normalizedTrip.routeId || null,
        routeName: driverProfile.routeName || null,
      };
    }
  }

  const effectiveRouteId = driver?.routeId || normalizedTrip.routeId || null;
  const routeStops = effectiveRouteId
    ? normalizeRouteStopsPayload(await getRouteStopsByRouteId(effectiveRouteId))
    : [];

  const currentRoute = normalizedTrip.currentRoute
    ? {
        ...normalizedTrip.currentRoute,
        points: Array.isArray(normalizedTrip.currentRoute.points)
          ? normalizedTrip.currentRoute.points
          : [],
        stopsMeta:
          routeStops.length
            ? routeStops
            : Array.isArray(normalizedTrip.currentRoute.stopsMeta) &&
                normalizedTrip.currentRoute.stopsMeta.length
              ? normalizedTrip.currentRoute.stopsMeta
              : [],
      }
    : {
        points: [],
        stopsMeta: routeStops,
      };

  const enrichedStops = Array.isArray(normalizedTrip.stops)
    ? normalizedTrip.stops.map((stop) =>
        stripPinFieldsFromStop(
          enrichStopWithRouteMeta(stop, routeStops, normalizedTrip.tripType)
        )
      )
    : [];

  const enrichedNextStop = normalizedTrip.nextStop
    ? stripPinFieldsFromStop(
        enrichStopWithRouteMeta(
          normalizedTrip.nextStop,
          routeStops,
          normalizedTrip.tripType
        )
      )
    : enrichedStops.find((stop) => stop?.status === 'pending') || null;

  const enrichedTrip = {
    ...normalizedTrip,
    routeId: effectiveRouteId,
    stops: enrichedStops,
    nextStop: enrichedNextStop,
    currentRoute,
  };

  return {
    ...enrichedTrip,
    stopGroups: buildStopGroupsFromTrip(enrichedTrip),
    driver,
    routeStops,
    serverTime: new Date().toISOString(),
  };
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
      skipped: stop.skipped === true,
      skippedReason: stop.skippedReason || null,
      type: stop.type,
      stopId,
      sequenceOrder,
      isNextStop:
        !!nextStop &&
        String(nextStop.childId) === String(stop.childId) &&
        String(nextStop.type) === String(stop.type) &&
        String(nextStop.status || 'pending') === String(stop.status || 'pending'),
      canVerifyPickup: stop.type === 'pickup' && childStatus === 'pending',
      canConfirmDropoff:
        tripType === 'afternoon' &&
        stop.type === 'dropoff' &&
        childStatus === 'pending',
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

function isSameRouteStopGroup(left, right) {
  if (!left || !right) return false;
  if (String(left.type || '') !== String(right.type || '')) return false;

  const leftStopId = normalizeId(left.stopId);
  const rightStopId = normalizeId(right.stopId);
  if (leftStopId || rightStopId) {
    return String(leftStopId || '') === String(rightStopId || '');
  }

  const leftSequence = Number.isFinite(Number(left.sequenceOrder)) ? Number(left.sequenceOrder) : null;
  const rightSequence = Number.isFinite(Number(right.sequenceOrder)) ? Number(right.sequenceOrder) : null;
  if (leftSequence !== null || rightSequence !== null) {
    return leftSequence === rightSequence;
  }

  const leftLat = parseCoordinate(left.lat);
  const leftLng = parseCoordinate(left.lng);
  const rightLat = parseCoordinate(right.lat);
  const rightLng = parseCoordinate(right.lng);
  return leftLat !== null &&
    leftLng !== null &&
    rightLat !== null &&
    rightLng !== null &&
    Math.abs(leftLat - rightLat) < 0.000001 &&
    Math.abs(leftLng - rightLng) < 0.000001;
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

function buildWaypointsFromRouteStops(driverLat, driverLng, routeStops, reverse = false) {
  const waypoints = [];
  const origin = { lat: parseCoordinate(driverLat), lng: parseCoordinate(driverLng) };
  if (origin.lat === null || origin.lng === null) return [];
  waypoints.push(origin);

  const normalizedStops = Array.isArray(routeStops)
    ? [...routeStops].sort((left, right) => {
        const leftSeq = routeStopOrder(left, 0);
        const rightSeq = routeStopOrder(right, 0);
        if (leftSeq !== rightSeq) return leftSeq - rightSeq;
        return normalizeId(left?.id ?? left?.stopId) - normalizeId(right?.id ?? right?.stopId);
      })
    : [];
  if (reverse) normalizedStops.reverse();
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

function approximateRouteDistance(points = []) {
  let distance = 0;
  for (let index = 0; index < points.length - 1; index += 1) {
    distance += distanceInMeters(
      points[index].lat,
      points[index].lng,
      points[index + 1].lat,
      points[index + 1].lng
    );
  }
  return distance;
}

function buildRouteFromStoredGeometry(driverLat, driverLng, geometryPoints, reverse = false) {
  const origin = { lat: parseCoordinate(driverLat), lng: parseCoordinate(driverLng) };
  if (origin.lat === null || origin.lng === null || !Array.isArray(geometryPoints) || geometryPoints.length < 2) {
    return null;
  }

  const orderedPoints = (reverse ? [...geometryPoints].reverse() : [...geometryPoints])
    .map((point) => ({
      lat: parseCoordinate(point?.lat),
      lng: parseCoordinate(point?.lng),
    }))
    .filter((point) => point.lat !== null && point.lng !== null);

  if (orderedPoints.length < 2) return null;

  const points = [...orderedPoints];
  if (!isSameCoordinate(origin, points[0])) {
    points.unshift(origin);
  }

  return {
    points,
    distance: approximateRouteDistance(points),
    duration: 0,
    waypoints: points,
  };
}

async function computeTripRoute(driverLat, driverLng, stops, options = {}) {
  const waypointsTail = options.waypointsTail;
  const routeStops = options.routeStops;
  const routeGeometryPoints = options.routeGeometryPoints;
  const stopsMetaOverride = options.stopsMeta;
  const reverseRouteStops = options.reverseRouteStops === true;

  const storedRoute = buildRouteFromStoredGeometry(
    driverLat,
    driverLng,
    routeGeometryPoints,
    reverseRouteStops
  );

  const waypoints =
    storedRoute
      ? []
      : Array.isArray(waypointsTail) && waypointsTail.length
      ? buildWaypointsFromTail(driverLat, driverLng, waypointsTail)
      : Array.isArray(routeStops) && routeStops.length
          ? buildWaypointsFromRouteStops(driverLat, driverLng, routeStops, reverseRouteStops)
          : buildPendingWaypoints(driverLat, driverLng, stops);

  if (!storedRoute && !waypoints.length) return null;
  const route = storedRoute || await calculateRouteWithWaypoints(waypoints);

  const stopsMetaSource = Array.isArray(stopsMetaOverride) && stopsMetaOverride.length
    ? stopsMetaOverride
    : routeStops;
  const stopsMeta = Array.isArray(stopsMetaSource) && stopsMetaSource.length
    ? normalizeRouteStopsPayload(stopsMetaSource)
    : null;

  return { ...route, waypoints, stopsMeta };
}

function sameRouteCoordinate(left, right) {
  const leftLat = parseCoordinate(left?.latitude ?? left?.lat);
  const leftLng = parseCoordinate(left?.longitude ?? left?.lng);
  const rightLat = parseCoordinate(right?.latitude ?? right?.lat);
  const rightLng = parseCoordinate(right?.longitude ?? right?.lng);
  return leftLat !== null &&
    leftLng !== null &&
    rightLat !== null &&
    rightLng !== null &&
    Math.abs(leftLat - rightLat) < 0.00005 &&
    Math.abs(leftLng - rightLng) < 0.00005;
}

function routeStopOrder(stop, fallback = 0) {
  return normalizeId(
    stop?.sequenceOrder ??
      stop?.sequence_order ??
      stop?.sequence ??
      stop?.stopOrder ??
      stop?.stop_order ??
      stop?.order
  ) ?? fallback;
}

function routeOptionsForTrip(normalizedTrip, nextStop = null) {
  const routeStops = normalizedTrip?.currentRoute?.stopsMeta;
  if (!Array.isArray(routeStops) || !routeStops.length) return {};

  const activeStop = nextStop || normalizedTrip.nextStop || null;
  if (
    normalizedTrip.tripType === 'morning' &&
    activeStop?.type === 'dropoff'
  ) {
    const pendingDropoffs = Array.isArray(normalizedTrip.stops)
      ? normalizedTrip.stops.filter(
          (stop) => stop?.status === 'pending' && stop?.type === 'dropoff'
        )
      : [];

    return {
      waypointsTail: pendingDropoffs.length ? pendingDropoffs : [activeStop],
      stopsMeta: routeStops,
    };
  }

  const isAfternoon = normalizedTrip.tripType === 'afternoon';
  const orderedRouteStops = [...routeStops].sort((left, right) => {
    const leftSeq = routeStopOrder(left, 0);
    const rightSeq = routeStopOrder(right, 0);
    if (leftSeq !== rightSeq) return leftSeq - rightSeq;
    return normalizeId(left.id) - normalizeId(right.id);
  });
  const activeStopId = normalizeId(activeStop?.stopId ?? activeStop?.id);
  const activeSeq = normalizeId(
    activeStop?.sequenceOrder ??
      activeStop?.sequence ??
      activeStop?.sequence_order
  );
  let activeIndex = orderedRouteStops.findIndex((stop) => {
    const stopId = normalizeId(stop.id ?? stop.stopId);
    if (activeStopId !== null && stopId !== null && activeStopId === stopId) {
      return true;
    }
    const stopSeq = normalizeId(stop.sequenceOrder ?? stop.sequence_order ?? stop.sequence);
    if (activeSeq !== null && stopSeq !== null && activeSeq === stopSeq) {
      return true;
    }
    return sameRouteCoordinate(stop, activeStop);
  });

  let routeTail = orderedRouteStops;
  let reverseRouteStops = false;
  if (isAfternoon) {
    routeTail = activeIndex >= 0
      ? orderedRouteStops.slice(0, activeIndex + 1)
      : orderedRouteStops;
    reverseRouteStops = true;
  } else if (activeIndex >= 0) {
    routeTail = orderedRouteStops.slice(activeIndex);
  }

  return {
    routeStops: routeTail,
    stopsMeta: routeStops,
    reverseRouteStops,
  };
}

function trimRouteFromDriverProgress(route, driverLat, driverLng) {
  const lat = parseCoordinate(driverLat);
  const lng = parseCoordinate(driverLng);
  const rawPoints = Array.isArray(route?.points) ? route.points : [];
  if (lat === null || lng === null || rawPoints.length < 2) {
    return null;
  }

  const points = rawPoints
    .map((point) => ({
      lat: parseCoordinate(point?.lat),
      lng: parseCoordinate(point?.lng),
    }))
    .filter((point) => point.lat !== null && point.lng !== null);
  if (points.length < 2) {
    return null;
  }

  const projection = projectPointOnRoute(lat, lng, points);

  // If GPS jumps far away from the active route, ask the routing service for
  // a fresh route instead of trusting a stale polyline projection.
  if (
    !projection ||
    !Number.isFinite(projection.distanceMeters) ||
    projection.distanceMeters > 1500
  ) {
    return null;
  }

  const remainingPoints = [
    { lat, lng },
    projection.point,
    ...points.slice(projection.segmentIndex + 1),
  ];
  if (remainingPoints.length < 2) {
    remainingPoints.push(points[points.length - 1]);
  }

  let remainingDistance = 0;
  for (let index = 0; index < remainingPoints.length - 1; index += 1) {
    const current = remainingPoints[index];
    const next = remainingPoints[index + 1];
    remainingDistance += distanceInMeters(current.lat, current.lng, next.lat, next.lng);
  }

  const priorDistance = Number(route?.distance);
  const priorDuration = Number(route?.duration);
  const duration =
    Number.isFinite(priorDistance) &&
    priorDistance > 0 &&
    Number.isFinite(priorDuration) &&
    priorDuration > 0
      ? (remainingDistance / priorDistance) * priorDuration
      : priorDuration || 0;

  return {
    ...route,
    points: remainingPoints,
    distance: remainingDistance,
    duration,
  };
}

function routeEndsNearStop(route, stop, toleranceMeters = 140) {
  if (!route || !stop) return false;

  const points = Array.isArray(route.points) ? route.points : [];
  const lastPoint = points.length ? points[points.length - 1] : null;
  const routeLat = parseCoordinate(lastPoint?.lat);
  const routeLng = parseCoordinate(lastPoint?.lng);
  const stopLat = parseCoordinate(stop?.lat);
  const stopLng = parseCoordinate(stop?.lng);

  if (
    routeLat === null ||
    routeLng === null ||
    stopLat === null ||
    stopLng === null
  ) {
    return false;
  }

  return distanceInMeters(routeLat, routeLng, stopLat, stopLng) <= toleranceMeters;
}

async function computeRouteAfterStopProgress(normalizedTrip, driverLat, driverLng, stops, nextStop) {
  if (!nextStop) return null;

  const trimmedRoute = trimRouteFromDriverProgress(
    normalizedTrip.currentRoute,
    driverLat,
    driverLng
  );
  if (
    trimmedRoute &&
    Array.isArray(trimmedRoute.points) &&
    trimmedRoute.points.length >= 2 &&
    routeEndsNearStop(trimmedRoute, nextStop)
  ) {
    return trimmedRoute;
  }

  return computeTripRoute(
    driverLat,
    driverLng,
    stops,
    routeOptionsForTrip(normalizedTrip, nextStop)
  );
}

async function refreshLiveTripSnapshot(trip, driverLat, driverLng) {
  const normalizedTrip = normalizeTripRecord(trip);
  if (!normalizedTrip) {
    return null;
  }

  const nextStop = Array.isArray(normalizedTrip.stops)
    ? normalizedTrip.stops.find((stop) => stop?.status === 'pending') || null
    : null;

  const nextRoute = nextStop
    // Live location progress should move forward on the current route.
    // Status can still wait for PIN/stop confirmation, but rerouting every
    // ping through a passed pending pickup makes km increase after the stop.
    ? trimRouteFromDriverProgress(normalizedTrip.currentRoute, driverLat, driverLng) ||
      await computeTripRoute(driverLat, driverLng, normalizedTrip.stops)
    : null;

  await trip.update({
    driverLat,
    driverLng,
    nextStop,
    currentRoute: nextRoute,
    status: nextStop ? normalizedTrip.status : 'completed',
  });
  await persistTripLocationSnapshot(trip.id, {
    driverLat,
    driverLng,
    nextStop,
    currentRoute: nextRoute,
  });
  await trip.reload();

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

      await Trip.destroy({ where: { status: 'running' } });
      let trip = await Trip.create({
        driverLat: parsedLat,
        driverLng: parsedLng,
        stops,
        nextStop,
        currentRoute: route,
        status: 'running',
        tripType,
        direction: tripType === 'morning' ? 'FORWARD' : 'REVERSE',
      });

      if (tripType === 'morning') {
        const createdPins = await generateTripPinsForChildren({
          children,
          tripId: trip.id,
          tripType,
        });
        const pinnedStops = applyTripPinsToStops(stops, createdPins);
        const pinnedNextStop =
          pinnedStops.find((stop) => stop.status === 'pending') || null;
        await trip.update({
          stops: pinnedStops,
          nextStop: pinnedNextStop,
        });
        await trip.reload();
      }

      const tripPayload = await buildTripResponsePayload(trip);
      await emitTripScopedEvent(req, 'trip_started', tripPayload || normalizeTripRecord(trip), {
        tripId: trip.id,
        broadcastParentRole: true,
        broadcastDriverRole: true,
      });

      await notifyTripStartedForChildren(children, tripType, trip.id);

      return res.json(tripPayload || trip);
    }

    const loginValue = req.body.email || req.query.email;
    if (!loginValue) {
      return res.status(400).json({ message: 'Driver email is required in shared-database mode' });
    }

    const sharedContext = await buildSharedTripContext(loginValue);
    if (sharedContext.error) {
      return res.status(sharedContext.error.status).json(sharedContext.error.body);
    }
    if (tripType === 'afternoon' && sharedContext.children.length) {
      const pickedChildIds = await getMorningPickedChildIdsForAfternoonTrip(
        sharedContext.driver.routeId ?? null,
        sharedContext.user.id ?? null
      );
      sharedContext.children = sharedContext.children.filter((child) =>
        pickedChildIds.has(normalizeId(child.id ?? child.raw?.id))
      );
    }
    if (tripType === 'afternoon' && !sharedContext.children.length) {
      return res.status(409).json({
        message: 'No children are available for the afternoon trip',
      });
    }

    const routeGeometryPoints = sharedContext.driver.routeId
      ? await getRouteGeometryPointsByRouteId(sharedContext.driver.routeId)
      : [];

    let stops = [];
    if (sharedContext.children.length) {
      stops = buildStopsFromSharedRoute(sharedContext.children, sharedContext.routeStops, tripType);
    }
    if (!stops.length) {
      // Fall back to route stops even when children are assigned, because some schemas
      // store child pickup/stop references that cannot be matched reliably.
      stops = buildStopsFromRouteStopsOnly(sharedContext.routeStops, tripType);
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
    const route = await computeTripRoute(parsedLat, parsedLng, stops, {
      routeStops: sharedContext.routeStops,
      routeGeometryPoints,
      stopsMeta: sharedContext.routeStops,
      reverseRouteStops: tripType === 'afternoon',
    });

    await Trip.destroy({ where: { status: 'running' } });
    let trip = await Trip.create({
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

    if (tripType === 'morning') {
      const createdPins = await generateTripPinsForChildren({
        children: sharedContext.children,
        tripId: trip.id,
        routeId: sharedContext.driver.routeId ?? null,
        driverUserId: sharedContext.user.id ?? null,
        tripType,
      });
      const pinnedStops = applyTripPinsToStops(stops, createdPins);
      const pinnedNextStop =
        pinnedStops.find((stop) => stop.status === 'pending') || null;
      await trip.update({
        stops: pinnedStops,
        nextStop: pinnedNextStop,
      });
      await trip.reload();
    }

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

    const tripPayload = await buildTripResponsePayload(trip);
    await emitTripScopedEvent(req, 'trip_started', tripPayload || normalizeTripRecord(trip), {
      tripId: trip.id,
      broadcastParentRole: true,
      broadcastDriverRole: true,
    });

    await notifyTripStartedForChildren(sharedContext.children, tripType, trip.id);

    return res.json(tripPayload || trip);
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
  const isMorningSchoolArrival =
    normalizedTrip?.tripType === 'morning' &&
    normalizedTrip?.nextStop?.type === 'dropoff';
  const isAfternoonTerminalArrival =
    normalizedTrip?.tripType === 'afternoon' &&
    !String(normalizedTrip?.nextStop?.childId || '').trim() &&
    ['dropoff', 'end', 'school'].includes(
      String(normalizedTrip?.nextStop?.type || '').trim().toLowerCase()
    );
  if (
    normalizedTrip?.nextStop?.type &&
    normalizedTrip.nextStop.type !== 'stop' &&
    !isMorningSchoolArrival &&
    !isAfternoonTerminalArrival
  ) {
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
    const tripPayload = await buildTripResponsePayload(trip);
    await emitTripScopedEvent(req, 'trip_completed', tripPayload || normalizeTripRecord(trip), {
      tripId: trip.id,
      broadcastParentRole: true,
      broadcastDriverRole: true,
    });
    return res.json({ message: 'Trip already completed' });
  }

  if (isMorningSchoolArrival) {
    const activeStop = normalizedTrip.nextStop;
    const completedChildIds = [];
    const completedAt = new Date().toISOString();
    stops.forEach((stop) => {
      if (
        stop?.status === 'pending' &&
        stop?.type === 'dropoff' &&
        isSameRouteStopGroup(stop, activeStop)
      ) {
        stop.status = 'completed';
        stop.completedAt = completedAt;
        const childId = normalizeId(stop.childId);
        if (childId) completedChildIds.push(childId);
      }
    });
    await updateTripStatusForChildren(completedChildIds, 'dropped');
  } else {
    stops[nextIndex].status = 'completed';
    stops[nextIndex].completedAt = new Date().toISOString();
  }
  const nextStop = stops.find((stop) => stop.status === 'pending') || null;
  const nextRoute = nextStop
    ? await computeRouteAfterStopProgress(
        normalizedTrip,
        normalizedTrip.driverLat,
        normalizedTrip.driverLng,
        stops,
        nextStop
      )
    : null;

  await trip.update({
    stops,
    nextStop,
    currentRoute: nextStop ? nextRoute : null,
    status: nextStop ? normalizedTrip.status : 'completed',
  });
  await persistTripLocationSnapshot(trip.id, {
    driverLat: normalizedTrip.driverLat,
    driverLng: normalizedTrip.driverLng,
    nextStop,
    currentRoute: nextStop ? nextRoute : null,
  });
  await trip.reload();

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

  const tripPayload = await buildTripResponsePayload(trip);
  await emitTripScopedEvent(
    req,
    nextStop ? 'stop_completed' : 'trip_completed',
    nextStop ? { trip: tripPayload || normalizeTripRecord(trip) } : tripPayload || normalizeTripRecord(trip),
    {
      tripId: trip.id,
      broadcastParentRole: true,
      broadcastDriverRole: true,
    }
  );

  return res.json({ message: 'Stop completed', trip: tripPayload || normalizeTripRecord(trip) });
};

exports.getTripData = async (req, res) => {
  const trip = await getRunningTrip();
  const tripPayload = await buildTripResponsePayload(trip);
  if (!tripPayload) {
    return res.json(tripPayload);
  }

  return res.json(tripPayload);
};

exports.verifyPickup = async (req, res) => {
  await ensureTripsTable();

  const { childId, pin } = req.body;
  const normalizedChildId = normalizeId(childId);
  if (!normalizedChildId) {
      return res.status(400).json({ message: 'Valid childId is required' });
  }

  let trip = await getRunningTrip();
  const activePin = await getActiveTripPinForChild(
    normalizedChildId,
    trip?.id || null
  );
  const expectedPin = activePin?.pin ? String(activePin.pin) : '';
  const providedPin = pin != null ? String(pin).trim() : '';

  if (!providedPin) {
    return res.status(400).json({ message: 'PIN is required' });
  }

  if (!expectedPin) {
    return res.status(409).json({ message: 'PIN is expired or not generated for this child' });
  }

  if (expectedPin !== providedPin) {
    return res.status(400).json({ message: 'Invalid PIN' });
  }

  if (await isLegacyNodeUserSchema()) {
    const child = await Child.findByPk(normalizedChildId);
    if (!child) return res.status(404).json({ message: 'Child not found' });

    await child.update({ tripStatus: 'picked_up' });
  } else {
    const child = await getChildRecordById(normalizedChildId);
    if (!child) return res.status(404).json({ message: 'Child not found' });
    await updateTripStatusForChildren([normalizedChildId], 'picked_up');
  }

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

    if (
      !normalizedTrip.nextStop ||
      normalizedTrip.nextStop.type !== 'pickup' ||
      !isSameRouteStopGroup(stops[stopIndex], normalizedTrip.nextStop)
    ) {
      return res.status(409).json({
        message: 'Bus has not reached this child pickup point yet',
      });
    }

    stops[stopIndex].status = 'completed';
    stops[stopIndex].completedAt = new Date().toISOString();
    const nextStop =
      stops.find((stop) => stop.status === 'pending' && stop.type === 'pickup') ||
      stops.find((stop) => stop.status === 'pending') ||
      null;
    const route = nextStop
      ? await computeRouteAfterStopProgress(
          normalizedTrip,
          normalizedTrip.driverLat,
          normalizedTrip.driverLng,
          stops,
          nextStop
        )
      : null;

    await trip.update({
      stops,
      nextStop,
      currentRoute: route,
      status: nextStop ? normalizedTrip.status : 'completed',
    });
    await persistTripLocationSnapshot(trip.id, {
      driverLat: normalizedTrip.driverLat,
      driverLng: normalizedTrip.driverLng,
      nextStop,
      currentRoute: route,
    });
    await trip.reload();

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
      {
        childId: normalizedChildId,
        trip: (await buildTripResponsePayload(trip)) || normalizeTripRecord(trip),
      },
      { tripId: trip.id, childId: normalizedChildId }
    );

    await sendChildEvent(
      'child_picked_up',
      normalizedChildId,
      { tripType: normalizedTrip.tripType },
      { tripId: trip.id, tripType: normalizedTrip.tripType }
    );
  }

  return res.json({
    message: 'Pickup verified',
    trip: trip ? (await buildTripResponsePayload(trip)) || normalizeTripRecord(trip) : null,
  });
};

exports.cancelPickup = async (req, res) => {
  await ensureTripsTable();

  const normalizedChildId = normalizeId(req.body.childId);
  if (!normalizedChildId) {
    return res.status(400).json({ message: 'Valid childId is required' });
  }

  const trip = await getRunningTrip();
  if (!trip) {
    return res.status(404).json({ message: 'No running trip found' });
  }

  const normalizedTrip = normalizeTripRecord(trip);
  if (!normalizedTrip || normalizedTrip.tripType !== 'morning') {
    return res.status(409).json({ message: 'Cancel child is only available during morning pickup trips' });
  }

  let stops = Array.isArray(normalizedTrip.stops) ? [...normalizedTrip.stops] : [];
  const pickupStopIndex = stops.findIndex(
    (stop) =>
      String(stop.childId) === String(normalizedChildId) &&
      stop.type === 'pickup' &&
      stop.status === 'pending'
  );

  if (pickupStopIndex === -1) {
    return res.status(409).json({ message: 'Pickup stop is not pending for this child' });
  }

  if (
    !normalizedTrip.nextStop ||
    normalizedTrip.nextStop.type !== 'pickup' ||
    !isSameRouteStopGroup(stops[pickupStopIndex], normalizedTrip.nextStop)
  ) {
    return res.status(409).json({
      message: 'Bus has not reached this child pickup point yet',
    });
  }

  const completedAt = new Date().toISOString();
  const cancelledPickup = { ...stops[pickupStopIndex] };
  stops[pickupStopIndex] = {
    ...stops[pickupStopIndex],
    status: 'completed',
    completedAt,
    skipped: true,
    skippedReason: 'child_absent',
  };

  for (let index = 0; index < stops.length; index += 1) {
    const stop = stops[index];
    if (
      String(stop?.childId) === String(normalizedChildId) &&
      stop?.type === 'dropoff' &&
      stop?.status === 'pending'
    ) {
      stops[index] = {
        ...stop,
        status: 'completed',
        completedAt,
        skipped: true,
        skippedReason: 'pickup_cancelled',
      };
    }
  }

  await deleteExistingPinsForChildren([normalizedChildId]);

  let nextStop =
    stops.find((stop) => stop.status === 'pending' && stop.type === 'pickup') ||
    stops.find((stop) => stop.status === 'pending') ||
    null;
  if (!nextStop) {
    const routeStops =
      Array.isArray(normalizedTrip.currentRoute?.stopsMeta) &&
      normalizedTrip.currentRoute.stopsMeta.length
        ? normalizedTrip.currentRoute.stopsMeta
        : normalizedTrip.routeId
          ? await getRouteStopsByRouteId(normalizedTrip.routeId)
          : [];
    const continuationStop = buildMorningRouteContinuationStop(routeStops, stops);
    if (continuationStop) {
      stops = sortStopsBySequence([...stops, continuationStop], normalizedTrip.tripType);
      nextStop = stops.find((stop) => stop.status === 'pending') || null;
    }
  }
  const nextRoute = nextStop
    ? await computeRouteAfterStopProgress(
        normalizedTrip,
        normalizedTrip.driverLat,
        normalizedTrip.driverLng,
        stops,
        nextStop
      )
    : null;

  await trip.update({
    stops,
    nextStop,
    currentRoute: nextRoute,
    status: nextStop ? normalizedTrip.status : 'completed',
  });
  await persistTripLocationSnapshot(trip.id, {
    driverLat: normalizedTrip.driverLat,
    driverLng: normalizedTrip.driverLng,
    nextStop,
    currentRoute: nextRoute,
  });
  await trip.reload();

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
    'pickup_cancelled',
    {
      childId: normalizedChildId,
      childName: cancelledPickup?.name || 'Child',
      trip: (await buildTripResponsePayload(trip)) || normalizeTripRecord(trip),
    },
    {
      tripId: trip.id,
      childId: normalizedChildId,
      broadcastParentRole: true,
      broadcastDriverRole: true,
    }
  );

  return res.json({
    message: 'Child cancelled for this trip',
    trip: (await buildTripResponsePayload(trip)) || normalizeTripRecord(trip),
  });
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
    await updateTripStatusForChildren([normalizedChildId], 'dropped');
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

    const activeStop = { ...stops[stopIndex] };
    stops[stopIndex].status = 'completed';
    stops[stopIndex].completedAt = new Date().toISOString();
    let nextStop = stops.find((stop) => stop.status === 'pending') || null;
    if (!nextStop) {
      const routeStops =
        Array.isArray(normalizedTrip.currentRoute?.stopsMeta) &&
        normalizedTrip.currentRoute.stopsMeta.length
          ? normalizedTrip.currentRoute.stopsMeta
          : normalizedTrip.routeId
            ? await getRouteStopsByRouteId(normalizedTrip.routeId)
            : [];
      const continuationStop = buildAfternoonRouteContinuationStop(routeStops, stops);
      if (continuationStop) {
        stops.push(continuationStop);
        nextStop = continuationStop;
      }
    }
    const nextRoute = nextStop
      ? await computeRouteAfterStopProgress(
          normalizedTrip,
          normalizedTrip.driverLat,
          normalizedTrip.driverLng,
          stops,
          nextStop
        )
      : null;

    await trip.update({
      stops,
      nextStop,
      currentRoute: nextRoute,
      status: nextStop ? normalizedTrip.status : 'completed',
    });
    await persistTripLocationSnapshot(trip.id, {
      driverLat: normalizedTrip.driverLat,
      driverLng: normalizedTrip.driverLng,
      nextStop,
      currentRoute: nextRoute,
    });
    await trip.reload();

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
      {
        childId: normalizedChildId,
        childName: activeStop?.name || 'Child',
        trip: (await buildTripResponsePayload(trip)) || normalizeTripRecord(trip),
      },
      { tripId: trip.id, childId: normalizedChildId }
    );

    await sendChildEvent(
      normalizedTrip.tripType === 'morning' ? 'child_arrived_school' : 'child_dropped_home',
      normalizedChildId,
      { tripType: normalizedTrip.tripType },
      { tripId: trip.id, tripType: normalizedTrip.tripType }
    );
  }

  return res.json({
    message: 'Child dropped',
    trip: trip ? (await buildTripResponsePayload(trip)) || normalizeTripRecord(trip) : null,
  });
};

exports.updateDriverLocation = async (req, res) => {
  await ensureTripsTable();

  const { lat, lng } = req.body;
  const parsedLat = parseCoordinate(lat);
  const parsedLng = parseCoordinate(lng);

  if (parsedLat === null || parsedLng === null) {
    return res.status(400).json({ message: 'Valid lat and lng are required' });
  }

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
    refreshedTrip = await buildTripResponsePayload(runningTrip);
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
  await Trip.destroy({ where: { status: 'running' } });

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
  const pickupPinRows = await buildPickupPinRowsForParent(normalizedTrip);
  const tripPinRows = await buildTripPinRowsForParent(normalizedTrip);

  return res.json({
    active: true,
    trip: normalizedTrip,
    child,
    routeStops,
    routePreview,
    pickupPinRows,
    tripPinRows,
  });
};

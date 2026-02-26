const { Op } = require('sequelize');
const Child = require('../models/Child');
const Driver = require('../models/Driver');
const Trip = require('../models/Trip');
const {
  buildStopsNearestFirst,
  calculateRoute,
  calculateRouteWithWaypoints
} = require('../services/route.service');

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

function normalizeId(value) {
  const num = Number(value);
  return Number.isFinite(num) && num > 0 ? Math.trunc(num) : null;
}

async function resolveParentIdFromChildId(childId) {
  const normalizedChildId = normalizeId(childId);
  if (!normalizedChildId) return null;
  const child = await Child.findByPk(normalizedChildId, { attributes: ['parentId'] });
  return child ? normalizeId(child.parentId) : null;
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
    emittedAt: new Date().toISOString()
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
          lng: parseCoordinate(s.lng) ?? s.lng
        }))
    : [];

  const rawNextStop = parseMaybeJson(raw.nextStop);
  const nextStop =
    rawNextStop && typeof rawNextStop === 'object'
      ? {
          ...rawNextStop,
          lat: parseCoordinate(rawNextStop.lat) ?? rawNextStop.lat,
          lng: parseCoordinate(rawNextStop.lng) ?? rawNextStop.lng
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
            lng: parseCoordinate(p.lng) ?? p.lng
          }))
      : [];
    currentRoute = { ...rawRoute, points };
  }

  return {
    ...raw,
    driverLat: parseCoordinate(raw.driverLat) ?? raw.driverLat,
    driverLng: parseCoordinate(raw.driverLng) ?? raw.driverLng,
    stops,
    nextStop,
    currentRoute
  };
}

exports.startTrip = async (req, res) => {
  const { lat, lng, tripType = 'morning' } = req.body;
  const parsedLat = parseCoordinate(lat);
  const parsedLng = parseCoordinate(lng);

  if (parsedLat === null || parsedLng === null) {
    return res.status(400).json({ message: 'Valid lat and lng are required' });
  }

  // In morning, children are 'pending' at home.
  // In afternoon, children were 'dropped' at school and now need to be picked up.
  const query = {
    subscriptionStatus: 'active',
  };

  if (tripType === 'morning') {
    query.tripStatus = 'pending';
  } else {
    query.tripStatus = 'dropped';
  }

  const children = await Child.findAll({
    where: query,
    order: [['routeOrder', 'ASC'], ['name', 'DESC']]
  });

  if (!children.length) return res.json({ message: `No children found for ${tripType} trip` });

  // Reset status to pending for the new trip if it's afternoon
  if (tripType === 'afternoon') {
    const childIds = children.map(c => c.id);
    await Child.update({ tripStatus: 'pending' }, { where: { id: childIds } });
  }

  const stops = buildStopsNearestFirst(children, parsedLat, parsedLng, tripType);
  const nextStop = stops[0];
  const route = await calculateRoute({ lat: parsedLat, lng: parsedLng }, nextStop);

  await Trip.destroy({ where: {} });
  const trip = await Trip.create({
    driverLat: parsedLat,
    driverLng: parsedLng,
    stops,
    nextStop,
    currentRoute: route,
    status: 'running',
    tripType,
    direction: tripType === 'morning' ? 'FORWARD' : 'REVERSE'
  });

  await emitTripScopedEvent(
    req,
    'trip_started',
    normalizeTripRecord(trip),
    {
      tripId: trip.id,
      broadcastParentRole: true,
      broadcastDriverRole: true
    }
  );

  res.json(trip);
};

exports.getTripData = async (req, res) => {
  const trip = await Trip.findOne({ where: { status: 'running' } });
  const normalizedTrip = normalizeTripRecord(trip);
  if (normalizedTrip) {
    console.log(`Fetching trip: status=${normalizedTrip.status}, stops=${normalizedTrip.stops?.length}, routePoints=${normalizedTrip.currentRoute?.points?.length || 0}`);
  }
  res.json(normalizedTrip);
};

exports.verifyPickup = async (req, res) => {
  const { childId, pin } = req.body;
  const child = await Child.findByPk(childId);
  if (!child) return res.status(404).json({ message: 'Child not found' });

  if (child.secretPin !== pin) {
    return res.status(400).json({ message: 'Invalid PIN' });
  }

  await child.update({ tripStatus: 'picked_up' });

  // Update Trip
  const trip = await Trip.findOne({ where: { status: 'running' } });
  if (trip) {
    const normalizedTrip = normalizeTripRecord(trip);
    // 1. Mark this specific pickup stop as completed
    const stops = [...normalizedTrip.stops];
    const stopIndex = stops.findIndex(
      s => String(s.childId) === String(childId) && s.type === 'pickup' && s.status === 'pending'
    );
    if (stopIndex !== -1) {
      stops[stopIndex].status = 'completed';
    }

    // 2. Find next pending stop
    const nextStop = stops.find(s => s.status === 'pending');

    let route = trip.currentRoute;
    if (nextStop) {
      // 3. Calc route from Driver Current Loc to Next Stop
      route = await calculateRoute(
        { lat: normalizedTrip.driverLat, lng: normalizedTrip.driverLng },
        nextStop
      );
    }

    await trip.update({
      stops,
      nextStop: nextStop || null,
      currentRoute: nextStop ? route : null
    });

    await emitTripScopedEvent(
      req,
      'pickup_completed',
      { childId, trip: normalizeTripRecord(trip) },
      { tripId: trip.id, childId }
    );
  }

  res.json({ message: 'Pickup verified', child });
};

exports.dropChild = async (req, res) => {
  const { childId } = req.body;
  await Child.update({ tripStatus: 'dropped' }, { where: { id: childId } });

  // Update Trip
  const trip = await Trip.findOne({ where: { status: 'running' } });
  if (trip) {
    const normalizedTrip = normalizeTripRecord(trip);
    // 1. Mark drop stop as completed
    const stops = [...normalizedTrip.stops];
    const stopIndex = stops.findIndex(
      s => String(s.childId) === String(childId) && s.type === 'dropoff' && s.status === 'pending'
    );
    if (stopIndex !== -1) {
      stops[stopIndex].status = 'completed';
    }

    // 2. Find next pending stop
    const nextStop = stops.find(s => s.status === 'pending');

    let route = normalizedTrip.currentRoute;
    let status = normalizedTrip.status;
    if (nextStop) {
      route = await calculateRoute(
        { lat: normalizedTrip.driverLat, lng: normalizedTrip.driverLng },
        nextStop
      );
    } else {
      route = null;
      status = 'completed'; // Trip over
    }

    await trip.update({
      stops,
      nextStop: nextStop || null,
      currentRoute: route,
      status: status
    });

    await emitTripScopedEvent(
      req,
      'drop_completed',
      { childId, trip: normalizeTripRecord(trip) },
      { tripId: trip.id, childId }
    );
  }

  res.json({ message: 'Child dropped' });
};

exports.updateDriverLocation = async (req, res) => {
  const { lat, lng } = req.body;
  const parsedLat = parseCoordinate(lat);
  const parsedLng = parseCoordinate(lng);

  if (parsedLat === null || parsedLng === null) {
    return res.status(400).json({ message: 'Valid lat and lng are required' });
  }

  // Update active trip
  await Trip.update(
    { driverLat: parsedLat, driverLng: parsedLng },
    { where: { status: 'running' } }
  );

  // Update Driver collection
  await Driver.update({ currentLat: parsedLat, currentLng: parsedLng }, { where: {} });

  // Update Child collection for parents who poll child data
  await Child.update(
    { driverCurrentLat: parsedLat, driverCurrentLng: parsedLng },
    { where: { tripStatus: { [Op.in]: ['pending', 'picked_up'] } } }
  );

  const runningTrip = await Trip.findOne({
    where: { status: 'running' },
    attributes: ['id']
  });
  await emitTripScopedEvent(
    req,
    'driver_moved',
    { lat: parsedLat, lng: parsedLng },
    { tripId: runningTrip?.id, broadcastParentRole: !runningTrip?.id }
  );

  res.json({ success: true, live: true });
};

exports.resetTrip = async (req, res) => {
  await Trip.destroy({ where: {} });
  await Child.update({
    tripStatus: 'pending',
    driverCurrentLat: 23.02431, // Shivranjani Flyover
    driverCurrentLng: 72.53016
  }, { where: {} });

  // Reset all drivers trip data
  await Driver.update({
    stops: [],
    currentRoute: null,
    lastCompletedStopIndex: -1,
    currentLat: 23.02431, // Shivranjani Flyover
    currentLng: 72.53016
  }, { where: {} });

  await emitTripScopedEvent(
    req,
    'trip_reset',
    {},
    { broadcastParentRole: true, broadcastDriverRole: true }
  );

  res.json({ message: 'Trip reset' });
};

exports.getChildRoutePreview = async (req, res) => {
  const { childId } = req.query;
  if (!childId) {
    return res.status(400).json({ message: 'childId is required' });
  }

  const trip = await Trip.findOne({ where: { status: 'running' } });
  const normalizedTrip = normalizeTripRecord(trip);
  if (!normalizedTrip) {
    return res.json({ points: [], distance: 0, duration: 0 });
  }

  const child = await Child.findByPk(childId);
  if (!child) {
    return res.status(404).json({ message: 'Child not found' });
  }

  const targetType = child.tripStatus === 'picked_up' ? 'dropoff' : 'pickup';
  const stops = normalizedTrip.stops || [];
  const nextStop = normalizedTrip.nextStop;

  if (!nextStop) {
    return res.json({ points: [], distance: 0, duration: 0 });
  }

  const nextStopIndex = stops.findIndex(
    (s) =>
      String(s.childId) === String(nextStop.childId) &&
      s.type === nextStop.type &&
      s.status === 'pending'
  );

  const targetStopIndex = stops.findIndex(
    (s) =>
      String(s.childId) === String(childId) &&
      s.type === targetType &&
      s.status === 'pending'
  );

  if (nextStopIndex === -1 || targetStopIndex === -1 || targetStopIndex < nextStopIndex) {
    return res.json({ points: [], distance: 0, duration: 0 });
  }

  const waypoints = [
    { lat: normalizedTrip.driverLat, lng: normalizedTrip.driverLng },
    ...stops.slice(nextStopIndex, targetStopIndex + 1).map((s) => ({
      lat: s.lat,
      lng: s.lng,
    })),
  ];

  const route = await calculateRouteWithWaypoints(waypoints);
  return res.json(route);
};

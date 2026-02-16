const { Op } = require('sequelize');
const Child = require('../models/Child');
const Driver = require('../models/Driver');
const Trip = require('../models/Trip');
const {
  buildStopsNearestFirst,
  calculateRoute
} = require('../services/route.service');

exports.startTrip = async (req, res) => {
  const { lat, lng, tripType = 'morning' } = req.body;

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

  const stops = buildStopsNearestFirst(children, lat, lng, tripType);
  const nextStop = stops[0];
  const route = await calculateRoute({ lat, lng }, nextStop);

  await Trip.destroy({ where: {} });
  const trip = await Trip.create({
    driverLat: lat,
    driverLng: lng,
    stops,
    nextStop,
    currentRoute: route,
    status: 'running',
    tripType,
    direction: tripType === 'morning' ? 'FORWARD' : 'REVERSE'
  });

  const io = req.app.get('io');
  if (io) {
    io.emit('trip_started', trip);
  }

  res.json(trip);
};

exports.getTripData = async (req, res) => {
  const trip = await Trip.findOne({ where: { status: 'running' } });
  if (trip) {
    console.log(`Fetching trip: status=${trip.status}, stops=${trip.stops?.length}, routePoints=${trip.currentRoute?.points?.length || 0}`);
  }
  res.json(trip);
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
    // 1. Mark this specific pickup stop as completed
    const stops = [...trip.stops];
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
        { lat: trip.driverLat, lng: trip.driverLng },
        nextStop
      );
    }

    await trip.update({
      stops,
      nextStop: nextStop || null,
      currentRoute: nextStop ? route : null
    });

    const io = req.app.get('io');
    if (io) {
      io.emit('pickup_completed', { childId, trip });
    }
  }

  res.json({ message: 'Pickup verified', child });
};

exports.dropChild = async (req, res) => {
  const { childId } = req.body;
  await Child.update({ tripStatus: 'dropped' }, { where: { id: childId } });

  // Update Trip
  const trip = await Trip.findOne({ where: { status: 'running' } });
  if (trip) {
    // 1. Mark drop stop as completed
    const stops = [...trip.stops];
    const stopIndex = stops.findIndex(
      s => String(s.childId) === String(childId) && s.type === 'dropoff' && s.status === 'pending'
    );
    if (stopIndex !== -1) {
      stops[stopIndex].status = 'completed';
    }

    // 2. Find next pending stop
    const nextStop = stops.find(s => s.status === 'pending');

    let route = trip.currentRoute;
    let status = trip.status;
    if (nextStop) {
      route = await calculateRoute(
        { lat: trip.driverLat, lng: trip.driverLng },
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

    const io = req.app.get('io');
    if (io) {
      io.emit('drop_completed', { childId, trip });
    }
  }

  res.json({ message: 'Child dropped' });
};

exports.updateDriverLocation = async (req, res) => {
  const { lat, lng } = req.body;

  if (!lat || !lng) {
    return res.status(400).json({ message: 'Lat and Lng are required' });
  }

  // Update active trip
  await Trip.update(
    { driverLat: lat, driverLng: lng },
    { where: { status: 'running' } }
  );

  // Update Driver collection
  await Driver.update({ currentLat: lat, currentLng: lng }, { where: {} });

  // Update Child collection for parents who poll child data
  await Child.update(
    { driverCurrentLat: lat, driverCurrentLng: lng },
    { where: { tripStatus: { [Op.in]: ['pending', 'picked_up'] } } }
  );

  const io = req.app.get('io');
  if (io) {
    io.emit('driver_moved', { lat, lng });
  }

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

  const io = req.app.get('io');
  if (io) {
    io.emit('trip_reset');
  }

  res.json({ message: 'Trip reset' });
};

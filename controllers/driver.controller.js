const Driver = require('../models/Driver');
const User = require('../models/User');
const {
  findUserByLogin,
  getDriverProfileForUser,
  getAssignedChildrenForDriverUser,
  isLegacyNodeUserSchema,
} = require('../services/schema-compat.service');
const { sequelize } = require('../config/db.config');
const { QueryTypes } = require('sequelize');

function safeJsonParse(value) {
  if (typeof value !== 'string') return value;
  try {
    return JSON.parse(value);
  } catch (_) {
    return value;
  }
}

function buildPolylinePointsFromGeojson(geojson) {
  const decoded = safeJsonParse(geojson);
  const geometry = decoded?.type === 'Feature' ? decoded.geometry : decoded;
  if (!geometry || geometry.type !== 'LineString' || !Array.isArray(geometry.coordinates)) {
    return [];
  }
  return geometry.coordinates
    .map((c) => (Array.isArray(c) && c.length >= 2 ? { lat: Number(c[1]), lng: Number(c[0]) } : null))
    .filter((p) => p && Number.isFinite(p.lat) && Number.isFinite(p.lng));
}

// GET DRIVER DETAILS
exports.getDriverDetails = async (req, res) => {
  try {
    const { email } = req.query;

    const user = await findUserByLogin(email);
    if (!user) return res.json(null);

    const driver = await getDriverProfileForUser(user.id);
    return res.json(driver);
  } catch (err) {
    console.error(err);
    res.status(500).json({ message: 'Error fetching driver details' });
  }
};

exports.getAssignedRoute = async (req, res) => {
  try {
    const { email } = req.query;
    if (!email) {
      return res.status(400).json({ message: 'Email required' });
    }

    const user = await findUserByLogin(email);
    if (!user) return res.status(404).json({ message: 'User not found' });

    const driver = await getDriverProfileForUser(user.id);
    if (!driver?.routeId) {
      return res.status(404).json({ message: 'No route assigned' });
    }

    const rows = await sequelize.query(
      `
        SELECT id, name, driver_id, bus_id, user_id, school_id, geojson, stops
        FROM routes
        WHERE id = :routeId
          AND COALESCE(deleted, 0) = 0
        LIMIT 1
      `,
      { replacements: { routeId: driver.routeId }, type: QueryTypes.SELECT }
    );

    const route = rows[0] || null;
    if (!route) {
      return res.status(404).json({ message: 'Assigned route not found' });
    }

    const polylinePoints = buildPolylinePointsFromGeojson(route.geojson);
    const stops = (() => {
      const decodedStops = safeJsonParse(route.stops);
      return Array.isArray(decodedStops) ? decodedStops : [];
    })();

    return res.json({
      id: route.id,
      name: route.name,
      driver_id: route.driver_id,
      bus_id: route.bus_id,
      user_id: route.user_id,
      school_id: route.school_id,
      geojson: safeJsonParse(route.geojson),
      stops,
      polylinePoints,
    });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Error fetching assigned route' });
  }
};

// SAVE / UPDATE DRIVER DETAILS
exports.saveDriverDetails = async (req, res) => {
  try {
    if (!(await isLegacyNodeUserSchema())) {
      return res.status(409).json({
        message: 'Driver master profile is managed from the Laravel admin or school panel in shared-database mode',
      });
    }

    const {
      email,
      fullName,
      licenseNumber,
      phoneNumber,
      vehicleNumber,
      vehicleModel,
      vehicleCapacity,
    } = req.body;

    const user = await User.findOne({ where: { email } });
    if (!user) return res.status(404).json({ message: 'User not found' });

    let driver = await Driver.findOne({ where: { userId: user.id } });

    if (!driver) {
      driver = await Driver.create({
        userId: user.id,
        fullName,
        licenseNumber,
        phoneNumber,
        vehicleNumber,
        vehicleModel,
        vehicleCapacity,
      });
    } else {
      await driver.update({
        fullName,
        licenseNumber,
        phoneNumber,
        vehicleNumber,
        vehicleModel,
        vehicleCapacity,
      });
    }

    res.json(driver);
  } catch (err) {
    console.error(err);
    res.status(500).json({ message: 'Error saving driver details' });
  }
};

exports.getTripChildren = async (req, res) => {
  try {
    const { email } = req.query;
    if (!email) {
      return res.status(400).json({ message: 'Email required' });
    }

    const user = await findUserByLogin(email);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    const children = await getAssignedChildrenForDriverUser(user.id);
    return res.json(children);
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Error fetching assigned route children' });
  }
};

const Driver = require('../models/Driver');
const DriverChecklist = require('../models/DriverChecklist');
const DriverEmergency = require('../models/DriverEmergency');
const Trip = require('../models/Trip');
const User = require('../models/User');
const {
  findUserByLogin,
  getDriverProfileForUser,
  getAssignedChildrenForDriverUser,
  isLegacyNodeUserSchema,
} = require('../services/schema-compat.service');
const { ensureDriverFeatureTables } = require('../services/driver-feature-schema.service');
const { sequelize } = require('../config/db.config');
const { Op, QueryTypes } = require('sequelize');

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

function getTodayDateKey() {
  return new Date().toISOString().slice(0, 10);
}

function normalizeChecklistItems(rawItems) {
  const fallbackItems = [
    { key: 'vehicle_check', label: 'Vehicle condition checked', checked: false },
    { key: 'fuel_check', label: 'Fuel / battery level checked', checked: false },
    { key: 'documents_check', label: 'License and vehicle documents available', checked: false },
    { key: 'safety_check', label: 'Safety items checked', checked: false },
  ];

  if (!Array.isArray(rawItems) || !rawItems.length) {
    return fallbackItems;
  }

  return rawItems.map((item, index) => ({
    key: String(item?.key || `item_${index + 1}`),
    label: String(item?.label || `Checklist item ${index + 1}`),
    checked: Boolean(item?.checked),
  }));
}

async function resolveDriverUserByEmail(email) {
  const normalizedEmail = String(email || '').trim();
  if (!normalizedEmail) {
    return { error: { status: 400, body: { message: 'Email required' } } };
  }

  const user = await findUserByLogin(normalizedEmail);
  if (!user) {
    return { error: { status: 404, body: { message: 'User not found' } } };
  }

  const driver = await getDriverProfileForUser(user.id);
  if (!driver) {
    return { error: { status: 404, body: { message: 'Driver profile not found' } } };
  }

  return { user, driver };
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

exports.getPreTripChecklist = async (req, res) => {
  try {
    await ensureDriverFeatureTables();

    const resolved = await resolveDriverUserByEmail(req.query.email);
    if (resolved.error) {
      return res.status(resolved.error.status).json(resolved.error.body);
    }

    const logDate = String(req.query.date || getTodayDateKey());
    const record = await DriverChecklist.findOne({
      where: { driverUserId: resolved.user.id, logDate },
    });

    const items = normalizeChecklistItems(record?.items);
    const completed = record ? Boolean(record.completed) : items.every((item) => item.checked);

    return res.json({
      logDate,
      completed,
      completedAt: record?.completedAt || null,
      items,
    });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Error fetching pre-trip checklist' });
  }
};

exports.savePreTripChecklist = async (req, res) => {
  try {
    await ensureDriverFeatureTables();

    const resolved = await resolveDriverUserByEmail(req.body.email);
    if (resolved.error) {
      return res.status(resolved.error.status).json(resolved.error.body);
    }

    const logDate = String(req.body.date || getTodayDateKey());
    const items = normalizeChecklistItems(req.body.items);
    const completed = items.length > 0 && items.every((item) => item.checked);

    const [record] = await DriverChecklist.findOrCreate({
      where: { driverUserId: resolved.user.id, logDate },
      defaults: {
        driverUserId: resolved.user.id,
        logDate,
        items,
        completed,
        completedAt: completed ? new Date() : null,
      },
    });

    await record.update({
      items,
      completed,
      completedAt: completed ? new Date() : null,
    });

    return res.json({
      message: completed ? 'Pre-trip checklist completed' : 'Pre-trip checklist saved',
      logDate,
      completed,
      completedAt: record.completedAt,
      items,
    });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Error saving pre-trip checklist' });
  }
};

exports.reportQuickEmergency = async (req, res) => {
  try {
    await ensureDriverFeatureTables();

    const resolved = await resolveDriverUserByEmail(req.body.email);
    if (resolved.error) {
      return res.status(resolved.error.status).json(resolved.error.body);
    }

    const emergencyType = String(req.body.emergencyType || '').trim();
    if (!emergencyType) {
      return res.status(422).json({ message: 'Emergency type is required' });
    }

    const record = await DriverEmergency.create({
      driverUserId: resolved.user.id,
      emergencyType,
      description: String(req.body.description || '').trim() || null,
      contactNumber: String(req.body.contactNumber || resolved.driver.emergencyPhone || resolved.driver.phoneNumber || '').trim() || null,
      status: 'reported',
    });

    const io = req.app.get('io');
    if (io) {
      io.to('role:admin').emit('driver_emergency_reported', {
        id: record.id,
        driverUserId: resolved.user.id,
        driverName: resolved.driver.fullName || resolved.user.name || resolved.user.email,
        emergencyType: record.emergencyType,
        description: record.description,
        contactNumber: record.contactNumber,
        reportedAt: record.createdAt,
      });
    }

    return res.status(201).json({
      message: 'Emergency reported successfully',
      emergency: record,
    });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Error reporting emergency' });
  }
};

exports.getTodaySummary = async (req, res) => {
  try {
    await ensureDriverFeatureTables();

    const resolved = await resolveDriverUserByEmail(req.query.email);
    if (resolved.error) {
      return res.status(resolved.error.status).json(resolved.error.body);
    }

    const logDate = getTodayDateKey();
    const checklist = await DriverChecklist.findOne({
      where: { driverUserId: resolved.user.id, logDate },
    });

    const emergencyCount = await DriverEmergency.count({
      where: {
        driverUserId: resolved.user.id,
        createdAt: {
          [Op.gte]: new Date(`${logDate}T00:00:00.000Z`),
        },
      },
    });

    const runningTrip = await Trip.findOne({
      where: { driverUserId: resolved.user.id },
      order: [['updatedAt', 'DESC']],
    });

    const tripJson = runningTrip?.toJSON ? runningTrip.toJSON() : runningTrip;
    const stops = Array.isArray(tripJson?.stops) ? tripJson.stops : [];
    const completedStops = stops.filter((stop) => stop?.status === 'completed').length;
    const pendingStops = stops.filter((stop) => stop?.status === 'pending').length;

    return res.json({
      logDate,
      checklistCompleted: Boolean(checklist?.completed),
      checklistCompletedAt: checklist?.completedAt || null,
      emergencyCount,
      tripStatus: tripJson?.status || 'idle',
      tripType: tripJson?.tripType || null,
      completedStops,
      pendingStops,
    });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Error fetching driver summary' });
  }
};

exports.getEmergencyHistory = async (req, res) => {
  try {
    await ensureDriverFeatureTables();

    const resolved = await resolveDriverUserByEmail(req.query.email);
    if (resolved.error) {
      return res.status(resolved.error.status).json(resolved.error.body);
    }

    const rows = await DriverEmergency.findAll({
      where: { driverUserId: resolved.user.id },
      order: [['createdAt', 'DESC']],
      limit: 10,
    });

    return res.json(rows);
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Error fetching emergency history' });
  }
};

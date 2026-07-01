const Driver = require('../models/Driver');
const DriverChecklist = require('../models/DriverChecklist');
const DriverEmergency = require('../models/DriverEmergency');
const Trip = require('../models/Trip');
const User = require('../models/User');
const {
  findUserByLogin,
  getDriverProfileForUser,
  getAssignedChildrenForDriverUser,
  getParentUserIdForChild,
  getUserRole,
  isLegacyNodeUserSchema,
  tableExists,
  tableHasColumn,
  updateSharedDriverProfileForUser,
} = require('../services/schema-compat.service');
const { ensureDriverFeatureTables } = require('../services/driver-feature-schema.service');
const { sendEventNotification } = require('../services/mobile-notification.service');
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

function normalizeRoutePayload(route) {
  const routeJson = safeJsonParse(route?.route_json);
  const geojson =
    routeJson?.geojson ??
    safeJsonParse(route?.geojson) ??
    null;
  const stops = [];
  const pushPoint = (point, fallbackType) => {
    if (!point || typeof point !== 'object') return;
    const lat = Number(point.lat ?? point.latitude);
    const lng = Number(point.lng ?? point.longitude);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
    stops.push({
      ...point,
      lat,
      lng,
      type: point.type ?? fallbackType,
      name: point.name ?? point.stop_name ?? point.pickup_name ?? fallbackType,
    });
  };

  pushPoint(routeJson?.start_point, 'start');
  const routeStops = Array.isArray(routeJson?.pickup_points)
    ? routeJson.pickup_points
    : Array.isArray(routeJson?.stops)
      ? routeJson.stops
      : [];
  routeStops.forEach((stop) => pushPoint(stop, 'pickup'));
  pushPoint(routeJson?.end_point, 'end');

  if (!stops.length) {
    const decodedStops = safeJsonParse(route?.stops);
    if (Array.isArray(decodedStops)) {
      decodedStops.forEach((stop) => pushPoint(stop, 'pickup'));
    }
  }

  return {
    geojson,
    stops,
    polylinePoints: buildPolylinePointsFromGeojson(geojson),
  };
}

function getTodayDateKey() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function getDayBounds(dateKey = getTodayDateKey()) {
  const normalized = String(dateKey || '').trim();
  const [year, month, day] = normalized.split('-').map((value) => Number(value));
  if (!year || !month || !day) {
    const now = new Date();
    return {
      start: new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0, 0),
      end: new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59, 999),
    };
  }

  return {
    start: new Date(year, month - 1, day, 0, 0, 0, 0),
    end: new Date(year, month - 1, day, 23, 59, 59, 999),
  };
}

function buildEmergencyDedupKey(item) {
  if (!item) return '';
  return [
    String(item.emergencyType || item.emergency_type || '').trim().toLowerCase(),
    String(item.description || '').trim().toLowerCase(),
    String(item.contactNumber || item.contact_number || '').trim(),
    String(item.createdAt || item.created_at || '').trim(),
  ].join('|');
}

function mergeEmergencyRecords(localRows, sharedRows, { limit } = {}) {
  const merged = [...localRows, ...sharedRows]
    .sort((left, right) => {
      const leftTime = new Date(left?.createdAt || left?.created_at || 0).getTime();
      const rightTime = new Date(right?.createdAt || right?.created_at || 0).getTime();
      return rightTime - leftTime;
    })
    .filter((item, index, list) => {
      if (!item) return false;
      const key = buildEmergencyDedupKey(item);
      return list.findIndex((candidate) => buildEmergencyDedupKey(candidate) === key) === index;
    });

  if (Number.isInteger(limit) && limit > 0) {
    return merged.slice(0, limit);
  }

  return merged;
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

async function getAdminUserIds() {
  if (!(await tableExists('users'))) {
    return [];
  }

  const hasDeletedColumn = await tableHasColumn('users', 'deleted');
  const rows = await sequelize.query(
    `
      SELECT *
      FROM users
      ${hasDeletedColumn ? 'WHERE COALESCE(deleted, 0) = 0' : ''}
      ORDER BY id ASC
    `,
    { type: QueryTypes.SELECT }
  );

  const adminIds = [];
  for (const row of rows) {
    const role = await getUserRole(row);
    if (role === 'admin' && row.id != null) {
      adminIds.push(Number(row.id));
    }
  }

  return [...new Set(adminIds.filter((value) => Number.isFinite(value) && value > 0))];
}

function buildEmergencyContext({ resolved, emergencyType, description, childCount }) {
  const driverName = resolved.driver.fullName || resolved.user.name || resolved.user.email || 'Driver';
  const routeLabel = resolved.driver.routeName
    ? `route ${resolved.driver.routeName}`
    : resolved.driver.vehicleNumber
    ? `vehicle ${resolved.driver.vehicleNumber}`
    : 'the assigned vehicle';
  const normalizedDescription = String(description || '').trim();

  return {
    driverName,
    emergencyType,
    routeLabel,
    childCount,
    detailSuffix: normalizedDescription ? `: ${normalizedDescription}` : '',
  };
}

function toPositiveNumber(value) {
  const normalized = Number(value);
  return Number.isFinite(normalized) && normalized > 0 ? normalized : 0;
}

function getEmergencyIdentity(resolved, overrides = {}) {
  return {
    ownerUserId: toPositiveNumber(
      overrides.userId ??
      resolved?.driver?.raw?.user_id ??
      resolved?.driver?.userId ??
      0
    ),
    driverId: toPositiveNumber(
      overrides.driverId ??
      resolved?.driver?.id ??
      0
    ),
    vehicleId: toPositiveNumber(
      overrides.vehicleId ??
      resolved?.driver?.vehicleId ??
      0
    ),
  };
}

function getEmergencyQueryIdentity(query = {}, resolved = null) {
  return {
    userId: toPositiveNumber(query?.user_id ?? query?.userId ?? 0) ||
      toPositiveNumber(resolved?.driver?.raw?.user_id ?? resolved?.driver?.userId ?? 0),
    driverId: toPositiveNumber(query?.driver_id ?? query?.driverId ?? 0) ||
      toPositiveNumber(resolved?.driver?.id ?? 0),
    vehicleId: toPositiveNumber(query?.vehicle_id ?? query?.vehicleId ?? 0) ||
      toPositiveNumber(resolved?.driver?.vehicleId ?? 0),
  };
}

async function getSharedEmergencyIncidentsForDriver(
  resolved,
  { limit, userId, driverId, vehicleId } = {},
) {
  if (!(await tableExists('emergency_incidents'))) {
    return [];
  }

  const predicates = [];
  const replacements = {};
  const identity = getEmergencyIdentity(resolved, { userId, driverId, vehicleId });

  if (identity.ownerUserId > 0 && await tableHasColumn('emergency_incidents', 'user_id')) {
    predicates.push('user_id = :ownerUserId');
    replacements.ownerUserId = identity.ownerUserId;
  }
  if (identity.driverId > 0 && await tableHasColumn('emergency_incidents', 'driver_id')) {
    predicates.push('driver_id = :driverId');
    replacements.driverId = identity.driverId;
  }
  if (identity.vehicleId > 0 && await tableHasColumn('emergency_incidents', 'vehicle_id')) {
    predicates.push('vehicle_id = :vehicleId');
    replacements.vehicleId = identity.vehicleId;
  }

  if (!predicates.length) {
    return [];
  }

  const rows = await sequelize.query(
    `
      SELECT id, emergency_type, description, contact_number, status, created_at, updated_at
      FROM emergency_incidents
      WHERE (${predicates.join(' OR ')})
        AND COALESCE(deleted, 0) = 0
      ORDER BY created_at DESC, id DESC
      ${Number.isInteger(limit) && limit > 0 ? `LIMIT ${limit}` : ''}
    `,
    {
      replacements,
      type: QueryTypes.SELECT,
    }
  );

  return rows.map((row) => ({
    id: row.id,
    emergencyType: row.emergency_type,
    description: row.description,
    contactNumber: row.contact_number,
    status: String(row.status ?? 'reported') === '1' ? 'reported' : String(row.status ?? 'reported'),
    createdAt: row.created_at,
    updated_at: row.updated_at,
    source: 'shared',
  }));
}

async function syncEmergencyIncidentToSharedPanel({
  resolved,
  emergencyType,
  description,
  contactNumber,
}) {
  if (!(await tableExists('emergency_incidents'))) {
    return null;
  }

  const availableColumns = {
    user_id: await tableHasColumn('emergency_incidents', 'user_id'),
    driver_id: await tableHasColumn('emergency_incidents', 'driver_id'),
    vehicle_id: await tableHasColumn('emergency_incidents', 'vehicle_id'),
    reported_by: await tableHasColumn('emergency_incidents', 'reported_by'),
    emergency_type: await tableHasColumn('emergency_incidents', 'emergency_type'),
    description: await tableHasColumn('emergency_incidents', 'description'),
    contact_number: await tableHasColumn('emergency_incidents', 'contact_number'),
    status: await tableHasColumn('emergency_incidents', 'status'),
    deleted: await tableHasColumn('emergency_incidents', 'deleted'),
    created_at: await tableHasColumn('emergency_incidents', 'created_at'),
    updated_at: await tableHasColumn('emergency_incidents', 'updated_at'),
  };

  const ownerUserId = Number(
    resolved?.driver?.raw?.user_id ||
    resolved?.driver?.userId ||
    0
  );
  const driverId = Number(resolved?.driver?.id || 0);
  const vehicleId = Number(resolved?.driver?.vehicleId || 0);

  const payload = {};
  if (availableColumns.user_id) payload.user_id = ownerUserId > 0 ? ownerUserId : null;
  if (availableColumns.driver_id) payload.driver_id = driverId > 0 ? driverId : null;
  if (availableColumns.vehicle_id) payload.vehicle_id = vehicleId > 0 ? vehicleId : null;
  if (availableColumns.reported_by) payload.reported_by = 'driver';
  if (availableColumns.emergency_type) payload.emergency_type = emergencyType;
  if (availableColumns.description) payload.description = description || '';
  if (availableColumns.contact_number) payload.contact_number = contactNumber || null;
  if (availableColumns.status) payload.status = 1;
  if (availableColumns.deleted) payload.deleted = 0;

  const columns = Object.keys(payload);
  const replacements = { ...payload };

  if (availableColumns.created_at) {
    columns.push('created_at');
  }
  if (availableColumns.updated_at) {
    columns.push('updated_at');
  }

  const valueSql = columns.map((column) => {
    if (column === 'created_at' || column === 'updated_at') {
      return 'NOW()';
    }
    return `:${column}`;
  });

  if (!columns.length) {
    return null;
  }

  await sequelize.query(
    `
      INSERT INTO emergency_incidents (${columns.join(', ')})
      VALUES (${valueSql.join(', ')})
    `,
    {
      replacements,
      type: QueryTypes.INSERT,
    }
  );

  return {
    userId: ownerUserId > 0 ? ownerUserId : null,
    driverId: driverId > 0 ? driverId : null,
    vehicleId: vehicleId > 0 ? vehicleId : null,
  };
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
        SELECT *
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

    const normalizedRoute = normalizeRoutePayload(route);

    return res.json({
      id: route.id,
      name: route.name,
      driver_id: route.driver_id,
      bus_id: route.bus_id,
      user_id: route.user_id,
      school_id: route.school_id,
      geojson: normalizedRoute.geojson,
      stops: normalizedRoute.stops,
      polylinePoints: normalizedRoute.polylinePoints,
    });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Error fetching assigned route' });
  }
};

// SAVE / UPDATE DRIVER DETAILS
exports.saveDriverDetails = async (req, res) => {
  try {
    const {
      email,
      fullName,
      licenseNumber,
      phoneNumber,
      vehicleNumber,
      vehicleCapacity,
    } = req.body;

    const user = await findUserByLogin(email);
    if (!user) return res.status(404).json({ message: 'User not found' });

    if (!(await isLegacyNodeUserSchema())) {
      const driver = await updateSharedDriverProfileForUser(user.id, {
        fullName,
        licenseNumber,
        phoneNumber,
        vehicleNumber,
        vehicleCapacity,
      });

      if (!driver) {
        return res.status(404).json({ message: 'Driver profile not found' });
      }

      return res.json(driver);
    }

    let driver = await Driver.findOne({ where: { userId: user.id } });

    if (!driver) {
      driver = await Driver.create({
        userId: user.id,
        fullName,
        licenseNumber,
        phoneNumber,
        vehicleNumber,
        vehicleCapacity,
      });
    } else {
      await driver.update({
        fullName,
        licenseNumber,
        phoneNumber,
        vehicleNumber,
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

    const driver = await getDriverProfileForUser(user.id);
    const children = await getAssignedChildrenForDriverUser(user.id);
    const pickupIds = [
      ...new Set(
        children
          .map((child) => Number(child.pickupName ?? child.pickup_name))
          .filter((id) => Number.isInteger(id) && id > 0)
      ),
    ];
    const pickupMap = new Map();

    if (pickupIds.length && await tableExists('stops_pickup')) {
      const rows = await sequelize.query(
        `
          SELECT id, pickup_name, stop_name, route_id, sequence_order
          FROM stops_pickup
          WHERE id IN (:pickupIds)
            AND COALESCE(deleted, 0) = 0
        `,
        {
          replacements: { pickupIds },
          type: QueryTypes.SELECT,
        }
      );

      rows.forEach((row) => pickupMap.set(Number(row.id), row));
    }

    const enrichedChildren = await Promise.all(children.map(async (child) => {
      const pickupId = Number(child.pickupName ?? child.pickup_name);
      const pickup = pickupMap.get(pickupId) || null;
      return {
        ...child,
        routeName: driver?.routeName || null,
        routeId: driver?.routeId || child.routeId || null,
        pickupPointId: Number.isInteger(pickupId) && pickupId > 0 ? pickupId : null,
        pickupLabel:
          pickup?.pickup_name ||
          child.effectivePickupName ||
          child.pickupName ||
          'Unassigned Pickup',
        stopLabel: pickup?.stop_name || child.stopName || null,
        pickupSequence:
          pickup?.sequence_order == null ? null : Number(pickup.sequence_order),
      };
    }));

    return res.json(enrichedChildren);
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

    const description = String(req.body.description || '').trim();
    const assignedChildren = await getAssignedChildrenForDriverUser(resolved.user.id);
    const parentUserIds = await Promise.all(
      assignedChildren.map((child) => getParentUserIdForChild(child.id))
    );
    const adminUserIds = await getAdminUserIds();
    const normalizedParentUserIds = [...new Set(
      parentUserIds
        .map((value) => Number(value))
        .filter((value) => Number.isFinite(value) && value > 0)
    )];
    const recipientUserIds = [
      ...adminUserIds,
      ...normalizedParentUserIds,
    ];

    const record = await DriverEmergency.create({
      driverUserId: resolved.user.id,
      emergencyType,
      description: description || null,
      contactNumber: String(req.body.contactNumber || resolved.driver.emergencyPhone || resolved.driver.phoneNumber || '').trim() || null,
      status: 'reported',
    });

    const sharedIncident = await syncEmergencyIncidentToSharedPanel({
      resolved,
      emergencyType: record.emergencyType,
      description: record.description,
      contactNumber: record.contactNumber,
    });

    const notificationContext = buildEmergencyContext({
      resolved,
      emergencyType: record.emergencyType,
      description: record.description,
      childCount: assignedChildren.length,
    });
    const notificationUserIds = [...new Set(
      recipientUserIds
        .map((value) => Number(value))
        .filter((value) => Number.isFinite(value) && value > 0)
    )];

    const notificationResult = await sendEventNotification({
      eventKey: 'driver_emergency_alert',
      userIds: notificationUserIds,
      type: 'driver_emergency',
      context: notificationContext,
      data: {
        emergencyId: record.id,
        driverUserId: resolved.user.id,
        driverName: notificationContext.driverName,
        routeId: resolved.driver.routeId,
        routeName: resolved.driver.routeName,
        vehicleNumber: resolved.driver.vehicleNumber,
        emergencyType: record.emergencyType,
        description: record.description,
        contactNumber: record.contactNumber,
        childIds: assignedChildren.map((child) => child.id).filter(Boolean),
      },
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

      for (const parentUserId of normalizedParentUserIds) {
        io.to(`parent:${parentUserId}`).emit('driver_emergency_reported', {
          id: record.id,
          driverUserId: resolved.user.id,
          driverName: notificationContext.driverName,
          emergencyType: record.emergencyType,
          description: record.description,
          contactNumber: record.contactNumber,
          reportedAt: record.createdAt,
        });
      }
    }

    return res.status(201).json({
      message: 'Emergency reported successfully',
      emergency: record,
      panelEmergency: sharedIncident,
      recipients: {
        parents: normalizedParentUserIds.length,
        admins: adminUserIds.length,
      },
      notifications: notificationResult,
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
    const identity = getEmergencyQueryIdentity(req.query, resolved.error ? null : resolved);
    if (resolved.error && identity.userId <= 0 && identity.driverId <= 0 && identity.vehicleId <= 0) {
      return res.status(resolved.error.status).json(resolved.error.body);
    }

    const logDate = getTodayDateKey();
    const dayBounds = getDayBounds(logDate);
    const checklist = resolved.error
      ? null
      : await DriverChecklist.findOne({
          where: { driverUserId: resolved.user.id, logDate },
        });

    const localEmergencyRows = resolved.error
      ? []
      : await DriverEmergency.findAll({
          where: {
            driverUserId: resolved.user.id,
            createdAt: {
              [Op.between]: [dayBounds.start, dayBounds.end],
            },
          },
          order: [['createdAt', 'DESC']],
        });
    const sharedEmergencyRows = (await getSharedEmergencyIncidentsForDriver(
      resolved.error ? null : resolved,
      identity
    )).filter((item) => {
      const createdAt = new Date(item.createdAt || item.updated_at || 0);
      return !Number.isNaN(createdAt.getTime()) &&
        createdAt >= dayBounds.start &&
        createdAt <= dayBounds.end;
    });
    const emergencyCount = mergeEmergencyRecords(
      localEmergencyRows.map((row) => (row.toJSON ? row.toJSON() : row)),
      sharedEmergencyRows
    ).length;
    const totalLocalEmergencyRows = resolved.error
      ? []
      : await DriverEmergency.findAll({
          where: { driverUserId: resolved.user.id },
          order: [['createdAt', 'DESC']],
        });
    const totalSharedEmergencyRows = await getSharedEmergencyIncidentsForDriver(
      resolved.error ? null : resolved,
      identity
    );
    const totalEmergencyCount = mergeEmergencyRecords(
      totalLocalEmergencyRows.map((row) => (row.toJSON ? row.toJSON() : row)),
      totalSharedEmergencyRows
    ).length;

    const runningTrip = resolved.error
      ? null
      : await Trip.findOne({
          where: { driverUserId: resolved.user.id },
          order: [['updated_at', 'DESC']],
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
      emergencyCountToday: emergencyCount,
      emergencyCountTotal: totalEmergencyCount,
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
    const identity = getEmergencyQueryIdentity(req.query, resolved.error ? null : resolved);
    if (resolved.error && identity.userId <= 0 && identity.driverId <= 0 && identity.vehicleId <= 0) {
      return res.status(resolved.error.status).json(resolved.error.body);
    }

    const rows = resolved.error
      ? []
      : await DriverEmergency.findAll({
          where: { driverUserId: resolved.user.id },
          order: [['createdAt', 'DESC']],
          limit: 10,
        });
    const localRows = rows.map((row) => (row.toJSON ? row.toJSON() : row));
    const sharedRows = await getSharedEmergencyIncidentsForDriver(
      resolved.error ? null : resolved,
      {
        limit: 10,
        userId: identity.userId,
        driverId: identity.driverId,
        vehicleId: identity.vehicleId,
      }
    );
    const merged = mergeEmergencyRecords(localRows, sharedRows, { limit: 10 });

    return res.json(merged);
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Error fetching emergency history' });
  }
};

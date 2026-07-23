const { QueryTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

const tableDescriptionCache = new Map();

function safeJsonParse(value) {
  if (typeof value !== 'string') return value;
  try {
    return JSON.parse(value);
  } catch (_) {
    return value;
  }
}

function normalizeRoutePoint(stop, index, fallbackType = 'pickup') {
  if (!stop || typeof stop !== 'object') return null;

  const latitude = stop.latitude ?? stop.lat ?? null;
  const longitude = stop.longitude ?? stop.lng ?? null;
  if (latitude == null || longitude == null) {
    return null;
  }

  return {
    id: stop.id ?? stop.stop_id ?? stop.sequence_order ?? stop.sequence ?? index + 1,
    route_id: stop.route_id ?? null,
    pickup_name: stop.pickup_name ?? stop.stop_name ?? stop.name ?? `Stop ${index + 1}`,
    stop_name: stop.stop_name ?? stop.name ?? stop.pickup_name ?? `Stop ${index + 1}`,
    latitude,
    longitude,
    sequence_order: stop.sequence_order ?? stop.sequence ?? index + 1,
    type: stop.type ?? fallbackType,
    name: stop.name ?? stop.stop_name ?? stop.pickup_name ?? `Stop ${index + 1}`,
  };
}

function extractRouteStopsFromRouteJson(routeJson, routeId) {
  if (!routeJson || typeof routeJson !== 'object') {
    return [];
  }

  const ordered = [];
  const startPoint = normalizeRoutePoint(routeJson.start_point, 0, 'start');
  if (startPoint) {
    ordered.push({ ...startPoint, route_id: routeId });
  }

  const pickupPoints = Array.isArray(routeJson.pickup_points)
    ? routeJson.pickup_points
    : Array.isArray(routeJson.stops)
      ? routeJson.stops
      : [];

  pickupPoints.forEach((stop, index) => {
    const normalized = normalizeRoutePoint(stop, index + 1, 'pickup');
    if (normalized) {
      ordered.push({ ...normalized, route_id: routeId });
    }
  });

  const endPoint = normalizeRoutePoint(routeJson.end_point, ordered.length, 'end');
  if (endPoint) {
    ordered.push({ ...endPoint, route_id: routeId });
  }

  return ordered;
}

function buildPolylinePointsFromGeojson(geojson) {
  const decoded = safeJsonParse(geojson);
  const geometry = decoded?.type === 'Feature' ? decoded.geometry : decoded;
  if (!geometry || geometry.type !== 'LineString' || !Array.isArray(geometry.coordinates)) {
    return [];
  }

  return geometry.coordinates
    .map((coordinate) => {
      if (!Array.isArray(coordinate) || coordinate.length < 2) return null;
      const lng = Number(coordinate[0]);
      const lat = Number(coordinate[1]);
      return Number.isFinite(lat) && Number.isFinite(lng) ? { lat, lng } : null;
    })
    .filter(Boolean);
}

async function describeTable(tableName) {
  if (tableDescriptionCache.has(tableName)) {
    return tableDescriptionCache.get(tableName);
  }

  try {
    const description = await sequelize.getQueryInterface().describeTable(tableName);
    tableDescriptionCache.set(tableName, description);
    return description;
  } catch (_) {
    tableDescriptionCache.set(tableName, null);
    return null;
  }
}

async function tableExists(tableName) {
  return !!(await describeTable(tableName));
}

async function tableHasColumn(tableName, columnName) {
  const description = await describeTable(tableName);
  return !!(description && Object.prototype.hasOwnProperty.call(description, columnName));
}

async function isLegacyNodeUserSchema() {
  // "Legacy node schema" is the original backend shape where both:
  // - `users.role` exists (role stored as a string), and
  // - the children table uses camelCase columns like `parentId`.
  //
  // In the shared Laravel database, `users.role` may still exist, but the
  // children table uses snake_case (`parent_id`, `child_name`, `secret_pin`, ...),
  // so treating it as legacy would break queries (e.g. selecting `parentId`).
  if (!(await tableHasColumn('users', 'role'))) return false;

  if (await tableHasColumn('children', 'parentId')) return true;

  return false;
}

async function getRoleNameById(roleId) {
  if (!roleId || !(await tableExists('roles'))) {
    return null;
  }

  const rows = await sequelize.query(
    'SELECT name FROM roles WHERE id = :roleId LIMIT 1',
    {
      replacements: { roleId },
      type: QueryTypes.SELECT,
    }
  );

  return rows[0]?.name || null;
}

function normalizeRoleName(roleName) {
  const normalized = String(roleName || '').trim().toLowerCase();
  return normalized === 'super admin' ? 'admin' : normalized;
}

async function findUserByLogin(loginValue, options = {}) {
  const normalizedLogin = String(loginValue || '').trim();
  if (!normalizedLogin) return null;
  const includeDeleted = options.includeDeleted === true;
  const normalizedLoginLower = normalizedLogin.toLowerCase();

  if (await isLegacyNodeUserSchema()) {
    const hasDeleted = await tableHasColumn('users', 'deleted');
    const rows = await sequelize.query(
      `
        SELECT *
        FROM users
        WHERE LOWER(TRIM(email)) = :email
          ${hasDeleted && !includeDeleted ? 'AND COALESCE(deleted, 0) = 0' : ''}
      LIMIT 1
      `,
      {
        replacements: { email: normalizedLoginLower },
        type: QueryTypes.SELECT,
      }
    );

    return rows[0] || null;
  }

  if (!(await tableExists('users'))) {
    return null;
  }

  const hasUsername = await tableHasColumn('users', 'username');
  const hasDeleted = await tableHasColumn('users', 'deleted');
  const predicates = ['LOWER(TRIM(email)) = :loginLower'];
  if (hasUsername) {
    predicates.push('LOWER(TRIM(username)) = :loginLower');
  }

  const rows = await sequelize.query(
    `
      SELECT *
      FROM users
      WHERE (${predicates.join(' OR ')})
        ${hasDeleted && !includeDeleted ? 'AND COALESCE(deleted, 0) = 0' : ''}
      LIMIT 1
    `,
    {
      replacements: { loginLower: normalizedLoginLower },
      type: QueryTypes.SELECT,
    }
  );

  if (rows[0]) {
    return rows[0];
  }

  if (!(await tableExists('parents'))) {
    return null;
  }

  const parentEmailRows = await sequelize.query(
    `
      SELECT *
      FROM parents
      WHERE LOWER(TRIM(email)) = :loginLower
        ${await tableHasColumn('parents', 'deleted') && !includeDeleted ? 'AND COALESCE(deleted, 0) = 0' : ''}
      LIMIT 1
    `,
    {
      replacements: { loginLower: normalizedLoginLower },
      type: QueryTypes.SELECT,
    }
  );

  const parent = parentEmailRows[0] || null;
  if (!parent) {
    return null;
  }

  const linkedUserId = Number(parent.login_user_id ?? parent.user_id ?? 0);
  if (!Number.isInteger(linkedUserId) || linkedUserId <= 0) {
    return null;
  }

  const linkedUserRows = await sequelize.query(
    `
      SELECT *
      FROM users
      WHERE id = :userId
        ${hasDeleted && !includeDeleted ? 'AND COALESCE(deleted, 0) = 0' : ''}
      LIMIT 1
    `,
    {
      replacements: { userId: linkedUserId },
      type: QueryTypes.SELECT,
    }
  );

  return linkedUserRows[0] || null;
}

async function getUserRole(user) {
  if (!user) return null;

  if (Object.prototype.hasOwnProperty.call(user, 'role_id')) {
    const roleName = await getRoleNameById(user.role_id);
    const normalizedRoleName = normalizeRoleName(roleName);
    if (normalizedRoleName) return normalizedRoleName;
  }

  if (Object.prototype.hasOwnProperty.call(user, 'role')) {
    return normalizeRoleName(user.role);
  }

  return null;
}

async function getParentProfileForUser(userId) {
  if (!userId || !(await tableExists('parents'))) {
    return null;
  }

  const loginColumn = (await tableHasColumn('parents', 'login_user_id')) ? 'login_user_id' : 'user_id';
  if (!(await tableHasColumn('parents', loginColumn))) {
    return null;
  }

  const rows = await sequelize.query(
    `
      SELECT *
      FROM parents
      WHERE ${loginColumn} = :userId
        AND COALESCE(deleted, 0) = 0
      LIMIT 1
    `,
    {
      replacements: { userId },
      type: QueryTypes.SELECT,
    }
  );

  return rows[0] || null;
}

async function getAssignedRouteForDriver(driverId) {
  if (!driverId || !(await tableExists('routes'))) {
    return null;
  }

  // Avoid selecting columns that may not exist across deployments (e.g. `school_id`).
  const rows = await sequelize.query(
    `
      SELECT *
      FROM routes
      WHERE driver_id = :driverId
        AND COALESCE(deleted, 0) = 0
      ORDER BY id DESC
      LIMIT 1
    `,
    {
      replacements: { driverId },
      type: QueryTypes.SELECT,
    }
  );

  return rows[0] || null;
}

async function getAssignedRouteForDriverAny(driverRow) {
  if (!driverRow) return null;

  const candidates = [
    driverRow.id,
    driverRow.user_id,
    driverRow.login_user_id,
  ]
    .map((value) => (value == null ? null : Number(value)))
    .filter((value) => Number.isFinite(value) && value > 0);

  for (const candidateId of candidates) {
    const route = await getAssignedRouteForDriver(candidateId);
    if (route) return route;
  }

  return null;
}

async function getVehicleSummary(vehicleId) {
  if (!vehicleId || !(await tableExists('vehicles'))) {
    return null;
  }

  const hasVehicleTypeId = await tableHasColumn('vehicles', 'vehicle_type_id');
  const hasVehicleTypesTable = await tableExists('vehicle_types');

  const joinClause = hasVehicleTypeId && hasVehicleTypesTable
    ? 'LEFT JOIN vehicle_types vt ON vt.id = v.vehicle_type_id'
    : '';
  const vehicleTypeSelect = hasVehicleTypeId && hasVehicleTypesTable
    ? ', vt.vehicle_type AS vehicle_type_name'
    : '';

  const rows = await sequelize.query(
    `
      SELECT
        v.id,
        v.vehicle_number,
        v.seating_capacity,
        v.current_latitude,
        v.current_longitude,
        v.location_recorded_at
        ${vehicleTypeSelect}
      FROM vehicles v
      ${joinClause}
      WHERE v.id = :vehicleId
        AND COALESCE(v.deleted, 0) = 0
      LIMIT 1
    `,
    {
      replacements: { vehicleId },
      type: QueryTypes.SELECT,
    }
  );

  return rows[0] || null;
}

async function getSharedDriverLoginColumn() {
  if (!(await tableExists('drivers'))) {
    return null;
  }

  if (await tableHasColumn('drivers', 'login_user_id')) {
    return 'login_user_id';
  }

  if (await tableHasColumn('drivers', 'user_id')) {
    return 'user_id';
  }

  return null;
}

async function getSharedDriverRowByUser(userId) {
  if (!userId) return null;

  const loginColumn = await getSharedDriverLoginColumn();
  if (!loginColumn) {
    return null;
  }

  const rows = await sequelize.query(
    `
      SELECT *
      FROM drivers
      WHERE ${loginColumn} = :userId
        AND COALESCE(deleted, 0) = 0
      LIMIT 1
    `,
    {
      replacements: { userId },
      type: QueryTypes.SELECT,
    }
  );

  return rows[0] || null;
}

async function updateSharedDriverStateForUser(userId, payload = {}) {
  if (!userId || !(await tableExists('drivers'))) {
    return false;
  }

  const driver = await getSharedDriverRowByUser(userId);
  if (!driver?.id) {
    return false;
  }

  const updates = [];
  const replacements = { driverId: driver.id };
  const fieldMap = [
    ['currentLat', 'current_lat'],
    ['currentLng', 'current_lng'],
    ['vehicleNumber', 'vehicle_number'],
    ['vehicleModel', 'vehicle_model'],
    ['vehicleCapacity', 'vehicle_capacity'],
    ['lastCompletedStopIndex', 'last_completed_stop_index'],
  ];

  for (const [key, column] of fieldMap) {
    if (!Object.prototype.hasOwnProperty.call(payload, key)) continue;
    if (!(await tableHasColumn('drivers', column))) continue;
    updates.push(`${column} = :${key}`);
    replacements[key] = payload[key];
  }

  if (Object.prototype.hasOwnProperty.call(payload, 'stops') && (await tableHasColumn('drivers', 'stops_json'))) {
    updates.push('stops_json = :stops');
    replacements.stops = payload.stops == null ? null : JSON.stringify(payload.stops);
  }

  if (Object.prototype.hasOwnProperty.call(payload, 'currentRoute') && (await tableHasColumn('drivers', 'current_route_json'))) {
    updates.push('current_route_json = :currentRoute');
    replacements.currentRoute = payload.currentRoute == null ? null : JSON.stringify(payload.currentRoute);
  }

  if (!updates.length) {
    return false;
  }

  await sequelize.query(
    `
      UPDATE drivers
      SET ${updates.join(', ')}
      WHERE id = :driverId
      LIMIT 1
    `,
    {
      replacements,
      type: QueryTypes.UPDATE,
    }
  );

  return true;
}

async function updateSharedDriverProfileForUser(userId, payload = {}) {
  if (!userId || !(await tableExists('drivers'))) {
    return null;
  }

  const driver = await getSharedDriverRowByUser(userId);
  if (!driver?.id) {
    return null;
  }

  const trimOrNull = (value) => {
    const normalized = String(value ?? '').trim();
    return normalized === '' ? null : normalized;
  };

  const capacity = Number(payload.vehicleCapacity);
  const normalized = {
    fullName: trimOrNull(payload.fullName),
    licenseNumber: trimOrNull(payload.licenseNumber),
    phoneNumber: trimOrNull(payload.phoneNumber),
    vehicleNumber: trimOrNull(payload.vehicleNumber),
    vehicleCapacity: Number.isFinite(capacity) && capacity >= 0 ? Math.trunc(capacity) : null,
  };

  const transaction = await sequelize.transaction();
  try {
    const driverUpdates = [];
    const driverReplacements = { driverId: driver.id };
    const driverFieldMap = [
      ['fullName', 'driver_name'],
      ['licenseNumber', 'license_no'],
      ['phoneNumber', 'driver_phone'],
      ['vehicleNumber', 'vehicle_number'],
      ['vehicleCapacity', 'vehicle_capacity'],
    ];

    for (const [key, column] of driverFieldMap) {
      if (!Object.prototype.hasOwnProperty.call(payload, key)) continue;
      if (!(await tableHasColumn('drivers', column))) continue;
      driverUpdates.push(`${column} = :${key}`);
      driverReplacements[key] = normalized[key];
    }

    if (await tableHasColumn('drivers', 'updated_at')) {
      driverUpdates.push('updated_at = NOW()');
    }

    if (driverUpdates.length) {
      await sequelize.query(
        `
          UPDATE drivers
          SET ${driverUpdates.join(', ')}
          WHERE id = :driverId
          LIMIT 1
        `,
        {
          replacements: driverReplacements,
          type: QueryTypes.UPDATE,
          transaction,
        }
      );
    }

    if (driver.vehicle_id && (await tableExists('vehicles'))) {
      const vehicleUpdates = [];
      const vehicleReplacements = { vehicleId: driver.vehicle_id };

      if (Object.prototype.hasOwnProperty.call(payload, 'vehicleNumber') && (await tableHasColumn('vehicles', 'vehicle_number'))) {
        vehicleUpdates.push('vehicle_number = :vehicleNumber');
        vehicleReplacements.vehicleNumber = normalized.vehicleNumber;
      }

      if (Object.prototype.hasOwnProperty.call(payload, 'vehicleCapacity') && (await tableHasColumn('vehicles', 'seating_capacity'))) {
        vehicleUpdates.push('seating_capacity = :vehicleCapacity');
        vehicleReplacements.vehicleCapacity = normalized.vehicleCapacity;
      }

      if (await tableHasColumn('vehicles', 'updated_at')) {
        vehicleUpdates.push('updated_at = NOW()');
      }

      if (vehicleUpdates.length) {
        await sequelize.query(
          `
            UPDATE vehicles
            SET ${vehicleUpdates.join(', ')}
            WHERE id = :vehicleId
            LIMIT 1
          `,
          {
            replacements: vehicleReplacements,
            type: QueryTypes.UPDATE,
            transaction,
          }
        );
      }
    }

    if (await tableExists('driverdetails')) {
      const detailsUpdates = [];
      const detailsReplacements = {
        userId,
        vehicleId: driver.vehicle_id || null,
      };
      const detailsFieldMap = [
        ['fullName', 'fullName'],
        ['licenseNumber', 'licenseNumber'],
        ['phoneNumber', 'phoneNumber'],
        ['vehicleNumber', 'vehicleNumber'],
        ['vehicleCapacity', 'vehicleCapacity'],
      ];

      for (const [key, column] of detailsFieldMap) {
        if (!Object.prototype.hasOwnProperty.call(payload, key)) continue;
        if (!(await tableHasColumn('driverdetails', column))) continue;
        detailsUpdates.push(`${column} = :${key}`);
        detailsReplacements[key] = normalized[key];
      }

      if (await tableHasColumn('driverdetails', 'updated_at')) {
        detailsUpdates.push('updated_at = NOW()');
      }

      if (detailsUpdates.length) {
        const predicates = [];
        if (await tableHasColumn('driverdetails', 'userId')) {
          predicates.push('userId = :userId');
        }
        if (driver.vehicle_id && (await tableHasColumn('driverdetails', 'vehicleId'))) {
          predicates.push('vehicleId = :vehicleId');
        }

        if (predicates.length) {
          await sequelize.query(
            `
              UPDATE driverdetails
              SET ${detailsUpdates.join(', ')}
              WHERE ${predicates.join(' OR ')}
            `,
            {
              replacements: detailsReplacements,
              type: QueryTypes.UPDATE,
              transaction,
            }
          );
        }
      }
    }

    await transaction.commit();
    return getDriverProfileForUser(userId);
  } catch (error) {
    await transaction.rollback();
    throw error;
  }
}

async function getDriverProfileForUser(userId) {
  if (!userId) return null;

  if (await tableExists('drivers')) {
    const driver = await getSharedDriverRowByUser(userId);
    if (!driver) return null;

    const route = await getAssignedRouteForDriverAny(driver);
    const vehicle = await getVehicleSummary(driver.vehicle_id);
    const rawStops = safeJsonParse(driver.stops_json);
    const rawCurrentRoute = safeJsonParse(driver.current_route_json);

    return {
      id: driver.id,
      userId: driver.login_user_id ?? driver.user_id ?? userId,
      fullName: driver.driver_name || null,
      licenseNumber: driver.license_no || null,
      phoneNumber: driver.driver_phone || null,
      emergencyPhone: driver.emergency_phone || null,
      vehicleId: driver.vehicle_id || null,
      vehicleNumber: vehicle?.vehicle_number || driver.vehicle_number || null,
      vehicleModel: vehicle?.vehicle_type_name || driver.vehicle_model || null,
      vehicleCapacity: vehicle?.seating_capacity || driver.vehicle_capacity || null,
      currentLat: driver.current_lat ?? vehicle?.current_latitude ?? null,
      currentLng: driver.current_lng ?? vehicle?.current_longitude ?? null,
      locationRecordedAt: vehicle?.location_recorded_at || null,
      stops: Array.isArray(rawStops) ? rawStops : [],
      currentRoute: rawCurrentRoute && typeof rawCurrentRoute === 'object' ? rawCurrentRoute : null,
      lastCompletedStopIndex:
        driver.last_completed_stop_index == null ? -1 : Number(driver.last_completed_stop_index),
      routeId: route?.id || null,
      routeName: route?.name || null,
      schoolId: route?.school_id || null,
      raw: driver,
    };
  }

  return null;
}

function normalizeChildRow(child, parentProfileId = null) {
  const todayDate = new Date();
  const todayDateKey = [
    todayDate.getFullYear(),
    String(todayDate.getMonth() + 1).padStart(2, '0'),
    String(todayDate.getDate()).padStart(2, '0'),
  ].join('-');
  const normalizedId = child.id ?? child._id ?? null;
  const todayPickupName = child.todayPickupName ?? child.today_pickup_name ?? null;
  const todayPickupDate = child.todayPickupDate ?? child.today_pickup_date ?? null;
  const normalizedTodayPickupName = String(todayPickupName ?? '').trim() || null;
  const normalizedTodayPickupDate = String(todayPickupDate ?? '').trim() || null;
  const hasTodayPickupOverride = !!(
    normalizedTodayPickupName &&
    normalizedTodayPickupDate &&
    normalizedTodayPickupDate === todayDateKey
  );
  const defaultPickupName = child.pickupName ?? child.pickup_name ?? null;

  return {
    id: normalizedId,
    _id: normalizedId,
    parentId: child.parentId ?? child.parent_id ?? parentProfileId ?? null,
    name: child.name ?? child.child_name ?? null,
    child_name: child.child_name ?? child.name ?? null,
    schoolName: child.schoolName ?? child.school_name ?? null,
    schoolId: child.school_id ?? null,
    routeId: child.route_id ?? child.routeId ?? null,
    pickupName: defaultPickupName,
    stopName: child.stopName ?? child.stop_name ?? null,
    todayPickupName: normalizedTodayPickupName,
    todayPickupDate: normalizedTodayPickupDate,
    hasTodayPickupOverride,
    effectivePickupName: hasTodayPickupOverride ? normalizedTodayPickupName : defaultPickupName,
    secretPin: child.secretPin ?? child.secret_pin ?? null,
    className: child.className ?? child.class ?? null,
    class: child.class ?? child.className ?? null,
    homeAddress: child.homeAddress ?? child.home_address ?? null,
    schoolAddress: child.schoolAddress ?? child.school_address ?? null,
    section: child.section ?? null,
    gender: child.gender ?? null,
    dateOfBirth: child.date_of_birth ?? null,
    homeLat: child.homeLat ?? null,
    homeLng: child.homeLng ?? null,
    schoolLat: child.schoolLat ?? null,
    schoolLng: child.schoolLng ?? null,
    tripStatus: child.tripStatus ?? child.trip_status ?? null,
    subscriptionStatus: child.subscriptionStatus ?? null,
    subscriptionExpiresAt: child.subscriptionExpiresAt ?? child.subscription_expires_at ?? null,
    packageType: child.packageType ?? child.package_type ?? null,
    raw: child,
  };
}

function isCancelledTripStopForParent(stop) {
  const skippedReason = String(stop?.skippedReason || '').trim().toLowerCase();
  return stop?.skipped === true && (
    skippedReason === 'child_absent' ||
    skippedReason === 'pickup_cancelled'
  );
}

async function getCancelledChildIdsForActiveTrips(childIds = []) {
  const normalizedIds = [...new Set(
    (Array.isArray(childIds) ? childIds : [])
      .map((id) => Number(id))
      .filter((id) => Number.isInteger(id) && id > 0)
  )];

  if (!normalizedIds.length || !(await tableExists('trips')) || !(await tableHasColumn('trips', 'status'))) {
    return new Set();
  }

  const rows = await sequelize.query(
    `
      SELECT stops
      FROM trips
      WHERE status = 'running'
      ORDER BY id DESC
      LIMIT 10
    `,
    {
      type: QueryTypes.SELECT,
    }
  );

  const cancelledChildIds = new Set();

  for (const row of rows) {
    const stops = safeJsonParse(row?.stops);
    if (!Array.isArray(stops)) continue;

    for (const stop of stops) {
      const childId = Number(stop?.childId ?? stop?.child_id ?? 0);
      if (!normalizedIds.includes(childId)) continue;
      if (isCancelledTripStopForParent(stop)) {
        cancelledChildIds.add(childId);
      }
    }
  }

  return cancelledChildIds;
}

async function attachStopPickupLabelsToChildren(children) {
  if (!Array.isArray(children) || !children.length || !(await tableExists('stops_pickup'))) {
    return children;
  }

  const stopIds = [
    ...new Set(
      children
        .flatMap((child) => [child.pickupName, child.todayPickupName, child.stopName])
        .map((value) => Number(String(value ?? '').trim()))
        .filter((value) => Number.isInteger(value) && value > 0)
    ),
  ];

  if (!stopIds.length) {
    return children;
  }

  const rows = await sequelize.query(
    `
      SELECT id, route_id, pickup_name, stop_name
      FROM stops_pickup
      WHERE id IN (:stopIds)
        AND COALESCE(deleted, 0) = 0
    `,
    {
      replacements: { stopIds },
      type: QueryTypes.SELECT,
    }
  );

  const labelsById = new Map(
    rows.map((row) => [
      String(row.id),
      {
        pickupLabel: String(row.pickup_name || row.stop_name || '').trim(),
        stopLabel: String(row.stop_name || row.pickup_name || '').trim(),
        routeId: row.route_id ?? null,
      },
    ])
  );

  for (const child of children) {
    const pickupMeta = labelsById.get(String(child.pickupName ?? '').trim());
    if (pickupMeta?.pickupLabel) {
      child.pickupLabel = pickupMeta.pickupLabel;
      child.pickup_label = pickupMeta.pickupLabel;
      if (!child.routeId && pickupMeta.routeId) {
        child.routeId = pickupMeta.routeId;
      }
    }

    const todayPickupMeta = labelsById.get(String(child.todayPickupName ?? '').trim());
    if (todayPickupMeta?.pickupLabel) {
      child.todayPickupLabel = todayPickupMeta.pickupLabel;
      child.today_pickup_label = todayPickupMeta.pickupLabel;
      if (!child.routeId && todayPickupMeta.routeId) {
        child.routeId = todayPickupMeta.routeId;
      }
    }

    const stopMeta = labelsById.get(String(child.stopName ?? '').trim());
    if (stopMeta?.stopLabel) {
      child.stopLabel = stopMeta.stopLabel;
      child.stop_label = stopMeta.stopLabel;
      if (!child.routeId && stopMeta.routeId) {
        child.routeId = stopMeta.routeId;
      }
    }
  }

  return children;
}

async function getUnifiedCurrentSubscriptionsByChildIds(childIds, serviceType = 'vehicle') {
  const normalizedIds = Array.isArray(childIds)
    ? childIds.map((id) => Number(id)).filter((id) => Number.isInteger(id))
    : [];

  if (!normalizedIds.length) return new Map();
  if (!(await tableExists('child_subscriptions'))) return new Map();

  const rows = await sequelize.query(
    `
      SELECT child_id, status, package_type, expires_at
      FROM child_subscriptions
      WHERE is_current = 1
        AND service_type = :serviceType
        AND child_id IN (:childIds)
    `,
    {
      replacements: { childIds: normalizedIds, serviceType },
      type: QueryTypes.SELECT,
    }
  );

  const map = new Map();
  const now = new Date();

  for (const row of rows) {
    const expiresAt = row.expires_at ? new Date(row.expires_at) : null;
    let status = row.status || null;
    if (status === 'active' && expiresAt) {
      if (expiresAt < now) {
        status = 'expired';
      }
    }

    map.set(Number(row.child_id), {
      subscriptionStatus: status,
      subscriptionExpiresAt: expiresAt,
      packageType: row.package_type || null,
    });
  }

  return map;
}

async function getChildrenForParentUser(userId) {
  if (!userId) return [];

  if (await tableExists('children')) {
    const parentProfile = await getParentProfileForUser(userId);
    const canJoinSchools =
      (await tableExists('schools')) &&
      (await tableHasColumn('schools', 'school_name')) &&
      (await tableHasColumn('children', 'school_id'));

    if (parentProfile?.id && (await tableHasColumn('children', 'parent_id'))) {
      const rows = await sequelize.query(
        canJoinSchools
          ? `
              SELECT c.*, s.school_name AS schoolName
              FROM children c
              LEFT JOIN schools s ON s.id = c.school_id AND COALESCE(s.deleted, 0) = 0
              WHERE c.parent_id = :parentId
                AND COALESCE(c.deleted, 0) = 0
              ORDER BY c.id DESC
            `
          : `
              SELECT *
              FROM children
              WHERE parent_id = :parentId
                AND COALESCE(deleted, 0) = 0
              ORDER BY id DESC
            `,
        {
          replacements: { parentId: parentProfile.id },
          type: QueryTypes.SELECT,
        }
      );

      const normalized = rows.map((row) => normalizeChildRow(row, parentProfile.id));

      const subscriptionMap = await getUnifiedCurrentSubscriptionsByChildIds(
        normalized.map((child) => child.id),
        'vehicle'
      );

      for (const child of normalized) {
        const subscription = subscriptionMap.get(Number(child.id));
        if (subscription) {
          child.subscriptionStatus = subscription.subscriptionStatus;
          child.subscriptionExpiresAt = subscription.subscriptionExpiresAt;
          child.packageType = subscription.packageType;
        } else if (!child.subscriptionStatus) {
          child.subscriptionStatus = 'inactive';
        }
      }

      const cancelledChildIds = await getCancelledChildIdsForActiveTrips(
        normalized.map((child) => child.id)
      );

      return attachStopPickupLabelsToChildren(
        normalized.filter((child) => !cancelledChildIds.has(Number(child.id)))
      );
    }

    if (await tableHasColumn('children', 'user_id')) {
      const rows = await sequelize.query(
        canJoinSchools
          ? `
              SELECT c.*, s.school_name AS schoolName
              FROM children c
              LEFT JOIN schools s ON s.id = c.school_id AND COALESCE(s.deleted, 0) = 0
              WHERE c.user_id = :userId
                AND COALESCE(c.deleted, 0) = 0
              ORDER BY c.id DESC
            `
          : `
              SELECT *
              FROM children
              WHERE user_id = :userId
                AND COALESCE(deleted, 0) = 0
              ORDER BY id DESC
            `,
        {
          replacements: { userId },
          type: QueryTypes.SELECT,
        }
      );

      const normalized = rows.map((row) => normalizeChildRow(row, parentProfile?.id || null));

      const subscriptionMap = await getUnifiedCurrentSubscriptionsByChildIds(
        normalized.map((child) => child.id),
        'vehicle'
      );

      for (const child of normalized) {
        const subscription = subscriptionMap.get(Number(child.id));
        if (subscription) {
          child.subscriptionStatus = subscription.subscriptionStatus;
          child.subscriptionExpiresAt = subscription.subscriptionExpiresAt;
          child.packageType = subscription.packageType;
        } else if (!child.subscriptionStatus) {
          child.subscriptionStatus = 'inactive';
        }
      }

      const cancelledChildIds = await getCancelledChildIdsForActiveTrips(
        normalized.map((child) => child.id)
      );

      return attachStopPickupLabelsToChildren(
        normalized.filter((child) => !cancelledChildIds.has(Number(child.id)))
      );
    }
  }

  return [];
}

async function getChildForParentUser(childId, userId) {
  const children = await getChildrenForParentUser(userId);
  return children.find((child) => String(child.id) === String(childId)) || null;
}

async function getChildRecordById(childId) {
  if (!childId) return null;

  if (await tableExists('children')) {
    const rows = await sequelize.query(
      `
        SELECT *
        FROM children
        WHERE id = :childId
          AND COALESCE(deleted, 0) = 0
        LIMIT 1
      `,
      {
        replacements: { childId },
        type: QueryTypes.SELECT,
      }
    );

    return rows[0] ? normalizeChildRow(rows[0]) : null;
  }

  return null;
}

async function getParentUserIdForChild(childId) {
  const child = await getChildRecordById(childId);
  if (!child) return null;

  if (await tableExists('children')) {
    const parentUserColumn =
      (await tableHasColumn('parents', 'login_user_id')) ? 'login_user_id' :
      (await tableHasColumn('parents', 'user_id')) ? 'user_id' :
      null;

    if (child.parentId && parentUserColumn) {
      const rows = await sequelize.query(
        `
          SELECT ${parentUserColumn} AS parent_user_id, email
          FROM parents
          WHERE id = :parentId
            AND COALESCE(deleted, 0) = 0
          LIMIT 1
        `,
        {
          replacements: { parentId: child.parentId },
          type: QueryTypes.SELECT,
        }
      );

      const parentUserId = Number(rows[0]?.parent_user_id || 0);
      if (Number.isFinite(parentUserId) && parentUserId > 0) {
        return parentUserId;
      }

      const parentEmail = String(rows[0]?.email || '').trim().toLowerCase();
      if (parentEmail) {
        const matchedUser = await findUserByLogin(parentEmail);
        const matchedUserId = Number(matchedUser?.id || 0);
        if (Number.isFinite(matchedUserId) && matchedUserId > 0) {
          return matchedUserId;
        }
      }

      return null;
    }
  }

  return child.parentId || null;
}

async function getAssignedChildrenForDriverUser(userId) {
  const driver = await getDriverProfileForUser(userId);
  if (!driver?.routeId || !(await tableExists('children'))) {
    return [];
  }

  const rows = await sequelize.query(
    `
      SELECT *
      FROM children
      WHERE route_id = :routeId
        AND COALESCE(deleted, 0) = 0
      ORDER BY id ASC
    `,
    {
      replacements: { routeId: driver.routeId },
      type: QueryTypes.SELECT,
    }
  );

  return rows.map((row) => normalizeChildRow(row));
}

async function getRouteStopsByRouteId(routeId) {
  if (!routeId) {
    return [];
  }

  let pickupTableStops = [];
  if (await tableExists('stops_pickup')) {
    pickupTableStops = await sequelize.query(
      `
        SELECT id, route_id, pickup_name, stop_name, latitude, longitude, sequence_order
        FROM stops_pickup
        WHERE route_id = :routeId
          AND COALESCE(deleted, 0) = 0
        ORDER BY
          CASE WHEN sequence_order IS NULL THEN 1 ELSE 0 END,
          sequence_order ASC,
          id ASC
      `,
      {
        replacements: { routeId },
        type: QueryTypes.SELECT,
      }
    );
  }

  // Prefer route-stored stops (used by the Laravel route module) when present,
  // then fall back to legacy `stops_pickup` table if it exists.
  if (await tableExists('routes')) {
    const hasRouteJson = await tableHasColumn('routes', 'route_json');
    const hasLegacyStops = await tableHasColumn('routes', 'stops');
    const selectColumns = [];
    if (hasRouteJson) selectColumns.push('route_json');
    if (hasLegacyStops) selectColumns.push('stops');

    if (selectColumns.length) {
      const rows = await sequelize.query(
        `
          SELECT ${selectColumns.join(', ')}
          FROM routes
          WHERE id = :routeId
            AND COALESCE(deleted, 0) = 0
          LIMIT 1
        `,
        {
          replacements: { routeId },
          type: QueryTypes.SELECT,
        }
      );

      const routeRow = rows[0] || null;
      const routeJson = safeJsonParse(routeRow?.route_json);
      const routeJsonStops = extractRouteStopsFromRouteJson(routeJson, routeId);
      const legacyStopsValue = routeRow?.stops ?? null;
      const decodedLegacyStops = safeJsonParse(legacyStopsValue);
      const stops = routeJsonStops.length
        ? routeJsonStops
        : Array.isArray(decodedLegacyStops)
          ? decodedLegacyStops
          : [];

      const normalizedStops = stops
        .map((stop, index) => normalizeRoutePoint(stop, index, stop.type ?? 'pickup'))
        .filter((stop) => stop.latitude != null && stop.longitude != null);

      if (pickupTableStops.length) {
        const tableStopsByLabel = new Map();
        const tableStopsBySequence = new Map();

        for (const stop of pickupTableStops) {
          const label = String(stop.pickup_name || stop.stop_name || '')
            .trim()
            .toLowerCase();
          if (label) tableStopsByLabel.set(label, stop);
          if (stop.sequence_order != null) {
            tableStopsBySequence.set(Number(stop.sequence_order), stop);
          }
        }

        for (const stop of normalizedStops) {
          const stopType = String(stop.type || '').trim().toLowerCase();
          const isExplicitEndpoint = stopType === 'start' || stopType === 'end';
          const label = String(stop.pickup_name || stop.stop_name || stop.name || '')
            .trim()
            .toLowerCase();
          const tableStop =
            tableStopsByLabel.get(label) ||
            (isExplicitEndpoint ? null : tableStopsBySequence.get(Number(stop.sequence_order)));

          if (tableStop) {
            stop.id = tableStop.id;
            stop.route_id = tableStop.route_id ?? stop.route_id;
            if (!isExplicitEndpoint) {
              stop.pickup_name = tableStop.pickup_name || stop.pickup_name;
              stop.stop_name = tableStop.stop_name || stop.stop_name;
              stop.latitude = tableStop.latitude ?? stop.latitude;
              stop.longitude = tableStop.longitude ?? stop.longitude;
              stop.sequence_order = tableStop.sequence_order ?? stop.sequence_order;
            }
          }
        }
      }

      if (normalizedStops.length) {
        return normalizedStops;
      }
    }
  }

  return pickupTableStops;
}

async function getRouteGeometryPointsByRouteId(routeId) {
  if (!routeId || !(await tableExists('routes'))) {
    return [];
  }

  const hasRouteJson = await tableHasColumn('routes', 'route_json');
  const hasGeojson = await tableHasColumn('routes', 'geojson');
  const selectColumns = [];
  if (hasRouteJson) selectColumns.push('route_json');
  if (hasGeojson) selectColumns.push('geojson');

  if (!selectColumns.length) {
    return [];
  }

  const rows = await sequelize.query(
    `
      SELECT ${selectColumns.join(', ')}
      FROM routes
      WHERE id = :routeId
        AND COALESCE(deleted, 0) = 0
      LIMIT 1
    `,
    {
      replacements: { routeId },
      type: QueryTypes.SELECT,
    }
  );

  const routeRow = rows[0] || null;
  const routeJson = safeJsonParse(routeRow?.route_json);
  const geojson = routeJson?.geojson ?? safeJsonParse(routeRow?.geojson) ?? null;
  return buildPolylinePointsFromGeojson(geojson);
}

module.exports = {
  tableExists,
  tableHasColumn,
  isLegacyNodeUserSchema,
  findUserByLogin,
  getUserRole,
  getParentProfileForUser,
  getDriverProfileForUser,
  updateSharedDriverProfileForUser,
  updateSharedDriverStateForUser,
  getChildrenForParentUser,
  getChildForParentUser,
  getChildRecordById,
  getParentUserIdForChild,
  getAssignedChildrenForDriverUser,
  getRouteStopsByRouteId,
  getRouteGeometryPointsByRouteId,
};

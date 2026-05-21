/* eslint-disable no-console */
require('dotenv').config({ path: require('path').join(__dirname, '..', '.env') });

const bcrypt = require('bcryptjs');
const { QueryTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');
const { tableExists, tableHasColumn } = require('../services/schema-compat.service');

async function ensureRole(roleName) {
  const rows = await sequelize.query(
    `
      SELECT id
      FROM roles
      WHERE LOWER(name) = :roleName
        AND COALESCE(deleted, 0) = 0
      ORDER BY id ASC
      LIMIT 1
    `,
    { replacements: { roleName: roleName.toLowerCase() }, type: QueryTypes.SELECT }
  );

  if (rows[0]?.id) return Number(rows[0].id);

  const [result] = await sequelize.query(
    `
      INSERT INTO roles (name, deleted, created_at, updated_at)
      VALUES (:name, 0, NOW(), NOW())
    `,
    { replacements: { name: roleName }, type: QueryTypes.INSERT }
  );

  return Number(result);
}

async function ensureUser({ email, password, firstName, lastName, roleId }) {
  const normalizedEmail = String(email).trim().toLowerCase();
  const rows = await sequelize.query(
    `
      SELECT id
      FROM users
      WHERE LOWER(email) = :email
        AND COALESCE(deleted, 0) = 0
      LIMIT 1
    `,
    { replacements: { email: normalizedEmail }, type: QueryTypes.SELECT }
  );

  if (rows[0]?.id) {
    return Number(rows[0].id);
  }

  const hashed = await bcrypt.hash(password, 10);
  const name = `${firstName} ${lastName}`.trim();
  const [result] = await sequelize.query(
    `
      INSERT INTO users
        (role_id, first_name, last_name, mobile, photo, name, email, password, status, deleted, created_at, updated_at)
      VALUES
        (:roleId, :firstName, :lastName, NULL, NULL, :name, :email, :password, 1, 0, NOW(), NOW())
    `,
    {
      replacements: {
        roleId,
        firstName,
        lastName,
        name,
        email: normalizedEmail,
        password: hashed,
      },
      type: QueryTypes.INSERT,
    }
  );

  return Number(result);
}

async function ensureParentProfile({ userId, email }) {
  if (!(await tableExists('parents'))) return null;

  const loginColumn = (await tableHasColumn('parents', 'login_user_id')) ? 'login_user_id' : 'user_id';
  if (!(await tableHasColumn('parents', loginColumn))) return null;

  const rows = await sequelize.query(
    `
      SELECT id
      FROM parents
      WHERE ${loginColumn} = :userId
        AND COALESCE(deleted, 0) = 0
      LIMIT 1
    `,
    { replacements: { userId }, type: QueryTypes.SELECT }
  );

  if (rows[0]?.id) return Number(rows[0].id);

  const columns = [
    'father_name',
    'mother_name',
    'email',
    'contact_number',
    'alternative_contact_number',
    'address_1',
    'city',
    'state',
    'pincode',
    'status',
    'deleted',
    'created_at',
    'updated_at',
  ];
  const values = [
    ':fatherName',
    ':motherName',
    ':email',
    ':contactNumber',
    ':altContactNumber',
    ':address1',
    ':city',
    ':state',
    ':pincode',
    '1',
    '0',
    'NOW()',
    'NOW()',
  ];

  if (await tableHasColumn('parents', 'user_id')) {
    columns.unshift('user_id');
    values.unshift(':userId');
  }
  if (await tableHasColumn('parents', 'login_user_id')) {
    columns.unshift('login_user_id');
    values.unshift(':userId');
  }

  const [result] = await sequelize.query(
    `
      INSERT INTO parents (${columns.join(', ')})
      VALUES (${values.join(', ')})
    `,
    {
      replacements: {
        userId,
        fatherName: 'Boarding Test Father',
        motherName: 'Boarding Test Mother',
        email: String(email || '').trim().toLowerCase(),
        contactNumber: '9100000001',
        altContactNumber: '9100000002',
        address1: 'Satellite, Ahmedabad',
        city: 'Ahmedabad',
        state: 'Gujarat',
        pincode: '380015',
      },
      type: QueryTypes.INSERT,
    }
  );

  return Number(result);
}

async function ensureDriverProfile({ userId, routeId = null }) {
  if (!(await tableExists('drivers'))) return null;

  const loginColumn = (await tableHasColumn('drivers', 'login_user_id')) ? 'login_user_id' : 'user_id';
  if (!(await tableHasColumn('drivers', loginColumn))) return null;

  const rows = await sequelize.query(
    `
      SELECT id
      FROM drivers
      WHERE ${loginColumn} = :userId
        AND COALESCE(deleted, 0) = 0
      LIMIT 1
    `,
    { replacements: { userId }, type: QueryTypes.SELECT }
  );

  if (rows[0]?.id) {
    const driverId = Number(rows[0].id);
    if (routeId && (await tableHasColumn('drivers', 'route_id'))) {
      await sequelize.query(
        `UPDATE drivers SET route_id = :routeId, updated_at = NOW() WHERE id = :driverId LIMIT 1`,
        { replacements: { routeId, driverId }, type: QueryTypes.UPDATE }
      );
    }
    return driverId;
  }

  const columns = [
    'driver_name',
    'driver_phone',
    'emergency_phone',
    'license_no',
    'experience_years',
    'status',
    'is_assigned',
    'deleted',
    'created_at',
    'updated_at',
  ];
  const values = [
    ':driverName',
    ':driverPhone',
    ':emergencyPhone',
    ':licenseNo',
    '5',
    '1',
    '1',
    '0',
    'NOW()',
    'NOW()',
  ];

  if (await tableHasColumn('drivers', 'user_id')) {
    columns.unshift('user_id');
    values.unshift(':userId');
  }
  if (await tableHasColumn('drivers', 'login_user_id')) {
    columns.unshift('login_user_id');
    values.unshift(':userId');
  }
  if (routeId && (await tableHasColumn('drivers', 'route_id'))) {
    columns.unshift('route_id');
    values.unshift(':routeId');
  }

  const [result] = await sequelize.query(
    `
      INSERT INTO drivers (${columns.join(', ')})
      VALUES (${values.join(', ')})
    `,
    {
      replacements: {
        userId,
        routeId,
        driverName: 'Boarding Test Driver',
        driverPhone: '9200000001',
        emergencyPhone: '9200000002',
        licenseNo: `BOARD-${String(userId).padStart(6, '0')}`,
      },
      type: QueryTypes.INSERT,
    }
  );

  return Number(result);
}

async function ensureRoute({ driverId, userId }) {
  if (!(await tableExists('routes'))) return null;

  const rows = await sequelize.query(
    `
      SELECT id
      FROM routes
      WHERE name = :name
        AND COALESCE(deleted, 0) = 0
      ORDER BY id DESC
      LIMIT 1
    `,
    { replacements: { name: 'Boarding Flow Test Route' }, type: QueryTypes.SELECT }
  );

  const stops = [
    { id: 1, name: 'Stop 1', pickup_name: '1', stop_name: '1', latitude: 23.0275, longitude: 72.5065, sequence_order: 1 },
    { id: 2, name: 'Stop 2', pickup_name: '2', stop_name: '2', latitude: 23.0335, longitude: 72.5145, sequence_order: 2 },
    { id: 3, name: 'School', pickup_name: 'School', stop_name: 'School', latitude: 23.042, longitude: 72.526, sequence_order: 3 },
  ];

  if (rows[0]?.id) {
    const routeId = Number(rows[0].id);
    await sequelize.query(
      `
        UPDATE routes
        SET driver_id = :driverId,
            stops = :stops,
            updated_at = NOW()
        WHERE id = :routeId
      `,
      {
        replacements: {
          routeId,
          driverId,
          stops: JSON.stringify(stops),
        },
        type: QueryTypes.UPDATE,
      }
    );
    return { routeId, stops };
  }

  const columns = ['name', 'driver_id', 'geojson', 'stops', 'created_at', 'updated_at'];
  const values = [':name', ':driverId', 'NULL', ':stops', 'NOW()', 'NOW()'];
  if (await tableHasColumn('routes', 'user_id')) {
    columns.unshift('user_id');
    values.unshift(':userId');
  }
  if (await tableHasColumn('routes', 'school_id')) {
    columns.unshift('school_id');
    values.unshift('NULL');
  }
  if (await tableHasColumn('routes', 'bus_id')) {
    columns.splice(columns.indexOf('driver_id'), 0, 'bus_id');
    values.splice(values.indexOf(':driverId'), 0, 'NULL');
  }

  const [result] = await sequelize.query(
    `
      INSERT INTO routes (${columns.join(', ')})
      VALUES (${values.join(', ')})
    `,
    {
      replacements: {
        userId,
        name: 'Boarding Flow Test Route',
        driverId,
        stops: JSON.stringify(stops),
      },
      type: QueryTypes.INSERT,
    }
  );

  return { routeId: Number(result), stops };
}

async function ensureLegacyStopRows(routeId, stops) {
  if (!(await tableExists('stops_pickup'))) return;

  for (const stop of stops) {
    const rows = await sequelize.query(
      `
        SELECT id
        FROM stops_pickup
        WHERE route_id = :routeId
          AND sequence_order = :sequenceOrder
          AND COALESCE(deleted, 0) = 0
        LIMIT 1
      `,
      {
        replacements: { routeId, sequenceOrder: stop.sequence_order },
        type: QueryTypes.SELECT,
      }
    );

    if (rows[0]?.id) {
      await sequelize.query(
        `
          UPDATE stops_pickup
          SET pickup_name = :pickupName,
              stop_name = :stopName,
              latitude = :lat,
              longitude = :lng,
              updated_at = NOW()
          WHERE id = :id
        `,
        {
          replacements: {
            id: rows[0].id,
            pickupName: stop.pickup_name,
            stopName: stop.stop_name,
            lat: stop.latitude,
            lng: stop.longitude,
          },
          type: QueryTypes.UPDATE,
        }
      );
      continue;
    }

    const columns = ['route_id', 'pickup_name', 'stop_name', 'latitude', 'longitude', 'sequence_order', 'status', 'deleted', 'created_at', 'updated_at'];
    const values = [':routeId', ':pickupName', ':stopName', ':lat', ':lng', ':sequenceOrder', '1', '0', 'NOW()', 'NOW()'];

    await sequelize.query(
      `
        INSERT INTO stops_pickup (${columns.join(', ')})
        VALUES (${values.join(', ')})
      `,
      {
        replacements: {
          routeId,
          pickupName: stop.pickup_name,
          stopName: stop.stop_name,
          lat: stop.latitude,
          lng: stop.longitude,
          sequenceOrder: stop.sequence_order,
        },
        type: QueryTypes.INSERT,
      }
    );
  }
}

async function ensureChild({
  parentUserId,
  parentProfileId,
  routeId,
  childName,
  className,
  section,
  pin,
  pickupName,
  stopName,
  todayPickupName = null,
  todayPickupDate = null,
}) {
  const parentColumn = (await tableHasColumn('children', 'parent_id')) ? 'parent_id' : 'parentId';
  const childNameColumn = (await tableHasColumn('children', 'child_name')) ? 'child_name' : 'name';

  const rows = await sequelize.query(
    `
      SELECT id
      FROM children
      WHERE ${childNameColumn} = :childName
        AND ${parentColumn} = :parentId
        AND COALESCE(deleted, 0) = 0
      LIMIT 1
    `,
    {
      replacements: { childName, parentId: parentProfileId },
      type: QueryTypes.SELECT,
    }
  );

  const fields = [
    [childNameColumn, childName],
    [parentColumn, parentProfileId || parentUserId],
    ['school_id', null],
    ['pickup_name', pickupName],
    ['stop_name', stopName],
    ['route_id', routeId],
    ['gender', 'Female'],
    ['date_of_birth', '2015-06-01'],
    ['class', className],
    ['section', section],
    ['secret_pin', pin],
    ['today_pickup_name', todayPickupName],
    ['today_pickup_date', todayPickupDate],
    ['status', 1],
    ['deleted', 0],
  ];

  if (await tableHasColumn('children', 'user_id')) {
    fields.push(['user_id', parentUserId]);
  }

  const supported = [];
  for (const [column, value] of fields) {
    if (!(await tableHasColumn('children', column))) continue;
    supported.push([column, value]);
  }

  if (rows[0]?.id) {
    const sets = supported
      .filter(([column]) => column !== 'parent_id')
      .map(([column]) => `${column} = :${column}`);
    const replacements = supported.reduce((acc, [column, value]) => {
      acc[column] = value;
      return acc;
    }, { childId: rows[0].id });

    let sql = `
      UPDATE children
      SET ${sets.join(', ')}
    `;
    if (await tableHasColumn('children', 'updated_at')) {
      sql += ', updated_at = NOW()';
    }
    sql += ' WHERE id = :childId LIMIT 1';

    await sequelize.query(sql, {
      replacements,
      type: QueryTypes.UPDATE,
    });

    return Number(rows[0].id);
  }

  const columns = supported.map(([column]) => column);
  const placeholders = supported.map(([column]) => `:${column}`);
  const replacements = supported.reduce((acc, [column, value]) => {
    acc[column] = value;
    return acc;
  }, {});

  if (await tableHasColumn('children', 'created_at')) {
    columns.push('created_at');
    placeholders.push('NOW()');
  }
  if (await tableHasColumn('children', 'updated_at')) {
    columns.push('updated_at');
    placeholders.push('NOW()');
  }

  const [result] = await sequelize.query(
    `
      INSERT INTO children (${columns.join(', ')})
      VALUES (${placeholders.join(', ')})
    `,
    {
      replacements,
      type: QueryTypes.INSERT,
    }
  );

  return Number(result);
}

async function ensureActiveSubscription(childId) {
  if (!(await tableExists('child_subscriptions'))) return;

  await sequelize.query(
    `
      UPDATE child_subscriptions
      SET is_current = NULL
      WHERE child_id = :childId
        AND service_type = 'vehicle'
        AND is_current = 1
    `,
    { replacements: { childId }, type: QueryTypes.UPDATE }
  );

  const columns = ['child_id', 'service_type', 'package_type', 'status', 'source', 'is_current', 'starts_at', 'expires_at', 'notes'];
  const values = [':childId', "'vehicle'", "'1month'", "'active'", "'seed'", '1', 'NOW()', 'DATE_ADD(NOW(), INTERVAL 1 MONTH)', "'Boarding flow seed'"];
  if (await tableHasColumn('child_subscriptions', 'created_at')) {
    columns.push('created_at');
    values.push('NOW()');
  }
  if (await tableHasColumn('child_subscriptions', 'updated_at')) {
    columns.push('updated_at');
    values.push('NOW()');
  } else if (await tableHasColumn('child_subscriptions', 'updatedAt')) {
    columns.push('updatedAt');
    values.push('NOW()');
  }
  if (await tableHasColumn('child_subscriptions', 'createdAt')) {
    columns.push('createdAt');
    values.push('NOW()');
  }

  await sequelize.query(
    `
      INSERT INTO child_subscriptions (${columns.join(', ')})
      VALUES (${values.join(', ')})
    `,
    {
      replacements: { childId },
      type: QueryTypes.INSERT,
    }
  );
}

async function seedBoardingFlow() {
  const parentRoleId = await ensureRole('parent');
  const driverRoleId = await ensureRole('driver');

  const parentUserId = await ensureUser({
    email: 'boarding.parent@test.com',
    password: '123456',
    firstName: 'Boarding',
    lastName: 'Parent',
    roleId: parentRoleId,
  });

  const driverUserId = await ensureUser({
    email: 'boarding.driver@test.com',
    password: '123456',
    firstName: 'Boarding',
    lastName: 'Driver',
    roleId: driverRoleId,
  });

  const parentProfileId = await ensureParentProfile({
    userId: parentUserId,
    email: 'boarding.parent@test.com',
  });

  const tempDriverId = await ensureDriverProfile({ userId: driverUserId });
  const { routeId, stops } = await ensureRoute({ driverId: tempDriverId, userId: driverUserId });
  const driverId = await ensureDriverProfile({ userId: driverUserId, routeId });

  await sequelize.query(
    `
      UPDATE routes
      SET driver_id = :driverId, updated_at = NOW()
      WHERE id = :routeId
    `,
    { replacements: { driverId, routeId }, type: QueryTypes.UPDATE }
  );

  await ensureLegacyStopRows(routeId, stops);

  const todayDateKey = new Date().toISOString().slice(0, 10);
  const children = [
    { childName: 'Aarav Test', className: '5', section: 'A', pin: '1111', pickupName: '1', stopName: 'School' },
    { childName: 'Diya Test', className: '5', section: 'A', pin: '2222', pickupName: '1', stopName: 'School' },
    { childName: 'Krish Test', className: '5', section: 'A', pin: '3333', pickupName: '1', stopName: 'School' },
    { childName: 'Anaya Test', className: '5', section: 'A', pin: '4444', pickupName: '1', stopName: 'School' },
    { childName: 'Meera Test', className: '5', section: 'A', pin: '5555', pickupName: '1', stopName: 'School', todayPickupName: '2', todayPickupDate: todayDateKey },
  ];

  for (const child of children) {
    const childId = await ensureChild({
      parentUserId,
      parentProfileId: parentProfileId || parentUserId,
      routeId,
      ...child,
    });
    await ensureActiveSubscription(childId);
  }

  console.log('Boarding flow seed ready.');
  console.log('Parent login : boarding.parent@test.com / 123456');
  console.log('Driver login : boarding.driver@test.com / 123456');
  console.log('Route        : Boarding Flow Test Route');
  console.log('Stops        : Stop 1, Stop 2, School');
  console.log('Children     : Aarav, Diya, Krish, Anaya on Stop 1; Meera overridden to Stop 2 for today.');
  console.log(`Today date   : ${todayDateKey}`);
}

seedBoardingFlow()
  .then(async () => {
    await sequelize.close();
    process.exit(0);
  })
  .catch(async (error) => {
    console.error('Boarding flow seed failed:', error);
    try {
      await sequelize.close();
    } catch (_) {}
    process.exit(1);
  });

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
  const existing = await sequelize.query(
    `
      SELECT id, email
      FROM users
      WHERE LOWER(email) = :email
        AND COALESCE(deleted, 0) = 0
      LIMIT 1
    `,
    { replacements: { email: normalizedEmail }, type: QueryTypes.SELECT }
  );

  if (existing[0]?.id) {
    const id = Number(existing[0].id);
    await sequelize.query(
      `
        UPDATE users
        SET role_id = :roleId
        WHERE id = :id
      `,
      { replacements: { id, roleId }, type: QueryTypes.UPDATE }
    );
    return id;
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

  const existing = await sequelize.query(
    `
      SELECT id
      FROM parents
      WHERE ${loginColumn} = :userId
        AND COALESCE(deleted, 0) = 0
      LIMIT 1
    `,
    { replacements: { userId }, type: QueryTypes.SELECT }
  );

  if (existing[0]?.id) return Number(existing[0].id);

  const hasUserId = await tableHasColumn('parents', 'user_id');
  const hasLoginUserId = await tableHasColumn('parents', 'login_user_id');

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

  if (hasUserId) {
    columns.unshift('user_id');
    values.unshift(':userId');
  }
  if (hasLoginUserId) {
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
        fatherName: 'Demo Father',
        motherName: 'Demo Mother',
        email: String(email || '').trim().toLowerCase(),
        contactNumber: '9999999999',
        altContactNumber: '8888888888',
        address1: 'Demo Address 1',
        city: 'Ahmedabad',
        state: 'Gujarat',
        pincode: '380000',
      },
      type: QueryTypes.INSERT,
    }
  );

  return Number(result);
}

async function ensureDriverProfile({ userId }) {
  if (!(await tableExists('drivers'))) return null;

  const loginColumn = (await tableHasColumn('drivers', 'login_user_id')) ? 'login_user_id' : 'user_id';
  if (!(await tableHasColumn('drivers', loginColumn))) return null;

  const existing = await sequelize.query(
    `
      SELECT id
      FROM drivers
      WHERE ${loginColumn} = :userId
        AND COALESCE(deleted, 0) = 0
      LIMIT 1
    `,
    { replacements: { userId }, type: QueryTypes.SELECT }
  );

  if (existing[0]?.id) return Number(existing[0].id);

  const hasUserId = await tableHasColumn('drivers', 'user_id');
  const hasLoginUserId = await tableHasColumn('drivers', 'login_user_id');
  const hasVehicleId = await tableHasColumn('drivers', 'vehicle_id');

  const licenseNo = `DEMO-LIC-${String(userId).padStart(6, '0')}`;

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
    '3',
    '1',
    '1',
    '0',
    'NOW()',
    'NOW()',
  ];

  if (hasVehicleId) {
    columns.unshift('vehicle_id');
    values.unshift('NULL');
  }
  if (hasUserId) {
    columns.unshift('user_id');
    values.unshift(':userId');
  }
  if (hasLoginUserId) {
    columns.unshift('login_user_id');
    values.unshift(':userId');
  }

  const [result] = await sequelize.query(
    `
      INSERT INTO drivers (${columns.join(', ')})
      VALUES (${values.join(', ')})
    `,
    {
      replacements: {
        userId,
        driverName: 'Demo Driver',
        driverPhone: '7777777777',
        emergencyPhone: '6666666666',
        licenseNo,
      },
      type: QueryTypes.INSERT,
    }
  );

  return Number(result);
}

async function ensureRoute({ driverId, createdByUserId }) {
  if (!(await tableExists('routes'))) return null;

  const existing = await sequelize.query(
    `
      SELECT id
      FROM routes
      WHERE driver_id = :driverId
        AND COALESCE(deleted, 0) = 0
      ORDER BY id DESC
      LIMIT 1
    `,
    { replacements: { driverId }, type: QueryTypes.SELECT }
  );

  if (existing[0]?.id) return Number(existing[0].id);

  const hasUserId = await tableHasColumn('routes', 'user_id');

  const columns = ['school_id', 'name', 'bus_id', 'driver_id', 'geojson', 'stops', 'created_at', 'updated_at'];
  const values = ['NULL', ':name', 'NULL', ':driverId', 'NULL', 'NULL', 'NOW()', 'NOW()'];

  if (hasUserId) {
    columns.unshift('user_id');
    values.unshift(':createdByUserId');
  }

  const [result] = await sequelize.query(
    `
      INSERT INTO routes (${columns.join(', ')})
      VALUES (${values.join(', ')})
    `,
    {
      replacements: {
        createdByUserId: createdByUserId || null,
        driverId,
        name: 'Demo Route (Iscon → Jodhpur)',
      },
      type: QueryTypes.INSERT,
    }
  );

  return Number(result);
}

async function ensureStop({ routeId, sequenceOrder, name, lat, lng, createdByUserId }) {
  if (!(await tableExists('stops_pickup'))) return null;

  const existing = await sequelize.query(
    `
      SELECT id
      FROM stops_pickup
      WHERE route_id = :routeId
        AND sequence_order = :sequenceOrder
        AND COALESCE(deleted, 0) = 0
      ORDER BY id ASC
      LIMIT 1
    `,
    { replacements: { routeId, sequenceOrder }, type: QueryTypes.SELECT }
  );

  if (existing[0]?.id) return Number(existing[0].id);

  const hasUserId = await tableHasColumn('stops_pickup', 'user_id');

  const columns = ['route_id', 'pickup_name', 'stop_name', 'latitude', 'longitude', 'sequence_order', 'status', 'deleted', 'created_at', 'updated_at'];
  const values = [':routeId', ':pickupName', ':stopName', ':lat', ':lng', ':sequenceOrder', '1', '0', 'NOW()', 'NOW()'];

  if (hasUserId) {
    columns.unshift('user_id');
    values.unshift(':createdByUserId');
  }

  const [result] = await sequelize.query(
    `
      INSERT INTO stops_pickup (${columns.join(', ')})
      VALUES (${values.join(', ')})
    `,
    {
      replacements: {
        createdByUserId: createdByUserId || null,
        routeId,
        pickupName: name,
        stopName: name,
        lat,
        lng,
        sequenceOrder,
      },
      type: QueryTypes.INSERT,
    }
  );

  return Number(result);
}

async function ensureChild({ parentProfileId, parentUserId, routeId, pickupStopId, dropStopId }) {
  if (!(await tableExists('children'))) return null;

  const existing = await sequelize.query(
    `
      SELECT id
      FROM children
      WHERE parent_id = :parentId
        AND COALESCE(deleted, 0) = 0
      ORDER BY id DESC
      LIMIT 1
    `,
    { replacements: { parentId: parentProfileId }, type: QueryTypes.SELECT }
  );

  if (existing[0]?.id) return Number(existing[0].id);

  const hasSecretPin = await tableHasColumn('children', 'secret_pin');
  const hasUserId = await tableHasColumn('children', 'user_id');

  const columns = [
    'child_name',
    'parent_id',
    'school_id',
    'pickup_name',
    'stop_name',
    'route_id',
    'gender',
    'date_of_birth',
    'class',
    'section',
    'status',
    'deleted',
    'created_at',
    'updated_at',
  ];
  const values = [
    ':childName',
    ':parentProfileId',
    'NULL',
    ':pickupStopId',
    ':dropStopId',
    ':routeId',
    ':gender',
    ':dob',
    ':className',
    ':section',
    '1',
    '0',
    'NOW()',
    'NOW()',
  ];

  if (hasUserId) {
    columns.unshift('user_id');
    values.unshift(':parentUserId');
  }
  if (hasSecretPin) {
    const routeIndex = columns.indexOf('route_id');
    columns.splice(routeIndex + 1, 0, 'secret_pin');
    values.splice(routeIndex + 1, 0, ':secretPin');
  }

  const [result] = await sequelize.query(
    `
      INSERT INTO children (${columns.join(', ')})
      VALUES (${values.join(', ')})
    `,
    {
      replacements: {
        parentUserId: parentUserId || null,
        childName: 'Meet Demo',
        parentProfileId,
        pickupStopId,
        dropStopId,
        routeId,
        gender: 'Male',
        dob: '2015-01-01',
        className: '5',
        section: 'A',
        secretPin: '1234',
      },
      type: QueryTypes.INSERT,
    }
  );

  return Number(result);
}

async function ensureActiveSubscription({ childId, createdByUserId }) {
  if (!(await tableExists('child_subscriptions'))) return null;

  const now = new Date();
  const expires = new Date(now.getTime());
  expires.setMonth(expires.getMonth() + 1);

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

  const hasCreatedAtCamel = await tableHasColumn('child_subscriptions', 'createdAt');
  const hasUpdatedAtSnake = await tableHasColumn('child_subscriptions', 'updated_at');
  const hasUpdatedAtCamel = await tableHasColumn('child_subscriptions', 'updatedAt');

  const columns = [
    'child_id',
    'service_type',
    'package_type',
    'status',
    'source',
    'is_current',
    'starts_at',
    'expires_at',
    'created_by_user_id',
    'notes',
  ];
  const values = [
    ':childId',
    "'vehicle'",
    "'1month'",
    "'active'",
    "'admin_cash'",
    '1',
    'NOW()',
    'DATE_ADD(NOW(), INTERVAL 1 MONTH)',
    ':createdByUserId',
    "'Demo cash subscription'",
  ];

  if (hasCreatedAtCamel) {
    columns.push('createdAt');
    values.push('NOW()');
  } else if (await tableHasColumn('child_subscriptions', 'created_at')) {
    columns.push('created_at');
    values.push('NOW()');
  }

  if (hasUpdatedAtSnake) {
    columns.push('updated_at');
    values.push('NOW()');
  } else if (hasUpdatedAtCamel) {
    columns.push('updatedAt');
    values.push('NOW()');
  }

  const [subId] = await sequelize.query(
    `
      INSERT INTO child_subscriptions (${columns.join(', ')})
      VALUES (${values.join(', ')})
    `,
    { replacements: { childId, createdByUserId: createdByUserId || null }, type: QueryTypes.INSERT }
  );

  if (await tableExists('subscription_payments')) {
    const paymentColumns = [
      'child_subscription_id',
      'channel',
      'status',
      'amount',
      'currency',
      'receipt_no',
      'paid_at',
      'meta',
    ];
    const paymentValues = [
      ':childSubscriptionId',
      "'cash'",
      "'paid'",
      '0.00',
      "'INR'",
      "'DEMO-REC-001'",
      'NOW()',
      'JSON_OBJECT("source","demo-seed")',
    ];

    if (await tableHasColumn('subscription_payments', 'createdAt')) {
      paymentColumns.push('createdAt');
      paymentValues.push('NOW()');
    } else if (await tableHasColumn('subscription_payments', 'created_at')) {
      paymentColumns.push('created_at');
      paymentValues.push('NOW()');
    }

    if (await tableHasColumn('subscription_payments', 'updated_at')) {
      paymentColumns.push('updated_at');
      paymentValues.push('NOW()');
    } else if (await tableHasColumn('subscription_payments', 'updatedAt')) {
      paymentColumns.push('updatedAt');
      paymentValues.push('NOW()');
    }

    await sequelize.query(
      `
        INSERT INTO subscription_payments (${paymentColumns.join(', ')})
        VALUES (${paymentValues.join(', ')})
      `,
      { replacements: { childSubscriptionId: Number(subId) }, type: QueryTypes.INSERT }
    );
  }

  return Number(subId);
}

async function main() {
  if (!(await tableExists('users')) || !(await tableExists('roles'))) {
    throw new Error('Shared Laravel tables not found (users/roles). Confirm DB points to the Laravel database.');
  }

  const parentRoleId = await ensureRole('parent');
  const driverRoleId = await ensureRole('driver');

  const parentEmail = 'rakholiyameet9@gmail.com';
  const driverEmail = 'meet@cherrypiksoftware.com';
  const password = 'Test@1234';

  const parentUserId = await ensureUser({
    email: parentEmail,
    password,
    firstName: 'Demo',
    lastName: 'Parent',
    roleId: parentRoleId,
  });
  const driverUserId = await ensureUser({
    email: driverEmail,
    password,
    firstName: 'Demo',
    lastName: 'Driver',
    roleId: driverRoleId,
  });

  const parentProfileId = await ensureParentProfile({ userId: parentUserId, email: parentEmail });
  const driverProfileId = await ensureDriverProfile({ userId: driverUserId });

  if (!parentProfileId) {
    throw new Error('Failed to create parents profile (missing parents table or columns).');
  }
  if (!driverProfileId) {
    throw new Error('Failed to create drivers profile (missing drivers table or columns).');
  }

  const routeId = await ensureRoute({ driverId: driverProfileId, createdByUserId: driverUserId });
  if (!routeId) {
    throw new Error('Failed to create route.');
  }

  const stopIsconId = await ensureStop({
    routeId,
    sequenceOrder: 1,
    name: 'Iscon Cross Road',
    lat: 23.0298,
    lng: 72.5053,
    createdByUserId: driverUserId,
  });
  const stopJodhpurId = await ensureStop({
    routeId,
    sequenceOrder: 2,
    name: 'Jodhpur',
    lat: 23.0154,
    lng: 72.5101,
    createdByUserId: driverUserId,
  });

  const childId = await ensureChild({
    parentProfileId,
    parentUserId,
    routeId,
    pickupStopId: stopIsconId,
    dropStopId: stopJodhpurId,
  });

  await ensureActiveSubscription({ childId, createdByUserId: driverUserId });

  console.log('Demo accounts ready:');
  console.log(`- Parent: ${parentEmail} / ${password}`);
  console.log(`- Driver: ${driverEmail} / ${password}`);
  console.log(`- Child: Meet Demo (PIN 1234), routeId=${routeId}, childId=${childId}`);
}

main()
  .then(() => sequelize.close())
  .catch(async (err) => {
    console.error(err);
    try {
      await sequelize.close();
    } catch (_) {}
    process.exitCode = 1;
  });


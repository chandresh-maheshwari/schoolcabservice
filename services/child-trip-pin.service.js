const crypto = require('crypto');
const { QueryTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');
const {
  tableExists,
  tableHasColumn,
} = require('./schema-compat.service');

const PIN_TTL_MS = 24 * 60 * 60 * 1000;

async function ensureChildTripPinsTable() {
  await sequelize.query(`
    CREATE TABLE IF NOT EXISTS child_trip_pins (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      child_id BIGINT UNSIGNED NOT NULL,
      trip_id BIGINT UNSIGNED NULL,
      route_id BIGINT UNSIGNED NULL,
      driver_user_id BIGINT UNSIGNED NULL,
      trip_type VARCHAR(32) NULL,
      pin VARCHAR(4) NOT NULL,
      expires_at DATETIME NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      INDEX child_trip_pins_child_id_idx (child_id),
      INDEX child_trip_pins_trip_id_idx (trip_id),
      INDEX child_trip_pins_expires_at_idx (expires_at)
    )
  `);
}

async function getChildSecretPinColumn() {
  if (!(await tableExists('children'))) return null;
  if (await tableHasColumn('children', 'secret_pin')) return 'secret_pin';
  if (await tableHasColumn('children', 'secretPin')) return 'secretPin';
  return null;
}

function normalizeChildId(value) {
  const id = Number(value);
  return Number.isInteger(id) && id > 0 ? id : null;
}

function generatePin(usedPins) {
  let pin;
  do {
    pin = String(crypto.randomInt(0, 10000)).padStart(4, '0');
  } while (usedPins.has(pin) && usedPins.size < 10000);
  usedPins.add(pin);
  return pin;
}

async function syncChildSecretPin(childId, pin) {
  const column = await getChildSecretPinColumn();
  if (!column) return;

  await sequelize.query(
    `
      UPDATE children
      SET \`${column}\` = :pin
      WHERE id = :childId
      LIMIT 1
    `,
    {
      replacements: { childId, pin },
      type: QueryTypes.UPDATE,
    }
  );
}

async function clearChildSecretPins(pinRows = []) {
  const column = await getChildSecretPinColumn();
  if (!column || !pinRows.length) return;

  for (const row of pinRows) {
    const childId = normalizeChildId(row.child_id ?? row.childId);
    const pin = row.pin == null ? '' : String(row.pin);
    if (!childId || !pin) continue;

    await sequelize.query(
      `
        UPDATE children
        SET \`${column}\` = NULL
        WHERE id = :childId
          AND \`${column}\` = :pin
        LIMIT 1
      `,
      {
        replacements: { childId, pin },
        type: QueryTypes.UPDATE,
      }
    );
  }
}

async function cleanupExpiredTripPins() {
  await ensureChildTripPinsTable();

  const expiredRows = await sequelize.query(
    `
      SELECT child_id, pin
      FROM child_trip_pins
      WHERE expires_at <= NOW()
    `,
    { type: QueryTypes.SELECT }
  );

  if (!expiredRows.length) return 0;

  await sequelize.query(
    `
      DELETE FROM child_trip_pins
      WHERE expires_at <= NOW()
    `,
    { type: QueryTypes.DELETE }
  );

  await clearChildSecretPins(expiredRows);
  return expiredRows.length;
}

async function deleteExistingPinsForChildren(childIds) {
  if (!childIds.length) return;

  const rows = await sequelize.query(
    `
      SELECT child_id, pin
      FROM child_trip_pins
      WHERE child_id IN (:childIds)
    `,
    {
      replacements: { childIds },
      type: QueryTypes.SELECT,
    }
  );

  await sequelize.query(
    `
      DELETE FROM child_trip_pins
      WHERE child_id IN (:childIds)
    `,
    {
      replacements: { childIds },
      type: QueryTypes.DELETE,
    }
  );

  await clearChildSecretPins(rows);
}

async function generateTripPinsForChildren({
  children = [],
  tripId = null,
  routeId = null,
  driverUserId = null,
  tripType = 'morning',
} = {}) {
  await ensureChildTripPinsTable();
  await cleanupExpiredTripPins();

  const childIds = [
    ...new Set(
      children
        .map((child) => normalizeChildId(child?.id ?? child?._id))
        .filter(Boolean)
    ),
  ];

  if (!childIds.length) return [];

  await deleteExistingPinsForChildren(childIds);

  const expiresAt = new Date(Date.now() + PIN_TTL_MS);
  const usedPins = new Set();
  const createdPins = [];

  for (const childId of childIds) {
    const pin = generatePin(usedPins);
    await sequelize.query(
      `
        INSERT INTO child_trip_pins
          (child_id, trip_id, route_id, driver_user_id, trip_type, pin, expires_at, created_at, updated_at)
        VALUES
          (:childId, :tripId, :routeId, :driverUserId, :tripType, :pin, :expiresAt, NOW(), NOW())
      `,
      {
        replacements: {
          childId,
          tripId,
          routeId,
          driverUserId,
          tripType,
          pin,
          expiresAt,
        },
        type: QueryTypes.INSERT,
      }
    );

    await syncChildSecretPin(childId, pin);
    createdPins.push({ childId, pin, expiresAt });
  }

  return createdPins;
}

async function getActiveTripPinForChild(childId, tripId = null) {
  await ensureChildTripPinsTable();
  await cleanupExpiredTripPins();

  const normalizedChildId = normalizeChildId(childId);
  if (!normalizedChildId) return null;

  const rows = await sequelize.query(
    `
      SELECT *
      FROM child_trip_pins
      WHERE child_id = :childId
        AND expires_at > NOW()
        ${tripId ? 'AND trip_id = :tripId' : ''}
      ORDER BY id DESC
      LIMIT 1
    `,
    {
      replacements: { childId: normalizedChildId, tripId },
      type: QueryTypes.SELECT,
    }
  );

  return rows[0] || null;
}

module.exports = {
  cleanupExpiredTripPins,
  generateTripPinsForChildren,
  getActiveTripPinForChild,
};

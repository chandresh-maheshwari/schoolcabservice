const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

let tripsEnsured = false;
let tripVehicleSegmentsEnsured = false;

async function ensureTripsTable() {
  if (tripsEnsured) {
    return;
  }

  const queryInterface = sequelize.getQueryInterface();

  try {
    const description = await queryInterface.describeTable('trips');

    if (!Object.prototype.hasOwnProperty.call(description, 'routeId')) {
      await queryInterface.addColumn('trips', 'routeId', {
        type: DataTypes.BIGINT.UNSIGNED,
        allowNull: true,
      });
    }

    if (!Object.prototype.hasOwnProperty.call(description, 'driverUserId')) {
      await queryInterface.addColumn('trips', 'driverUserId', {
        type: DataTypes.BIGINT.UNSIGNED,
        allowNull: true,
      });
    }

    tripsEnsured = true;
    return;
  } catch (_) {
    // The runtime table does not exist yet in the shared DB.
  }

  await queryInterface.createTable('trips', {
    id: {
      type: DataTypes.INTEGER,
      primaryKey: true,
      autoIncrement: true,
      allowNull: false,
    },
    driverLat: {
      type: DataTypes.DOUBLE,
      allowNull: true,
    },
    driverLng: {
      type: DataTypes.DOUBLE,
      allowNull: true,
    },
    routeId: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: true,
    },
    driverUserId: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: true,
    },
    stops: {
      type: DataTypes.JSON,
      allowNull: true,
    },
    nextStop: {
      type: DataTypes.JSON,
      allowNull: true,
    },
    currentRoute: {
      type: DataTypes.JSON,
      allowNull: true,
    },
    tripType: {
      type: DataTypes.STRING,
      allowNull: true,
    },
    direction: {
      type: DataTypes.STRING,
      allowNull: true,
    },
    lastCompletedStopIndex: {
      type: DataTypes.INTEGER,
      allowNull: false,
      defaultValue: -1,
    },
    status: {
      type: DataTypes.STRING,
      allowNull: false,
      defaultValue: 'idle',
    },
    createdAt: {
      type: DataTypes.DATE,
      allowNull: false,
      defaultValue: sequelize.literal('CURRENT_TIMESTAMP'),
    },
    updated_at: {
      type: DataTypes.DATE,
      allowNull: false,
      defaultValue: sequelize.literal('CURRENT_TIMESTAMP'),
    },
  });

  tripsEnsured = true;
}

async function ensureTripVehicleSegmentsTable() {
  if (tripVehicleSegmentsEnsured) {
    return;
  }

  const queryInterface = sequelize.getQueryInterface();

  try {
    const description = await queryInterface.describeTable('trip_vehicle_segments');

    if (!Object.prototype.hasOwnProperty.call(description, 'emergency_incident_id')) {
      await queryInterface.addColumn('trip_vehicle_segments', 'emergency_incident_id', {
        type: DataTypes.BIGINT.UNSIGNED,
        allowNull: true,
      });
    }

    tripVehicleSegmentsEnsured = true;
    return;
  } catch (_) {
    // Table does not exist yet in the shared DB.
  }

  await queryInterface.createTable('trip_vehicle_segments', {
    id: {
      type: DataTypes.INTEGER,
      primaryKey: true,
      autoIncrement: true,
      allowNull: false,
    },
    trip_id: {
      type: DataTypes.INTEGER,
      allowNull: false,
    },
    route_id: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: true,
    },
    driver_user_id: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: true,
    },
    driver_id: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: true,
    },
    vehicle_id: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: true,
    },
    parent_segment_id: {
      type: DataTypes.INTEGER,
      allowNull: true,
    },
    segment_order: {
      type: DataTypes.INTEGER,
      allowNull: false,
      defaultValue: 1,
    },
    handover_type: {
      type: DataTypes.STRING,
      allowNull: false,
      defaultValue: 'initial',
    },
    handover_reason: {
      type: DataTypes.STRING,
      allowNull: true,
    },
    emergency_incident_id: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: true,
    },
    status: {
      type: DataTypes.STRING,
      allowNull: false,
      defaultValue: 'active',
    },
    start_lat: {
      type: DataTypes.DOUBLE,
      allowNull: true,
    },
    start_lng: {
      type: DataTypes.DOUBLE,
      allowNull: true,
    },
    end_lat: {
      type: DataTypes.DOUBLE,
      allowNull: true,
    },
    end_lng: {
      type: DataTypes.DOUBLE,
      allowNull: true,
    },
    started_at: {
      type: DataTypes.DATE,
      allowNull: false,
      defaultValue: sequelize.literal('CURRENT_TIMESTAMP'),
    },
    ended_at: {
      type: DataTypes.DATE,
      allowNull: true,
    },
    created_at: {
      type: DataTypes.DATE,
      allowNull: false,
      defaultValue: sequelize.literal('CURRENT_TIMESTAMP'),
    },
    updated_at: {
      type: DataTypes.DATE,
      allowNull: false,
      defaultValue: sequelize.literal('CURRENT_TIMESTAMP'),
    },
  });

  tripVehicleSegmentsEnsured = true;
}

module.exports = {
  ensureTripsTable,
  ensureTripVehicleSegmentsTable,
};

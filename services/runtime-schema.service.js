const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

let tripsEnsured = false;

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
    updatedAt: {
      type: DataTypes.DATE,
      allowNull: false,
      defaultValue: sequelize.literal('CURRENT_TIMESTAMP'),
    },
  });

  tripsEnsured = true;
}

module.exports = {
  ensureTripsTable,
};

const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

let featureTablesEnsured = false;

async function ensureDriverFeatureTables() {
  if (featureTablesEnsured) {
    return;
  }

  const queryInterface = sequelize.getQueryInterface();

  try {
    await queryInterface.describeTable('driver_checklists');
  } catch (_) {
    await queryInterface.createTable('driver_checklists', {
      id: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
        allowNull: false,
      },
      driverUserId: {
        type: DataTypes.BIGINT.UNSIGNED,
        allowNull: false,
      },
      logDate: {
        type: DataTypes.STRING(10),
        allowNull: false,
      },
      items: {
        type: DataTypes.JSON,
        allowNull: false,
      },
      completed: {
        type: DataTypes.BOOLEAN,
        allowNull: false,
        defaultValue: false,
      },
      completedAt: {
        type: DataTypes.DATE,
        allowNull: true,
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
  }

  try {
    await queryInterface.describeTable('driver_emergencies');
  } catch (_) {
    await queryInterface.createTable('driver_emergencies', {
      id: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
        allowNull: false,
      },
      driverUserId: {
        type: DataTypes.BIGINT.UNSIGNED,
        allowNull: false,
      },
      emergencyType: {
        type: DataTypes.STRING,
        allowNull: false,
      },
      description: {
        type: DataTypes.TEXT,
        allowNull: true,
      },
      contactNumber: {
        type: DataTypes.STRING,
        allowNull: true,
      },
      status: {
        type: DataTypes.STRING,
        allowNull: false,
        defaultValue: 'reported',
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
  }

  featureTablesEnsured = true;
}

module.exports = {
  ensureDriverFeatureTables,
};

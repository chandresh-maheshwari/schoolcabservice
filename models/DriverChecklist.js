const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

const DriverChecklist = sequelize.define('DriverChecklist', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true,
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
    defaultValue: [],
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
}, {
  tableName: 'driver_checklists',
  timestamps: true,
  updatedAt: 'updated_at',
});

module.exports = DriverChecklist;

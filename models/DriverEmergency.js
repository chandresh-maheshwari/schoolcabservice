const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

const DriverEmergency = sequelize.define('DriverEmergency', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true,
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
}, {
  tableName: 'driver_emergencies',
  timestamps: true,
});

module.exports = DriverEmergency;

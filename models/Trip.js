const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

const Trip = sequelize.define('Trip', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  driverLat: {
    type: DataTypes.DOUBLE
  },
  driverLng: {
    type: DataTypes.DOUBLE
  },
  stops: {
    type: DataTypes.JSON,
    defaultValue: []
  },
  nextStop: {
    type: DataTypes.JSON,
    defaultValue: null
  },
  currentRoute: {
    type: DataTypes.JSON,
    defaultValue: null
  },
  tripType: {
    type: DataTypes.STRING // morning | afternoon
  },
  direction: {
    type: DataTypes.STRING // FORWARD | REVERSE
  },
  lastCompletedStopIndex: {
    type: DataTypes.INTEGER,
    defaultValue: -1
  },
  status: {
    type: DataTypes.STRING,
    defaultValue: 'idle' // idle | running | completed
  }
}, {
  timestamps: true
});

module.exports = Trip;

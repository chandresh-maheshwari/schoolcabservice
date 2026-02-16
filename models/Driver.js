const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');
const User = require('./User');

const Driver = sequelize.define('Driver', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  userId: {
    type: DataTypes.INTEGER,
    unique: true,
    references: {
      model: User,
      key: 'id'
    }
  },
  fullName: {
    type: DataTypes.STRING
  },
  licenseNumber: {
    type: DataTypes.STRING
  },
  phoneNumber: {
    type: DataTypes.STRING
  },
  vehicleNumber: {
    type: DataTypes.STRING
  },
  vehicleModel: {
    type: DataTypes.STRING
  },
  vehicleCapacity: {
    type: DataTypes.INTEGER
  },
  currentLat: {
    type: DataTypes.DOUBLE
  },
  currentLng: {
    type: DataTypes.DOUBLE
  },
  stops: {
    type: DataTypes.JSON,
    defaultValue: []
  },
  currentRoute: {
    type: DataTypes.JSON,
    defaultValue: null
  },
  lastCompletedStopIndex: {
    type: DataTypes.INTEGER,
    defaultValue: -1
  }
}, {
  tableName: 'driverdetails',
  timestamps: true
});

Driver.belongsTo(User, { foreignKey: 'userId' });
User.hasOne(Driver, { foreignKey: 'userId' });

module.exports = Driver;

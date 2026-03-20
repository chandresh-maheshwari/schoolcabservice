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
    field: 'login_user_id',
    references: {
      model: User,
      key: 'id'
    }
  },
  fullName: {
    type: DataTypes.STRING,
    field: 'driver_name',
  },
  licenseNumber: {
    type: DataTypes.STRING,
    field: 'license_no',
  },
  phoneNumber: {
    type: DataTypes.STRING,
    field: 'driver_phone',
  },
  vehicleNumber: {
    type: DataTypes.STRING,
    field: 'vehicle_number',
  },
  vehicleModel: {
    type: DataTypes.STRING,
    field: 'vehicle_model',
  },
  vehicleCapacity: {
    type: DataTypes.INTEGER,
    field: 'vehicle_capacity',
  },
  currentLat: {
    type: DataTypes.DOUBLE,
    field: 'current_lat',
  },
  currentLng: {
    type: DataTypes.DOUBLE,
    field: 'current_lng',
  },
  stops: {
    type: DataTypes.JSON,
    defaultValue: [],
    field: 'stops_json',
  },
  currentRoute: {
    type: DataTypes.JSON,
    defaultValue: null,
    field: 'current_route_json',
  },
  lastCompletedStopIndex: {
    type: DataTypes.INTEGER,
    defaultValue: -1,
    field: 'last_completed_stop_index',
  }
}, {
  tableName: 'drivers',
  timestamps: true,
  createdAt: 'created_at',
  updatedAt: 'updated_at',
});

Driver.belongsTo(User, { foreignKey: 'userId' });
User.hasOne(Driver, { foreignKey: 'userId' });

module.exports = Driver;

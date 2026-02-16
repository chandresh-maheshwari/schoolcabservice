const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');
const User = require('./User');

const Child = sequelize.define('Child', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  parentId: {
    type: DataTypes.INTEGER,
    references: {
      model: User,
      key: 'id'
    }
  },
  name: {
    type: DataTypes.STRING
  },
  schoolName: {
    type: DataTypes.STRING
  },
  className: {
    type: DataTypes.STRING
  },
  homeLat: {
    type: DataTypes.DOUBLE
  },
  homeLng: {
    type: DataTypes.DOUBLE
  },
  schoolLat: {
    type: DataTypes.DOUBLE
  },
  schoolLng: {
    type: DataTypes.DOUBLE
  },
  secretPin: {
    type: DataTypes.STRING
  },
  tripStatus: {
    type: DataTypes.STRING,
    defaultValue: 'pending'
  },
  routeOrder: {
    type: DataTypes.INTEGER,
    defaultValue: 0
  },
  driverCurrentLat: {
    type: DataTypes.DOUBLE
  },
  driverCurrentLng: {
    type: DataTypes.DOUBLE
  },
  subscriptionStatus: {
    type: DataTypes.ENUM('active', 'inactive', 'expired'),
    defaultValue: 'inactive'
  },
  subscriptionExpiresAt: {
    type: DataTypes.DATE
  },
  packageType: {
    type: DataTypes.ENUM('1day', '1month', '1year', 'none'),
    defaultValue: 'none'
  }
}, {
  timestamps: true
});

Child.belongsTo(User, { foreignKey: 'parentId' });
User.hasMany(Child, { foreignKey: 'parentId' });

module.exports = Child;

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
    field: 'parent_id',
    references: {
      model: User,
      key: 'id'
    }
  },
  name: {
    type: DataTypes.STRING,
    field: 'child_name'
  },
  schoolName: {
    type: DataTypes.STRING,
    field: 'school_name'
  },
  className: {
    type: DataTypes.STRING,
    field: 'class'
  },
  homeAddress: {
    type: DataTypes.TEXT,
    field: 'home_address'
  },
  homeLat: {
    type: DataTypes.DOUBLE
  },
  homeLng: {
    type: DataTypes.DOUBLE
  },
  schoolAddress: {
    type: DataTypes.TEXT,
    field: 'school_address'
  },
  schoolLat: {
    type: DataTypes.DOUBLE
  },
  schoolLng: {
    type: DataTypes.DOUBLE
  },
  secretPin: {
    type: DataTypes.STRING,
    field: 'secret_pin'
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
    defaultValue: 'inactive',
    field: 'subscription_status'
  },
  subscriptionExpiresAt: {
    type: DataTypes.DATE,
    field: 'subscription_expires_at'
  },
  packageType: {
    type: DataTypes.ENUM('1day', '1month', '1year', 'none'),
    defaultValue: 'none',
    field: 'package_type'
  }
}, {
  tableName: 'children',
  timestamps: true,
  createdAt: 'created_at',
  updatedAt: 'updated_at'
});

Child.belongsTo(User, { foreignKey: 'parentId' });
User.hasMany(Child, { foreignKey: 'parentId' });

module.exports = Child;

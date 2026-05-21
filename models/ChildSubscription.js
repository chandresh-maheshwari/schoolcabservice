const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

const ChildSubscription = sequelize.define(
  'ChildSubscription',
  {
    id: {
      type: DataTypes.BIGINT.UNSIGNED,
      primaryKey: true,
      autoIncrement: true,
    },
    childId: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: false,
      field: 'child_id',
    },
    serviceType: {
      type: DataTypes.STRING(32),
      allowNull: false,
      defaultValue: 'vehicle',
      field: 'service_type',
    },
    packageType: {
      type: DataTypes.STRING(32),
      allowNull: true,
      field: 'package_type',
    },
    status: {
      type: DataTypes.STRING(16),
      allowNull: false,
      defaultValue: 'pending',
    },
    source: {
      type: DataTypes.STRING(32),
      allowNull: false,
      defaultValue: 'app',
    },
    isCurrent: {
      type: DataTypes.TINYINT.UNSIGNED,
      allowNull: true,
      field: 'is_current',
    },
    startsAt: {
      type: DataTypes.DATE,
      allowNull: true,
      field: 'starts_at',
    },
    expiresAt: {
      type: DataTypes.DATE,
      allowNull: true,
      field: 'expires_at',
    },
    createdByUserId: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: true,
      field: 'created_by_user_id',
    },
    notes: {
      type: DataTypes.TEXT,
      allowNull: true,
    },
  },
  {
    tableName: 'child_subscriptions',
    timestamps: true,
    updatedAt: 'updated_at',
  }
);

module.exports = ChildSubscription;


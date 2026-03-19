const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

const DeviceToken = sequelize.define(
  'DeviceToken',
  {
    id: {
      type: DataTypes.BIGINT.UNSIGNED,
      primaryKey: true,
      autoIncrement: true,
    },
    userId: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: false,
      field: 'user_id',
    },
    email: {
      type: DataTypes.STRING,
      allowNull: false,
    },
    platform: {
      type: DataTypes.STRING,
      allowNull: false,
    },
    token: {
      type: DataTypes.STRING(512),
      allowNull: false,
    },
    lastSeenAt: {
      type: DataTypes.DATE,
      allowNull: true,
      field: 'last_seen_at',
    },
  },
  {
    tableName: 'device_tokens',
    timestamps: true,
  },
);

module.exports = DeviceToken;

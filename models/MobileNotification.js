const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

const MobileNotification = sequelize.define(
  'MobileNotification',
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
    title: {
      type: DataTypes.STRING,
      allowNull: false,
    },
    message: {
      type: DataTypes.TEXT,
      allowNull: false,
    },
    type: {
      type: DataTypes.STRING,
      allowNull: false,
      defaultValue: 'general',
    },
    isRead: {
      type: DataTypes.BOOLEAN,
      allowNull: false,
      defaultValue: false,
      field: 'is_read',
    },
    data: {
      type: DataTypes.JSON,
      allowNull: true,
    },
  },
  {
    tableName: 'mobile_notifications',
    timestamps: true,
  },
);

module.exports = MobileNotification;

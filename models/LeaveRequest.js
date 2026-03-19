const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

const LeaveRequest = sequelize.define(
  'LeaveRequest',
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
    childId: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: true,
      field: 'child_id',
    },
    childName: {
      type: DataTypes.STRING,
      allowNull: false,
      field: 'child_name',
    },
    fromDate: {
      type: DataTypes.DATEONLY,
      allowNull: false,
      field: 'from_date',
    },
    toDate: {
      type: DataTypes.DATEONLY,
      allowNull: false,
      field: 'to_date',
    },
    reason: {
      type: DataTypes.TEXT,
      allowNull: false,
    },
    status: {
      type: DataTypes.STRING,
      allowNull: false,
      defaultValue: 'requested',
    },
  },
  {
    tableName: 'leave_requests',
    timestamps: true,
  },
);

module.exports = LeaveRequest;

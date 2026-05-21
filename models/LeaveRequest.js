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
    parentId: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: true,
      field: 'parent_id',
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
    adminNotes: {
      type: DataTypes.TEXT,
      allowNull: true,
      field: 'admin_notes',
    },
    reviewedBy: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: true,
      field: 'reviewed_by',
    },
    reviewedAt: {
      type: DataTypes.DATE,
      allowNull: true,
      field: 'reviewed_at',
    },
  },
  {
    tableName: 'leave_requests',
    timestamps: true,
    updatedAt: 'updated_at',
  },
);

module.exports = LeaveRequest;

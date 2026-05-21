const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

const SupportRequest = sequelize.define(
  'SupportRequest',
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
    category: {
      type: DataTypes.STRING,
      allowNull: false,
    },
    subject: {
      type: DataTypes.STRING,
      allowNull: false,
    },
    message: {
      type: DataTypes.TEXT,
      allowNull: false,
    },
    status: {
      type: DataTypes.STRING,
      allowNull: false,
      defaultValue: 'open',
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
    tableName: 'support_requests',
    timestamps: true,
    updatedAt: 'updated_at',
  },
);

module.exports = SupportRequest;

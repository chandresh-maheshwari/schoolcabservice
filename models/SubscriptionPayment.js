const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

const SubscriptionPayment = sequelize.define(
  'SubscriptionPayment',
  {
    id: {
      type: DataTypes.BIGINT.UNSIGNED,
      primaryKey: true,
      autoIncrement: true,
    },
    childSubscriptionId: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: false,
      field: 'child_subscription_id',
    },
    channel: {
      type: DataTypes.STRING(32),
      allowNull: false,
      defaultValue: 'cash',
    },
    status: {
      type: DataTypes.STRING(16),
      allowNull: false,
      defaultValue: 'created',
    },
    amount: {
      type: DataTypes.DECIMAL(10, 2),
      allowNull: false,
      defaultValue: 0,
    },
    currency: {
      type: DataTypes.STRING(8),
      allowNull: false,
      defaultValue: 'INR',
    },
    orderId: {
      type: DataTypes.STRING,
      allowNull: true,
      field: 'order_id',
    },
    paymentId: {
      type: DataTypes.STRING,
      allowNull: true,
      field: 'payment_id',
    },
    signature: {
      type: DataTypes.STRING,
      allowNull: true,
    },
    receiptNo: {
      type: DataTypes.STRING,
      allowNull: true,
      field: 'receipt_no',
    },
    referenceNo: {
      type: DataTypes.STRING,
      allowNull: true,
      field: 'reference_no',
    },
    collectedByUserId: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: true,
      field: 'collected_by_user_id',
    },
    paidAt: {
      type: DataTypes.DATE,
      allowNull: true,
      field: 'paid_at',
    },
    meta: {
      type: DataTypes.JSON,
      allowNull: true,
    },
  },
  {
    tableName: 'subscription_payments',
    timestamps: true,
    updatedAt: 'updated_at',
  }
);

module.exports = SubscriptionPayment;


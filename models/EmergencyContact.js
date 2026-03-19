const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

const EmergencyContact = sequelize.define(
  'EmergencyContact',
  {
    id: {
      type: DataTypes.BIGINT.UNSIGNED,
      primaryKey: true,
      autoIncrement: true,
    },
    userId: {
      type: DataTypes.BIGINT.UNSIGNED,
      allowNull: false,
      unique: true,
      field: 'user_id',
    },
    email: {
      type: DataTypes.STRING,
      allowNull: false,
    },
    schoolContact: {
      type: DataTypes.STRING,
      allowNull: true,
      field: 'school_contact',
    },
    transportContact: {
      type: DataTypes.STRING,
      allowNull: true,
      field: 'transport_contact',
    },
    notes: {
      type: DataTypes.TEXT,
      allowNull: true,
    },
  },
  {
    tableName: 'emergency_contacts',
    timestamps: true,
  },
);

module.exports = EmergencyContact;

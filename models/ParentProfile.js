const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

const ParentProfile = sequelize.define(
  'ParentProfile',
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
    fullName: {
      type: DataTypes.STRING,
      allowNull: true,
      field: 'full_name',
    },
    motherName: {
      type: DataTypes.STRING,
      allowNull: true,
      field: 'mother_name',
    },
    phoneNumber: {
      type: DataTypes.STRING,
      allowNull: true,
      field: 'phone_number',
    },
    alternatePhone: {
      type: DataTypes.STRING,
      allowNull: true,
      field: 'alternate_phone',
    },
    homeAddress: {
      type: DataTypes.TEXT,
      allowNull: true,
      field: 'home_address',
    },
    city: {
      type: DataTypes.STRING,
      allowNull: true,
      field: 'city',
    },
    state: {
      type: DataTypes.STRING,
      allowNull: true,
      field: 'state',
    },
    pincode: {
      type: DataTypes.STRING,
      allowNull: true,
      field: 'pincode',
    },
    emergencyContact: {
      type: DataTypes.STRING,
      allowNull: true,
      field: 'emergency_contact',
    },
    profileImageUrl: {
      type: DataTypes.TEXT,
      allowNull: true,
      field: 'profile_image_url',
    },
  },
  {
    tableName: 'parent_profiles',
    timestamps: true,
  },
);

module.exports = ParentProfile;

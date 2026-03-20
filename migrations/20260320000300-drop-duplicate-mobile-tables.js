'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface) {
    const tables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    if (tables.includes('parent_profiles')) {
      await queryInterface.dropTable('parent_profiles');
    }

    if (tables.includes('driverdetails')) {
      await queryInterface.dropTable('driverdetails');
    }
  },

  async down(queryInterface, Sequelize) {
    const tables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    if (!tables.includes('parent_profiles')) {
      await queryInterface.createTable('parent_profiles', {
        id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: false,
          autoIncrement: true,
          primaryKey: true,
        },
        user_id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: false,
          unique: true,
        },
        email: {
          type: Sequelize.STRING,
          allowNull: false,
        },
        full_name: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        mother_name: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        phone_number: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        alternate_phone: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        home_address: {
          type: Sequelize.TEXT,
          allowNull: true,
        },
        city: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        state: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        pincode: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        emergency_contact: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        profile_image_url: {
          type: Sequelize.TEXT,
          allowNull: true,
        },
        createdAt: {
          type: Sequelize.DATE,
          allowNull: false,
        },
        updatedAt: {
          type: Sequelize.DATE,
          allowNull: false,
        },
      });
    }

    if (!tables.includes('driverdetails')) {
      await queryInterface.createTable('driverdetails', {
        id: {
          type: Sequelize.INTEGER,
          allowNull: false,
          autoIncrement: true,
          primaryKey: true,
        },
        userId: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: true,
          unique: true,
        },
        fullName: {
          type: Sequelize.STRING,
        },
        licenseNumber: {
          type: Sequelize.STRING,
        },
        phoneNumber: {
          type: Sequelize.STRING,
        },
        vehicleNumber: {
          type: Sequelize.STRING,
        },
        vehicleModel: {
          type: Sequelize.STRING,
        },
        vehicleCapacity: {
          type: Sequelize.INTEGER,
        },
        currentLat: {
          type: Sequelize.DOUBLE,
        },
        currentLng: {
          type: Sequelize.DOUBLE,
        },
        stops: {
          type: Sequelize.JSON,
          defaultValue: [],
        },
        currentRoute: {
          type: Sequelize.JSON,
          defaultValue: null,
        },
        lastCompletedStopIndex: {
          type: Sequelize.INTEGER,
          defaultValue: -1,
        },
        createdAt: {
          type: Sequelize.DATE,
          allowNull: false,
        },
        updatedAt: {
          type: Sequelize.DATE,
          allowNull: false,
        },
      });
    }
  },
};

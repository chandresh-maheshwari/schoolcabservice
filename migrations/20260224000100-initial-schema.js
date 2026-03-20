'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface, Sequelize) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') {
        return table.toLowerCase();
      }

      return String(table.tableName || table.name || '').toLowerCase();
    });

    const hasTable = (name) => existingTables.includes(String(name).toLowerCase());
    const hasSharedUsersTable = hasTable('users');
    const hasSharedChildrenTable = hasTable('children');

    if (!hasTable('driverdetails')) {
      await queryInterface.createTable('driverdetails', {
        id: {
          type: Sequelize.INTEGER,
          allowNull: false,
          autoIncrement: true,
          primaryKey: true,
        },
        userId: {
          type: hasSharedUsersTable ? Sequelize.BIGINT.UNSIGNED : Sequelize.INTEGER,
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

    if (!hasTable('trips')) {
      await queryInterface.createTable('Trips', {
        id: {
          type: Sequelize.INTEGER,
          allowNull: false,
          autoIncrement: true,
          primaryKey: true,
        },
        driverLat: {
          type: Sequelize.DOUBLE,
        },
        driverLng: {
          type: Sequelize.DOUBLE,
        },
        stops: {
          type: Sequelize.JSON,
          defaultValue: [],
        },
        nextStop: {
          type: Sequelize.JSON,
          defaultValue: null,
        },
        currentRoute: {
          type: Sequelize.JSON,
          defaultValue: null,
        },
        tripType: {
          type: Sequelize.STRING,
        },
        direction: {
          type: Sequelize.STRING,
        },
        lastCompletedStopIndex: {
          type: Sequelize.INTEGER,
          defaultValue: -1,
        },
        status: {
          type: Sequelize.STRING,
          defaultValue: 'idle',
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

  async down(queryInterface) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') {
        return table.toLowerCase();
      }

      return String(table.tableName || table.name || '').toLowerCase();
    });

    const hasTable = (name) => existingTables.includes(String(name).toLowerCase());

    if (hasTable('trips')) {
      await queryInterface.dropTable('Trips');
    }
    if (hasTable('driverdetails')) {
      await queryInterface.dropTable('driverdetails');
    }
  },
};

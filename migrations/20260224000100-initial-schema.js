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

    if (!hasSharedUsersTable) {
      await queryInterface.createTable('Users', {
        id: {
          type: Sequelize.INTEGER,
          allowNull: false,
          autoIncrement: true,
          primaryKey: true,
        },
        email: {
          type: Sequelize.STRING,
          allowNull: false,
          unique: true,
        },
        password: {
          type: Sequelize.STRING,
          allowNull: false,
        },
        role: {
          type: Sequelize.STRING,
          allowNull: false,
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

    if (!hasSharedChildrenTable) {
      await queryInterface.createTable('Children', {
        id: {
          type: Sequelize.INTEGER,
          allowNull: false,
          autoIncrement: true,
          primaryKey: true,
        },
        parentId: {
          type: hasSharedUsersTable ? Sequelize.BIGINT.UNSIGNED : Sequelize.INTEGER,
          allowNull: true,
        },
        name: {
          type: Sequelize.STRING,
        },
        schoolName: {
          type: Sequelize.STRING,
        },
        className: {
          type: Sequelize.STRING,
        },
        homeLat: {
          type: Sequelize.DOUBLE,
        },
        homeLng: {
          type: Sequelize.DOUBLE,
        },
        schoolLat: {
          type: Sequelize.DOUBLE,
        },
        schoolLng: {
          type: Sequelize.DOUBLE,
        },
        secretPin: {
          type: Sequelize.STRING,
        },
        tripStatus: {
          type: Sequelize.STRING,
          defaultValue: 'pending',
        },
        routeOrder: {
          type: Sequelize.INTEGER,
          defaultValue: 0,
        },
        driverCurrentLat: {
          type: Sequelize.DOUBLE,
        },
        driverCurrentLng: {
          type: Sequelize.DOUBLE,
        },
        subscriptionStatus: {
          type: Sequelize.ENUM('active', 'inactive', 'expired'),
          defaultValue: 'inactive',
        },
        subscriptionExpiresAt: {
          type: Sequelize.DATE,
        },
        packageType: {
          type: Sequelize.ENUM('1day', '1month', '1year', 'none'),
          defaultValue: 'none',
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

    if (!hasTable('payments')) {
      await queryInterface.createTable('Payments', {
        id: {
          type: Sequelize.INTEGER,
          allowNull: false,
          autoIncrement: true,
          primaryKey: true,
        },
        parentId: {
          type: hasSharedUsersTable ? Sequelize.BIGINT.UNSIGNED : Sequelize.INTEGER,
          allowNull: true,
        },
        childId: {
          type: hasSharedChildrenTable ? Sequelize.BIGINT.UNSIGNED : Sequelize.INTEGER,
          allowNull: true,
        },
        orderId: {
          type: Sequelize.STRING,
        },
        paymentId: {
          type: Sequelize.STRING,
        },
        signature: {
          type: Sequelize.STRING,
        },
        amount: {
          type: Sequelize.FLOAT,
        },
        currency: {
          type: Sequelize.STRING,
          defaultValue: 'INR',
        },
        status: {
          type: Sequelize.ENUM('created', 'captured', 'failed'),
          defaultValue: 'created',
        },
        packageType: {
          type: Sequelize.STRING,
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

    if (hasTable('payments')) {
      await queryInterface.dropTable('Payments');
    }
    if (hasTable('trips')) {
      await queryInterface.dropTable('Trips');
    }
    if (hasTable('children')) {
      await queryInterface.dropTable('Children');
    }
    if (hasTable('driverdetails')) {
      await queryInterface.dropTable('driverdetails');
    }
    if (hasTable('users')) {
      await queryInterface.dropTable('Users');
    }
  },
};

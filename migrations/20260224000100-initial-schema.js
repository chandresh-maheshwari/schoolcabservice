'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface, Sequelize) {
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

    await queryInterface.createTable('driverdetails', {
      id: {
        type: Sequelize.INTEGER,
        allowNull: false,
        autoIncrement: true,
        primaryKey: true,
      },
      userId: {
        type: Sequelize.INTEGER,
        unique: true,
        references: {
          model: 'Users',
          key: 'id',
        },
        onUpdate: 'CASCADE',
        onDelete: 'SET NULL',
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

    await queryInterface.createTable('Children', {
      id: {
        type: Sequelize.INTEGER,
        allowNull: false,
        autoIncrement: true,
        primaryKey: true,
      },
      parentId: {
        type: Sequelize.INTEGER,
        references: {
          model: 'Users',
          key: 'id',
        },
        onUpdate: 'CASCADE',
        onDelete: 'SET NULL',
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

    await queryInterface.createTable('Payments', {
      id: {
        type: Sequelize.INTEGER,
        allowNull: false,
        autoIncrement: true,
        primaryKey: true,
      },
      parentId: {
        type: Sequelize.INTEGER,
        references: {
          model: 'Users',
          key: 'id',
        },
        onUpdate: 'CASCADE',
        onDelete: 'SET NULL',
      },
      childId: {
        type: Sequelize.INTEGER,
        references: {
          model: 'Children',
          key: 'id',
        },
        onUpdate: 'CASCADE',
        onDelete: 'SET NULL',
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
  },

  async down(queryInterface) {
    await queryInterface.dropTable('Payments');
    await queryInterface.dropTable('Trips');
    await queryInterface.dropTable('Children');
    await queryInterface.dropTable('driverdetails');
    await queryInterface.dropTable('Users');
  },
};


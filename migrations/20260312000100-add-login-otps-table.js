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

    if (existingTables.includes('login_otps')) {
      return;
    }

    await queryInterface.createTable('login_otps', {
      id: {
        type: Sequelize.BIGINT.UNSIGNED,
        allowNull: false,
        autoIncrement: true,
        primaryKey: true,
      },
      user_id: {
        type: Sequelize.BIGINT.UNSIGNED,
        allowNull: true,
      },
      email: {
        type: Sequelize.STRING,
        allowNull: false,
      },
      role: {
        type: Sequelize.STRING,
        allowNull: false,
      },
      otp_hash: {
        type: Sequelize.STRING,
        allowNull: false,
      },
      expires_at: {
        type: Sequelize.DATE,
        allowNull: false,
      },
      consumed_at: {
        type: Sequelize.DATE,
        allowNull: true,
      },
      attempts: {
        type: Sequelize.INTEGER,
        allowNull: false,
        defaultValue: 0,
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

    await queryInterface.addIndex('login_otps', ['email'], { name: 'login_otps_email_idx' });
    await queryInterface.addIndex('login_otps', ['user_id'], { name: 'login_otps_user_id_idx' });
  },

  async down(queryInterface) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') {
        return table.toLowerCase();
      }

      return String(table.tableName || table.name || '').toLowerCase();
    });

    if (existingTables.includes('login_otps')) {
      await queryInterface.dropTable('login_otps');
    }
  },
};

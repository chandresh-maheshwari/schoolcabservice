'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface, Sequelize) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    if (existingTables.includes('child_trip_pins')) {
      return;
    }

    await queryInterface.createTable('child_trip_pins', {
      id: {
        type: Sequelize.BIGINT.UNSIGNED,
        primaryKey: true,
        autoIncrement: true,
        allowNull: false,
      },
      child_id: {
        type: Sequelize.BIGINT.UNSIGNED,
        allowNull: false,
      },
      trip_id: {
        type: Sequelize.BIGINT.UNSIGNED,
        allowNull: true,
      },
      route_id: {
        type: Sequelize.BIGINT.UNSIGNED,
        allowNull: true,
      },
      driver_user_id: {
        type: Sequelize.BIGINT.UNSIGNED,
        allowNull: true,
      },
      trip_type: {
        type: Sequelize.STRING(32),
        allowNull: true,
      },
      pin: {
        type: Sequelize.STRING(4),
        allowNull: false,
      },
      expires_at: {
        type: Sequelize.DATE,
        allowNull: false,
      },
      created_at: {
        type: Sequelize.DATE,
        allowNull: false,
        defaultValue: Sequelize.literal('CURRENT_TIMESTAMP'),
      },
      updated_at: {
        type: Sequelize.DATE,
        allowNull: false,
        defaultValue: Sequelize.literal('CURRENT_TIMESTAMP'),
      },
    });

    await queryInterface.addIndex('child_trip_pins', ['child_id'], {
      name: 'child_trip_pins_child_id_idx',
    });
    await queryInterface.addIndex('child_trip_pins', ['trip_id'], {
      name: 'child_trip_pins_trip_id_idx',
    });
    await queryInterface.addIndex('child_trip_pins', ['expires_at'], {
      name: 'child_trip_pins_expires_at_idx',
    });
  },

  async down(queryInterface) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    if (existingTables.includes('child_trip_pins')) {
      await queryInterface.dropTable('child_trip_pins');
    }
  },
};

'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface, Sequelize) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    if (existingTables.includes('push_notification_settings')) {
      return;
    }

    await queryInterface.createTable('push_notification_settings', {
      id: {
        type: Sequelize.BIGINT.UNSIGNED,
        allowNull: false,
        autoIncrement: true,
        primaryKey: true,
      },
      event_key: {
        type: Sequelize.STRING,
        allowNull: false,
      },
      enabled: {
        type: Sequelize.BOOLEAN,
        allowNull: false,
        defaultValue: true,
      },
      title_template: {
        type: Sequelize.STRING,
        allowNull: false,
      },
      message_template: {
        type: Sequelize.TEXT,
        allowNull: false,
      },
      metadata: {
        type: Sequelize.JSON,
        allowNull: true,
      },
      createdAt: {
        type: Sequelize.DATE,
        allowNull: false,
      },
      updated_at: {
        type: Sequelize.DATE,
        allowNull: false,
      },
    });

    await queryInterface.addIndex('push_notification_settings', ['event_key'], {
      name: 'push_notification_settings_event_key_unique',
      unique: true,
    });
  },

  async down(queryInterface) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    if (existingTables.includes('push_notification_settings')) {
      await queryInterface.dropTable('push_notification_settings');
    }
  },
};

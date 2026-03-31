'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface, Sequelize) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    const hasTable = (name) => existingTables.includes(String(name).toLowerCase());
    if (!hasTable('device_tokens')) {
      return;
    }

    const description = await queryInterface.describeTable('device_tokens');

    if (!description.installation_id) {
      await queryInterface.addColumn('device_tokens', 'installation_id', {
        type: Sequelize.STRING(191),
        allowNull: true,
        after: 'platform',
      });
    }

    try {
      await queryInterface.addIndex('device_tokens', ['installation_id'], {
        name: 'device_tokens_installation_id_idx',
      });
    } catch (_) {}
  },

  async down(queryInterface) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    const hasTable = (name) => existingTables.includes(String(name).toLowerCase());
    if (!hasTable('device_tokens')) {
      return;
    }

    const description = await queryInterface.describeTable('device_tokens');

    try {
      await queryInterface.removeIndex('device_tokens', 'device_tokens_installation_id_idx');
    } catch (_) {}

    if (description.installation_id) {
      await queryInterface.removeColumn('device_tokens', 'installation_id');
    }
  },
};

'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface, Sequelize) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    if (!existingTables.includes('children')) {
      return;
    }

    const description = await queryInterface.describeTable('children');

    if (!description.today_pickup_name) {
      await queryInterface.addColumn('children', 'today_pickup_name', {
        type: Sequelize.STRING,
        allowNull: true,
      });
    }

    if (!description.today_pickup_date) {
      await queryInterface.addColumn('children', 'today_pickup_date', {
        type: Sequelize.DATEONLY,
        allowNull: true,
      });
    }
  },

  async down(queryInterface) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    if (!existingTables.includes('children')) {
      return;
    }

    const description = await queryInterface.describeTable('children');

    if (description.today_pickup_date) {
      await queryInterface.removeColumn('children', 'today_pickup_date');
    }

    if (description.today_pickup_name) {
      await queryInterface.removeColumn('children', 'today_pickup_name');
    }
  },
};

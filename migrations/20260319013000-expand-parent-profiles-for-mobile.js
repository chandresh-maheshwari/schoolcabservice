'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface, Sequelize) {
    const tables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    if (!tables.includes('parent_profiles')) {
      return;
    }

    const description = await queryInterface.describeTable('parent_profiles');

    if (!description.mother_name) {
      await queryInterface.addColumn('parent_profiles', 'mother_name', {
        type: Sequelize.STRING,
        allowNull: true,
      });
    }

    if (!description.alternate_phone) {
      await queryInterface.addColumn('parent_profiles', 'alternate_phone', {
        type: Sequelize.STRING,
        allowNull: true,
      });
    }

    if (!description.city) {
      await queryInterface.addColumn('parent_profiles', 'city', {
        type: Sequelize.STRING,
        allowNull: true,
      });
    }

    if (!description.state) {
      await queryInterface.addColumn('parent_profiles', 'state', {
        type: Sequelize.STRING,
        allowNull: true,
      });
    }

    if (!description.pincode) {
      await queryInterface.addColumn('parent_profiles', 'pincode', {
        type: Sequelize.STRING,
        allowNull: true,
      });
    }

    if (!description.profile_image_url) {
      await queryInterface.addColumn('parent_profiles', 'profile_image_url', {
        type: Sequelize.TEXT,
        allowNull: true,
      });
    }
  },

  async down(queryInterface) {
    const tables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    if (!tables.includes('parent_profiles')) {
      return;
    }

    const description = await queryInterface.describeTable('parent_profiles');

    if (description.profile_image_url) {
      await queryInterface.removeColumn('parent_profiles', 'profile_image_url');
    }
    if (description.pincode) {
      await queryInterface.removeColumn('parent_profiles', 'pincode');
    }
    if (description.state) {
      await queryInterface.removeColumn('parent_profiles', 'state');
    }
    if (description.city) {
      await queryInterface.removeColumn('parent_profiles', 'city');
    }
    if (description.alternate_phone) {
      await queryInterface.removeColumn('parent_profiles', 'alternate_phone');
    }
    if (description.mother_name) {
      await queryInterface.removeColumn('parent_profiles', 'mother_name');
    }
  },
};

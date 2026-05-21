'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface, Sequelize) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    const hasTable = (name) => existingTables.includes(String(name).toLowerCase());

    if (!hasTable('parent_profiles')) {
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
        phone_number: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        home_address: {
          type: Sequelize.TEXT,
          allowNull: true,
        },
        emergency_contact: {
          type: Sequelize.STRING,
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
      await queryInterface.addIndex('parent_profiles', ['user_id'], {
        name: 'parent_profiles_user_id_idx',
      });
    }

    if (hasTable('children')) {
      const description = await queryInterface.describeTable('children');
      if (!description.home_address) {
        await queryInterface.addColumn('children', 'home_address', {
          type: Sequelize.TEXT,
          allowNull: true,
        });
      }
      if (!description.school_address) {
        await queryInterface.addColumn('children', 'school_address', {
          type: Sequelize.TEXT,
          allowNull: true,
        });
      }
    }

  },

  async down(queryInterface) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    const hasTable = (name) => existingTables.includes(String(name).toLowerCase());

    if (hasTable('children')) {
      const description = await queryInterface.describeTable('children');
      if (description.school_address) {
        await queryInterface.removeColumn('children', 'school_address');
      }
      if (description.home_address) {
        await queryInterface.removeColumn('children', 'home_address');
      }
    }

    if (hasTable('parent_profiles')) {
      await queryInterface.dropTable('parent_profiles');
    }
  },
};

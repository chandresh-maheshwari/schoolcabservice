'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface, Sequelize) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    const hasTable = (name) => existingTables.includes(String(name).toLowerCase());

    if (hasTable('login_otps')) {
      const description = await queryInterface.describeTable('login_otps');
      if (!description.purpose) {
        await queryInterface.addColumn('login_otps', 'purpose', {
          type: Sequelize.STRING,
          allowNull: false,
          defaultValue: 'mobile-login',
        });
        await queryInterface.addIndex('login_otps', ['email', 'purpose'], {
          name: 'login_otps_email_purpose_idx',
        });
      }
    }

    if (!hasTable('support_requests')) {
      await queryInterface.createTable('support_requests', {
        id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: false,
          autoIncrement: true,
          primaryKey: true,
        },
        user_id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: false,
        },
        email: {
          type: Sequelize.STRING,
          allowNull: false,
        },
        category: {
          type: Sequelize.STRING,
          allowNull: false,
        },
        subject: {
          type: Sequelize.STRING,
          allowNull: false,
        },
        message: {
          type: Sequelize.TEXT,
          allowNull: false,
        },
        status: {
          type: Sequelize.STRING,
          allowNull: false,
          defaultValue: 'open',
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
      await queryInterface.addIndex('support_requests', ['user_id'], {
        name: 'support_requests_user_id_idx',
      });
    }

    if (!hasTable('leave_requests')) {
      await queryInterface.createTable('leave_requests', {
        id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: false,
          autoIncrement: true,
          primaryKey: true,
        },
        user_id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: false,
        },
        email: {
          type: Sequelize.STRING,
          allowNull: false,
        },
        child_id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: true,
        },
        child_name: {
          type: Sequelize.STRING,
          allowNull: false,
        },
        from_date: {
          type: Sequelize.DATEONLY,
          allowNull: false,
        },
        to_date: {
          type: Sequelize.DATEONLY,
          allowNull: false,
        },
        reason: {
          type: Sequelize.TEXT,
          allowNull: false,
        },
        status: {
          type: Sequelize.STRING,
          allowNull: false,
          defaultValue: 'requested',
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
      await queryInterface.addIndex('leave_requests', ['user_id'], {
        name: 'leave_requests_user_id_idx',
      });
    }

    if (!hasTable('emergency_contacts')) {
      await queryInterface.createTable('emergency_contacts', {
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
        school_contact: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        transport_contact: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        notes: {
          type: Sequelize.TEXT,
          allowNull: true,
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

    if (!hasTable('mobile_notifications')) {
      await queryInterface.createTable('mobile_notifications', {
        id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: false,
          autoIncrement: true,
          primaryKey: true,
        },
        user_id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: false,
        },
        title: {
          type: Sequelize.STRING,
          allowNull: false,
        },
        message: {
          type: Sequelize.TEXT,
          allowNull: false,
        },
        type: {
          type: Sequelize.STRING,
          allowNull: false,
          defaultValue: 'general',
        },
        is_read: {
          type: Sequelize.BOOLEAN,
          allowNull: false,
          defaultValue: false,
        },
        data: {
          type: Sequelize.JSON,
          allowNull: true,
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
      await queryInterface.addIndex('mobile_notifications', ['user_id', 'is_read'], {
        name: 'mobile_notifications_user_read_idx',
      });
    }

    if (!hasTable('device_tokens')) {
      await queryInterface.createTable('device_tokens', {
        id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: false,
          autoIncrement: true,
          primaryKey: true,
        },
        user_id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: false,
        },
        email: {
          type: Sequelize.STRING,
          allowNull: false,
        },
        platform: {
          type: Sequelize.STRING,
          allowNull: false,
        },
        token: {
          type: Sequelize.STRING(512),
          allowNull: false,
        },
        last_seen_at: {
          type: Sequelize.DATE,
          allowNull: true,
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
      await queryInterface.addIndex('device_tokens', ['user_id'], {
        name: 'device_tokens_user_id_idx',
      });
      await queryInterface.addIndex('device_tokens', ['token'], {
        name: 'device_tokens_token_idx',
      });
    }
  },

  async down(queryInterface) {
    const existingTables = (await queryInterface.showAllTables()).map((table) => {
      if (typeof table === 'string') return table.toLowerCase();
      return String(table.tableName || table.name || '').toLowerCase();
    });

    const hasTable = (name) => existingTables.includes(String(name).toLowerCase());

    if (hasTable('device_tokens')) await queryInterface.dropTable('device_tokens');
    if (hasTable('mobile_notifications')) await queryInterface.dropTable('mobile_notifications');
    if (hasTable('emergency_contacts')) await queryInterface.dropTable('emergency_contacts');
    if (hasTable('leave_requests')) await queryInterface.dropTable('leave_requests');
    if (hasTable('support_requests')) await queryInterface.dropTable('support_requests');

    if (hasTable('login_otps')) {
      const description = await queryInterface.describeTable('login_otps');
      if (description.purpose) {
        try {
          await queryInterface.removeIndex('login_otps', 'login_otps_email_purpose_idx');
        } catch (_) {}
        await queryInterface.removeColumn('login_otps', 'purpose');
      }
    }
  },
};

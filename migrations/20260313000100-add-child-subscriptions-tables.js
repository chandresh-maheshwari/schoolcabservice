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

    if (!hasTable('child_subscriptions')) {
      await queryInterface.createTable('child_subscriptions', {
        id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: false,
          autoIncrement: true,
          primaryKey: true,
        },
        child_id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: false,
        },
        service_type: {
          type: Sequelize.STRING(32),
          allowNull: false,
          defaultValue: 'vehicle',
        },
        package_type: {
          type: Sequelize.STRING(32),
          allowNull: true,
        },
        status: {
          type: Sequelize.STRING(16),
          allowNull: false,
          defaultValue: 'pending',
        },
        source: {
          type: Sequelize.STRING(32),
          allowNull: false,
          defaultValue: 'app',
        },
        is_current: {
          type: Sequelize.TINYINT.UNSIGNED,
          allowNull: true,
        },
        starts_at: {
          type: Sequelize.DATE,
          allowNull: true,
        },
        expires_at: {
          type: Sequelize.DATE,
          allowNull: true,
        },
        created_by_user_id: {
          type: Sequelize.BIGINT.UNSIGNED,
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

      await queryInterface.addIndex('child_subscriptions', ['child_id']);
      await queryInterface.addIndex('child_subscriptions', ['child_id', 'service_type']);
      await queryInterface.addIndex('child_subscriptions', ['service_type', 'status']);
      await queryInterface.addConstraint('child_subscriptions', {
        type: 'unique',
        name: 'uniq_child_service_current',
        fields: ['child_id', 'service_type', 'is_current'],
      });
    }

    if (!hasTable('subscription_payments')) {
      await queryInterface.createTable('subscription_payments', {
        id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: false,
          autoIncrement: true,
          primaryKey: true,
        },
        child_subscription_id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: false,
        },
        channel: {
          type: Sequelize.STRING(32),
          allowNull: false,
          defaultValue: 'cash',
        },
        status: {
          type: Sequelize.STRING(16),
          allowNull: false,
          defaultValue: 'created',
        },
        amount: {
          type: Sequelize.DECIMAL(10, 2),
          allowNull: false,
          defaultValue: 0,
        },
        currency: {
          type: Sequelize.STRING(8),
          allowNull: false,
          defaultValue: 'INR',
        },
        order_id: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        payment_id: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        signature: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        receipt_no: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        reference_no: {
          type: Sequelize.STRING,
          allowNull: true,
        },
        collected_by_user_id: {
          type: Sequelize.BIGINT.UNSIGNED,
          allowNull: true,
        },
        paid_at: {
          type: Sequelize.DATE,
          allowNull: true,
        },
        meta: {
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

      await queryInterface.addIndex('subscription_payments', ['child_subscription_id']);
      await queryInterface.addIndex('subscription_payments', ['channel', 'status']);
      await queryInterface.addIndex('subscription_payments', ['order_id']);
      await queryInterface.addIndex('subscription_payments', ['payment_id']);
      await queryInterface.addConstraint('subscription_payments', {
        type: 'unique',
        name: 'uniq_payment_channel_order',
        fields: ['channel', 'order_id'],
      });

      await queryInterface.addConstraint('subscription_payments', {
        type: 'foreign key',
        name: 'fk_subscription_payments_subscription',
        fields: ['child_subscription_id'],
        references: {
          table: 'child_subscriptions',
          field: 'id',
        },
        onDelete: 'CASCADE',
        onUpdate: 'CASCADE',
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

    if (hasTable('subscription_payments')) {
      await queryInterface.dropTable('subscription_payments');
    }

    if (hasTable('child_subscriptions')) {
      await queryInterface.dropTable('child_subscriptions');
    }
  },
};


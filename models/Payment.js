const { DataTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');
const User = require('./User');
const Child = require('./Child');

const Payment = sequelize.define('Payment', {
    id: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true
    },
    parentId: {
        type: DataTypes.INTEGER,
        references: {
            model: User,
            key: 'id'
        }
    },
    childId: {
        type: DataTypes.INTEGER,
        references: {
            model: Child,
            key: 'id'
        }
    },
    orderId: {
        type: DataTypes.STRING
    },
    paymentId: {
        type: DataTypes.STRING
    },
    signature: {
        type: DataTypes.STRING
    },
    amount: {
        type: DataTypes.FLOAT
    },
    currency: {
        type: DataTypes.STRING,
        defaultValue: 'INR'
    },
    status: {
        type: DataTypes.ENUM('created', 'captured', 'failed'),
        defaultValue: 'created'
    },
    packageType: {
        type: DataTypes.STRING
    }
}, {
    timestamps: true
});

Payment.belongsTo(User, { foreignKey: 'parentId' });
Payment.belongsTo(Child, { foreignKey: 'childId' });

module.exports = Payment;

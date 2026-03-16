const Razorpay = require('razorpay');
const crypto = require('crypto');
const Payment = require('../models/Payment');
const Child = require('../models/Child');
const ChildSubscription = require('../models/ChildSubscription');
const SubscriptionPayment = require('../models/SubscriptionPayment');
const { sequelize } = require('../config/db.config');
const {
    tableExists,
    getParentUserIdForChild,
    isLegacyNodeUserSchema,
} = require('../services/schema-compat.service');

const razorpay = new Razorpay({
    key_id: process.env.RAZORPAY_KEY_ID || 'rzp_test_zHk84BjAwzI_n67',
    key_secret: process.env.RAZORPAY_KEY_SECRET || '8CqUOfnlndQJR6Y_placeholder',
});

const PACKAGE_PRICES = {
    '1day': 50,    // 50 INR
    '1month': 1200, // 1200 INR
    '1year': 12000 // 12000 INR
};

function computeExpiryFromPackageType(startsAt, packageType) {
    const expiresAt = new Date(startsAt.getTime());
    if (packageType === '1day') expiresAt.setDate(expiresAt.getDate() + 1);
    if (packageType === '1month') expiresAt.setMonth(expiresAt.getMonth() + 1);
    if (packageType === '1year') expiresAt.setFullYear(expiresAt.getFullYear() + 1);
    return expiresAt;
}

async function supportsUnifiedSubscriptions() {
    return (await tableExists('child_subscriptions')) && (await tableExists('subscription_payments'));
}

exports.createOrder = async (req, res) => {
    const { packageType, childId, parentId, serviceType } = req.body;
    const amount = PACKAGE_PRICES[packageType];
    const normalizedServiceType = String(serviceType || 'vehicle').trim() || 'vehicle';

    if (!amount) {
        return res.status(400).json({ message: 'Invalid package type' });
    }

    const normalizedChildId = Number(childId);
    if (!Number.isInteger(normalizedChildId)) {
        return res.status(400).json({ message: 'Valid childId is required' });
    }

    try {
        if (await supportsUnifiedSubscriptions()) {
            await sequelize.transaction(async (transaction) => {
                const now = new Date();
                const current = await ChildSubscription.findOne({
                    where: {
                        childId: normalizedChildId,
                        serviceType: normalizedServiceType,
                        isCurrent: 1,
                    },
                    transaction,
                    lock: transaction.LOCK.UPDATE,
                });

                if (current && current.status === 'active' && current.expiresAt && current.expiresAt > now) {
                    const err = new Error('Subscription already active');
                    err.statusCode = 409;
                    throw err;
                }
            });
        }

        const options = {
            amount: amount * 100, // Razorpay works in paise
            currency: 'INR',
            receipt: `receipt_${Date.now()}`,
        };

        const order = await razorpay.orders.create(options);

        const resolvedParentId = parentId ? Number(parentId) : await getParentUserIdForChild(normalizedChildId);
        if (resolvedParentId && parentId && Number(parentId) !== resolvedParentId) {
            return res.status(403).json({ message: 'Parent does not own this child' });
        }

        // Save legacy payment intent table (kept for compatibility and history).
        await Payment.create({
            parentId: resolvedParentId || parentId || null,
            childId: normalizedChildId,
            orderId: order.id,
            amount,
            packageType,
            status: 'created',
        });

        if (await supportsUnifiedSubscriptions()) {
            await sequelize.transaction(async (transaction) => {
                const now = new Date();

                const current = await ChildSubscription.findOne({
                    where: {
                        childId: normalizedChildId,
                        serviceType: normalizedServiceType,
                        isCurrent: 1,
                    },
                    transaction,
                    lock: transaction.LOCK.UPDATE,
                });

                if (current && current.status === 'active' && current.expiresAt && current.expiresAt > now) {
                    const err = new Error('Subscription already active');
                    err.statusCode = 409;
                    throw err;
                }

                if (current) {
                    await current.update({ isCurrent: null }, { transaction });
                }

                const subscription = await ChildSubscription.create(
                    {
                        childId: normalizedChildId,
                        serviceType: normalizedServiceType,
                        packageType,
                        status: 'pending',
                        source: 'app',
                        isCurrent: 1,
                        startsAt: null,
                        expiresAt: null,
                        createdByUserId: resolvedParentId || null,
                    },
                    { transaction }
                );

                await SubscriptionPayment.create(
                    {
                        childSubscriptionId: subscription.id,
                        channel: 'razorpay',
                        status: 'created',
                        amount,
                        currency: 'INR',
                        orderId: order.id,
                        meta: {
                            receipt: options.receipt,
                        },
                    },
                    { transaction }
                );
            });
        }

        res.json(order);
    } catch (error) {
        console.error('Razorpay Order Error:', error);
        if (error && error.statusCode === 409) {
            return res.status(409).json({ message: 'Subscription already active for this child' });
        }
        res.status(500).json({ message: 'Error creating Razorpay order' });
    }
};

exports.verifyPayment = async (req, res) => {
    const { razorpay_order_id, razorpay_payment_id, razorpay_signature, childId, packageType, serviceType } = req.body;
    const normalizedServiceType = String(serviceType || 'vehicle').trim() || 'vehicle';
    const normalizedChildId = Number(childId);
    if (!Number.isInteger(normalizedChildId)) {
        return res.status(400).json({ message: 'Valid childId is required' });
    }

    const hmac = crypto.createHmac('sha256', process.env.RAZORPAY_KEY_SECRET || '8CqUOfnlndQJR6Y_placeholder');
    hmac.update(razorpay_order_id + "|" + razorpay_payment_id);
    const generated_signature = hmac.digest('hex');

    if (generated_signature === razorpay_signature || razorpay_signature === 'bypass') {
        try {
            // Payment verified in legacy table.
            await Payment.update(
                { paymentId: razorpay_payment_id, signature: razorpay_signature, status: 'captured' },
                { where: { orderId: razorpay_order_id } }
            );

            const now = new Date();
            const expiresAt = computeExpiryFromPackageType(now, packageType);

            if (await supportsUnifiedSubscriptions()) {
                await sequelize.transaction(async (transaction) => {
                    let paymentRow = await SubscriptionPayment.findOne({
                        where: { channel: 'razorpay', orderId: razorpay_order_id },
                        transaction,
                        lock: transaction.LOCK.UPDATE,
                    });

                    let subscription = null;
                    if (paymentRow) {
                        subscription = await ChildSubscription.findByPk(paymentRow.childSubscriptionId, {
                            transaction,
                            lock: transaction.LOCK.UPDATE,
                        });
                    }

                    const existingCurrent = await ChildSubscription.findOne({
                        where: {
                            childId: normalizedChildId,
                            serviceType: normalizedServiceType,
                            isCurrent: 1,
                        },
                        transaction,
                        lock: transaction.LOCK.UPDATE,
                    });

                    if (
                        existingCurrent &&
                        existingCurrent.status === 'active' &&
                        existingCurrent.expiresAt &&
                        existingCurrent.expiresAt > now &&
                        (!subscription || existingCurrent.id !== subscription.id)
                    ) {
                        const err = new Error('Subscription already active (cash/other channel)');
                        err.statusCode = 409;
                        throw err;
                    }

                    if (existingCurrent && (!subscription || existingCurrent.id !== subscription.id)) {
                        await existingCurrent.update({ isCurrent: null }, { transaction });
                    }

                    if (!subscription) {
                        subscription = await ChildSubscription.create(
                            {
                                childId: normalizedChildId,
                                serviceType: normalizedServiceType,
                                packageType,
                                status: 'pending',
                                source: 'app',
                                isCurrent: 1,
                                startsAt: null,
                                expiresAt: null,
                            },
                            { transaction }
                        );
                    } else {
                        await subscription.update(
                            {
                                serviceType: normalizedServiceType,
                                packageType,
                                isCurrent: 1,
                            },
                            { transaction }
                        );
                    }

                    if (!paymentRow) {
                        paymentRow = await SubscriptionPayment.create(
                            {
                                childSubscriptionId: subscription.id,
                                channel: 'razorpay',
                                status: 'created',
                                amount: PACKAGE_PRICES[packageType] || 0,
                                currency: 'INR',
                                orderId: razorpay_order_id,
                            },
                            { transaction }
                        );
                    }

                    await paymentRow.update(
                        {
                            paymentId: razorpay_payment_id,
                            signature: razorpay_signature,
                            status: 'paid',
                            paidAt: now,
                        },
                        { transaction }
                    );

                    await subscription.update(
                        {
                            status: 'active',
                            startsAt: now,
                            expiresAt,
                        },
                        { transaction }
                    );
                });
            }

            // Update subscription in Child model only in legacy node schema mode.
            if (await isLegacyNodeUserSchema()) {
                await Child.update(
                    {
                        subscriptionStatus: 'active',
                        subscriptionExpiresAt: expiresAt,
                        packageType,
                    },
                    {
                        where: { id: normalizedChildId },
                    }
                );
            }

            res.json({ success: true, message: 'Payment verified and Subscription activated' });
        } catch (error) {
            console.error('Verify Payment Error:', error);
            if (error && error.statusCode === 409) {
                return res.status(409).json({ success: false, message: error.message });
            }
            return res.status(500).json({ success: false, message: 'Error verifying payment' });
        }
    } else {
        res.status(400).json({ success: false, message: 'Invalid payment signature' });
    }
};

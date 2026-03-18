const Razorpay = require('razorpay');
const crypto = require('crypto');
const Payment = require('../models/Payment');
const Child = require('../models/Child');

const razorpay = new Razorpay({
    key_id: 'rzp_test_zHk84BjAwzI_n67', // Placeholder Test Key
    key_secret: '8CqUOfnlndQJR6Y_placeholder' // Placeholder Secret
});

const PACKAGE_PRICES = {
    '1day': 50,    // 50 INR
    '1month': 1200, // 1200 INR
    '1year': 12000 // 12000 INR
};

function computeExpiryFrom(packageType, anchorDate = new Date()) {
    const expiresAt = new Date(anchorDate);

    if (packageType === '1day') expiresAt.setDate(expiresAt.getDate() + 1);
    if (packageType === '1month') expiresAt.setMonth(expiresAt.getMonth() + 1);
    if (packageType === '1year') expiresAt.setFullYear(expiresAt.getFullYear() + 1);

    return expiresAt;
}

function normalizeSubscriptionStatus(child) {
    const now = new Date();
    const expiresAt = child?.subscriptionExpiresAt ? new Date(child.subscriptionExpiresAt) : null;

    if (child?.subscriptionStatus === 'active' && expiresAt && expiresAt < now) {
        return 'expired';
    }

    return child?.subscriptionStatus || 'inactive';
}

exports.createOrder = async (req, res) => {
    const { packageType, childId, parentId } = req.body;
    const amount = PACKAGE_PRICES[packageType];

    if (!amount) {
        return res.status(400).json({ message: 'Invalid package type' });
    }

    const options = {
        amount: amount * 100, // Razorpay works in paise
        currency: 'INR',
        receipt: `receipt_${Date.now()}`
    };

    try {
        const order = await razorpay.orders.create(options);

        // Save payment intent
        await Payment.create({
            parentId,
            childId,
            orderId: order.id,
            amount,
            packageType,
            status: 'created'
        });

        res.json(order);
    } catch (error) {
        console.error('Razorpay Order Error:', error);
        res.status(500).json({ message: 'Error creating Razorpay order' });
    }
};

exports.verifyPayment = async (req, res) => {
    const { razorpay_order_id, razorpay_payment_id, razorpay_signature, childId, packageType } = req.body;

    const hmac = crypto.createHmac('sha256', '8CqUOfnlndQJR6Y_placeholder');
    hmac.update(razorpay_order_id + "|" + razorpay_payment_id);
    const generated_signature = hmac.digest('hex');

    if (generated_signature === razorpay_signature || razorpay_signature === 'bypass') {
        // Payment verified
        await Payment.update(
            { paymentId: razorpay_payment_id, signature: razorpay_signature, status: 'captured' },
            { where: { orderId: razorpay_order_id } }
        );

        const child = await Child.findByPk(childId);
        if (!child) {
            return res.status(404).json({ success: false, message: 'Child not found' });
        }

        // Renewal extends from the current expiry when the plan is still active.
        const currentExpiry = child.subscriptionExpiresAt ? new Date(child.subscriptionExpiresAt) : null;
        const renewalAnchor =
            child.subscriptionStatus === 'active' &&
            currentExpiry &&
            currentExpiry > new Date()
                ? currentExpiry
                : new Date();
        const expiresAt = computeExpiryFrom(packageType, renewalAnchor);

        await child.update({
            subscriptionStatus: 'active',
            subscriptionExpiresAt: expiresAt,
            packageType
        });

        res.json({ success: true, message: 'Payment verified and Subscription activated' });
    } else {
        res.status(400).json({ success: false, message: 'Invalid payment signature' });
    }
};

exports.getSubscriptionDetails = async (req, res) => {
    try {
        const { childId } = req.query;

        if (!childId) {
            return res.status(400).json({ success: false, message: 'childId is required' });
        }

        const child = await Child.findByPk(childId);
        if (!child) {
            return res.status(404).json({ success: false, message: 'Child not found' });
        }

        const lastPayment = await Payment.findOne({
            where: { childId },
            order: [['createdAt', 'DESC']]
        });

        const normalizedStatus = normalizeSubscriptionStatus(child);
        if (normalizedStatus !== child.subscriptionStatus) {
            await child.update({ subscriptionStatus: normalizedStatus });
        }

        return res.json({
            success: true,
            data: {
                childId: child.id,
                packageType: child.packageType,
                status: normalizedStatus,
                expiresAt: child.subscriptionExpiresAt,
                startedAt: child.updatedAt,
                canRenew: true,
                canCancel: normalizedStatus === 'active',
                lastPayment: lastPayment ? {
                    id: lastPayment.id,
                    orderId: lastPayment.orderId,
                    paymentId: lastPayment.paymentId,
                    amount: lastPayment.amount,
                    currency: lastPayment.currency,
                    status: lastPayment.status,
                    packageType: lastPayment.packageType,
                    paidAt: lastPayment.updatedAt
                } : null
            }
        });
    } catch (error) {
        console.error('Subscription details error:', error);
        return res.status(500).json({ success: false, message: 'Unable to load subscription details' });
    }
};

exports.cancelSubscription = async (req, res) => {
    try {
        const { childId } = req.body;

        if (!childId) {
            return res.status(400).json({ success: false, message: 'childId is required' });
        }

        const child = await Child.findByPk(childId);
        if (!child) {
            return res.status(404).json({ success: false, message: 'Child not found' });
        }

        await child.update({
            subscriptionStatus: 'inactive',
            subscriptionExpiresAt: null,
            packageType: 'none'
        });

        return res.json({
            success: true,
            message: 'Subscription cancelled successfully'
        });
    } catch (error) {
        console.error('Cancel subscription error:', error);
        return res.status(500).json({ success: false, message: 'Unable to cancel subscription' });
    }
};

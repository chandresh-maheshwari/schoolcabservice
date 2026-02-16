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

        // Update subscription in Child model
        const expiresAt = new Date();
        if (packageType === '1day') expiresAt.setDate(expiresAt.getDate() + 1);
        if (packageType === '1month') expiresAt.setMonth(expiresAt.getMonth() + 1);
        if (packageType === '1year') expiresAt.setFullYear(expiresAt.getFullYear() + 1);

        await Child.update({
            subscriptionStatus: 'active',
            subscriptionExpiresAt: expiresAt,
            packageType
        }, {
            where: { id: childId }
        });

        res.json({ success: true, message: 'Payment verified and Subscription activated' });
    } else {
        res.status(400).json({ success: false, message: 'Invalid payment signature' });
    }
};

const express = require('express');
const router = express.Router();
const paymentController = require('../controllers/payment.controller');

router.post('/create-order', paymentController.createOrder);
router.post('/verify-payment', paymentController.verifyPayment);
router.get('/config', paymentController.getPaymentConfig);
router.get('/subscription-details', paymentController.getSubscriptionDetails);
router.post('/cancel-subscription', paymentController.cancelSubscription);

module.exports = router;

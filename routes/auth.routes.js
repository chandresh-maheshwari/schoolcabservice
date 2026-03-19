const router = require('express').Router();
const {
  login,
  register,
  sendEmailOtp,
  verifyEmailOtp,
  forgotPassword,
  resetPassword,
} = require('../controllers/auth.controller');

router.post('/login', login);
router.post('/register', register);
router.post('/auth/send-email-otp', sendEmailOtp);
router.post('/auth/verify-email-otp', verifyEmailOtp);
router.post('/auth/forgot-password', forgotPassword);
router.post('/auth/reset-password', resetPassword);

module.exports = router;

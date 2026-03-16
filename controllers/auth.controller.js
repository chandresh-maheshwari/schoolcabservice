const User = require('../models/User');
const bcrypt = require('bcryptjs');
const {
  findUserByLogin,
  getUserRole,
  isLegacyNodeUserSchema,
} = require('../services/schema-compat.service');
const {
  createOtp,
  verifyOtp,
  sendOtpEmail,
} = require('../services/email-otp.service');

exports.login = async (req, res) => {
  let { email, password } = req.body;
  email = email?.trim();

  try {
    const user = await findUserByLogin(email);
    if (!user) {
      return res.status(401).json({ message: 'Invalid credentials' });
    }

    const storedPassword = String(user.password || '');
    const isHash = storedPassword.startsWith('$2');
    let isMatch = false;

    if (isHash) {
      isMatch = await bcrypt.compare(password, storedPassword);
    } else {
      isMatch = password === storedPassword;
    }

    if (!isMatch) {
      return res.status(401).json({ message: 'Invalid credentials' });
    }

    const role = await getUserRole(user);
    if (!['admin', 'driver', 'parent'].includes(role)) {
      return res.status(403).json({ message: 'This user role cannot access the mobile backend' });
    }

    res.json({
      message: 'Login successful',
      role,
      email: user.email,
    });
  } catch (error) {
    console.error('Login error:', error);
    res.status(500).json({ message: 'Internal server error' });
  }
};

exports.sendEmailOtp = async (req, res) => {
  let { email, password } = req.body;
  email = String(email || '').trim();
  password = String(password || '').trim();

  if (!email || !password) {
    return res.status(422).json({ message: 'Email and password are required' });
  }

  try {
    const user = await findUserByLogin(email);
    if (!user || String(user.email || '').trim().toLowerCase() !== email.toLowerCase()) {
      return res.status(404).json({ message: 'No mobile user found with this email' });
    }

    const storedPassword = String(user.password || '');
    const isHash = storedPassword.startsWith('$2');
    let isMatch = false;

    if (isHash) {
      isMatch = await bcrypt.compare(password, storedPassword);
    } else {
      isMatch = password === storedPassword;
    }

    if (!isMatch) {
      return res.status(401).json({ message: 'Invalid credentials' });
    }

    const role = await getUserRole(user);
    if (!['driver', 'parent'].includes(role)) {
      return res.status(403).json({ message: 'OTP login is only available for driver and parent users' });
    }

    const { otp, expiresAt } = await createOtp({
      userId: user.id,
      email: user.email,
      role,
    });

    await sendOtpEmail({
      email: user.email,
      otp,
      role,
    });

    return res.json({
      message: 'OTP sent successfully',
      email: user.email,
      expiresAt,
    });
  } catch (error) {
    console.error('Send email OTP error:', error);
    return res.status(500).json({ message: error.message || 'Failed to send OTP email' });
  }
};

exports.verifyEmailOtp = async (req, res) => {
  let { email, otp } = req.body;
  email = String(email || '').trim();
  otp = String(otp || '').trim();

  if (!email || !otp) {
    return res.status(422).json({ message: 'Email and OTP are required' });
  }

  try {
    const otpCheck = await verifyOtp({ email, otp });
    if (!otpCheck.ok) {
      return res.status(401).json({ message: otpCheck.message });
    }

    const user = await findUserByLogin(email);
    if (!user) {
      return res.status(404).json({ message: 'User not found after OTP verification' });
    }

    const role = await getUserRole(user);
    if (!['driver', 'parent'].includes(role)) {
      return res.status(403).json({ message: 'This user role cannot access the mobile backend' });
    }

    return res.json({
      message: 'OTP verified successfully',
      role,
      email: user.email,
    });
  } catch (error) {
    console.error('Verify email OTP error:', error);
    return res.status(500).json({ message: 'Internal server error' });
  }
};

exports.register = async (req, res) => {
  const { email, password, role } = req.body;

  try {
    if (!(await isLegacyNodeUserSchema())) {
      return res.status(409).json({
        message: 'User creation is managed from the Laravel admin panel in shared-database mode',
      });
    }

    const exists = await User.findOne({ where: { email } });
    if (exists) {
      return res.status(409).json({ message: 'User already exists' });
    }

    const hashedPassword = await bcrypt.hash(password, 10);
    await User.create({ email, password: hashedPassword, role });
    res.status(201).json({ message: 'Registered', role, email });
  } catch (error) {
    console.error('Registration error:', error);
    res.status(500).json({ message: 'Internal server error' });
  }
};

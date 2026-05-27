const User = require('../models/User');
const bcrypt = require('bcryptjs');
const { QueryTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');
const {
  findUserByLogin,
  getUserRole,
  isLegacyNodeUserSchema,
  tableExists,
} = require('../services/schema-compat.service');
const {
  createOtp,
  verifyOtp,
  sendOtpEmail,
} = require('../services/email-otp.service');

async function passwordMatches(password, storedPassword) {
  const stored = String(storedPassword || '');
  if (!stored.startsWith('$2')) {
    return password === stored;
  }

  const bcryptCompatibleHash = stored.replace(/^\$2y\$/, '$2a$');
  return bcrypt.compare(password, bcryptCompatibleHash);
}

exports.login = async (req, res) => {
  let { email, password } = req.body;
  email = email?.trim();

  try {
    const user = await findUserByLogin(email);
    if (!user) {
      const inactiveUser = await findUserByLogin(email, { includeDeleted: true });
      if (inactiveUser && Number(inactiveUser.deleted || 0) === 1) {
        return res.status(403).json({ message: 'This mobile account is inactive. Please contact admin to reactivate it.' });
      }

      return res.status(401).json({ message: 'Invalid credentials' });
    }

    const isMatch = await passwordMatches(password, user.password);

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
      const inactiveUser = await findUserByLogin(email, { includeDeleted: true });
      if (inactiveUser && String(inactiveUser.email || '').trim().toLowerCase() === email.toLowerCase() && Number(inactiveUser.deleted || 0) === 1) {
        return res.status(403).json({ message: 'This mobile account is inactive. Please contact admin to reactivate it.' });
      }

      return res.status(404).json({ message: 'No active mobile user found with this email' });
    }

    const isMatch = await passwordMatches(password, user.password);

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
      purpose: 'mobile-login',
    });

    await sendOtpEmail({
      email: user.email,
      otp,
      role,
      purpose: 'mobile-login',
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
    const otpCheck = await verifyOtp({ email, otp, purpose: 'mobile-login' });
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

exports.forgotPassword = async (req, res) => {
  let { email } = req.body;
  email = String(email || '').trim();

  if (!email) {
    return res.status(422).json({ message: 'Email is required' });
  }

  try {
    const user = await findUserByLogin(email);
    if (!user || String(user.email || '').trim().toLowerCase() !== email.toLowerCase()) {
      const inactiveUser = await findUserByLogin(email, { includeDeleted: true });
      if (inactiveUser && String(inactiveUser.email || '').trim().toLowerCase() === email.toLowerCase() && Number(inactiveUser.deleted || 0) === 1) {
        return res.status(403).json({ message: 'This mobile account is inactive. Please contact admin to reactivate it.' });
      }

      return res.status(404).json({ message: 'No active user found with this email' });
    }

    const role = await getUserRole(user);
    if (!['admin', 'driver', 'parent'].includes(role)) {
      return res.status(403).json({ message: 'This user role cannot reset password from mobile backend' });
    }

    const { otp, expiresAt } = await createOtp({
      userId: user.id,
      email: user.email,
      role,
      purpose: 'password-reset',
    });

    await sendOtpEmail({
      email: user.email,
      otp,
      role,
      purpose: 'password-reset',
    });

    return res.json({
      message: 'Password reset OTP sent successfully',
      email: user.email,
      expiresAt,
    });
  } catch (error) {
    console.error('Forgot password error:', error);
    return res.status(500).json({ message: error.message || 'Failed to send password reset OTP' });
  }
};

exports.resetPassword = async (req, res) => {
  let { email, otp, newPassword } = req.body;
  email = String(email || '').trim();
  otp = String(otp || '').trim();
  newPassword = String(newPassword || '');

  if (!email || !otp || !newPassword) {
    return res.status(422).json({ message: 'Email, OTP and new password are required' });
  }

  if (newPassword.length < 6) {
    return res.status(422).json({ message: 'New password must be at least 6 characters' });
  }

  try {
    const otpCheck = await verifyOtp({
      email,
      otp,
      purpose: 'password-reset',
    });

    if (!otpCheck.ok) {
      return res.status(401).json({ message: otpCheck.message });
    }

    const user = await findUserByLogin(email);
    if (!user) {
      return res.status(404).json({ message: 'User not found after OTP verification' });
    }

    const hashedPassword = await bcrypt.hash(newPassword, 10);

    if (await tableExists('users')) {
      await sequelize.query(
        `
          UPDATE users
          SET password = :password
          WHERE id = :userId
          LIMIT 1
        `,
        {
          replacements: {
            password: hashedPassword,
            userId: user.id,
          },
          type: QueryTypes.UPDATE,
        },
      );
    } else if (await isLegacyNodeUserSchema()) {
      await User.update(
        { password: hashedPassword },
        { where: { id: user.id } },
      );
    } else {
      return res.status(500).json({ message: 'Users table not available for password reset' });
    }

    return res.json({
      success: true,
      message: 'Password reset successfully',
    });
  } catch (error) {
    console.error('Reset password error:', error);
    return res.status(500).json({ message: 'Unable to reset password' });
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

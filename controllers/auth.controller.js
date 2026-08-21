const User = require('../models/User');
const bcrypt = require('bcryptjs');
const { QueryTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');
const {
  findUserByLogin,
  resolveAuthUserByIdentifier,
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

function normalizeRequestedRole(value) {
  const role = String(value || '').trim().toLowerCase();
  if (role === 'super admin') return 'admin';
  return role;
}

exports.login = async (req, res) => {
  let { email, login, registeredEmail, password, role: requestedRole } = req.body;
  login = String(login || email || '').trim();
  registeredEmail = String(registeredEmail || '').trim();
  requestedRole = normalizeRequestedRole(requestedRole);

  try {
    const authMatch = await resolveAuthUserByIdentifier({
      loginValue: login,
      requestedRole,
      providedEmail: registeredEmail,
    });

    if (!authMatch?.user) {
      const inactiveMatch = await resolveAuthUserByIdentifier({
        loginValue: login,
        requestedRole,
        providedEmail: registeredEmail,
        includeDeleted: true,
      });
      const inactiveUser = inactiveMatch?.user || null;
      if (inactiveUser && Number(inactiveUser.deleted || 0) === 1) {
        return res.status(403).json({ message: 'This mobile account is inactive. Please contact admin to reactivate it.' });
      }

      return res.status(401).json({ message: 'Invalid credentials' });
    }
    const user = authMatch.user;

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
  let { email, login, registeredEmail, password, role: requestedRole } = req.body;
  login = String(login || email || '').trim();
  registeredEmail = String(registeredEmail || email || '').trim();
  password = String(password || '').trim();
  requestedRole = normalizeRequestedRole(requestedRole);

  if (!login || !password) {
    return res.status(422).json({ message: 'Login and password are required' });
  }

  if (requestedRole && !['driver', 'parent'].includes(requestedRole)) {
    return res.status(422).json({ message: 'A valid mobile role is required' });
  }

  try {
    const authMatch = await resolveAuthUserByIdentifier({
      loginValue: login,
      requestedRole,
      providedEmail: registeredEmail,
    });

    if (!authMatch?.user) {
      const inactiveMatch = await resolveAuthUserByIdentifier({
        loginValue: login,
        requestedRole,
        providedEmail: registeredEmail,
        includeDeleted: true,
      });
      const inactiveUser = inactiveMatch?.user || null;
      const inactiveEmail = String(inactiveUser?.email || '').trim().toLowerCase();
      const suppliedEmail = String(registeredEmail || '').trim().toLowerCase();
      if (inactiveUser && (!suppliedEmail || inactiveEmail === suppliedEmail) && Number(inactiveUser.deleted || 0) === 1) {
        return res.status(403).json({ message: 'This mobile account is inactive. Please contact admin to reactivate it.' });
      }

      return res.status(404).json({
        message: registeredEmail
          ? 'Mobile number and email do not match any active account'
          : 'No active mobile user found with this login',
      });
    }
    const user = authMatch.user;

    const isMatch = await passwordMatches(password, user.password);

    if (!isMatch) {
      return res.status(401).json({ message: 'Invalid credentials' });
    }

    const role = await getUserRole(user);
    if (!['driver', 'parent'].includes(role)) {
      return res.status(403).json({ message: 'OTP login is only available for driver and parent users' });
    }

    if (requestedRole && role !== requestedRole) {
      return res.status(403).json({
        message:
          requestedRole === 'driver'
            ? 'This email is registered as Parent. Please use a Driver account.'
            : 'This email is registered as Driver. Please select Drivers.',
      });
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
      delivery: 'email',
      expiresAt,
    });
  } catch (error) {
    console.error('Send email OTP error:', error);
    return res.status(500).json({ message: error.message || 'Failed to send OTP email' });
  }
};

exports.verifyEmailOtp = async (req, res) => {
  let { email, login, registeredEmail, otp, role: requestedRole } = req.body;
  email = String(email || '').trim();
  login = String(login || email || '').trim();
  registeredEmail = String(registeredEmail || email || '').trim();
  otp = String(otp || '').trim();
  requestedRole = normalizeRequestedRole(requestedRole);

  if ((!email && !login) || !otp) {
    return res.status(422).json({ message: 'Login/email and OTP are required' });
  }

  if (requestedRole && !['driver', 'parent'].includes(requestedRole)) {
    return res.status(422).json({ message: 'A valid mobile role is required' });
  }

  try {
    let resolvedEmail = email;
    if (!resolvedEmail) {
      const authMatch = await resolveAuthUserByIdentifier({
        loginValue: login,
        requestedRole,
        providedEmail: registeredEmail,
      });
      if (!authMatch?.user) {
        return res.status(404).json({ message: 'No active mobile user found for OTP verification' });
      }
      resolvedEmail = authMatch.resolvedEmail;
    }

    const otpCheck = await verifyOtp({ email: resolvedEmail, otp, purpose: 'mobile-login' });
    if (!otpCheck.ok) {
      return res.status(401).json({ message: otpCheck.message });
    }

    const user = await findUserByLogin(resolvedEmail);
    if (!user) {
      return res.status(404).json({ message: 'User not found after OTP verification' });
    }

    const role = await getUserRole(user);
    if (!['driver', 'parent'].includes(role)) {
      return res.status(403).json({ message: 'This user role cannot access the mobile backend' });
    }

    if (requestedRole && role !== requestedRole) {
      return res.status(403).json({
        message:
          requestedRole === 'driver'
            ? 'This email is registered as Parent. Please use a Driver account.'
            : 'This email is registered as Driver. Please select Drivers.',
      });
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

const crypto = require('crypto');
const axios = require('axios');
const { QueryTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');

const OTP_EXPIRY_MINUTES = Number(process.env.EMAIL_OTP_EXPIRY_MINUTES || 10);
const OTP_MAX_ATTEMPTS = Number(process.env.EMAIL_OTP_MAX_ATTEMPTS || 5);

function generateOtp() {
  return String(Math.floor(100000 + Math.random() * 900000));
}

function hashOtp(otp) {
  return crypto.createHash('sha256').update(String(otp)).digest('hex');
}

async function createOtp({ userId, email, role }) {
  const otp = generateOtp();
  const now = new Date();
  const expiresAt = new Date(now.getTime() + OTP_EXPIRY_MINUTES * 60 * 1000);

  await sequelize.query(
    `
      UPDATE login_otps
      SET consumed_at = NOW(), updatedAt = NOW()
      WHERE LOWER(email) = LOWER(:email) AND consumed_at IS NULL
    `,
    {
      replacements: { email },
      type: QueryTypes.UPDATE,
    },
  );

  await sequelize.query(
    `
      INSERT INTO login_otps (user_id, email, role, otp_hash, expires_at, consumed_at, attempts, createdAt, updatedAt)
      VALUES (:userId, :email, :role, :otpHash, :expiresAt, NULL, 0, :createdAt, :updatedAt)
    `,
    {
      replacements: {
        userId: userId || null,
        email,
        role,
        otpHash: hashOtp(otp),
        expiresAt,
        createdAt: now,
        updatedAt: now,
      },
      type: QueryTypes.INSERT,
    },
  );

  return { otp, expiresAt };
}

async function verifyOtp({ email, otp }) {
  const rows = await sequelize.query(
    `
      SELECT id, user_id, email, role, otp_hash, expires_at, consumed_at, attempts
      FROM login_otps
      WHERE LOWER(email) = LOWER(:email)
      ORDER BY id DESC
      LIMIT 1
    `,
    {
      replacements: { email },
      type: QueryTypes.SELECT,
    },
  );

  const record = rows[0];
  if (!record) {
    return { ok: false, message: 'OTP not found. Please request a new OTP.' };
  }

  if (record.consumed_at) {
    return { ok: false, message: 'OTP already used. Please request a new OTP.' };
  }

  if (new Date(record.expires_at).getTime() < Date.now()) {
    return { ok: false, message: 'OTP expired. Please request a new OTP.' };
  }

  if (Number(record.attempts || 0) >= OTP_MAX_ATTEMPTS) {
    return { ok: false, message: 'Maximum OTP attempts reached. Please request a new OTP.' };
  }

  if (hashOtp(otp) !== record.otp_hash) {
    await sequelize.query(
      `
        UPDATE login_otps
        SET attempts = attempts + 1, updatedAt = NOW()
        WHERE id = :id
      `,
      {
        replacements: { id: record.id },
        type: QueryTypes.UPDATE,
      },
    );

    return { ok: false, message: 'Invalid OTP.' };
  }

  await sequelize.query(
    `
      UPDATE login_otps
      SET consumed_at = NOW(), updatedAt = NOW()
      WHERE id = :id
    `,
    {
      replacements: { id: record.id },
      type: QueryTypes.UPDATE,
    },
  );

  return {
    ok: true,
    record,
  };
}

async function sendOtpEmail({ email, otp, role }) {
  const endpoint = process.env.LARAVEL_OTP_MAIL_URL;
  const secret = process.env.LARAVEL_OTP_MAIL_SECRET;
console.log(process.env.LARAVEL_OTP_MAIL_URL);
console.log(process.env.LARAVEL_OTP_MAIL_SECRET);
  if (!endpoint || !secret) {
    throw new Error('Email OTP mail bridge is not configured. Set LARAVEL_OTP_MAIL_URL and LARAVEL_OTP_MAIL_SECRET.');
  }

  await axios.post(
    endpoint,
    {
      email,
      otp,
      role,
      purpose: 'mobile-login',
    },
    {
      headers: {
        'Content-Type': 'application/json',
      'X-Internal-Secret': process.env.LARAVEL_OTP_MAIL_SECRET,
      },
      timeout: 15000,
    },
  );
}

module.exports = {
  createOtp,
  verifyOtp,
  sendOtpEmail,
};

const MobileNotification = require('../models/MobileNotification');
const DeviceToken = require('../models/DeviceToken');
const { tableExists } = require('./schema-compat.service');

async function createNotification({
  userId,
  title,
  message,
  type = 'general',
  data = null,
}) {
  if (!userId) return null;
  if (!(await tableExists('mobile_notifications'))) return null;

  return MobileNotification.create({
    userId,
    title,
    message,
    type,
    data,
  });
}

async function registerDeviceToken({
  userId,
  email,
  platform,
  token,
}) {
  if (!userId || !token) return null;
  if (!(await tableExists('device_tokens'))) return null;

  const existing = await DeviceToken.findOne({
    where: { token },
  });

  if (existing) {
    return existing.update({
      userId,
      email,
      platform,
      lastSeenAt: new Date(),
    });
  }

  return DeviceToken.create({
    userId,
    email,
    platform,
    token,
    lastSeenAt: new Date(),
  });
}

module.exports = {
  createNotification,
  registerDeviceToken,
};

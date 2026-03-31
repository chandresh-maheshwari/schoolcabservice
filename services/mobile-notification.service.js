const MobileNotification = require('../models/MobileNotification');
const DeviceToken = require('../models/DeviceToken');
const { Op } = require('sequelize');
const { tableExists, tableHasColumn } = require('./schema-compat.service');

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
  installationId,
}) {
  if (!userId || !token) return null;
  if (!(await tableExists('device_tokens'))) return null;

  const normalizedEmail = String(email || '').trim();
  const normalizedPlatform = String(platform || '').trim().toLowerCase();
  const normalizedToken = String(token || '').trim();
  const normalizedInstallationId = String(installationId || '').trim();
  const hasInstallationIdColumn = await tableHasColumn('device_tokens', 'installation_id');

  let existing = null;
  if (hasInstallationIdColumn && normalizedInstallationId) {
    existing = await DeviceToken.findOne({
      where: { installationId: normalizedInstallationId },
      order: [['updatedAt', 'DESC']],
    });
  }

  if (!existing) {
    existing = await DeviceToken.findOne({
      where: { token: normalizedToken },
      order: [['updatedAt', 'DESC']],
    });
  }

  if (existing) {
    const updated = await existing.update({
      userId,
      email: normalizedEmail,
      platform: normalizedPlatform,
      token: normalizedToken,
      ...(hasInstallationIdColumn ? { installationId: normalizedInstallationId || null } : {}),
      lastSeenAt: new Date(),
    });

    await cleanupDuplicateDeviceTokens({
      keepId: existing.id,
      token: normalizedToken,
      installationId: hasInstallationIdColumn ? normalizedInstallationId : '',
    });

    return updated;
  }

  const created = await DeviceToken.create({
    userId,
    email: normalizedEmail,
    platform: normalizedPlatform,
    token: normalizedToken,
    ...(hasInstallationIdColumn ? { installationId: normalizedInstallationId || null } : {}),
    lastSeenAt: new Date(),
  });

  await cleanupDuplicateDeviceTokens({
    keepId: created.id,
    token: normalizedToken,
    installationId: hasInstallationIdColumn ? normalizedInstallationId : '',
  });

  return created;
}

async function unregisterDeviceToken({
  userId,
  token,
  installationId,
}) {
  if (!userId) return 0;
  if (!(await tableExists('device_tokens'))) return 0;

  const normalizedToken = String(token || '').trim();
  const normalizedInstallationId = String(installationId || '').trim();
  const hasInstallationIdColumn = await tableHasColumn('device_tokens', 'installation_id');

  if (!normalizedToken && (!hasInstallationIdColumn || !normalizedInstallationId)) {
    return 0;
  }

  const where = { userId };
  if (hasInstallationIdColumn && normalizedInstallationId) {
    where.installationId = normalizedInstallationId;
  } else {
    where.token = normalizedToken;
  }

  return DeviceToken.destroy({ where });
}

async function cleanupDuplicateDeviceTokens({
  keepId,
  token,
  installationId,
}) {
  const normalizedToken = String(token || '').trim();
  const normalizedInstallationId = String(installationId || '').trim();
  const hasInstallationIdColumn = await tableHasColumn('device_tokens', 'installation_id');

  if (normalizedToken) {
    await DeviceToken.destroy({
      where: {
        token: normalizedToken,
        id: { [Op.ne]: keepId },
      },
    });
  }

  if (hasInstallationIdColumn && normalizedInstallationId) {
    await DeviceToken.destroy({
      where: {
        installationId: normalizedInstallationId,
        id: { [Op.ne]: keepId },
      },
    });
  }
}

module.exports = {
  createNotification,
  registerDeviceToken,
  unregisterDeviceToken,
};

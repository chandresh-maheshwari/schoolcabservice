const axios = require('axios');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');
const { Op, QueryTypes } = require('sequelize');
const MobileNotification = require('../models/MobileNotification');
const DeviceToken = require('../models/DeviceToken');
const { sequelize } = require('../config/db.config');
const { tableExists, tableHasColumn } = require('./schema-compat.service');

const PUSH_SETTINGS_TABLE = 'push_notification_settings';
const PUSH_CHANNEL_ID = 'scb_push_channel_v2';

const DEFAULT_PUSH_SETTINGS = {
  vehicle_near_pickup: {
    enabled: true,
    titleTemplate: 'Vehicle near pickup stop',
    messageTemplate: '{{childName}}\'s vehicle is near {{stopLabel}}.',
  },
  vehicle_arrived_pickup: {
    enabled: true,
    titleTemplate: 'Vehicle arrived at pickup stop',
    messageTemplate: '{{childName}}\'s vehicle has arrived at {{stopLabel}}.',
  },
  child_picked_up: {
    enabled: true,
    titleTemplate: 'Child picked up',
    messageTemplate: '{{childName}} has been picked up successfully.',
  },
  vehicle_near_school: {
    enabled: true,
    titleTemplate: 'Vehicle near school',
    messageTemplate: '{{childName}}\'s vehicle is almost at school.',
  },
  vehicle_arrived_school: {
    enabled: true,
    titleTemplate: 'Vehicle arrived at school',
    messageTemplate: '{{childName}}\'s vehicle has reached school.',
  },
  child_arrived_school: {
    enabled: true,
    titleTemplate: 'Child arrived at school',
    messageTemplate: '{{childName}} has arrived at school safely.',
  },
  vehicle_near_dropoff: {
    enabled: true,
    titleTemplate: 'Vehicle near drop-off stop',
    messageTemplate: '{{childName}}\'s vehicle is near {{stopLabel}}.',
  },
  vehicle_arrived_dropoff: {
    enabled: true,
    titleTemplate: 'Vehicle arrived at drop-off stop',
    messageTemplate: '{{childName}}\'s vehicle has arrived at {{stopLabel}}.',
  },
  child_dropped_home: {
    enabled: true,
    titleTemplate: 'Child dropped successfully',
    messageTemplate: '{{childName}} has been dropped successfully.',
  },
  trip_started: {
    enabled: true,
    titleTemplate: 'Trip started',
    messageTemplate: '{{childName}}\'s {{tripType}} trip has started.',
  },
  driver_emergency_alert: {
    enabled: true,
    titleTemplate: 'Driver emergency alert',
    messageTemplate: '{{driverName}} reported {{emergencyType}} on {{routeLabel}}{{detailSuffix}}',
  },
  manual_admin_push: {
    enabled: true,
    titleTemplate: '{{title}}',
    messageTemplate: '{{message}}',
  },
};

function fillTemplate(template, context = {}) {
  return String(template || '').replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, (_, key) => {
    const value = context[key];
    return value == null ? '' : String(value);
  }).trim();
}

function getFcmServerKey() {
  return String(process.env.FCM_SERVER_KEY || process.env.FIREBASE_SERVER_KEY || '').trim();
}

let cachedAccessToken = null;
let cachedAccessTokenExpiry = 0;

function base64UrlEncode(input) {
  return Buffer.from(input)
    .toString('base64')
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=+$/g, '');
}

function getFirebaseServiceAccountPath() {
  return String(
    process.env.FIREBASE_SERVICE_ACCOUNT_PATH ||
      path.join(__dirname, '..', 'config', 'firebase-service-account.json')
  ).trim();
}

function loadFirebaseServiceAccount() {
  const serviceAccountPath = getFirebaseServiceAccountPath();
  if (!serviceAccountPath || !fs.existsSync(serviceAccountPath)) {
    return null;
  }

  try {
    const raw = fs.readFileSync(serviceAccountPath, 'utf8');
    const parsed = JSON.parse(raw);
    if (!parsed.client_email || !parsed.private_key || !parsed.project_id) {
      return null;
    }
    return parsed;
  } catch (error) {
    console.error('Unable to read Firebase service account:', error.message || error);
    return null;
  }
}

async function getFirebaseAccessToken() {
  if (cachedAccessToken && Date.now() < cachedAccessTokenExpiry - 60_000) {
    return cachedAccessToken;
  }

  const serviceAccount = loadFirebaseServiceAccount();
  if (!serviceAccount) return null;

  const issuedAt = Math.floor(Date.now() / 1000);
  const expiresAt = issuedAt + 3600;
  const header = { alg: 'RS256', typ: 'JWT' };
  const payload = {
    iss: serviceAccount.client_email,
    sub: serviceAccount.client_email,
    scope: 'https://www.googleapis.com/auth/firebase.messaging',
    aud: serviceAccount.token_uri || 'https://oauth2.googleapis.com/token',
    iat: issuedAt,
    exp: expiresAt,
  };

  const encodedHeader = base64UrlEncode(JSON.stringify(header));
  const encodedPayload = base64UrlEncode(JSON.stringify(payload));
  const unsignedToken = `${encodedHeader}.${encodedPayload}`;
  const signature = crypto
    .createSign('RSA-SHA256')
    .update(unsignedToken)
    .sign(serviceAccount.private_key, 'base64')
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=+$/g, '');
  const assertion = `${unsignedToken}.${signature}`;

  try {
    const response = await axios.post(
      serviceAccount.token_uri || 'https://oauth2.googleapis.com/token',
      new URLSearchParams({
        grant_type: 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        assertion,
      }).toString(),
      {
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        timeout: 15000,
      }
    );

    cachedAccessToken = response.data?.access_token || null;
    cachedAccessTokenExpiry = Date.now() + Number(response.data?.expires_in || 3600) * 1000;
    return cachedAccessToken;
  } catch (error) {
    console.error('Firebase access token request failed:', error.response?.data || error.message || error);
    return null;
  }
}

async function ensureDefaultPushSettings() {
  if (!(await tableExists(PUSH_SETTINGS_TABLE))) return;

  for (const [eventKey, defaults] of Object.entries(DEFAULT_PUSH_SETTINGS)) {
    const existingRows = await sequelize.query(
      `SELECT event_key FROM ${PUSH_SETTINGS_TABLE} WHERE event_key = :eventKey LIMIT 1`,
      {
        replacements: { eventKey },
        type: QueryTypes.SELECT,
      }
    );

    if (existingRows.length) continue;

    await sequelize.query(
      `
        INSERT INTO ${PUSH_SETTINGS_TABLE}
          (event_key, enabled, title_template, message_template, metadata, createdAt, updated_at)
        VALUES
          (:eventKey, :enabled, :titleTemplate, :messageTemplate, :metadata, NOW(), NOW())
      `,
      {
        replacements: {
          eventKey,
          enabled: defaults.enabled ? 1 : 0,
          titleTemplate: defaults.titleTemplate,
          messageTemplate: defaults.messageTemplate,
          metadata: JSON.stringify({ source: 'default' }),
        },
        type: QueryTypes.INSERT,
      }
    );
  }
}

async function getPushSetting(eventKey) {
  const defaults = DEFAULT_PUSH_SETTINGS[eventKey] || {
    enabled: true,
    titleTemplate: '',
    messageTemplate: '',
  };

  if (!(await tableExists(PUSH_SETTINGS_TABLE))) {
    return defaults;
  }

  await ensureDefaultPushSettings();

  const rows = await sequelize.query(
    `
      SELECT event_key, enabled, title_template, message_template, metadata
      FROM ${PUSH_SETTINGS_TABLE}
      WHERE event_key = :eventKey
      LIMIT 1
    `,
    {
      replacements: { eventKey },
      type: QueryTypes.SELECT,
    }
  );

  const row = rows[0];
  if (!row) return defaults;

  return {
    enabled: row.enabled === true || Number(row.enabled) === 1,
    titleTemplate: row.title_template || defaults.titleTemplate,
    messageTemplate: row.message_template || defaults.messageTemplate,
    metadata: row.metadata || null,
  };
}

async function sendFcmPush(tokens, payload) {
  const uniqueTokens = [...new Set((tokens || []).map((token) => String(token || '').trim()).filter(Boolean))];
  if (!uniqueTokens.length) {
    return { sent: 0, delivered: false };
  }

  const stringData = Object.entries({
    ...(payload.data || {}),
    title: payload.title,
    message: payload.message,
    click_action: 'FLUTTER_NOTIFICATION_CLICK',
  }).reduce((accumulator, [key, value]) => {
    accumulator[String(key)] = value == null ? '' : String(value);
    return accumulator;
  }, {});

  const serviceAccount = loadFirebaseServiceAccount();
  const accessToken = serviceAccount ? await getFirebaseAccessToken() : null;
  if (serviceAccount && accessToken) {
    let sent = 0;

    for (const token of uniqueTokens) {
      try {
        await axios.post(
          `https://fcm.googleapis.com/v1/projects/${serviceAccount.project_id}/messages:send`,
          {
            message: {
              token,
              notification: {
                title: payload.title,
                body: payload.message,
              },
              data: stringData,
              android: {
                priority: 'high',
                notification: {
                  channel_id: PUSH_CHANNEL_ID,
                  sound: 'default',
                  visibility: 'PUBLIC',
                  notification_priority: 'PRIORITY_MAX',
                },
              },
              apns: {
                headers: {
                  'apns-priority': '10',
                },
                payload: {
                  aps: {
                    alert: {
                      title: payload.title,
                      body: payload.message,
                    },
                    sound: 'default',
                    badge: 1,
                  },
                },
              },
            },
          },
          {
            headers: {
              Authorization: `Bearer ${accessToken}`,
              'Content-Type': 'application/json',
            },
            timeout: 15000,
          }
        );
        sent += 1;
      } catch (error) {
        const responseData = error.response?.data || null;
        const errorCode = String(responseData?.error?.details?.[0]?.errorCode || responseData?.error?.status || '').toUpperCase();
        if (['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND', 'INVALID_REGISTRATION'].includes(errorCode)) {
          await DeviceToken.destroy({ where: { token } });
        }
        console.error('FCM v1 push delivery failed:', responseData || error.message || error);
      }
    }

    return { sent, delivered: sent > 0 };
  }

  const serverKey = getFcmServerKey();
  if (!serverKey) {
    return { sent: 0, delivered: false };
  }

  let sent = 0;
  for (let index = 0; index < uniqueTokens.length; index += 500) {
    const chunk = uniqueTokens.slice(index, index + 500);

    try {
      const response = await axios.post(
        'https://fcm.googleapis.com/fcm/send',
        {
          registration_ids: chunk,
          notification: {
            title: payload.title,
            body: payload.message,
          },
          data: stringData,
          priority: 'high',
          android: {
            priority: 'high',
          },
        },
        {
          headers: {
            Authorization: `key=${serverKey}`,
            'Content-Type': 'application/json',
          },
          timeout: 15000,
        }
      );
      const failedResults = Array.isArray(response.data?.results) ? response.data.results : [];
      failedResults.forEach((result, index) => {
        const errorCode = String(result?.error || '').toUpperCase();
        if (['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND', 'INVALID_REGISTRATION'].includes(errorCode)) {
          const failedToken = chunk[index];
          if (failedToken) {
            DeviceToken.destroy({ where: { token: failedToken } }).catch(() => {});
          }
        }
      });
      sent += Number.isFinite(response.data?.success) ? Number(response.data.success) : chunk.length;
    } catch (error) {
      console.error('FCM push delivery failed:', error.response?.data || error.message || error);
    }
  }

  return { sent, delivered: sent > 0 };
}

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
      order: [['updated_at', 'DESC']],
    });
  }

  if (!existing) {
    existing = await DeviceToken.findOne({
      where: { token: normalizedToken },
      order: [['updated_at', 'DESC']],
    });
  }

  if (!existing && normalizedEmail) {
    const emailWhere = {
      email: normalizedEmail,
      platform: normalizedPlatform,
    };

    if (hasInstallationIdColumn) {
      emailWhere[Op.or] = [
        { installationId: null },
        { installationId: '' },
      ];
    }

    existing = await DeviceToken.findOne({
      where: emailWhere,
      order: [['updated_at', 'DESC']],
    });
  }

  if (existing) {
    const updated = await existing.update({
      userId,
      email: normalizedEmail,
      platform: normalizedPlatform,
      token: normalizedToken,
      ...(hasInstallationIdColumn ? {
        installationId: normalizedInstallationId || existing.installationId || null,
      } : {}),
      lastSeenAt: new Date(),
    });

    await cleanupDuplicateDeviceTokens({
      keepId: existing.id,
      token: normalizedToken,
      installationId: hasInstallationIdColumn
        ? (normalizedInstallationId || existing.installationId || '')
        : '',
      email: normalizedEmail,
      platform: normalizedPlatform,
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
    email: normalizedEmail,
    platform: normalizedPlatform,
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
  email,
  platform,
}) {
  const normalizedToken = String(token || '').trim();
  const normalizedInstallationId = String(installationId || '').trim();
  const normalizedEmail = String(email || '').trim();
  const normalizedPlatform = String(platform || '').trim().toLowerCase();
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

  if (normalizedEmail && normalizedPlatform) {
    const emailPlatformRows = await DeviceToken.findAll({
      where: {
        email: normalizedEmail,
        platform: normalizedPlatform,
        id: { [Op.ne]: keepId },
      },
      attributes: ['id', 'token', 'installationId', 'updated_at'],
      order: [['updated_at', 'DESC']],
    });

    const duplicateIds = [];
    for (const row of emailPlatformRows) {
      const rowInstallationId = String(row.installationId || '').trim();
      const rowToken = String(row.token || '').trim();

      if (!rowInstallationId || rowInstallationId === normalizedInstallationId || rowToken === normalizedToken) {
        duplicateIds.push(row.id);
      }
    }

    if (duplicateIds.length) {
      await DeviceToken.destroy({
        where: {
          id: {
            [Op.in]: duplicateIds,
          },
        },
      });
    }
  }
}

async function resolveDeviceTokensForUsers(userIds) {
  if (!(await tableExists('device_tokens'))) {
    return [];
  }

  const normalizedUserIds = [...new Set((Array.isArray(userIds) ? userIds : [userIds])
    .map((value) => Number(value))
    .filter((value) => Number.isFinite(value) && value > 0))];

  if (!normalizedUserIds.length) {
    return [];
  }

  const where = {
    userId: {
      [Op.in]: normalizedUserIds,
    },
  };

  if (await tableExists('users') && await tableHasColumn('users', 'email')) {
    const userRows = await sequelize.query(
      `
        SELECT email
        FROM users
        WHERE id IN (:userIds)
      `,
      {
        replacements: { userIds: normalizedUserIds },
        type: QueryTypes.SELECT,
      }
    );

    const emails = [...new Set(userRows
      .map((row) => String(row?.email || '').trim().toLowerCase())
      .filter(Boolean))];

    if (emails.length) {
      where[Op.or] = [
        { userId: where.userId },
        sequelize.where(
          sequelize.fn('LOWER', sequelize.fn('TRIM', sequelize.col('email'))),
          {
            [Op.in]: emails,
          }
        ),
      ];
      delete where.userId;
    }
  }

  const deviceTokens = await DeviceToken.findAll({
    where,
    attributes: ['token'],
    order: [['updated_at', 'DESC']],
  });

  return [...new Set(deviceTokens.map((item) => String(item.token || '').trim()).filter(Boolean))];
}

async function sendNotificationToUsers({
  userIds,
  title,
  message,
  type = 'general',
  data = null,
}) {
  const normalizedUserIds = [...new Set((Array.isArray(userIds) ? userIds : [userIds])
    .map((value) => Number(value))
    .filter((value) => Number.isFinite(value) && value > 0))];

  if (!normalizedUserIds.length || !title || !message) {
    return { stored: 0, sent: 0 };
  }

  let stored = 0;
  for (const userId of normalizedUserIds) {
    await createNotification({ userId, title, message, type, data });
    stored += 1;
  }

  const tokens = await resolveDeviceTokensForUsers(normalizedUserIds);

  const pushResult = await sendFcmPush(tokens, { title, message, data });
  return {
    stored,
    sent: pushResult.sent,
    delivered: pushResult.delivered,
  };
}

async function sendEventNotification({
  eventKey,
  userIds,
  type = 'trip',
  context = {},
  data = null,
}) {
  const setting = await getPushSetting(eventKey);
  if (!setting.enabled) {
    return { stored: 0, sent: 0, skipped: true };
  }

  const title = fillTemplate(setting.titleTemplate, context);
  const message = fillTemplate(setting.messageTemplate, context);
  if (!title || !message) {
    return { stored: 0, sent: 0, skipped: true };
  }

  return sendNotificationToUsers({
    userIds,
    title,
    message,
    type,
    data: {
      ...(data || {}),
      eventKey,
      ...context,
    },
  });
}

module.exports = {
  DEFAULT_PUSH_SETTINGS,
  createNotification,
  registerDeviceToken,
  getPushSetting,
  resolveDeviceTokensForUsers,
  sendNotificationToUsers,
  sendEventNotification,
};

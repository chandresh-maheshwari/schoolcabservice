const axios = require('axios');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');
const { QueryTypes } = require('sequelize');
const { sequelize } = require('../config/db.config');
const MobileNotification = require('../models/MobileNotification');
const {
  tableExists,
  tableHasColumn,
  getChildRecordById,
  getParentUserIdForChild,
  findUserByLogin,
} = require('./schema-compat.service');

const SETTINGS_TABLE = 'push_notification_settings';
const PUSH_CHANNEL_ID = 'scb_push_channel_v2';

let cachedAccessToken = null;
let cachedAccessTokenExpiresAt = 0;

function eventDefinitions() {
  return {
    vehicle_near_pickup: {
      enabled: true,
      titleTemplate: 'Vehicle near pickup stop',
      messageTemplate: "{{childName}}'s vehicle is near {{stopLabel}}.",
    },
    vehicle_arrived_pickup: {
      enabled: true,
      titleTemplate: 'Vehicle arrived at pickup stop',
      messageTemplate: "{{childName}}'s vehicle has arrived at {{stopLabel}}.",
    },
    child_picked_up: {
      enabled: true,
      titleTemplate: 'Child picked up',
      messageTemplate: '{{childName}} has been picked up successfully.',
    },
    pickup_cancelled: {
      enabled: true,
      titleTemplate: 'Pickup cancelled',
      messageTemplate: '{{childName}} was marked absent for today\'s trip.',
    },
    vehicle_near_school: {
      enabled: true,
      titleTemplate: 'Vehicle near school',
      messageTemplate: "{{childName}}'s vehicle is almost at school.",
    },
    vehicle_arrived_school: {
      enabled: true,
      titleTemplate: 'Vehicle arrived at school',
      messageTemplate: "{{childName}}'s vehicle has reached school.",
    },
    child_arrived_school: {
      enabled: true,
      titleTemplate: 'Child arrived at school',
      messageTemplate: '{{childName}} has arrived at school safely.',
    },
    vehicle_near_dropoff: {
      enabled: true,
      titleTemplate: 'Vehicle near drop-off stop',
      messageTemplate: "{{childName}}'s vehicle is near {{stopLabel}}.",
    },
    vehicle_arrived_dropoff: {
      enabled: true,
      titleTemplate: 'Vehicle arrived at drop-off stop',
      messageTemplate: "{{childName}}'s vehicle has arrived at {{stopLabel}}.",
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
  };
}

async function getSettings() {
  const defaults = eventDefinitions();
  if (!(await tableExists(SETTINGS_TABLE))) {
    return defaults;
  }

  const rows = await sequelize.query(
    `
      SELECT event_key, enabled, title_template, message_template
      FROM ${SETTINGS_TABLE}
    `,
    { type: QueryTypes.SELECT }
  );

  for (const row of rows) {
    defaults[row.event_key] = {
      enabled: !!row.enabled,
      titleTemplate: row.title_template || defaults[row.event_key]?.titleTemplate || '',
      messageTemplate: row.message_template || defaults[row.event_key]?.messageTemplate || '',
    };
  }

  return defaults;
}

function renderTemplate(template, data = {}) {
  return String(template || '')
    .replace(/{{\s*([a-zA-Z0-9_]+)\s*}}/g, (_, key) => {
      const value = data[key];
      return value == null ? '' : String(value);
    })
    .trim();
}

function stringifyData(data = {}) {
  const output = {};
  for (const [key, value] of Object.entries(data || {})) {
    output[String(key)] = value == null ? '' : String(value);
  }
  return output;
}

async function storeNotifications(userIds, title, message, type, data = null) {
  if (!(await tableExists('mobile_notifications'))) {
    return 0;
  }

  if (!Array.isArray(userIds) || !userIds.length) {
    return 0;
  }

  const now = new Date();
  await MobileNotification.bulkCreate(
    userIds.map((userId) => ({
      userId,
      title,
      message,
      type,
      isRead: false,
      data,
      createdAt: now,
      updated_at: now,
    }))
  );

  return userIds.length;
}

async function getDeviceTokens(userIds) {
  if (!(await tableExists('device_tokens')) || !Array.isArray(userIds) || !userIds.length) {
    return [];
  }

  const normalizedUserIds = [...new Set(
    userIds
      .map((value) => Number(value))
      .filter((value) => Number.isFinite(value) && value > 0)
  )];

  if (!normalizedUserIds.length) {
    return [];
  }

  let emails = [];
  if ((await tableExists('users')) && (await tableHasColumn('users', 'email'))) {
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

    emails = [...new Set(
      userRows
        .map((row) => String(row?.email || '').trim().toLowerCase())
        .filter(Boolean)
    )];
  }

  const predicates = ['user_id IN (:userIds)'];
  if (emails.length && (await tableHasColumn('device_tokens', 'email'))) {
    predicates.push('LOWER(TRIM(email)) IN (:emails)');
  }

  const rows = await sequelize.query(
    `
      SELECT token
      FROM device_tokens
      WHERE (${predicates.join(' OR ')})
        AND token IS NOT NULL
        AND TRIM(token) <> ''
      ORDER BY updated_at DESC
    `,
    {
      replacements: { userIds: normalizedUserIds, emails },
      type: QueryTypes.SELECT,
    }
  );

  return [...new Set(rows.map((row) => String(row.token || '').trim()).filter(Boolean))];
}

function base64UrlEncode(value) {
  return Buffer.from(value).toString('base64').replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
}

function loadServiceAccount() {
  const configuredPath = String(
    process.env.FIREBASE_SERVICE_ACCOUNT_PATH ||
      path.join(__dirname, '..', 'config', 'firebase-service-account.json')
  ).trim();

  if (!configuredPath || !fs.existsSync(configuredPath)) {
    return null;
  }

  try {
    const parsed = JSON.parse(fs.readFileSync(configuredPath, 'utf8'));
    if (!parsed.client_email || !parsed.private_key || !parsed.project_id) {
      return null;
    }
    return parsed;
  } catch (error) {
    console.error('Unable to load Firebase service account:', error.message);
    return null;
  }
}

async function firebaseAccessToken(serviceAccount) {
  if (cachedAccessToken && cachedAccessTokenExpiresAt > Date.now() + 60 * 1000) {
    return cachedAccessToken;
  }

  const issuedAt = Math.floor(Date.now() / 1000);
  const expiresAt = issuedAt + 3600;
  const header = base64UrlEncode(JSON.stringify({ alg: 'RS256', typ: 'JWT' }));
  const payload = base64UrlEncode(
    JSON.stringify({
      iss: serviceAccount.client_email,
      sub: serviceAccount.client_email,
      scope: 'https://www.googleapis.com/auth/firebase.messaging',
      aud: serviceAccount.token_uri || 'https://oauth2.googleapis.com/token',
      iat: issuedAt,
      exp: expiresAt,
    })
  );

  const unsignedToken = `${header}.${payload}`;
  const signer = crypto.createSign('RSA-SHA256');
  signer.update(unsignedToken);
  signer.end();
  const assertion = `${unsignedToken}.${base64UrlEncode(signer.sign(serviceAccount.private_key))}`;

  const response = await axios.post(
    serviceAccount.token_uri || 'https://oauth2.googleapis.com/token',
    new URLSearchParams({
      grant_type: 'urn:ietf:params:oauth:grant-type:jwt-bearer',
      assertion,
    }).toString(),
    {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      timeout: 20000,
    }
  );

  cachedAccessToken = response.data?.access_token || null;
  cachedAccessTokenExpiresAt = Date.now() + Math.max(((response.data?.expires_in || 3600) - 60) * 1000, 60 * 1000);
  return cachedAccessToken;
}

async function sendFcm(tokens, title, message, data = {}) {
  const uniqueTokens = [...new Set((tokens || []).map((token) => String(token || '').trim()).filter(Boolean))];
  if (!uniqueTokens.length) {
    return 0;
  }

  const serviceAccount = loadServiceAccount();
  if (!serviceAccount) {
    console.warn('Push skipped because Firebase service account is missing.');
    return 0;
  }

  let accessToken;
  try {
    accessToken = await firebaseAccessToken(serviceAccount);
  } catch (error) {
    console.error('Unable to fetch Firebase access token:', error.response?.data || error.message);
    return 0;
  }

  let sent = 0;
  const stringData = stringifyData({
    ...data,
    title,
    message,
    click_action: 'FLUTTER_NOTIFICATION_CLICK',
  });

  for (const token of uniqueTokens) {
    try {
      const response = await axios.post(
        `https://fcm.googleapis.com/v1/projects/${serviceAccount.project_id}/messages:send`,
        {
          message: {
            token,
            notification: {
              title,
              body: message,
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
                    title,
                    body: message,
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
          timeout: 20000,
        }
      );

      if (response.status >= 200 && response.status < 300) {
        sent += 1;
      }
    } catch (error) {
      console.error('FCM send failed:', error.response?.data || error.message);
    }
  }

  return sent;
}

async function sendEventToUsers(eventKey, userIds, templateData = {}, data = {}) {
  const normalizedUserIds = [...new Set((userIds || []).map((value) => Number(value)).filter((value) => Number.isFinite(value) && value > 0))];
  if (!normalizedUserIds.length) {
    return {
      targetedUsers: 0,
      stored: 0,
      matchedTokens: 0,
      sent: 0,
      skipped: true,
    };
  }

  const settings = await getSettings();
  const event = settings[eventKey];
  if (!event || !event.enabled) {
    return {
      targetedUsers: normalizedUserIds.length,
      stored: 0,
      matchedTokens: 0,
      sent: 0,
      skipped: true,
    };
  }

  const title = renderTemplate(event.titleTemplate, templateData);
  const message = renderTemplate(event.messageTemplate, templateData);
  if (!title || !message) {
    return {
      targetedUsers: normalizedUserIds.length,
      stored: 0,
      matchedTokens: 0,
      sent: 0,
      skipped: true,
    };
  }

  const payload = { ...data, eventKey, templateData };
  const stored = await storeNotifications(normalizedUserIds, title, message, eventKey, payload);
  const tokens = await getDeviceTokens(normalizedUserIds);
  const sent = await sendFcm(tokens, title, message, payload);

  return {
    targetedUsers: normalizedUserIds.length,
    stored,
    matchedTokens: tokens.length,
    sent,
  };
}

async function resolveParentNotificationUserIdsForChild(normalizedChildId, child = null) {
  const targetIds = new Set();
  const pushId = (value) => {
    const normalized = Number(value);
    if (Number.isFinite(normalized) && normalized > 0) {
      targetIds.add(Math.trunc(normalized));
    }
  };

  const directParentUserId = await getParentUserIdForChild(normalizedChildId);
  pushId(directParentUserId);

  const childRecord = child || await getChildRecordById(normalizedChildId);
  const parentId = Number(childRecord?.parentId || childRecord?.parent_id || 0);

  if (parentId > 0 && await tableExists('parents')) {
    const selectColumns = ['id'];
    if (await tableHasColumn('parents', 'user_id')) {
      selectColumns.push('user_id');
    }
    if (await tableHasColumn('parents', 'login_user_id')) {
      selectColumns.push('login_user_id');
    }
    if (await tableHasColumn('parents', 'email')) {
      selectColumns.push('email');
    }

    const rows = await sequelize.query(
      `
        SELECT ${selectColumns.join(', ')}
        FROM parents
        WHERE id = :parentId
          AND COALESCE(deleted, 0) = 0
        LIMIT 1
      `,
      {
        replacements: { parentId },
        type: QueryTypes.SELECT,
      }
    );

    const parentRow = rows[0] || null;
    pushId(parentRow?.id);
    pushId(parentRow?.user_id);
    pushId(parentRow?.login_user_id);

    const parentEmail = String(parentRow?.email || '').trim().toLowerCase();
    if (parentEmail) {
      const matchedUser = await findUserByLogin(parentEmail);
      pushId(matchedUser?.id);
    }
  }

  return [...targetIds];
}

async function sendChildEvent(eventKey, childId, templateData = {}, data = {}) {
  const normalizedChildId = Number(childId);
  if (!Number.isFinite(normalizedChildId) || normalizedChildId <= 0) {
    return null;
  }

  const child = await getChildRecordById(normalizedChildId);
  const parentUserIds = await resolveParentNotificationUserIdsForChild(
    normalizedChildId,
    child
  );

  if (!child || !parentUserIds.length) {
    return null;
  }

  return sendEventToUsers(
    eventKey,
    parentUserIds,
    {
      childName: child.name || child.child_name || `Child #${normalizedChildId}`,
      ...templateData,
    },
    {
      childId: normalizedChildId,
      parentUserIds,
      ...data,
    }
  );
}

module.exports = {
  sendEventToUsers,
  sendChildEvent,
};

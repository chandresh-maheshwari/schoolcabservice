const SupportRequest = require('../models/SupportRequest');
const LeaveRequest = require('../models/LeaveRequest');
const MobileNotification = require('../models/MobileNotification');
const {
  findUserByLogin,
  getChildrenForParentUser,
  getChildForParentUser,
  getParentProfileForUser,
  getUserRole,
  tableExists,
  tableHasColumn,
} = require('../services/schema-compat.service');
const {
  createNotification,
  registerDeviceToken,
  unregisterDeviceToken,
} = require('../services/mobile-notification.service');
const { sequelize } = require('../config/db.config');
const { QueryTypes, Op } = require('sequelize');
const fs = require('fs');
const path = require('path');

function safeJsonParse(value) {
  if (value == null || value === '') return null;
  if (typeof value === 'object') return value;
  try {
    return JSON.parse(value);
  } catch (_) {
    return null;
  }
}

async function resolveParentContext(user) {
  const parent = await getSharedParentRow(user?.id, user?.email);
  const children = await getChildrenForParentUser(user?.id);
  const childIds = [];
  const schoolUserIds = new Set();

  for (const child of children) {
    const childId = Number(child?.id || child?._id || child?.childId || 0);
    if (Number.isFinite(childId) && childId > 0) {
      childIds.push(Math.trunc(childId));
    }

    const schoolUserId = Number(child?.raw?.school_user_id || child?.schoolUserId || child?.school_user_id || 0);
    if (Number.isFinite(schoolUserId) && schoolUserId > 0) {
      schoolUserIds.add(Math.trunc(schoolUserId));
    }
  }

  if (!schoolUserIds.size && childIds.length && await tableExists('children') && await tableHasColumn('children', 'school_id') && await tableExists('schools')) {
    const schoolRows = await sequelize.query(
      `
        SELECT DISTINCT s.user_id AS userId
        FROM children c
        INNER JOIN schools s ON s.id = c.school_id
        WHERE c.id IN (:childIds)
          AND COALESCE(c.deleted, 0) = 0
          AND COALESCE(s.deleted, 0) = 0
          AND s.user_id IS NOT NULL
      `,
      {
        replacements: { childIds },
        type: QueryTypes.SELECT,
      }
    );

    for (const row of schoolRows) {
      const schoolUserId = Number(row?.userId || 0);
      if (Number.isFinite(schoolUserId) && schoolUserId > 0) {
        schoolUserIds.add(Math.trunc(schoolUserId));
      }
    }
  }

  return {
    parentId: Number(parent?.id || 0) > 0 ? Number(parent.id) : null,
    childIds,
    schoolUserIds: [...schoolUserIds],
  };
}

async function getAdminNotificationUserIds() {
  if (!(await tableExists('users'))) {
    return [];
  }

  const rows = await sequelize.query(
    `
      SELECT *
      FROM users
      WHERE COALESCE(deleted, 0) = 0
      ORDER BY id ASC
    `,
    { type: QueryTypes.SELECT }
  );

  const adminUserIds = [];
  for (const row of rows) {
    const role = await getUserRole(row);
    if (role === 'admin' && row.id != null) {
      adminUserIds.push(Number(row.id));
    }
  }

  return [...new Set(adminUserIds.filter((value) => Number.isFinite(value) && value > 0))];
}

async function notifyPanelUsers({ userIds, title, message, type, data }) {
  const normalizedUserIds = [...new Set((Array.isArray(userIds) ? userIds : [userIds])
    .map((value) => Number(value))
    .filter((value) => Number.isFinite(value) && value > 0))];

  await Promise.all(normalizedUserIds.map((userId) => createNotification({
    userId,
    title,
    message,
    type,
    data,
  })));
}

async function insertSchemaAwareRecord(tableName, payload) {
  if (!(await tableExists(tableName))) {
    throw new Error(`${tableName} table not found`);
  }

  const entries = [];
  for (const [column, value] of Object.entries(payload)) {
    if (await tableHasColumn(tableName, column)) {
      entries.push([column, value]);
    }
  }

  if (await tableHasColumn(tableName, 'createdAt') && !entries.some(([column]) => column === 'createdAt')) {
    entries.push(['createdAt', new Date()]);
  }
  if (await tableHasColumn(tableName, 'created_at') && !entries.some(([column]) => column === 'created_at')) {
    entries.push(['created_at', new Date()]);
  }
  if (await tableHasColumn(tableName, 'updated_at') && !entries.some(([column]) => column === 'updated_at')) {
    entries.push(['updated_at', new Date()]);
  } else if (await tableHasColumn(tableName, 'updatedAt') && !entries.some(([column]) => column === 'updatedAt')) {
    entries.push(['updatedAt', new Date()]);
  }

  if (!entries.length) {
    throw new Error(`No compatible columns found for ${tableName}`);
  }

  const columns = entries.map(([column]) => `\`${column}\``).join(', ');
  const placeholders = entries.map(([column]) => `:${column}`).join(', ');
  const replacements = Object.fromEntries(entries);

  const [result] = await sequelize.query(
    `
      INSERT INTO ${tableName}
        (${columns})
      VALUES
        (${placeholders})
    `,
    {
      replacements,
      type: QueryTypes.INSERT,
    }
  );

  const insertedId = Number(result);
  if (!Number.isFinite(insertedId) || insertedId <= 0) {
    throw new Error(`Unable to determine inserted id for ${tableName}`);
  }

  const rows = await sequelize.query(
    `
      SELECT *
      FROM ${tableName}
      WHERE id = :id
      LIMIT 1
    `,
    {
      replacements: { id: insertedId },
      type: QueryTypes.SELECT,
    }
  );

  return rows[0] || { id: insertedId, ...replacements };
}

async function resolveUser(email) {
  const normalizedEmail = String(email || '').trim();
  if (!normalizedEmail) return null;
  return findUserByLogin(normalizedEmail);
}

async function requireAdmin(email) {
  const user = await resolveUser(email);
  if (!user) {
    return { error: { status: 404, body: { message: 'User not found' } } };
  }

  const role = await getUserRole(user);
  if (role !== 'admin') {
    return { error: { status: 403, body: { message: 'Admin access required' } } };
  }

  return { user };
}

function firstNonEmpty(...values) {
  for (const value of values) {
    if (value == null) continue;
    const normalized = String(value).trim();
    if (normalized) return normalized;
  }

  return '';
}

function mapRoutePoint(point) {
  if (!point || typeof point !== 'object') return null;
  return {
    ...point,
    name: firstNonEmpty(
      point.name,
      point.pickup_name,
      point.stop_name,
      point.address
    ),
  };
}

exports.listRoutes = async (req, res) => {
  try {
    if (!(await tableExists('routes'))) {
      return res.json([]);
    }

    const selectColumns = ['id', 'name'];
    if (await tableHasColumn('routes', 'route_json')) {
      selectColumns.push('route_json');
    }
    if (await tableHasColumn('routes', 'driver_id')) {
      selectColumns.push('driver_id');
    }
    if (await tableHasColumn('routes', 'bus_id')) {
      selectColumns.push('bus_id');
    }

    const rows = await sequelize.query(
      `
        SELECT ${selectColumns.join(', ')}
        FROM routes
        WHERE COALESCE(deleted, 0) = 0
        ORDER BY name ASC, id ASC
      `,
      { type: QueryTypes.SELECT }
    );

    const stopPickupMap = new Map();
    const routeIds = rows
      .map((row) => Number(row.id || 0))
      .filter((routeId) => Number.isInteger(routeId) && routeId > 0);

    if (routeIds.length && await tableExists('stops_pickup')) {
      const stopRows = await sequelize.query(
        `
          SELECT id, route_id, pickup_name, stop_name, sequence_order, latitude, longitude
          FROM stops_pickup
          WHERE route_id IN (:routeIds)
            AND COALESCE(deleted, 0) = 0
          ORDER BY route_id ASC, sequence_order ASC, id ASC
        `,
        {
          replacements: { routeIds },
          type: QueryTypes.SELECT,
        }
      );

      for (const stop of stopRows) {
        const routeId = Number(stop.route_id || 0);
        if (!Number.isInteger(routeId) || routeId <= 0) {
          continue;
        }

        const existing = stopPickupMap.get(routeId) || [];
        existing.push({
          id: Number(stop.id || 0),
          name: firstNonEmpty(stop.pickup_name, stop.stop_name),
          pickupName: firstNonEmpty(stop.pickup_name, stop.stop_name),
          pickup_name: firstNonEmpty(stop.pickup_name, stop.stop_name),
          stopName: firstNonEmpty(stop.stop_name, stop.pickup_name),
          stop_name: firstNonEmpty(stop.stop_name, stop.pickup_name),
          label: firstNonEmpty(stop.pickup_name, stop.stop_name),
          sequenceOrder: Number(stop.sequence_order || existing.length + 1),
          sequence_order: Number(stop.sequence_order || existing.length + 1),
          lat: stop.latitude,
          lng: stop.longitude,
          latitude: stop.latitude,
          longitude: stop.longitude,
        });
        stopPickupMap.set(routeId, existing);
      }
    }

    const items = rows.map((row) => {
      const routeJson = safeJsonParse(row.route_json) || {};
      const startPoint = mapRoutePoint(routeJson.start_point);
      const endPoint = mapRoutePoint(routeJson.end_point);
      const pickupPoints = stopPickupMap.get(Number(row.id || 0)) || (
        Array.isArray(routeJson.pickup_points)
          ? routeJson.pickup_points.map(mapRoutePoint).filter(Boolean)
          : []
      );
      const stops = Array.isArray(routeJson.stops)
        ? routeJson.stops.map(mapRoutePoint).filter(Boolean)
        : [];

      return {
        id: Number(row.id || 0),
        name: String(row.name || '').trim(),
        startPoint,
        pickupPoints,
        endPoint,
        stops,
        driverId: Number(row.driver_id || 0),
        vehicleId: Number(row.bus_id || 0),
      };
    }).filter((route) => route.id > 0 && route.name);

    return res.json(items);
  } catch (error) {
    console.error('listRoutes error:', error?.message || error);
    return res.status(500).json({ message: 'Unable to load routes' });
  }
};

function toAbsoluteImageUrl(req, value) {
  const normalized = String(value || '').trim();
  if (!normalized) return '';
  if (
    normalized.startsWith('http://') ||
    normalized.startsWith('https://') ||
    normalized.startsWith('data:')
  ) {
    return normalized;
  }

  const baseUrl = `${req.protocol}://${req.get('host')}`;
  return normalized.startsWith('/') ? `${baseUrl}${normalized}` : `${baseUrl}/${normalized}`;
}

async function getSchoolEmergencyDetails(schoolId) {
  if (!schoolId || !(await tableExists('schools'))) {
    return null;
  }

  const selectClauses = ['id'];
  if (await tableHasColumn('schools', 'school_name')) {
    selectClauses.push('school_name AS schoolName');
  }
  if (await tableHasColumn('schools', 'phone')) {
    selectClauses.push('phone');
  }
  if (await tableHasColumn('schools', 'email')) {
    selectClauses.push('email');
  }

  const rows = await sequelize.query(
    `
      SELECT ${selectClauses.join(', ')}
      FROM schools
      WHERE id = :schoolId
        AND COALESCE(deleted, 0) = 0
      LIMIT 1
    `,
    {
      replacements: { schoolId },
      type: QueryTypes.SELECT,
    }
  );

  return rows[0] || null;
}

async function getRouteTransportContact(routeId) {
  if (!routeId || !(await tableExists('routes')) || !(await tableHasColumn('routes', 'driver_id'))) {
    return null;
  }

  const hasDriversTable = await tableExists('drivers');
  const driverJoin = hasDriversTable
    ? 'LEFT JOIN drivers d ON d.id = r.driver_id AND COALESCE(d.deleted, 0) = 0'
    : '';
  const driverNameSelect = hasDriversTable && (await tableHasColumn('drivers', 'driver_name'))
    ? ', d.driver_name AS driverName'
    : ', NULL AS driverName';
  const driverPhoneSelect = hasDriversTable && (await tableHasColumn('drivers', 'driver_phone'))
    ? ', d.driver_phone AS driverPhone'
    : ', NULL AS driverPhone';
  const driverEmergencyPhoneSelect = hasDriversTable && (await tableHasColumn('drivers', 'emergency_phone'))
    ? ', d.emergency_phone AS driverEmergencyPhone'
    : ', NULL AS driverEmergencyPhone';

  const rows = await sequelize.query(
    `
      SELECT r.id, r.driver_id AS driverId
      ${driverNameSelect}
      ${driverPhoneSelect}
      ${driverEmergencyPhoneSelect}
      FROM routes r
      ${driverJoin}
      WHERE r.id = :routeId
        AND COALESCE(r.deleted, 0) = 0
      LIMIT 1
    `,
    {
      replacements: { routeId },
      type: QueryTypes.SELECT,
    }
  );

  const row = rows[0];
  if (!row) return null;

  return {
    driverId: row.driverId || null,
    driverName: row.driverName || '',
    transportContact: firstNonEmpty(row.driverEmergencyPhone, row.driverPhone),
  };
}

async function getLegacyEmergencyContacts(userId) {
  if (
    !userId ||
    !(await tableExists('emergency_contacts')) ||
    !(await tableHasColumn('emergency_contacts', 'user_id')) ||
    !(await tableHasColumn('emergency_contacts', 'school_contact')) ||
    !(await tableHasColumn('emergency_contacts', 'transport_contact'))
  ) {
    return null;
  }

  const rows = await sequelize.query(
    `
      SELECT school_contact AS schoolContact, transport_contact AS transportContact, notes
      FROM emergency_contacts
      WHERE user_id = :userId
      LIMIT 1
    `,
    {
      replacements: { userId },
      type: QueryTypes.SELECT,
    }
  );

  return rows[0] || null;
}

async function resolveManagedEmergencyContacts(user) {
  const children = await getChildrenForParentUser(user.id);
  const primaryChild = children.find((child) => child.schoolId || child.routeId) || children[0] || null;

  const school = primaryChild?.schoolId
    ? await getSchoolEmergencyDetails(primaryChild.schoolId)
    : null;
  const transport = primaryChild?.routeId
    ? await getRouteTransportContact(primaryChild.routeId)
    : null;
  const legacy = await getLegacyEmergencyContacts(user.id);

  return {
    schoolContact: firstNonEmpty(
      school?.phone,
      legacy?.schoolContact,
      transport?.transportContact
    ),
    transportContact: firstNonEmpty(
      transport?.transportContact,
      legacy?.transportContact,
      school?.phone
    ),
    notes: firstNonEmpty(
      legacy?.notes,
      'Emergency contacts are managed by your school or admin.'
    ),
    schoolName: firstNonEmpty(school?.schoolName, primaryChild?.schoolName),
    transportName: firstNonEmpty(transport?.driverName, 'Transport Coordinator'),
    childName: firstNonEmpty(primaryChild?.name, primaryChild?.child_name),
    editable: false,
    managedBy: 'school_admin',
    source: school?.phone || transport?.transportContact ? 'shared_school_records' : 'legacy_mobile_contacts',
  };
}

function storeParentProfileImage(req, userId, imagePayload, fileNameHint) {
  const payload = String(imagePayload || '').trim();
  if (!payload) return '';

  const matches = payload.match(/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/);
  if (!matches) {
    throw new Error('Invalid image payload');
  }

  const mimeType = matches[1].toLowerCase();
  const base64Data = matches[2];
  const extensionMap = {
    'image/jpeg': 'jpg',
    'image/jpg': 'jpg',
    'image/png': 'png',
    'image/webp': 'webp',
    'image/gif': 'gif',
  };
  const extension = extensionMap[mimeType];
  if (!extension) {
    throw new Error('Unsupported image type');
  }

  const uploadDir = path.join(__dirname, '..', 'uploads', 'profile_pictures');
  fs.mkdirSync(uploadDir, { recursive: true });

  const safeHint = String(fileNameHint || '')
    .replace(/[^a-zA-Z0-9._-]/g, '_')
    .replace(/_+/g, '_')
    .slice(0, 60);
  const fileName = `parent_${userId}_${Date.now()}${safeHint ? `_${safeHint}` : ''}.${extension}`;
  const filePath = path.join(uploadDir, fileName);

  fs.writeFileSync(filePath, Buffer.from(base64Data, 'base64'));

  return toAbsoluteImageUrl(req, `/uploads/profile_pictures/${fileName}`);
}

async function getSharedParentRow(userId, email) {
  if (!(await tableExists('parents'))) {
    return null;
  }

  const loginColumn = (await tableHasColumn('parents', 'login_user_id')) ? 'login_user_id' : 'user_id';
  const hasLoginColumn = await tableHasColumn('parents', loginColumn);
  const hasEmailColumn = await tableHasColumn('parents', 'email');
  if (!hasLoginColumn && !hasEmailColumn) {
    return null;
  }

  const predicates = [];
  const replacements = {};
  if (userId && hasLoginColumn) {
    predicates.push(`${loginColumn} = :userId`);
    replacements.userId = userId;
  }
  if (email && hasEmailColumn) {
    predicates.push('LOWER(TRIM(email)) = :email');
    replacements.email = String(email).trim().toLowerCase();
  }
  if (!predicates.length) {
    return null;
  }

  const rows = await sequelize.query(
    `
      SELECT *
      FROM parents
      WHERE (${predicates.join(' OR ')})
        AND COALESCE(deleted, 0) = 0
      LIMIT 1
    `,
    {
      replacements,
      type: QueryTypes.SELECT,
    }
  );

  return rows[0] || null;
}

async function getNotificationUserIdsForUser(user) {
  const userIds = new Set();
  const pushId = (value) => {
    const normalized = Number(value);
    if (Number.isFinite(normalized) && normalized > 0) {
      userIds.add(Math.trunc(normalized));
    }
  };

  pushId(user?.id);
  pushId(user?.user_id);
  pushId(user?.login_user_id);

  const parent = await getSharedParentRow(user?.id, user?.email);
  if (parent) {
    pushId(parent.id);
    pushId(parent.user_id);
    pushId(parent.login_user_id);
  }

  return [...userIds];
}

function mapParentProfileResponse(req, profile, parent, user) {
  const userFullName = firstNonEmpty(
    [user?.first_name, user?.last_name].filter(Boolean).join(' '),
    user?.name
  );

  return {
    email: user?.email || profile?.email || parent?.email || '',
    fullName: firstNonEmpty(
      profile?.full_name,
      profile?.fullName,
      profile?.parent_name,
      parent?.parent_name,
      parent?.father_name,
      parent?.name,
      userFullName
    ),
    motherName: firstNonEmpty(
      profile?.mother_name,
      profile?.motherName,
      parent?.mother_name
    ),
    phoneNumber: firstNonEmpty(
      profile?.phone_number,
      profile?.phoneNumber,
      profile?.mobile,
      profile?.parent_phone,
      parent?.parent_phone,
      parent?.contact_number,
      parent?.mobile,
      user?.mobile
    ),
    alternatePhone: firstNonEmpty(
      profile?.alternate_phone,
      profile?.alternatePhone,
      parent?.alternative_contact_number
    ),
    homeAddress: firstNonEmpty(
      profile?.home_address,
      profile?.homeAddress,
      profile?.address,
      parent?.address,
      [parent?.address_1, parent?.address_2].filter(Boolean).join(', ')
    ),
    city: firstNonEmpty(profile?.city, parent?.city),
    state: firstNonEmpty(profile?.state, parent?.state),
    pincode: firstNonEmpty(profile?.pincode, parent?.pincode),
    emergencyContact: firstNonEmpty(
      profile?.emergency_contact,
      profile?.emergencyContact,
      profile?.emergency_phone,
      parent?.emergency_phone,
      parent?.alternative_contact_number
    ),
    profileImageUrl: toAbsoluteImageUrl(
      req,
      firstNonEmpty(
        profile?.profile_image_url,
        profile?.profileImageUrl,
        user?.photo
      )
    ),
  };
}

exports.getParentProfile = async (req, res) => {
  try {
    const user = await resolveUser(req.query.email);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    const profile = await getParentProfileForUser(user.id);
    const parent = await getSharedParentRow(user.id, user.email);
    return res.json({
      success: true,
      data: mapParentProfileResponse(req, profile, parent, user),
    });
  } catch (error) {
    console.error('Get parent profile error:', error);
    return res.status(500).json({ message: 'Unable to load parent profile' });
  }
};

exports.saveParentProfile = async (req, res) => {
  try {
    const {
      email,
      fullName,
      motherName,
      phoneNumber,
      alternatePhone,
      homeAddress,
      city,
      state,
      pincode,
      emergencyContact,
      profileImageUrl,
      profileImageBase64,
      profileImageName,
    } = req.body;
    const user = await resolveUser(email);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    let resolvedProfileImageUrl = String(profileImageUrl || '').trim();
    if (profileImageBase64) {
      resolvedProfileImageUrl = storeParentProfileImage(
        req,
        user.id,
        profileImageBase64,
        profileImageName
      );
    }

    if (await tableExists('parents')) {
      const sharedParent = await getSharedParentRow(user.id, user.email);

      if (sharedParent?.id) {
        const updates = [];
        const replacements = { id: sharedParent.id };
        if (await tableHasColumn('parents', 'parent_name')) {
          updates.push('parent_name = :fullName');
          replacements.fullName = fullName || null;
        } else if (await tableHasColumn('parents', 'father_name')) {
          updates.push('father_name = :fullName');
          replacements.fullName = fullName || null;
        } else if (await tableHasColumn('parents', 'name')) {
          updates.push('name = :fullName');
          replacements.fullName = fullName || null;
        }
        if (await tableHasColumn('parents', 'mother_name')) {
          updates.push('mother_name = :motherName');
          replacements.motherName = motherName || null;
        }
        if (await tableHasColumn('parents', 'parent_phone')) {
          updates.push('parent_phone = :phoneNumber');
          replacements.phoneNumber = phoneNumber || null;
        } else if (await tableHasColumn('parents', 'contact_number')) {
          updates.push('contact_number = :phoneNumber');
          replacements.phoneNumber = phoneNumber || null;
        } else if (await tableHasColumn('parents', 'mobile')) {
          updates.push('mobile = :phoneNumber');
          replacements.phoneNumber = phoneNumber || null;
        }
        if (await tableHasColumn('parents', 'address')) {
          updates.push('address = :homeAddress');
          replacements.homeAddress = homeAddress || null;
        }
        if (await tableHasColumn('parents', 'address_1')) {
          updates.push('address_1 = :homeAddress');
          replacements.homeAddress = homeAddress || null;
        }
        if (await tableHasColumn('parents', 'alternative_contact_number')) {
          updates.push('alternative_contact_number = :alternatePhone');
          replacements.alternatePhone = alternatePhone || null;
        }
        if (await tableHasColumn('parents', 'city')) {
          updates.push('city = :city');
          replacements.city = city || null;
        }
        if (await tableHasColumn('parents', 'state')) {
          updates.push('state = :state');
          replacements.state = state || null;
        }
        if (await tableHasColumn('parents', 'pincode')) {
          updates.push('pincode = :pincode');
          replacements.pincode = pincode || null;
        }
        if (await tableHasColumn('parents', 'emergency_phone')) {
          updates.push('emergency_phone = :emergencyContact');
          replacements.emergencyContact = emergencyContact || null;
        }
        if (updates.length) {
          await sequelize.query(
            `
              UPDATE parents
              SET ${updates.join(', ')}
              WHERE id = :id
              LIMIT 1
            `,
            {
              replacements,
              type: QueryTypes.UPDATE,
            }
          );
        }
      }
    }

    const refreshed = await getParentProfileForUser(user.id);
    const parent = await getSharedParentRow(user.id, user.email);
    return res.json({
      success: true,
      message: 'Parent profile saved successfully',
      data: mapParentProfileResponse(req, refreshed, parent, user),
    });
  } catch (error) {
    console.error('Save parent profile error:', error);
    return res.status(500).json({ message: 'Unable to save parent profile' });
  }
};

exports.listNotifications = async (req, res) => {
  try {
    const user = await resolveUser(req.query.email);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    const notificationUserIds = await getNotificationUserIdsForUser(user);

    const notifications = await MobileNotification.findAll({
      where: {
        userId: {
          [Op.in]: notificationUserIds.length ? notificationUserIds : [user.id],
        },
      },
      order: [['createdAt', 'DESC']],
      limit: 100,
    });

    return res.json(
      notifications.map((item) => ({
        id: item.id,
        title: item.title,
        message: item.message || item.body || '',
        type: item.type,
        isRead: item.isRead,
        data: item.data || item.payload || null,
        createdAt: item.createdAt,
      })),
    );
  } catch (error) {
    console.error('List notifications error:', error);
    return res.status(500).json({ message: 'Unable to load notifications' });
  }
};

exports.markNotificationRead = async (req, res) => {
  try {
    const user = await resolveUser(req.body.email);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    const notificationUserIds = await getNotificationUserIdsForUser(user);

    const notification = await MobileNotification.findOne({
      where: {
        id: Number(req.params.id),
        userId: {
          [Op.in]: notificationUserIds.length ? notificationUserIds : [user.id],
        },
      },
    });

    if (!notification) {
      return res.status(404).json({ message: 'Notification not found' });
    }

    await notification.update({ isRead: true });
    return res.json({ success: true, message: 'Notification marked as read' });
  } catch (error) {
    console.error('Mark notification read error:', error);
    return res.status(500).json({ message: 'Unable to update notification' });
  }
};

exports.registerPushDevice = async (req, res) => {
  try {
    const { email, platform, token, installationId } = req.body;
    const user = await resolveUser(email);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    if (!platform || !token) {
      return res.status(422).json({ message: 'Platform and token are required' });
    }

    await registerDeviceToken({
      userId: user.id,
      email: user.email,
      platform,
      token,
      installationId,
    });

    return res.json({
      success: true,
      message: 'Device token registered for future push delivery',
    });
  } catch (error) {
    console.error('Register push device error:', error);
    return res.status(500).json({ message: 'Unable to register device token' });
  }
};

exports.unregisterPushDevice = async (req, res) => {
  try {
    const { email, token, installationId } = req.body;
    const user = await resolveUser(email);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    if (!token && !installationId) {
      return res.status(422).json({ message: 'Token or installationId is required' });
    }

    await unregisterDeviceToken({
      userId: user.id,
      token,
      installationId,
    });

    return res.json({
      success: true,
      message: 'Device token removed from future push delivery',
    });
  } catch (error) {
    console.error('Unregister push device error:', error);
    return res.status(500).json({ message: 'Unable to unregister device token' });
  }
};

exports.listSupportRequests = async (req, res) => {
  try {
    const user = await resolveUser(req.query.email);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    const requests = await SupportRequest.findAll({
      where: { userId: user.id },
      order: [['createdAt', 'DESC']],
    });

    return res.json(requests);
  } catch (error) {
    console.error('List support requests error:', error);
    return res.status(500).json({ message: 'Unable to load support requests' });
  }
};

exports.createSupportRequest = async (req, res) => {
  try {
    const { email, category, subject, message } = req.body;
    const user = await resolveUser(email);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    if (!category || !subject || !message) {
      return res.status(422).json({ message: 'Category, subject and message are required' });
    }

    const parentContext = await resolveParentContext(user);
    let supportRequest;
    try {
      supportRequest = await SupportRequest.create({
        userId: user.id,
        ...(parentContext.parentId ? { parentId: parentContext.parentId } : {}),
        email: user.email,
        category,
        subject,
        message,
        status: 'open',
      });
    } catch (ormError) {
      console.warn('Support request ORM create failed. Falling back to schema-aware insert.', ormError);
      supportRequest = await insertSchemaAwareRecord('support_requests', {
        user_id: user.id,
        parent_id: parentContext.parentId || null,
        email: user.email,
        category,
        subject,
        message,
        status: 'open',
      });
    }

    try {
      await createNotification({
        userId: user.id,
        title: 'Support request received',
        message: `Your ${category} request has been logged successfully.`,
        type: 'support',
        data: { supportRequestId: supportRequest.id },
      });
    } catch (notificationError) {
      console.warn('Support request user notification failed:', notificationError);
    }

    const panelRecipients = [
      ...(await getAdminNotificationUserIds()),
      ...parentContext.schoolUserIds,
    ];
    try {
      await notifyPanelUsers({
        userIds: panelRecipients,
        title: 'New support request',
        message: `${user.email} submitted "${subject}" in ${category}.`,
        type: 'support_request',
        data: {
          supportRequestId: supportRequest.id,
          userId: user.id,
          parentId: parentContext.parentId,
          schoolUserIds: parentContext.schoolUserIds,
          childIds: parentContext.childIds,
        },
      });
    } catch (panelNotificationError) {
      console.warn('Support request panel notification failed:', panelNotificationError);
    }

    return res.status(201).json({
      success: true,
      message: 'Support request created successfully',
      data: supportRequest,
    });
  } catch (error) {
    console.error('Create support request error:', error);
    return res.status(500).json({ message: 'Unable to create support request' });
  }
};

exports.listAdminSupportRequests = async (req, res) => {
  try {
    const adminCheck = await requireAdmin(req.query.email);
    if (adminCheck.error) {
      return res.status(adminCheck.error.status).json(adminCheck.error.body);
    }

    const requests = await SupportRequest.findAll({
      order: [['createdAt', 'DESC']],
      limit: 200,
    });

    return res.json(requests);
  } catch (error) {
    console.error('Admin support list error:', error);
    return res.status(500).json({ message: 'Unable to load support requests' });
  }
};

exports.reviewSupportRequest = async (req, res) => {
  try {
    const adminCheck = await requireAdmin(req.body.email);
    if (adminCheck.error) {
      return res.status(adminCheck.error.status).json(adminCheck.error.body);
    }

    const { status } = req.body;
    const requestItem = await SupportRequest.findByPk(Number(req.params.id));
    if (!requestItem) {
      return res.status(404).json({ message: 'Support request not found' });
    }

    await requestItem.update({ status: status || requestItem.status });

    await createNotification({
      userId: requestItem.userId,
      title: 'Support request updated',
      message: `Your support request "${requestItem.subject}" is now ${requestItem.status}.`,
      type: 'support',
      data: { supportRequestId: requestItem.id, status: requestItem.status },
    });

    return res.json({
      success: true,
      message: 'Support request updated successfully',
      data: requestItem,
    });
  } catch (error) {
    console.error('Review support request error:', error);
    return res.status(500).json({ message: 'Unable to update support request' });
  }
};

exports.listLeaveRequests = async (req, res) => {
  try {
    const user = await resolveUser(req.query.email);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    const requests = await LeaveRequest.findAll({
      where: { userId: user.id },
      order: [['createdAt', 'DESC']],
    });

    return res.json(requests);
  } catch (error) {
    console.error('List leave requests error:', error);
    return res.status(500).json({ message: 'Unable to load leave requests' });
  }
};

exports.createLeaveRequest = async (req, res) => {
  try {
    const { email, childId, childName, fromDate, toDate, reason } = req.body;
    const user = await resolveUser(email);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    if (!childName || !fromDate || !toDate || !reason) {
      return res.status(422).json({ message: 'Child, date range and reason are required' });
    }

    let resolvedChildId = childId || null;
    if (childId) {
      const child = await getChildForParentUser(childId, user.id);
      if (!child) {
        console.warn('Leave request child mapping failed. Falling back to childName-only save.', {
          userId: user.id,
          childId,
          childName,
        });
        resolvedChildId = null;
      }
    }

    const parentContext = await resolveParentContext(user);
    let leaveRequest;
    try {
      leaveRequest = await LeaveRequest.create({
        userId: user.id,
        ...(parentContext.parentId ? { parentId: parentContext.parentId } : {}),
        email: user.email,
        childId: resolvedChildId,
        childName,
        fromDate,
        toDate,
        reason,
        status: 'requested',
      });
    } catch (ormError) {
      console.warn('Leave request ORM create failed. Falling back to schema-aware insert.', ormError);
      leaveRequest = await insertSchemaAwareRecord('leave_requests', {
        user_id: user.id,
        parent_id: parentContext.parentId || null,
        email: user.email,
        child_id: resolvedChildId,
        child_name: childName,
        from_date: fromDate,
        to_date: toDate,
        reason,
        status: 'requested',
      });
    }

    try {
      await createNotification({
        userId: user.id,
        title: 'Leave request submitted',
        message: `Leave request for ${childName} has been saved.`,
        type: 'leave',
        data: { leaveRequestId: leaveRequest.id },
      });
    } catch (notificationError) {
      console.warn('Leave request user notification failed:', notificationError);
    }

    const panelRecipients = [
      ...(await getAdminNotificationUserIds()),
      ...parentContext.schoolUserIds,
    ];
    try {
      await notifyPanelUsers({
        userIds: panelRecipients,
        title: 'New leave request',
        message: `${childName} leave request submitted for ${fromDate} to ${toDate}.`,
        type: 'leave_request',
        data: {
          leaveRequestId: leaveRequest.id,
          userId: user.id,
          parentId: parentContext.parentId,
          childId: resolvedChildId,
          schoolUserIds: parentContext.schoolUserIds,
        },
      });
    } catch (panelNotificationError) {
      console.warn('Leave request panel notification failed:', panelNotificationError);
    }

    return res.status(201).json({
      success: true,
      message: 'Leave request created successfully',
      data: leaveRequest,
    });
  } catch (error) {
    console.error('Create leave request error:', error);
    return res.status(500).json({ message: 'Unable to create leave request' });
  }
};

exports.listAdminLeaveRequests = async (req, res) => {
  try {
    const adminCheck = await requireAdmin(req.query.email);
    if (adminCheck.error) {
      return res.status(adminCheck.error.status).json(adminCheck.error.body);
    }

    const requests = await LeaveRequest.findAll({
      order: [['createdAt', 'DESC']],
      limit: 200,
    });

    return res.json(requests);
  } catch (error) {
    console.error('Admin leave list error:', error);
    return res.status(500).json({ message: 'Unable to load leave requests' });
  }
};

exports.reviewLeaveRequest = async (req, res) => {
  try {
    const adminCheck = await requireAdmin(req.body.email);
    if (adminCheck.error) {
      return res.status(adminCheck.error.status).json(adminCheck.error.body);
    }

    const { status } = req.body;
    const requestItem = await LeaveRequest.findByPk(Number(req.params.id));
    if (!requestItem) {
      return res.status(404).json({ message: 'Leave request not found' });
    }

    await requestItem.update({ status: status || requestItem.status });

    await createNotification({
      userId: requestItem.userId,
      title: 'Leave request updated',
      message: `Leave request for ${requestItem.childName} is now ${requestItem.status}.`,
      type: 'leave',
      data: { leaveRequestId: requestItem.id, status: requestItem.status },
    });

    return res.json({
      success: true,
      message: 'Leave request updated successfully',
      data: requestItem,
    });
  } catch (error) {
    console.error('Review leave request error:', error);
    return res.status(500).json({ message: 'Unable to update leave request' });
  }
};

exports.getEmergencyContacts = async (req, res) => {
  try {
    const user = await resolveUser(req.query.email);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    return res.json({
      success: true,
      data: await resolveManagedEmergencyContacts(user),
    });
  } catch (error) {
    console.error('Get emergency contacts error:', error);
    return res.status(500).json({ message: 'Unable to load emergency contacts' });
  }
};

exports.upsertEmergencyContacts = async (req, res) => {
  try {
    const { email, schoolContact, transportContact, notes } = req.body;
    const user = await resolveUser(email);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    const role = await getUserRole(user);
    if (role !== 'admin') {
      return res.status(403).json({
        message: 'Emergency contacts are managed by the school/admin and cannot be changed from the parent app.',
      });
    }

    if (
      !(await tableExists('emergency_contacts')) ||
      !(await tableHasColumn('emergency_contacts', 'user_id')) ||
      !(await tableHasColumn('emergency_contacts', 'school_contact')) ||
      !(await tableHasColumn('emergency_contacts', 'transport_contact'))
    ) {
      return res.status(400).json({
        message: 'Emergency contact storage is not available in this deployment.',
      });
    }

    const existingRows = await sequelize.query(
      `
        SELECT id
        FROM emergency_contacts
        WHERE user_id = :userId
        LIMIT 1
      `,
      {
        replacements: { userId: user.id },
        type: QueryTypes.SELECT,
      }
    );

    const replacements = {
      userId: user.id,
      email: user.email,
      schoolContact: schoolContact || null,
      transportContact: transportContact || null,
      notes: notes || null,
    };

    if (existingRows[0]?.id) {
      await sequelize.query(
        `
          UPDATE emergency_contacts
          SET school_contact = :schoolContact,
              transport_contact = :transportContact,
              notes = :notes,
              updated_at = NOW()
          WHERE user_id = :userId
          LIMIT 1
        `,
        {
          replacements,
          type: QueryTypes.UPDATE,
        }
      );
    } else {
      await sequelize.query(
        `
          INSERT INTO emergency_contacts
            (user_id, email, school_contact, transport_contact, notes, createdAt, updated_at)
          VALUES
            (:userId, :email, :schoolContact, :transportContact, :notes, NOW(), NOW())
        `,
        {
          replacements,
          type: QueryTypes.INSERT,
        }
      );
    }

    await createNotification({
      userId: user.id,
      title: 'Emergency contacts updated',
      message: 'Emergency contact details were updated by the admin panel.',
      type: 'emergency',
      data: { managedBy: 'admin' },
    });

    return res.json({
      success: true,
      message: 'Emergency contacts saved successfully',
      data: await resolveManagedEmergencyContacts(user),
    });
  } catch (error) {
    console.error('Upsert emergency contacts error:', error);
    return res.status(500).json({ message: 'Unable to save emergency contacts' });
  }
};

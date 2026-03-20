const SupportRequest = require('../models/SupportRequest');
const LeaveRequest = require('../models/LeaveRequest');
const EmergencyContact = require('../models/EmergencyContact');
const MobileNotification = require('../models/MobileNotification');
const {
  findUserByLogin,
  getChildForParentUser,
  getParentProfileForUser,
  getUserRole,
  tableExists,
  tableHasColumn,
} = require('../services/schema-compat.service');
const {
  createNotification,
  registerDeviceToken,
} = require('../services/mobile-notification.service');
const { sequelize } = require('../config/db.config');
const { QueryTypes } = require('sequelize');
const fs = require('fs');
const path = require('path');

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

    const notifications = await MobileNotification.findAll({
      where: { userId: user.id },
      order: [['createdAt', 'DESC']],
      limit: 100,
    });

    return res.json(
      notifications.map((item) => ({
        id: item.id,
        title: item.title,
        message: item.message,
        type: item.type,
        isRead: item.isRead,
        data: item.data,
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

    const notification = await MobileNotification.findOne({
      where: {
        id: Number(req.params.id),
        userId: user.id,
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
    const { email, platform, token } = req.body;
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

    const supportRequest = await SupportRequest.create({
      userId: user.id,
      email: user.email,
      category,
      subject,
      message,
      status: 'open',
    });

    await createNotification({
      userId: user.id,
      title: 'Support request received',
      message: `Your ${category} request has been logged successfully.`,
      type: 'support',
      data: { supportRequestId: supportRequest.id },
    });

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

    if (childId) {
      const child = await getChildForParentUser(childId, user.id);
      if (!child) {
        return res.status(404).json({ message: 'Child not found for this parent' });
      }
    }

    const leaveRequest = await LeaveRequest.create({
      userId: user.id,
      email: user.email,
      childId: childId || null,
      childName,
      fromDate,
      toDate,
      reason,
      status: 'requested',
    });

    await createNotification({
      userId: user.id,
      title: 'Leave request submitted',
      message: `Leave request for ${childName} has been saved.`,
      type: 'leave',
      data: { leaveRequestId: leaveRequest.id },
    });

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

    const contacts = await EmergencyContact.findOne({
      where: { userId: user.id },
    });

    return res.json({
      success: true,
      data: contacts || {
        schoolContact: '',
        transportContact: '',
        notes: '',
      },
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

    const existing = await EmergencyContact.findOne({
      where: { userId: user.id },
    });

    let contacts;
    if (existing) {
      contacts = await existing.update({
        schoolContact,
        transportContact,
        notes,
      });
    } else {
      contacts = await EmergencyContact.create({
        userId: user.id,
        email: user.email,
        schoolContact,
        transportContact,
        notes,
      });
    }

    await createNotification({
      userId: user.id,
      title: 'Emergency contacts updated',
      message: 'Your emergency contact details are now saved on the backend.',
      type: 'emergency',
      data: { emergencyContactId: contacts.id },
    });

    return res.json({
      success: true,
      message: 'Emergency contacts saved successfully',
      data: contacts,
    });
  } catch (error) {
    console.error('Upsert emergency contacts error:', error);
    return res.status(500).json({ message: 'Unable to save emergency contacts' });
  }
};

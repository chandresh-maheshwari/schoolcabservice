const Child = require('../models/Child');
const User = require('../models/User');
const {
  findUserByLogin,
  getChildrenForParentUser,
  getChildForParentUser,
  isLegacyNodeUserSchema,
  tableHasColumn,
} = require('../services/schema-compat.service');
const { sequelize } = require('../config/db.config');
const { QueryTypes } = require('sequelize');
// const { Child, User } = require('../models'); // adjust path if needed


exports.getChildren = async (req, res) => {
    try {
        const { email } = req.query;
        if (!email) return res.status(400).json({ message: 'Email required' });

        const user = await findUserByLogin(email);
        if (!user) return res.status(404).json({ message: 'User not found' });

        const children = await getChildrenForParentUser(user.id);
        res.json(children);
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Server error' });
    }
};

exports.addChild = async (req, res) => {
    try {
        if (!(await isLegacyNodeUserSchema())) {
            return res.status(409).json({
                message: 'Child master data is managed from the Laravel admin or school panel in shared-database mode'
            });
        }

        const {
            email,
            name,
            schoolName,
            className,
            homeAddress,
            homeLat,
            homeLng,
            schoolAddress,
            schoolLat,
            schoolLng,
            secretPin
        } = req.body;

        const user = await User.findOne({ where: { email } });
        if (!user) return res.status(404).json({ message: 'User not found' });

        const child = await Child.create({
            parentId: user.id,
            name,
            schoolName,
            className,
            homeAddress,
            homeLat,
            homeLng,
            schoolAddress,
            schoolLat,
            schoolLng,
            secretPin
        });

        res.json(child);
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Error adding child' });
    }
};

exports.updateChild = async (req, res) => {
    try {
        const rawChildId = req.params.id ?? req.body?.id;
        const childId = parseInt(rawChildId, 10);
        const email = String(req.body?.email || req.query?.email || '').trim();

        if (!rawChildId || !Number.isInteger(childId)) {
            return res.status(400).json({ message: 'Valid child id is required' });
        }

        if (!email) {
            return res.status(400).json({ message: 'Email required' });
        }

        const user = await findUserByLogin(email);
        if (!user) {
            return res.status(404).json({ message: 'User not found' });
        }

        const existingChild = await getChildForParentUser(childId, user.id);
        if (!existingChild) {
            return res.status(404).json({ message: 'Child not found' });
        }

        const payload = {
            name: req.body?.name,
            schoolName: req.body?.schoolName,
            className: req.body?.className,
            homeAddress: req.body?.homeAddress,
            homeLat: req.body?.homeLat,
            homeLng: req.body?.homeLng,
            schoolAddress: req.body?.schoolAddress,
            schoolLat: req.body?.schoolLat,
            schoolLng: req.body?.schoolLng,
            secretPin: req.body?.secretPin,
        };

        const ignoredFields = [];

        if (await isLegacyNodeUserSchema()) {
            const updates = {};
            for (const [key, value] of Object.entries(payload)) {
                if (value !== undefined) {
                    updates[key] = value;
                }
            }

            if (!Object.keys(updates).length) {
                return res.status(422).json({ message: 'No supported fields provided' });
            }

            await Child.update(updates, {
                where: { id: childId, parentId: user.id },
            });
        } else {
            const setClauses = [];
            const replacements = { childId };
            const columnMap = [
                ['name', 'child_name'],
                ['schoolName', 'school_name'],
                ['className', 'class'],
                ['homeAddress', 'home_address'],
                ['secretPin', 'secret_pin'],
                ['homeLat', 'homeLat'],
                ['homeLng', 'homeLng'],
                ['schoolAddress', 'school_address'],
                ['schoolLat', 'schoolLat'],
                ['schoolLng', 'schoolLng'],
            ];

            for (const [field, column] of columnMap) {
                const value = payload[field];
                if (value === undefined) continue;

                if (await tableHasColumn('children', column)) {
                    setClauses.push(`${column} = :${field}`);
                    replacements[field] = value;
                } else {
                    ignoredFields.push(field);
                }
            }

            if (!setClauses.length) {
                return res.status(422).json({
                    message: 'No supported fields could be updated in shared-database mode',
                    ignoredFields,
                });
            }

            await sequelize.query(
                `
                    UPDATE children
                    SET ${setClauses.join(', ')}
                    WHERE id = :childId
                    LIMIT 1
                `,
                {
                    replacements,
                    type: QueryTypes.UPDATE,
                }
            );
        }

        const updatedChild = await getChildForParentUser(childId, user.id);
        return res.json({
            success: true,
            message: ignoredFields.length
                ? 'Child updated with some fields skipped due to schema limits'
                : 'Child updated successfully',
            ignoredFields,
            data: updatedChild,
        });
    } catch (err) {
        console.error(err);
        return res.status(500).json({ message: 'Error updating child' });
    }
};

exports.setTodayPickupStop = async (req, res) => {
    try {
        const rawChildId = req.params.id ?? req.body?.id;
        const childId = parseInt(rawChildId, 10);
        const email = String(req.body?.email || req.query?.email || '').trim();
        const pickupName = String(req.body?.pickupName || req.body?.todayPickupName || '').trim();
        const pickupDate = String(req.body?.pickupDate || req.body?.todayPickupDate || new Date().toISOString().slice(0, 10)).trim();

        if (!rawChildId || !Number.isInteger(childId)) {
            return res.status(400).json({ message: 'Valid child id is required' });
        }

        if (!email) {
            return res.status(400).json({ message: 'Email required' });
        }

        if (!pickupName) {
            return res.status(422).json({ message: 'pickupName is required' });
        }

        if (!/^\d{4}-\d{2}-\d{2}$/.test(pickupDate)) {
            return res.status(422).json({ message: 'pickupDate must be in YYYY-MM-DD format' });
        }

        const user = await findUserByLogin(email);
        if (!user) {
            return res.status(404).json({ message: 'User not found' });
        }

        const existingChild = await getChildForParentUser(childId, user.id);
        if (!existingChild) {
            return res.status(404).json({ message: 'Child not found' });
        }

        const hasPickupNameColumn = await tableHasColumn('children', 'today_pickup_name');
        const hasPickupDateColumn = await tableHasColumn('children', 'today_pickup_date');
        if (!hasPickupNameColumn || !hasPickupDateColumn) {
            return res.status(409).json({
                message: 'Today pickup override columns are not available yet. Please run the latest migrations.',
            });
        }

        await sequelize.query(
            `
                UPDATE children
                SET today_pickup_name = :pickupName,
                    today_pickup_date = :pickupDate
                WHERE id = :childId
                LIMIT 1
            `,
            {
                replacements: {
                    childId,
                    pickupName,
                    pickupDate,
                },
                type: QueryTypes.UPDATE,
            }
        );

        const updatedChild = await getChildForParentUser(childId, user.id);
        return res.json({
            success: true,
            message: 'Today pickup stop saved successfully',
            data: updatedChild,
        });
    } catch (err) {
        console.error(err);
        return res.status(500).json({ message: 'Error saving today pickup stop' });
    }
};

exports.clearTodayPickupStop = async (req, res) => {
    try {
        const rawChildId = req.params.id ?? req.body?.id;
        const childId = parseInt(rawChildId, 10);
        const email = String(req.body?.email || req.query?.email || '').trim();

        if (!rawChildId || !Number.isInteger(childId)) {
            return res.status(400).json({ message: 'Valid child id is required' });
        }

        if (!email) {
            return res.status(400).json({ message: 'Email required' });
        }

        const user = await findUserByLogin(email);
        if (!user) {
            return res.status(404).json({ message: 'User not found' });
        }

        const existingChild = await getChildForParentUser(childId, user.id);
        if (!existingChild) {
            return res.status(404).json({ message: 'Child not found' });
        }

        const hasPickupNameColumn = await tableHasColumn('children', 'today_pickup_name');
        const hasPickupDateColumn = await tableHasColumn('children', 'today_pickup_date');
        if (!hasPickupNameColumn || !hasPickupDateColumn) {
            return res.status(409).json({
                message: 'Today pickup override columns are not available yet. Please run the latest migrations.',
            });
        }

        await sequelize.query(
            `
                UPDATE children
                SET today_pickup_name = NULL,
                    today_pickup_date = NULL
                WHERE id = :childId
                LIMIT 1
            `,
            {
                replacements: { childId },
                type: QueryTypes.UPDATE,
            }
        );

        const updatedChild = await getChildForParentUser(childId, user.id);
        return res.json({
            success: true,
            message: 'Today pickup stop cleared successfully',
            data: updatedChild,
        });
    } catch (err) {
        console.error(err);
        return res.status(500).json({ message: 'Error clearing today pickup stop' });
    }
};

// exports.deleteChild = async (req, res) => {
//     try {
//         const rawChildId = req.params.id ?? req.body?.id;
//         const childId = parseInt(rawChildId, 10);
//         const email = req.query.email?.trim()?.toLowerCase();

//         if (!rawChildId || !Number.isInteger(childId)) {
//             return res.status(400).json({ message: 'Valid child id is required' });
//         }

//         if (!email) {
//             return res.status(400).json({ message: 'Email required' });
//         }

//         const user = await User.findOne({ where: { email } });
//         if (!user) {
//             return res.status(404).json({ message: 'User not found' });
//         }

//         const child = await Child.findOne({ where: { id: childId, parentId: user.id } });
//         if (!child) {
//             return res.status(404).json({ message: 'Child not found' });
//         }

//         await child.destroy();
//         return res.json({ success: true, message: 'Child deleted successfully' });
//     } catch (err) {
//         console.error(err);
//         return res.status(500).json({ message: 'Error deleting child' });
//     }
// };


// exports.deleteChild = async (req, res) => {
//   try {
//     const childId = parseInt(req.params.id, 10);
//     const parentId = req.user.id; // from auth middleware

//     const child = await Child.findOne({
//       where: { id: childId, parentId }
//     });

//     if (!child) {
//       return res.status(404).json({ message: 'Child not found' });
//     }

//     await child.destroy();

//     return res.json({
//       success: true,
//       message: 'Child deleted successfully',
//     });
//   } catch (err) {
//     return res.status(500).json({ message: 'Error deleting child' });
//   }
// };



exports.deleteChild = async (req, res) => {
  try {
    const childId = parseInt(req.params.id, 10);
    const email = req.query.email;

    if (!childId) {
      return res.status(400).json({ message: 'Valid child id required' });
    }

    if (!email) {
      return res.status(400).json({ message: 'Email required' });
    }

    const user = await findUserByLogin(email);

    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    if (!(await isLegacyNodeUserSchema())) {
      const child = await getChildForParentUser(childId, user.id);

      if (!child) {
        return res.status(404).json({ message: 'Child not found' });
      }

      return res.status(409).json({
        message: 'Child deletion is managed from the Laravel admin or school panel in shared-database mode',
      });
    }

    const child = await Child.findOne({
      where: { id: childId, parentId: user.id }
    });

    if (!child) {
      return res.status(404).json({ message: 'Child not found' });
    }

    await child.destroy();

    return res.json({
      success: true,
      message: 'Child deleted successfully',
    });

  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Error deleting child' });
  }
};

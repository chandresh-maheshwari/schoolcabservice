const Child = require('../models/Child');
const User = require('../models/User');
const { Child, User } = require('../models'); // adjust path if needed


exports.getChildren = async (req, res) => {
    try {
        const { email } = req.query;
        if (!email) return res.status(400).json({ message: 'Email required' });

        const user = await User.findOne({ where: { email } });
        if (!user) return res.status(404).json({ message: 'User not found' });

        const children = await Child.findAll({ where: { parentId: user.id } });
        res.json(children);
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Server error' });
    }
};

exports.addChild = async (req, res) => {
    try {
        const { email, name, schoolName, className, homeLat, homeLng, schoolLat, schoolLng, secretPin } = req.body;

        const user = await User.findOne({ where: { email } });
        if (!user) return res.status(404).json({ message: 'User not found' });

        const child = await Child.create({
            parentId: user.id,
            name,
            schoolName,
            className,
            homeLat,
            homeLng,
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

    const user = await User.findOne({ where: { email } });

    if (!user) {
      return res.status(404).json({ message: 'User not found' });
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

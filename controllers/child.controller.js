const Child = require('../models/Child');
const User = require('../models/User');

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

exports.deleteChild = async (req, res) => {
    try {
        const { id } = req.params;
        const { email } = req.query;

        if (!email) return res.status(400).json({ message: 'Email required' });

        const user = await User.findOne({ where: { email } });
        if (!user) return res.status(404).json({ message: 'User not found' });

        const child = await Child.findOne({ where: { id, parentId: user.id } });
        if (!child) return res.status(404).json({ message: 'Child not found' });

        await child.destroy();
        return res.json({ message: 'Child deleted successfully' });
    } catch (err) {
        console.error(err);
        return res.status(500).json({ message: 'Error deleting child' });
    }
};

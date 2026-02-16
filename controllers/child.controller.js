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

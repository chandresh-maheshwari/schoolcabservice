const Driver = require('../models/Driver');
const User = require('../models/User');

// GET DRIVER DETAILS
exports.getDriverDetails = async (req, res) => {
  try {
    const { email } = req.query;

    const user = await User.findOne({ where: { email } });
    if (!user) return res.json(null);

    const driver = await Driver.findOne({ where: { userId: user.id } });
    return res.json(driver);
  } catch (err) {
    console.error(err);
    res.status(500).json({ message: 'Error fetching driver details' });
  }
};

// SAVE / UPDATE DRIVER DETAILS
exports.saveDriverDetails = async (req, res) => {
  try {
    const {
      email,
      fullName,
      licenseNumber,
      phoneNumber,
      vehicleNumber,
      vehicleModel,
      vehicleCapacity,
    } = req.body;

    const user = await User.findOne({ where: { email } });
    if (!user) return res.status(404).json({ message: 'User not found' });

    let driver = await Driver.findOne({ where: { userId: user.id } });

    if (!driver) {
      driver = await Driver.create({
        userId: user.id,
        fullName,
        licenseNumber,
        phoneNumber,
        vehicleNumber,
        vehicleModel,
        vehicleCapacity,
      });
    } else {
      await driver.update({
        fullName,
        licenseNumber,
        phoneNumber,
        vehicleNumber,
        vehicleModel,
        vehicleCapacity,
      });
    }

    res.json(driver);
  } catch (err) {
    console.error(err);
    res.status(500).json({ message: 'Error saving driver details' });
  }
};

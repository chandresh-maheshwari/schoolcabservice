// routes/driver.routes.js
const express = require('express');
const router = express.Router();

const driverController = require('../controllers/driver.controller');

// 🔥 VERY IMPORTANT: these names MUST match exports
router.get('/details', driverController.getDriverDetails);
router.post('/details', driverController.saveDriverDetails);

module.exports = router;

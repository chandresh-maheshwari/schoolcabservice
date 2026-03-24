// routes/driver.routes.js
const express = require('express');
const router = express.Router();

const driverController = require('../controllers/driver.controller');

// 🔥 VERY IMPORTANT: these names MUST match exports
router.get('/details', driverController.getDriverDetails);
router.get('/assigned-route', driverController.getAssignedRoute);
router.post('/details', driverController.saveDriverDetails);
router.get('/trip-children', driverController.getTripChildren);
router.get('/pre-trip-checklist', driverController.getPreTripChecklist);
router.post('/pre-trip-checklist', driverController.savePreTripChecklist);
router.get('/today-summary', driverController.getTodaySummary);
router.get('/emergency-history', driverController.getEmergencyHistory);
router.post('/quick-emergency', driverController.reportQuickEmergency);

module.exports = router;

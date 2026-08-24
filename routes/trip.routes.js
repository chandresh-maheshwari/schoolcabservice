const router = require('express').Router();
const {
  startTrip,
  getTripData,
  getChildTracking,
  getChildRoutePreview,
  verifyPickup,
  cancelPickup,
  dropChild,
  completeStop,
  updateDriverLocation,
  resetTrip,
  handoverEmergencyTrip
} = require('../controllers/trip.controller');

router.post('/start', startTrip);
router.get('/data', getTripData);
router.get('/child-tracking', getChildTracking);
router.get('/child-route', getChildRoutePreview);
router.post('/verify-pickup', verifyPickup);
router.post('/cancel-pickup', cancelPickup);
router.post('/drop', dropChild);
router.post('/complete-stop', completeStop);
router.post('/update-location', updateDriverLocation);
router.post('/reset', resetTrip);
router.post('/handover', handoverEmergencyTrip);

module.exports = router;

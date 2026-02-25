const router = require('express').Router();
const {
  startTrip,
  getTripData,
  getChildRoutePreview,
  verifyPickup,
  dropChild,
  updateDriverLocation,
  resetTrip
} = require('../controllers/trip.controller');

router.post('/start', startTrip);
router.get('/data', getTripData);
router.get('/child-route', getChildRoutePreview);
router.post('/verify-pickup', verifyPickup);
router.post('/drop', dropChild);
router.post('/update-location', updateDriverLocation);
router.post('/reset', resetTrip);

module.exports = router;

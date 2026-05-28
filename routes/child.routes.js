const router = require('express').Router();
const childController = require('../controllers/child.controller');

router.get('/', childController.getChildren);
router.get('/:id/route-stops', childController.getChildRouteStops);
router.post('/', childController.addChild);
router.post('/:id/regenerate-pin', childController.regenerateChildPin);
router.patch('/:id/today-pickup-stop', childController.setTodayPickupStop);
router.delete('/:id/today-pickup-stop', childController.clearTodayPickupStop);
router.patch('/:id', childController.updateChild);
router.delete('/:id', childController.deleteChild);


module.exports = router;

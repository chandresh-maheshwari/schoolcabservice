const router = require('express').Router();
const childController = require('../controllers/child.controller');

router.get('/', childController.getChildren);
router.post('/', childController.addChild);
router.patch('/:id/today-pickup-stop', childController.setTodayPickupStop);
router.delete('/:id/today-pickup-stop', childController.clearTodayPickupStop);
router.patch('/:id', childController.updateChild);
router.delete('/:id', childController.deleteChild);


module.exports = router;

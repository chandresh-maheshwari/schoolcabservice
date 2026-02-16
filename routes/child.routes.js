const router = require('express').Router();
const childController = require('../controllers/child.controller');

router.get('/', childController.getChildren);
router.post('/', childController.addChild);

module.exports = router;

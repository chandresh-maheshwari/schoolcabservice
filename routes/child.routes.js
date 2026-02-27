const router = require('express').Router();
const childController = require('../controllers/child.controller');

router.get('/', childController.getChildren);
router.post('/', childController.addChild);
router.post('/delete', childController.deleteChild);
router.delete('/:id', childController.deleteChild);

module.exports = router;

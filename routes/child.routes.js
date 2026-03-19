const router = require('express').Router();
const childController = require('../controllers/child.controller');

router.get('/', childController.getChildren);
router.post('/', childController.addChild);
router.patch('/:id', childController.updateChild);
router.delete('/:id', childController.deleteChild);


module.exports = router;

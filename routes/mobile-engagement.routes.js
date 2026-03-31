const router = require('express').Router();
const controller = require('../controllers/mobile-engagement.controller');

router.get('/notifications', controller.listNotifications);
router.post('/notifications/register-device', controller.registerPushDevice);
router.post('/notifications/unregister-device', controller.unregisterPushDevice);
router.post('/notifications/:id/read', controller.markNotificationRead);

router.get('/parent-profile', controller.getParentProfile);
router.post('/parent-profile', controller.saveParentProfile);

router.get('/support-requests', controller.listSupportRequests);
router.post('/support-requests', controller.createSupportRequest);
router.get('/admin/support-requests', controller.listAdminSupportRequests);
router.post('/admin/support-requests/:id/review', controller.reviewSupportRequest);

router.get('/leave-requests', controller.listLeaveRequests);
router.post('/leave-requests', controller.createLeaveRequest);
router.get('/admin/leave-requests', controller.listAdminLeaveRequests);
router.post('/admin/leave-requests/:id/review', controller.reviewLeaveRequest);

router.get('/emergency-contacts', controller.getEmergencyContacts);
router.post('/emergency-contacts', controller.upsertEmergencyContacts);

module.exports = router;

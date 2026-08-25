<?php



use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserAuthController;

use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Session;

use App\Http\Controllers\UserController;

use App\Http\Controllers\RoleController;

use App\Http\Controllers\PermissionController;

use App\Http\Controllers\AdminHomeController;

use App\Http\Controllers\BookingController;

use App\Http\Controllers\ParentController;

use App\Http\Controllers\DriverController;

use App\Http\Controllers\HeroController;

use App\Http\Controllers\VehicleController;

use App\Http\Controllers\VehicleTypeController;
use App\Http\Controllers\EmergencyTypeController;

use App\Http\Controllers\SchoolController;

use App\Http\Controllers\RouteController;

use App\Http\Controllers\PackageDetailController;

use App\Http\Controllers\EmergencyController;
use App\Http\Controllers\PushNotificationController;

use App\Http\Controllers\RatingController;

use App\Http\Controllers\StopPickupController;

use App\Http\Controllers\ChildController;
use App\Http\Controllers\MobileRequestController;
use App\Http\Controllers\MobileOtpMailController;
use App\Http\Controllers\MobileAuthController;
use App\Http\Controllers\ChildSubscriptionController;
use App\Http\Controllers\PaymentHistoryController;



use App\Http\Controllers\DriverVehicleHistoryController;

use Facade\FlareClient\Http\Client;

use App\Http\Controllers\Frontend\AboutSectionController;



/*

|--------------------------------------------------------------------------

| API Routes

|--------------------------------------------------------------------------

|

| Here is where you can register API routes for your application. These

| routes are loaded by the RouteServiceProvider within a group which

| is assigned the "api" middleware group. Enjoy building your API!

|

*/



Route::middleware('authweb.jwt')->get('/user', [UserAuthController::class, 'getAuthenticatedUser'])->name('api.user');

Route::post('/register', [UserAuthController::class, 'register'])->name('api.register');

Route::middleware(['authweb.jwt', 'permission'])->group(function () {
Route::post('/logout', [UserAuthController::class, 'logout'])->name('api.logout');
Route::post('/userlist', [UserController::class, 'userlist'])->name('api.userlist');
Route::post('/users/deleted-list', [UserController::class, 'deletedUserList'])->name('api.users.deleted-list');



/** Route for vehicle type by ns  */



Route::post('/vehicleType/store', [VehicleTypeController::class, 'store'])->name('api.vehicleType.store');

Route::get('/vehicleType/{id}/edit', [VehicleTypeController::class, 'edit'])->name('api.vehicleType.edit');

Route::put('/vehicleType/{id}', [VehicleTypeController::class, 'update'])->name('api.vehicleType.update');

Route::delete('/vehicleType/{id}', [VehicleTypeController::class, 'destroy'])->name('api.vehicleType.destroy');

Route::post('/vehicleType/list', [VehicleTypeController::class, 'vehicleTypeList'])->name('vehicleType.list');

Route::post('vehicleType/multi-delete', [VehicleTypeController::class, 'multiDelete'])->name('api.vehicleType.multi-delete');

Route::post('/vehicleType/{id}/toggle-status', [VehicleTypeController::class, 'toggleStatus'])->name('api.vehicleType.toggleStatus');

Route::get('/vehicleType/active-count', [VehicleTypeController::class, 'getActiveCount']);

Route::post('/emergencyType/store', [EmergencyTypeController::class, 'store'])->name('api.emergencyType.store');

Route::get('/emergencyType/{id}/edit', [EmergencyTypeController::class, 'edit'])->name('api.emergencyType.edit');

Route::put('/emergencyType/{id}', [EmergencyTypeController::class, 'update'])->name('api.emergencyType.update');

Route::delete('/emergencyType/{id}', [EmergencyTypeController::class, 'destroy'])->name('api.emergencyType.destroy');

Route::post('/emergencyType/list', [EmergencyTypeController::class, 'emergencyTypeList'])->name('emergencyType.list');

Route::post('emergencyType/multi-delete', [EmergencyTypeController::class, 'multiDelete'])->name('api.emergencyType.multi-delete');

Route::post('/emergencyType/{id}/toggle-status', [EmergencyTypeController::class, 'toggleStatus'])->name('api.emergencyType.toggleStatus');

Route::get('/emergencyType/active-count', [EmergencyTypeController::class, 'getActiveCount']);



/** Route for route by mr */

//  Route::group(['prefix' => 'routes'], function () {

Route::post('/routes/store', [RouteController::class, 'store']);

Route::get('/routes/{id}/edit', [RouteController::class, 'edit']);

Route::put('/routes/{id}', [RouteController::class, 'update']);

Route::delete('/routes/{id}', [RouteController::class, 'destroy'])->name('api.routes.destroy');

Route::post('/routes/{id}/toggle-status', [RouteController::class, 'toggleStatus'])->name('api.routes.toggleStatus');

Route::get('/routes/active-count', [RouteController::class, 'getActiveCount']);

Route::post('routes/multi-delete', [RouteController::class, 'multiDelete'])->name('api.routes.multi-delete');









Route::post('/routes/list', [RouteController::class, 'routeList'])->name('routes.list');



// });

//





/** Route for vehicle by ns */



Route::post('/vehicle/store', [VehicleController::class, 'store'])->name('api.vehicle.store');

Route::get('/vehicle/{id}/edit', [VehicleController::class, 'edit'])->name('api.vehicle.edit');

Route::put('/vehicle/{id}', [VehicleController::class, 'update'])->name('api.vehicle.update');

Route::delete('/vehicle/{id}', [VehicleController::class, 'destroy'])->name('api.vehicle.destroy');

Route::post('/vehicle/list', [VehicleController::class, 'vehicleList'])->name('vehicle.list');

Route::post('vehicle/multi-delete', [VehicleController::class, 'multiDelete'])->name('api.vehicle.multi-delete');

Route::post('/vehicle/{id}/toggle-status', [VehicleController::class, 'toggleStatus'])->name('api.vehicle.toggleStatus');

Route::get('/vehicle/active-count', [VehicleController::class, 'getActiveCount']);

Route::delete('/vehicle/{id}/image', [VehicleController::class, 'vehicleImage'])->name('api.vehicle.vehicleImage');

Route::delete('/vehicle/{id}/rcimage', [VehicleController::class, 'rcImage'])->name('api.vehicle.rcImage');

Route::delete('/vehicle/{id}/insuranceimage', [VehicleController::class, 'insuranceImage'])->name('api.vehicle.insuranceImage');

Route::get('/vehicle/live-tracking/{id}', [VehicleController::class, 'getLiveTracking'])->name('api.vehicle.live-tracking');

Route::get('/vehicle/all-live-tracking', [VehicleController::class, 'getAllLiveTracking'])->name('api.vehicle.all-live-tracking');
Route::get('/vehicle/tracking/live', [VehicleController::class, 'getAllLiveTracking'])->name('api.vehicle.tracking.live');
Route::get('/vehicle/tracking/debug', [VehicleController::class, 'debugTrackingMappings'])->name('api.vehicle.tracking.debug');
Route::post('/vehicle/tracking/update', [VehicleController::class, 'updateLiveTracking'])->name('api.vehicle.tracking.update');



/** Route for vehicle by ns */



Route::post('/driver/store', [DriverController::class, 'store'])->name('api.driver.store');

Route::get('/driver/{id}/edit', [DriverController::class, 'edit'])->name('api.driver.edit');

Route::put('/driver/{id}', [DriverController::class, 'update'])->name('api.driver.update');

Route::delete('/driver/{id}', [DriverController::class, 'destroy'])->name('api.driver.destroy');

Route::post('/driver/list', [DriverController::class, 'driverList'])->name('driver.list');

Route::post('/driver/multi-delete', [DriverController::class, 'multiDelete'])->name('api.driver.multi-delete');

Route::post('/driver/{id}/toggle-status', [DriverController::class, 'toggleStatus'])->name('api.driver.toggleStatus');

Route::get('/driver/active-count', [DriverController::class, 'getActiveCount']);

Route::delete('/driver/{id}/image', [DriverController::class, 'driverImage'])->name('api.driver.driverImage');

Route::delete('/driver/{id}/licenseimage', [DriverController::class, 'licenseImage'])->name('api.driver.licenseImage');

Route::delete('/driver/{id}/adherCardimage', [DriverController::class, 'adharCardImage'])->name('api.driver.adharCardImage');



/** Route for school module created by ns */

Route::post('/school/store', [SchoolController::class, 'store'])->name('api.school.store');

Route::post('/school/get-cities', [SchoolController::class, 'getCities'])->name('api.school.getCities');

Route::get('/school/get-pincode/{city}', [SchoolController::class, 'getPincode'])->name('api.school.getPincode');

Route::get('/school/create', [SchoolController::class, 'create']);

Route::get('/school/{id}/edit', [SchoolController::class, 'edit'])->name('api.school.edit');

Route::put('/school/{id}', [SchoolController::class, 'update'])->name('api.school.update');

Route::delete('/school/{id}', [SchoolController::class, 'destroy'])->name('api.school.destroy');

Route::post('/school/list', [SchoolController::class, 'schoolList'])->name('school.list');
Route::post('/school/deleted-list', [SchoolController::class, 'deletedSchoolList'])->name('school.deleted-list');
Route::post('/school/multi-delete', [SchoolController::class, 'multiDelete'])->name('api.school.multi-delete');

Route::post('/school/{id}/toggle-status', [SchoolController::class, 'toggleStatus'])->name('api.school.toggleStatus');
Route::post('/school/{id}/restore', [SchoolController::class, 'restore'])->name('api.school.restore');
Route::post('/school/{id}/export-deleted-data', [SchoolController::class, 'exportDeletedSchool'])->name('api.school.export-deleted-data');
Route::post('/school/{id}/force-delete', [SchoolController::class, 'forceDelete'])->name('api.school.force-delete');
Route::get('/school/active-count', [SchoolController::class, 'getActiveCount']);

// Route::delete('/school/{id}/image', [SchoolController::class, 'schoolImage'])->name('api.school.schoolImage');



/** Route for package detail module created by ns */



Route::post('/packageDetails/store', [PackageDetailController::class, 'store'])->name('api.packageDetails.store');

Route::get('/packageDetails/{id}/edit', [PackageDetailController::class, 'edit'])->name('api.packageDetails.edit');

Route::put('/packageDetails/{id}', [PackageDetailController::class, 'update'])->name('api.packageDetails.update');

Route::delete('/packageDetails/{id}', [PackageDetailController::class, 'destroy'])->name('api.packageDetails.destroy');

Route::post('/packageDetails/list', [PackageDetailController::class, 'packageDetailsList'])->name('packageDetails.list');

Route::post('/packageDetails/multi-delete', [PackageDetailController::class, 'multiDelete'])->name('api.packageDetails.multi-delete');

Route::post('/packageDetails/{id}/toggle-status', [PackageDetailController::class, 'toggleStatus'])->name('api.packageDetails.toggleStatus');

Route::get('/packageDetails/active-count', [PackageDetailController::class, 'getActiveCount']);



/** Route for Booking details created by ns */



Route::post('/booking/store', [BookingController::class, 'store'])->name('api.booking.store');

Route::get('/booking/{id}/edit', [BookingController::class, 'edit'])->name('api.booking.edit');

Route::put('/booking/{id}', [BookingController::class, 'update'])->name('api.booking.update');

Route::delete('/booking/{id}', [BookingController::class, 'destroy'])->name('api.booking.destroy');

Route::post('/booking/list', [BookingController::class, 'bookingList'])->name('booking.list');

Route::post('/booking/{id}/toggle-status', [BookingController::class, 'toggleStatus'])->name('api.booking.toggleStatus');

Route::get('/booking/active-count', [BookingController::class, 'getActiveCount']);

Route::post('/booking/multi-delete', [BookingController::class, 'multiDelete'])->name('api.booking.multi-delete');





/** Route for Emergency details created by ns */



Route::post('/emergency/store', [EmergencyController::class, 'store'])->name('api.emergency.store');

Route::get('/emergency/{id}/edit', [EmergencyController::class, 'edit'])->name('api.emergency.edit');

Route::put('/emergency/{id}', [EmergencyController::class, 'update'])->name('api.emergency.update');

Route::delete('/emergency/{id}', [EmergencyController::class, 'destroy'])->name('api.emergency.destroy');

Route::post('/emergency/list', [EmergencyController::class, 'emergencyList'])->name('emergency.list');

Route::post('/emergency/{id}/toggle-status', [EmergencyController::class, 'toggleStatus'])->name('api.emergency.toggleStatus');

Route::get('/emergency/active-count', [EmergencyController::class, 'getActiveCount']);

Route::post('/emergency/multi-delete', [EmergencyController::class, 'multiDelete'])->name('api.emergency.multi-delete');





/** Route for Rating details created by ns */



Route::post('/rating/store', [RatingController::class, 'store'])->name('api.rating.store');

Route::get('/rating/{id}/edit', [RatingController::class, 'edit'])->name('api.rating.edit');

Route::put('/rating/{id}', [RatingController::class, 'update'])->name('api.rating.update');

Route::delete('/rating/{id}', [RatingController::class, 'destroy'])->name('api.rating.destroy');

Route::post('/rating/list', [RatingController::class, 'ratingList'])->name('rating.list');

Route::post('/rating/multi-delete', [RatingController::class, 'multiDelete'])->name('api.rating.multi-delete');



// Route::post('/rating/{id}/toggle-status', [RatingController::class, 'toggleStatus'])->name('api.rating.toggleStatus');

// Route::get('/rating/active-count', [RatingController::class, 'getActiveCount']);





/** Route for Stop and Pickup Point created by ns */

Route::post('/stopPickup/store', [StopPickupController::class, 'store'])->name('api.stopPickup.store');

Route::get('/stopPickup/route-points/{routeId}', [StopPickupController::class, 'routePoints'])->name('api.stopPickup.route-points');

Route::get('/stopPickup/{id}/edit', [StopPickupController::class, 'edit'])->name('api.stopPickup.edit');

Route::put('/stopPickup/{id}', [StopPickupController::class, 'update'])->name('api.stopPickup.update');

Route::delete('/stopPickup/{id}', [StopPickupController::class, 'destroy'])->name('api.stopPickup.destroy');

Route::post('/stopPickup/list', [StopPickupController::class, 'stopPickupList'])->name('stopPickup.list');

Route::post('/stopPickup/{id}/toggle-status', [StopPickupController::class, 'toggleStatus'])->name('api.stopPickup.toggleStatus');

Route::get('/stopPickup/active-count', [StopPickupController::class, 'getActiveCount']);

Route::post('/stopPickup/multi-delete', [StopPickupController::class, 'multiDelete'])->name('api.stopPickup.multi-delete');





/** Route for all driver and vehicle history created by ns */

Route::post('/driverHistory/list', [DriverVehicleHistoryController::class, 'driverHistoryList'])->name('driverHistoryList.list');

Route::delete('/driverHistory/{id}', [DriverVehicleHistoryController::class, 'destroy'])->name('api.driverHistoryList.destroy');

Route::post('/driverHistory/multi-delete', [DriverVehicleHistoryController::class, 'multiDelete'])->name('api.driverHistoryList.multi-delete');





/** Routre for child and parent details created by ns */



Route::post('/parent/store', [ParentController::class, 'store'])->name('api.parent.store');

Route::get('/parent/{id}/edit', [ParentController::class, 'edit'])->name('api.parent.edit');

Route::put('/parent/{id}', [ParentController::class, 'update'])->name('api.parent.update');

Route::delete('/parent/{id}', [ParentController::class, 'destroy'])->name('api.parent.destroy');

Route::post('/parent/list', [ParentController::class, 'parentList'])->name('parent.list');
Route::post('/parent/find-existing', [ParentController::class, 'findExistingParent'])->name('api.parent.find-existing');

Route::post('/parent/{id}/toggle-status', [ParentController::class, 'toggleStatus'])->name('api.parent.toggleStatus');

Route::get('/parent/active-count', [ParentController::class, 'getActiveCount']);

Route::post('/parent/get-cities', [ParentController::class, 'getCities'])->name('api.parent.getCities');

// Route::get('/parent/create', ParentController::class ,'create')->name('api.parent.create');

Route::delete('/parent/{id}/parentAdhaarImage', [ParentController::class, 'parentAdhaarImage'])->name('api.parent.parentAdhaarImage');

Route::delete('/parent/{id}/motherAdhaarImage', [ParentController::class, 'motherAdhaarImage'])->name('api.parent.motherAdhaarImage');

Route::post('/parent/multi-delete', [ParentController::class, 'multiDelete'])->name('api.parent.multi-delete');









Route::post('/child/store', [ChildController::class, 'store'])->name('api.child.store');

Route::get('/child/{id}/edit', [ChildController::class, 'edit'])->name('api.child.edit');

Route::put('/child/{id}', [ChildController::class, 'update'])->name('api.child.update');

Route::delete('/child/{id}', [ChildController::class, 'destroy'])->name('api.child.destroy');
Route::delete('/leaveRequests/{id}', [MobileRequestController::class, 'destroyLeave'])->name('api.leaveRequests.destroy');
Route::delete('/supportRequests/{id}', [MobileRequestController::class, 'destroySupport'])->name('api.supportRequests.destroy');
Route::delete('/pushNotifications/{id}', [PushNotificationController::class, 'destroy'])->name('api.pushNotifications.destroy');

Route::post('/child/{id}/set-parent', [ChildController::class, 'setParent'])->name('api.child.setParent');

Route::post('/child/list', [ChildController::class, 'childList'])->name('child.list');
Route::post('/leaveRequests/list', [MobileRequestController::class, 'leaveList'])->name('leaveRequests.list');
Route::post('/leaveRequests/multi-delete', [MobileRequestController::class, 'multiDeleteLeave'])->name('api.leaveRequests.multi-delete');
Route::post('/supportRequests/multi-delete', [MobileRequestController::class, 'multiDeleteSupport'])->name('api.supportRequests.multi-delete');
Route::post('/pushNotifications/list', [PushNotificationController::class, 'notificationList'])->name('pushNotifications.list');
Route::post('/pushNotifications/multi-delete', [PushNotificationController::class, 'multiDelete'])->name('api.pushNotifications.multi-delete');

Route::post('/child/{id}/toggle-status', [ChildController::class, 'toggleStatus'])->name('api.child.toggleStatus');

Route::get('/child/active-count', [ChildController::class, 'getActiveCount']);

// Route::get('/child/create', ChildController::class ,'create')->name('api.child.create');

Route::delete('/child/{id}/childImage', [ChildController::class, 'childImage'])->name('api.child.childImage');

Route::delete('/child/{id}/childAdhaarImage', [ChildController::class, 'childAdhaarImage'])->name('api.child.childAdhaarImage');

Route::post('/child/multi-delete', [ChildController::class, 'multiDelete'])->name('api.child.multi-delete');





// User routes

Route::get('/users/{id}/edit', [UserAuthController::class, 'edit'])->name('api.users.edit');

Route::put('/users/{id}', [UserAuthController::class, 'update'])->name('api.users.update');

Route::delete('/users/{id}', [UserAuthController::class, 'deleteUser'])->name('api.users.delete');
Route::post('/users/multi-delete', [UserAuthController::class, 'multiDelete'])->name('api.users.multi-delete');
Route::post('/users/permanent-multi-delete', [UserAuthController::class, 'permanentMultiDelete'])->name('api.users.permanent-multi-delete');
Route::post('/users/{id}/restore', [UserController::class, 'restore'])->name('api.users.restore');

Route::delete('/users/{id}/image', [UserAuthController::class, 'deleteImage'])->name('api.users.deleteImage');

Route::post('/users/{id}/change-password', [UserAuthController::class, 'changePassword'])->name('api.users.changePassword');

Route::get('/users/{id}/content-counts', [UserAuthController::class, 'getUserContentCounts'])->name('api.users.content-counts');

Route::delete('/users/{id}/delete-all', [UserAuthController::class, 'deleteUserAndData'])->name('api.users.delete-all');

Route::post('/toggle-user-status/{id}', [UserAuthController::class, 'toggleUserStatus'])->name('api.toggle-user-status');



// Role routes

Route::post('/rolelist', [RoleController::class, 'rolelist'])->name('api.rolelist');

Route::post('/roles/store', [RoleController::class, 'apiStore'])->name('api.roles.store');

Route::put('/roles/{id}', [RoleController::class, 'apiUpdate'])->name('api.roles.update');

Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->name('api.roles.destroy');

Route::post('/roles/{id}/edit', [RoleController::class, 'edit'])->name('api.roles.edit');



// Permission routes

Route::post('/permissions/store', [PermissionController::class, 'store'])->name('api.permissions.store');

Route::get('/permissions/{id}/edit', [PermissionController::class, 'edit'])->name('api.permissions.edit');

Route::put('/permissions/{id}', [PermissionController::class, 'update'])->name('api.permissions.update');

Route::delete('/permissions/{id}', [PermissionController::class, 'destroy'])->name('api.permissions.destroy');

Route::post('/api/permissions/list', [PermissionController::class, 'permissionList'])->name('api.permissions.list');





// Admin Layout routes



Route::post('/sendOtp', [UserAuthController::class, 'sendOtp'])->name('api.sendOtp');

Route::post('/verifyOtp', [UserAuthController::class, 'verifyOtp'])->name('api.verifyOtp');

Route::post('/resetnewPassword', [UserAuthController::class, 'resetnewPassword'])->name('api.resetnewPassword');

Route::get('/user-data/{id}', [UserAuthController::class, 'getUserDataById'])->name('api.user-data.get');

Route::get('/dashboard-stats', [UserAuthController::class, 'getDashboardStats']);

Route::post('/refreshToken', [UserAuthController::class, 'refreshToken'])->name('api.refreshToken');

Route::post('/profile/{id}', [AdminHomeController::class, 'update'])->name('api.profile.update');

Route::get('/profile/{id}/edit', [AdminHomeController::class, 'edit'])->name('api.profile.edit');

Route::post('/profile/{id}/update-photo', [AdminHomeController::class, 'updatePhoto'])->name('api.profile.update-photo');

Route::post('roles/multi-delete', [RoleController::class, 'multiDelete'])->name('api.roles.multi-delete');

Route::post('users/multi-delete', [UserAuthController::class, 'multiDelete'])->name('api.users.multi-delete');

Route::post('permissions/multi-delete', [PermissionController::class, 'multiDelete'])->name('api.permissions.multi-delete');

/** Subscription (cash/admin/school) */
Route::post('/subscriptions/cash', [ChildSubscriptionController::class, 'storeCash'])->name('api.subscriptions.cash');
Route::get('/subscriptions/current', [ChildSubscriptionController::class, 'current'])->name('api.subscriptions.current');
Route::post('/subscriptions/mobile-sync', [ChildSubscriptionController::class, 'syncFromMobile'])->name('api.subscriptions.mobile-sync');
Route::post('/subscriptions/cancel', [ChildSubscriptionController::class, 'cancelFromMobile'])->name('api.subscriptions.cancel');
Route::post('/payment-history/list', [PaymentHistoryController::class, 'list'])->name('paymentHistory.list');
Route::delete('/paymentHistory/{id}', [PaymentHistoryController::class, 'destroy'])->name('api.paymentHistory.destroy');
Route::post('/paymentHistory/multi-delete', [PaymentHistoryController::class, 'multiDelete'])->name('api.paymentHistory.multi-delete');
});

/** Route start for Frontend apis  */
Route::middleware(['authweb.jwt', 'permission'])->group(function () {

/** Route for About Section details created by ns */
Route::post('/aboutSection/store', [AboutSectionController::class, 'store'])->name('api.aboutSection.store');
Route::get('/aboutSection/{id}/edit', [AboutSectionController::class, 'edit'])->name('api.aboutSection.edit');
Route::put('/aboutSection/{id}', [AboutSectionController::class, 'update'])->name('api.aboutSection.update');
Route::delete('/aboutSection/{id}', [AboutSectionController::class, 'destroy'])->name('api.aboutSection.destroy');
Route::post('/aboutSection/{id}/toggle-status', [AboutSectionController::class, 'toggleStatus'])->name('api.aboutSection.toggleStatus');
Route::delete('/aboutSection/{id}/aboutImage', [AboutSectionController::class, 'aboutImage'])->name('api.aboutSection.aboutImage');
Route::post('/aboutSection/multi-delete', [AboutSectionController::class, 'multiDelete'])->name('api.aboutSection.multi-delete');

/** Route for Service details created by ns */
Route::post('/service/store', [App\Http\Controllers\Frontend\ServiceController::class, 'store'])->name('api.service.store');
Route::get('/service/{id}/edit', [App\Http\Controllers\Frontend\ServiceController::class, 'edit'])->name('api.service.edit');
Route::put('/service/{id}', [App\Http\Controllers\Frontend\ServiceController::class, 'update'])->name('api.service.update');
Route::delete('/service/{id}', [App\Http\Controllers\Frontend\ServiceController::class, 'destroy'])->name('api.service.destroy');
Route::post('/service/{id}/toggle-status', [App\Http\Controllers\Frontend\ServiceController::class, 'toggleStatus'])->name('api.service.toggleStatus');
Route::post('/service/multi-delete', [App\Http\Controllers\Frontend\ServiceController::class, 'multiDelete'])->name('api.service.multi-delete');

/** Route for How it works created by ns */
Route::post('/howItWorks/store', [App\Http\Controllers\Frontend\HowItWorkController::class, 'store'])->name('api.howItWorks.store');
Route::get('/howItWorks/{id}/edit', [App\Http\Controllers\Frontend\HowItWorkController::class, 'edit'])->name('api.howItWorks.edit');
Route::put('/howItWorks/{id}', [App\Http\Controllers\Frontend\HowItWorkController::class, 'update'])->name('api.howItWorks.update');
Route::delete('/howItWorks/{id}', [App\Http\Controllers\Frontend\HowItWorkController::class, 'destroy'])->name('api.howItWorks.destroy');
Route::post('/howItWorks/{id}/toggle-status', [App\Http\Controllers\Frontend\HowItWorkController::class, 'toggleStatus'])->name('api.howItWorks.toggleStatus');
Route::post('/howItWorks/multi-delete', [App\Http\Controllers\Frontend\HowItWorkController::class, 'multiDelete'])->name('api.howItWorks.multi-delete');

/** Route for Client Section created by ns */
Route::post('/clientSection/store', [App\Http\Controllers\Frontend\ClientSectionController::class, 'store'])->name('api.clientSection.store');
Route::get('/clientSection/{id}/edit', [App\Http\Controllers\Frontend\ClientSectionController::class, 'edit'])->name('api.clientSection.edit');
Route::put('/clientSection/{id}', [App\Http\Controllers\Frontend\ClientSectionController::class, 'update'])->name('api.clientSection.update');
Route::delete('/clientSection/{id}', [App\Http\Controllers\Frontend\ClientSectionController::class, 'destroy'])->name('api.clientSection.destroy');
Route::post('/clientSection/{id}/toggle-status', [App\Http\Controllers\Frontend\ClientSectionController::class, 'toggleStatus'])->name('api.clientSection.toggleStatus');
Route::delete('/clientSection/{id}/clientImage', [App\Http\Controllers\Frontend\ClientSectionController::class, 'clientImage'])->name('api.clientSection.clientImage');
Route::post('/clientSection/multi-delete', [App\Http\Controllers\Frontend\ClientSectionController::class, 'multiDelete'])->name('api.clientSection.multi-delete');

/** Route for Benefit Section created by ns */
Route::post('/benefitSection/store', [App\Http\Controllers\Frontend\BenefitSectionController::class, 'store'])->name('api.benefitSection.store');
Route::get('/benefitSection/{id}/edit', [App\Http\Controllers\Frontend\BenefitSectionController::class, 'edit'])->name('api.benefitSection.edit');
Route::put('/benefitSection/{id}', [App\Http\Controllers\Frontend\BenefitSectionController::class, 'update'])->name('api.benefitSection.update');
Route::delete('/benefitSection/{id}', [App\Http\Controllers\Frontend\BenefitSectionController::class, 'destroy'])->name('api.benefitSection.destroy');
Route::post('/benefitSection/{id}/toggle-status', [App\Http\Controllers\Frontend\BenefitSectionController::class, 'toggleStatus'])->name('api.benefitSection.toggleStatus');
Route::delete('/benefitSection/{id}/benefitImage', [App\Http\Controllers\Frontend\BenefitSectionController::class, 'benefitImage'])->name('api.benefitSection.benefitImage');
Route::post('/benefitSection/multi-delete', [App\Http\Controllers\Frontend\BenefitSectionController::class, 'multiDelete'])->name('api.benefitSection.multi-delete');

/** Route for Testimonial Section created by ns */
Route::post('/testimonialSection/store', [App\Http\Controllers\Frontend\TestimonialSectionController::class, 'store'])->name('api.testimonialSection.store');
Route::get('/testimonialSection/{id}/edit', [App\Http\Controllers\Frontend\TestimonialSectionController::class, 'edit'])->name('api.testimonialSection.edit');
Route::put('/testimonialSection/{id}', [App\Http\Controllers\Frontend\TestimonialSectionController::class, 'update'])->name('api.testimonialSection.update');
Route::delete('/testimonialSection/{id}', [App\Http\Controllers\Frontend\TestimonialSectionController::class, 'destroy'])->name('api.testimonialSection.destroy');
Route::post('/testimonialSection/{id}/toggle-status', [App\Http\Controllers\Frontend\TestimonialSectionController::class, 'toggleStatus'])->name('api.testimonialSection.toggleStatus');
Route::delete('/testimonialSection/{id}/testimonialImage', [App\Http\Controllers\Frontend\TestimonialSectionController::class, 'testimonialImage'])->name('api.testimonialSection.testimonialImage');
Route::post('/testimonailSection/multi-delete', [App\Http\Controllers\Frontend\TestimonialSectionController::class, 'multiDelete'])->name('api.testimonialSection.multi-delete');

/** Route for FAQ Section created by ns */
Route::post('/faqSection/store', [App\Http\Controllers\Frontend\FaqSectionController::class, 'store'])->name('api.faqSection.store');
Route::get('/faqSection/{id}/edit', [App\Http\Controllers\Frontend\FaqSectionController::class, 'edit'])->name('api.faqSection.edit');
Route::put('/faqSection/{id}', [App\Http\Controllers\Frontend\FaqSectionController::class, 'update'])->name('api.faqSection.update');
Route::delete('/faqSection/{id}', [App\Http\Controllers\Frontend\FaqSectionController::class, 'destroy'])->name('api.faqSection.destroy');
Route::post('/faqSection/{id}/toggle-status', [App\Http\Controllers\Frontend\FaqSectionController::class, 'toggleStatus'])->name('api.faqSection.toggleStatus');
Route::post('/faqSection/multi-delete', [App\Http\Controllers\Frontend\FaqSectionController::class, 'multiDelete'])->name('api.faqSection.multi-delete');

/** Route for Pricing Plan Section created by ns */
Route::post('/priceSection/store', [App\Http\Controllers\Frontend\PricingPlanSectionController::class, 'store'])->name('api.priceSection.store');
Route::get('/priceSection/{id}/edit', [App\Http\Controllers\Frontend\PricingPlanSectionController::class, 'edit'])->name('api.priceSection.edit');
Route::put('/priceSection/{id}', [App\Http\Controllers\Frontend\PricingPlanSectionController::class, 'update'])->name('api.priceSection.update');
Route::delete('/priceSection/{id}', [App\Http\Controllers\Frontend\PricingPlanSectionController::class, 'destroy'])->name('api.priceSection.destroy');
Route::post('/priceSection/{id}/toggle-status', [App\Http\Controllers\Frontend\PricingPlanSectionController::class, 'toggleStatus'])->name('api.priceSection.toggleStatus');
Route::post('/priceSection/multi-delete', [App\Http\Controllers\Frontend\PricingPlanSectionController::class, 'multiDelete'])->name('api.priceSection.multi-delete');

/** Route for Mobile App Section created by ns */
Route::post('/msbAppSection/store', [App\Http\Controllers\Frontend\MsbAppSectionController::class, 'store'])->name('api.msbAppSection.store');
Route::get('/msbAppSection/{id}/edit', [App\Http\Controllers\Frontend\MsbAppSectionController::class, 'edit'])->name('api.msbAppSection.edit');
Route::put('/msbAppSection/{id}', [App\Http\Controllers\Frontend\MsbAppSectionController::class, 'update'])->name('api.msbAppSection.update');
Route::delete('/msbAppSection/{id}', [App\Http\Controllers\Frontend\MsbAppSectionController::class, 'destroy'])->name('api.msbAppSection.destroy');
Route::post('/msbAppSection/{id}/toggle-status', [App\Http\Controllers\Frontend\MsbAppSectionController::class, 'toggleStatus'])->name('api.msbAppSection.toggleStatus');
Route::post('/msbAppSection/multi-delete', [App\Http\Controllers\Frontend\MsbAppSectionController::class, 'multiDelete'])->name('api.msbAppSection.multi-delete');

/** Route for Social Media Section created by ns */
Route::post('/socialMediaSection/store', [App\Http\Controllers\Frontend\SocialMediaController::class, 'store'])->name('api.socialMediaSection.store');
Route::get('/socialMediaSection/{id}/edit', [App\Http\Controllers\Frontend\SocialMediaController::class, 'edit'])->name('api.socialMediaSection.edit');
Route::put('/socialMediaSection/{id}', [App\Http\Controllers\Frontend\SocialMediaController::class, 'update'])->name('api.socialMediaSection.update');
Route::delete('/socialMediaSection/{id}', [App\Http\Controllers\Frontend\SocialMediaController::class, 'destroy'])->name('api.socialMediaSection.destroy');
Route::post('/socialMediaSection/{id}/toggle-status', [App\Http\Controllers\Frontend\SocialMediaController::class, 'toggleStatus'])->name('api.socialMediaSection.toggleStatus');
Route::post('/socialMediaSection/multi-delete', [App\Http\Controllers\Frontend\SocialMediaController::class, 'multiDelete'])->name('api.socialMediaSection.multi-delete');

});

Route::middleware('frontend.api.key')->group(function () {
Route::post('/aboutSection/list', [AboutSectionController::class, 'aboutSectionList'])->name('aboutSection.List');
Route::get('/aboutSection/active-count', [AboutSectionController::class, 'getActiveCount']);

Route::post('/service/list', [App\Http\Controllers\Frontend\ServiceController::class, 'serviceList'])->name('service.List');
Route::get('/service/active-count', [App\Http\Controllers\Frontend\ServiceController::class, 'getActiveCount']);

Route::post('/howItWorks/list', [App\Http\Controllers\Frontend\HowItWorkController::class, 'howItWorkList'])->name('howItWorks.List');
Route::get('/howItWorks/active-count', [App\Http\Controllers\Frontend\HowItWorkController::class, 'getActiveCount']);

Route::post('/clientSection/list', [App\Http\Controllers\Frontend\ClientSectionController::class, 'clientSectionList'])->name('clientSection.List');
Route::get('/clientSection/active-count', [App\Http\Controllers\Frontend\ClientSectionController::class, 'getActiveCount']);

Route::post('/benefitSection/list', [App\Http\Controllers\Frontend\BenefitSectionController::class, 'benefitList'])->name('benefitSection.List');
Route::get('/benefitSection/active-count', [App\Http\Controllers\Frontend\BenefitSectionController::class, 'getActiveCount']);

Route::post('/testimonialSection/list', [App\Http\Controllers\Frontend\TestimonialSectionController::class, 'testimonialList'])->name('testimonialSection.List');
Route::get('/testimonialSection/active-count', [App\Http\Controllers\Frontend\TestimonialSectionController::class, 'getActiveCount']);

Route::post('/faqSection/list', [App\Http\Controllers\Frontend\FaqSectionController::class, 'faqList'])->name('faqSection.List');
Route::get('/faqSection/active-count', [App\Http\Controllers\Frontend\FaqSectionController::class, 'getActiveCount']);

Route::post('/priceSection/list', [App\Http\Controllers\Frontend\PricingPlanSectionController::class, 'pricingPlanList'])->name('priceSection.List');
Route::get('/priceSection/active-count', [App\Http\Controllers\Frontend\PricingPlanSectionController::class, 'getActiveCount']);

Route::post('/msbAppSection/list', [App\Http\Controllers\Frontend\MsbAppSectionController::class, 'msbAppSectionList'])->name('msbAppSection.List');
Route::get('/msbAppSection/active-count', [App\Http\Controllers\Frontend\MsbAppSectionController::class, 'getActiveCount']);

Route::post('/socialMediaSection/list', [App\Http\Controllers\Frontend\SocialMediaController::class, 'socialMediaList'])->name('socialMediaSection.List');
Route::get('/socialMediaSection/active-count', [App\Http\Controllers\Frontend\SocialMediaController::class, 'getActiveCount']);

/** Route for Contact Message Section created by ns */
Route::post('/contactMessageSection/list', [App\Http\Controllers\Frontend\ContactMessageController::class, 'contactMessageList'])->name('api.contactMessageSection.list');
});

Route::post('/contactMessageSection/store', [App\Http\Controllers\Frontend\ContactMessageController::class, 'store'])->name('api.contactMessageSection.store');

Route::prefix('mobile-auth')->group(function () {
Route::post('/login', [MobileAuthController::class, 'login'])->name('api.mobile-auth.login');
Route::post('/send-email-otp', [MobileAuthController::class, 'sendEmailOtp'])->name('api.mobile-auth.send-email-otp');
Route::post('/verify-email-otp', [MobileAuthController::class, 'verifyEmailOtp'])->name('api.mobile-auth.verify-email-otp');
Route::post('/forgot-password', [MobileAuthController::class, 'forgotPassword'])->name('api.mobile-auth.forgot-password');
Route::post('/reset-password', [MobileAuthController::class, 'resetPassword'])->name('api.mobile-auth.reset-password');
});

Route::post('/mobile-auth/email-otp', [MobileOtpMailController::class, 'send'])->name('api.mobile-auth.email-otp');

Route::prefix('mobile')->group(function () {
Route::get('/notifications', [PushNotificationController::class, 'listMobileNotifications'])->name('api.mobile.notifications.index');
Route::post('/notifications/register-device', [PushNotificationController::class, 'registerMobileDevice'])->name('api.mobile.notifications.register-device');
Route::post('/notifications/unregister-device', [PushNotificationController::class, 'unregisterMobileDevice'])->name('api.mobile.notifications.unregister-device');
Route::post('/notifications/{id}/read', [PushNotificationController::class, 'markMobileNotificationRead'])->name('api.mobile.notifications.read');
Route::get('/schools', [MobileRequestController::class, 'listMobileSchools'])->name('api.mobile.schools.index');
Route::get('/routes', [MobileRequestController::class, 'listMobileRoutes'])->name('api.mobile.routes.index');
Route::get('/parent-profile', [MobileRequestController::class, 'getParentProfile'])->name('api.mobile.parent.profile.show');
Route::post('/parent-profile', [MobileRequestController::class, 'saveParentProfile'])->name('api.mobile.parent.profile.update');
Route::delete('/children/{child}', [MobileRequestController::class, 'deleteParentChild'])->name('api.mobile.parent.child.destroy');
Route::get('/children/{child}/route-stops', [MobileRequestController::class, 'getChildRouteStops'])->name('api.mobile.parent.child.route-stops');
Route::get('/children/{child}/trips', [MobileRequestController::class, 'getChildTripHistory'])->name('api.mobile.parent.child.trips');
Route::get('/emergency-contacts', [MobileRequestController::class, 'getEmergencyContacts'])->name('api.mobile.parent.emergency-contacts.show');
Route::post('/emergency-contacts', [MobileRequestController::class, 'saveEmergencyContacts'])->name('api.mobile.parent.emergency-contacts.update');
Route::get('/driver/school-contact', [EmergencyController::class, 'getDriverSchoolEmergencyContact'])->name('api.mobile.driver.school-contact');
Route::get('/driver/emergency-history', [EmergencyController::class, 'getDriverEmergencyHistory'])->name('api.mobile.driver.emergency-history');
Route::post('/driver/emergency-report', [EmergencyController::class, 'storeDriverEmergencyFromEmail'])->name('api.mobile.driver.emergency-report');
Route::get('/support-requests', [MobileRequestController::class, 'listParentSupportRequests'])->name('api.mobile.parent.support.index');
Route::post('/support-requests', [MobileRequestController::class, 'createParentSupportRequest'])->name('api.mobile.parent.support.store');
Route::get('/leave-requests', [MobileRequestController::class, 'listParentLeaveRequests'])->name('api.mobile.parent.leave.index');
Route::post('/leave-requests', [MobileRequestController::class, 'createParentLeaveRequest'])->name('api.mobile.parent.leave.store');
Route::post('/parent/feedback-submit', [RatingController::class, 'storeParentFeedbackFromEmail'])->name('api.mobile.parent.feedback-submit');
});

Route::middleware(['jwt.auth'])->prefix('mobile')->group(function () {
Route::post('/driver/emergency', [EmergencyController::class, 'storeDriverEmergency'])->name('api.mobile.driver.emergency.store');
Route::post('/parent/feedback', [RatingController::class, 'storeParentFeedback'])->name('api.mobile.parent.feedback.store');
});

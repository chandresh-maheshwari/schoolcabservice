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
use App\Http\Controllers\DriverController;
use App\Http\Controllers\HeroController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleTypeController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\PackageDetailController;
use App\Http\Controllers\EmergencyController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\StopPickupController;
use Facade\FlareClient\Http\Client;

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
Route::middleware('authweb.jwt')->post('/logout', [UserAuthController::class, 'logout'])->name('api.logout');
Route::post('/userlist', [UserController::class, 'userlist'])->name('api.userlist');

/** Route for vehicle type by ns  */

Route::post('/vehicleType/store', [VehicleTypeController::class, 'store'])->name('api.vehicleType.store');
Route::get('/vehicleType/{id}/edit', [VehicleTypeController::class, 'edit'])->name('api.vehicleType.edit');
Route::put('/vehicleType/{id}', [VehicleTypeController::class, 'update'])->name('api.vehicleType.update');
Route::delete('/vehicleType/{id}', [VehicleTypeController::class, 'destroy'])->name('api.vehicleType.destroy');
Route::post('/vehicleType/list', [VehicleTypeController::class, 'vehicleTypeList'])->name('vehicleType.list');
Route::post('vehicleType/multi-delete', [VehicleTypeController::class, 'multiDelete'])->name('api.vehicleType.multi-delete');
Route::post('/vehicleType/{id}/toggle-status', [VehicleTypeController::class, 'toggleStatus'])->name('api.vehicleType.toggleStatus');
Route::get('/vehicleType/active-count', [VehicleTypeController::class, 'getActiveCount']);

/** Route for route by mr */
//  Route::group(['prefix' => 'routes'], function () {
Route::post('/routes/store', [RouteController::class, 'store']);
Route::get('/routes/{id}/edit', [RouteController::class, 'edit']);
Route::put('/routes/{id}', [RouteController::class, 'update']);



Route::post('/list', [RouteController::class, 'routeList'])->name('routes.list');

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

/** Route for vehicle by ns */

Route::post('/driver/store', [DriverController::class, 'store'])->name('api.driver.store');
Route::get('/driver/{id}/edit', [DriverController::class, 'edit'])->name('api.driver.edit');
Route::put('/driver/{id}', [DriverController::class, 'update'])->name('api.driver.update');
Route::delete('/driver/{id}', [DriverController::class, 'destroy'])->name('api.driver.destroy');
Route::post('/driver/list', [DriverController::class, 'driverList'])->name('driver.list');
// Route::post('vehicle/multi-delete', [VehicleController::class, 'multiDelete'])->name('api.vehicle.multi-delete');
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
// Route::post('vehicle/multi-delete', [VehicleController::class, 'multiDelete'])->name('api.vehicle.multi-delete');
Route::post('/school/{id}/toggle-status', [SchoolController::class, 'toggleStatus'])->name('api.school.toggleStatus');
Route::get('/school/active-count', [SchoolController::class, 'getActiveCount']);
// Route::delete('/school/{id}/image', [SchoolController::class, 'schoolImage'])->name('api.school.schoolImage');

/** Route for package detail module created by ns */

Route::post('/packageDetails/store', [PackageDetailController::class, 'store'])->name('api.packageDetails.store');
Route::get('/packageDetails/{id}/edit', [PackageDetailController::class, 'edit'])->name('api.packageDetails.edit');
Route::put('/packageDetails/{id}', [PackageDetailController::class, 'update'])->name('api.packageDetails.update');
Route::delete('/packageDetails/{id}', [PackageDetailController::class, 'destroy'])->name('api.packageDetails.destroy');
Route::post('/packageDetails/list', [PackageDetailController::class, 'packageDetailsList'])->name('packageDetails.list');
// Route::post('vehicle/multi-delete', [VehicleController::class, 'multiDelete'])->name('api.vehicle.multi-delete');
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

/** Route for Emergency details created by ns */

Route::post('/emergency/store', [EmergencyController::class, 'store'])->name('api.emergency.store');
Route::get('/emergency/{id}/edit', [EmergencyController::class, 'edit'])->name('api.emergency.edit');
Route::put('/emergency/{id}', [EmergencyController::class, 'update'])->name('api.emergency.update');
Route::delete('/emergency/{id}', [EmergencyController::class, 'destroy'])->name('api.emergency.destroy');
Route::post('/emergency/list', [EmergencyController::class, 'emergencyList'])->name('emergency.list');
Route::post('/emergency/{id}/toggle-status', [EmergencyController::class, 'toggleStatus'])->name('api.emergency.toggleStatus');
Route::get('/emergency/active-count', [EmergencyController::class, 'getActiveCount']);

/** Route for Rating details created by ns */

Route::post('/rating/store', [RatingController::class, 'store'])->name('api.rating.store');
Route::get('/rating/{id}/edit', [RatingController::class, 'edit'])->name('api.rating.edit');
Route::put('/rating/{id}', [RatingController::class, 'update'])->name('api.rating.update');
Route::delete('/rating/{id}', [RatingController::class, 'destroy'])->name('api.rating.destroy');
Route::post('/rating/list', [RatingController::class, 'ratingList'])->name('rating.list');
// Route::post('/rating/{id}/toggle-status', [RatingController::class, 'toggleStatus'])->name('api.rating.toggleStatus');
// Route::get('/rating/active-count', [RatingController::class, 'getActiveCount']);


/** Route for Stop and Pickup Point created by ns */
Route::post('/stopPickup/store', [StopPickupController::class, 'store'])->name('api.stopPickup.store');
Route::get('/stopPickup/{id}/edit', [StopPickupController::class, 'edit'])->name('api.stopPickup.edit');
Route::put('/stopPickup/{id}', [StopPickupController::class, 'update'])->name('api.stopPickup.update');
Route::delete('/stopPickup/{id}', [StopPickupController::class, 'destroy'])->name('api.stopPickup.destroy');
Route::post('/stopPickup/list', [StopPickupController::class, 'stopPickupList'])->name('stopPickup.list');
Route::post('/stopPickup/{id}/toggle-status', [StopPickupController::class, 'toggleStatus'])->name('api.stopPickup.toggleStatus');
Route::get('/stopPickup/active-count', [StopPickupController::class, 'getActiveCount']);

// User routes
Route::get('/users/{id}/edit', [UserAuthController::class, 'edit'])->name('api.users.edit');
Route::put('/users/{id}', [UserAuthController::class, 'update'])->name('api.users.update');
Route::delete('/users/{id}', [UserAuthController::class, 'deleteUser'])->name('api.users.delete');
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










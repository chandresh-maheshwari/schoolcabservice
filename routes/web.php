<?php

use App\Http\Controllers\AdminHomeController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\ChildSubscriptionController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverVehicleHistoryController;
use App\Http\Controllers\EmergencyController;
use App\Http\Controllers\Frontend\AboutSectionController;
use App\Http\Controllers\Frontend\BenefitSectionController;
use App\Http\Controllers\Frontend\ClientSectionController;
use App\Http\Controllers\Frontend\FaqSectionController;
use App\Http\Controllers\Frontend\HowItWorkController;
use App\Http\Controllers\Frontend\MsbAppSectionController;
use App\Http\Controllers\Frontend\PricingPlanSectionController;
use App\Http\Controllers\Frontend\ServiceController;
use App\Http\Controllers\Frontend\TestimonialSectionController;
use App\Http\Controllers\Frontend\SocialMediaController;
use App\Http\Controllers\Frontend\ContactMessageController;
use App\Http\Controllers\Frontend\StayConnectController;
use App\Http\Controllers\MobileRequestController;
use App\Http\Controllers\PackageDetailController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PushNotificationController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\StopPickupController;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleTypeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CKEditorController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => ['auth']], function () {
    Route::post('ckeditor/upload', [CKEditorController::class, 'upload'])->name('ckeditor.upload');
    Route::get('/dashboard', function () {
        return redirect()->route('admin_layout.index');
    });

    Route::prefix('admin')->middleware(['school.admin.redirect', 'permission'])->group(function () {
        Route::resource('roles', RoleController::class);
        Route::resource('users', UserController::class);
        Route::get('users-trash', [UserController::class, 'trash'])->name('users.deleted-list');
        Route::resource('permissions', PermissionController::class);
        Route::resource('profile', AdminHomeController::class)->only('edit', 'update');
        Route::get('dashboard', [AdminHomeController::class, 'index'])->name('admin_layout.index');
        Route::get('dashboard/live-summary', [AdminHomeController::class, 'liveSummary'])->name('admin.dashboard.live-summary');
        Route::post('dashboard/cards/order', [AdminHomeController::class, 'updateDashboardCardOrder'])->name('admin.dashboard.cards.order');
        Route::get('/profile', [AdminHomeController::class, 'profile'])->name('admin.profile');
        // CHERRYPIK WEBSITE ROUTES
        Route::resource('vehicleType', VehicleTypeController::class);
        Route::resource('vehicle', VehicleController::class);
        Route::get('vehicle-tracking', [VehicleController::class, 'tracking'])->name('vehicle.tracking');
        Route::get('vehicle-tracking/live', [VehicleController::class, 'getAllLiveTracking'])->name('vehicle.tracking.live');
        Route::resource('driver', DriverController::class);
        Route::resource('school', SchoolController::class);
        Route::post('routes/google-preview', [RouteController::class, 'previewGoogleRoute'])->name('routes.google-preview');
        Route::get('routes/custom-locations/search', [RouteController::class, 'searchCustomLocations'])->name('routes.customLocations.search');
        Route::post('routes/custom-locations', [RouteController::class, 'storeCustomLocation'])->name('routes.customLocations.store');
        Route::get('routes/vehicle/{vehicle}/drivers', [RouteController::class, 'vehicleDrivers'])->name('routes.vehicleDrivers');
        Route::get('routes/driver/{driver}/vehicles', [RouteController::class, 'driverVehicles'])->name('routes.driverVehicles');
        Route::post('school/{school}/login-as', [SchoolController::class, 'loginAs'])->name('school.loginAs');
        Route::get('school-trash', [SchoolController::class, 'trash'])->name('school.trash');
        Route::post('school/{id}/restore', [SchoolController::class, 'restore'])->name('school.restore');
        Route::get('school-export/{file}', [SchoolController::class, 'downloadExport'])->name('school.export.download');
        Route::post('school/get-cities', [SchoolController::class, 'getCities'])->name('school.getCities');
        Route::get('school/get-pincode/{city}', [SchoolController::class, 'getPincode'])->name('school.getPincode');
        Route::resource('routes', RouteController::class);
        Route::resource('packageDetails', PackageDetailController::class);
        Route::resource('booking', BookingController::class);
        Route::resource('emergency', EmergencyController::class);
        Route::resource('rating', RatingController::class);
        Route::get('stopPickup/route-points/{routeId}', [StopPickupController::class, 'routePoints'])->name('stopPickup.route-points');
        Route::resource('stopPickup', StopPickupController::class);
        Route::resource('driverHistoryList', DriverVehicleHistoryController::class);
        Route::get('parent/{parent}/children/pins', [ParentController::class, 'currentChildPins'])
            ->name('parent.child-pins');
        Route::post('parent/{parent}/children/{child}/regenerate-pin', [ParentController::class, 'regenerateChildPin'])
            ->name('parent.regenerate-pin');
        Route::resource('parent', ParentController::class);
        Route::post('parent/get-cities', [ParentController::class, 'getCities'])->name('parent.getCities');
        Route::resource('child', ChildController::class);
        Route::get('subscriptions/cash/create', [ChildSubscriptionController::class, 'createCashForm'])
            ->name('subscriptions.cash.create');
        Route::get('leaveRequests', [MobileRequestController::class, 'leaveIndex'])->name('leaveRequests.index');
        Route::delete('leaveRequests/{id}', [MobileRequestController::class, 'destroyLeave'])->name('leaveRequests.destroy');
        Route::match(['put', 'patch', 'post'], 'leaveRequests/{id}/review', [MobileRequestController::class, 'reviewLeave'])->name('leaveRequests.review');
        Route::get('supportRequests', [MobileRequestController::class, 'supportIndex'])->name('supportRequests.index');
        Route::delete('supportRequests/{id}', [MobileRequestController::class, 'destroySupport'])->name('supportRequests.destroy');
        Route::match(['put', 'patch', 'post'], 'supportRequests/{id}/review', [MobileRequestController::class, 'reviewSupport'])->name('supportRequests.review');
        Route::get('pushNotifications', [PushNotificationController::class, 'index'])->name('pushNotifications.index');
        Route::delete('pushNotifications/{id}', [PushNotificationController::class, 'destroy'])->name('pushNotifications.destroy');
        Route::post('pushNotifications/send', [PushNotificationController::class, 'send'])->name('pushNotifications.send');
        Route::post('pushNotifications/settings', [PushNotificationController::class, 'updateSettings'])->name('pushNotifications.settings');

    });

    // School panel routes (same controllers, slug-prefixed URLs).
    Route::prefix('{schoolSlug}')
        ->middleware(['school.slug', 'permission'])
        ->where(['schoolSlug' => '[A-Za-z0-9\\-]+'])
        ->group(function () {
            Route::get('dashboard', [AdminHomeController::class, 'index'])->name('school.dashboard');
            Route::get('dashboard/live-summary', [AdminHomeController::class, 'liveSummary'])->name('school.dashboard.live-summary');
            Route::post('dashboard/cards/order', [AdminHomeController::class, 'updateDashboardCardOrder'])->name('school.dashboard.cards.order');

            Route::resource('vehicleType', VehicleTypeController::class)->names('school.vehicleType');
            Route::resource('vehicle', VehicleController::class)->names('school.vehicle');
            Route::get('vehicle-tracking', [VehicleController::class, 'tracking'])->name('school.vehicle.tracking');
            Route::get('vehicle-tracking/live', [VehicleController::class, 'getAllLiveTracking'])->name('school.vehicle.tracking.live');
            Route::resource('driver', DriverController::class)->names('school.driver');
            Route::resource('school', SchoolController::class)->names('school.school');
            Route::get('school-trash', [SchoolController::class, 'trash'])->name('school.school.trash');
            Route::post('routes/google-preview', [RouteController::class, 'previewGoogleRoute'])->name('school.routes.google-preview');
            Route::get('routes/custom-locations/search', [RouteController::class, 'searchCustomLocations'])->name('school.routes.customLocations.search');
            Route::post('routes/custom-locations', [RouteController::class, 'storeCustomLocation'])->name('school.routes.customLocations.store');
            Route::get('routes/vehicle/{vehicle}/drivers', [RouteController::class, 'vehicleDrivers'])->name('school.routes.vehicleDrivers');
            Route::get('routes/driver/{driver}/vehicles', [RouteController::class, 'driverVehicles'])->name('school.routes.driverVehicles');
            Route::post('school/get-cities', [SchoolController::class, 'getCities'])->name('school.school.getCities');
            Route::get('school/get-pincode/{city}', [SchoolController::class, 'getPincode'])->name('school.school.getPincode');
            Route::resource('routes', RouteController::class)->names('school.routes');
            Route::resource('packageDetails', PackageDetailController::class)->names('school.packageDetails');
            Route::resource('booking', BookingController::class)->names('school.booking');
            Route::resource('emergency', EmergencyController::class)->names('school.emergency');
            Route::resource('rating', RatingController::class)->names('school.rating');
            Route::get('stopPickup/route-points/{routeId}', [StopPickupController::class, 'routePoints'])->name('school.stopPickup.route-points');
            Route::resource('stopPickup', StopPickupController::class)->names('school.stopPickup');
            Route::resource('driverHistoryList', DriverVehicleHistoryController::class)->names('school.driverHistoryList');
            Route::get('parent/{parent}/children/pins', [ParentController::class, 'currentChildPins'])
                ->name('school.parent.child-pins');
            Route::post('parent/{parent}/children/{child}/regenerate-pin', [ParentController::class, 'regenerateChildPin'])
                ->name('school.parent.regenerate-pin');
            Route::resource('parent', ParentController::class)->names('school.parent');
            Route::resource('child', ChildController::class)->names('school.child');
            Route::get('subscriptions/cash/create', [ChildSubscriptionController::class, 'createCashForm'])
                ->name('school.subscriptions.cash.create');
            Route::get('leaveRequests', [MobileRequestController::class, 'leaveIndex'])->name('school.leaveRequests.index');
            Route::delete('leaveRequests/{id}', [MobileRequestController::class, 'destroyLeave'])->name('school.leaveRequests.destroy');
            Route::match(['put', 'patch', 'post'], 'leaveRequests/{id}/review', [MobileRequestController::class, 'reviewLeave'])->name('school.leaveRequests.review');
            Route::get('supportRequests', [MobileRequestController::class, 'supportIndex'])->name('school.supportRequests.index');
            Route::delete('supportRequests/{id}', [MobileRequestController::class, 'destroySupport'])->name('school.supportRequests.destroy');
            Route::match(['put', 'patch', 'post'], 'supportRequests/{id}/review', [MobileRequestController::class, 'reviewSupport'])->name('school.supportRequests.review');
            Route::get('pushNotifications', [PushNotificationController::class, 'index'])->name('school.pushNotifications.index');
            Route::delete('pushNotifications/{id}', [PushNotificationController::class, 'destroy'])->name('school.pushNotifications.destroy');
            Route::post('pushNotifications/send', [PushNotificationController::class, 'send'])->name('school.pushNotifications.send');
            Route::post('pushNotifications/settings', [PushNotificationController::class, 'updateSettings'])->name('school.pushNotifications.settings');

            // Keep profile actions available.
            Route::get('profile', [AdminHomeController::class, 'profile'])->name('school.profile');
            Route::resource('profile', AdminHomeController::class)->only('edit', 'update')->names([
                'edit' => 'school.profile.edit',
                'update' => 'school.profile.update',
            ]);
        });
    /** routes for the frontend */
    Route::prefix('cms')->middleware('permission')->group(function () {
        Route::resource('aboutSection', AboutSectionController::class);
        Route::resource('service', ServiceController::class);
        Route::resource('howItWorks', HowItWorkController::class);
        Route::resource('clientSection', ClientSectionController::class);
        Route::resource('benefitSection', BenefitSectionController::class);
        Route::resource('testimonialSection', TestimonialSectionController::class);
        Route::resource('faqSection', FaqSectionController::class);
        Route::resource('priceSection', PricingPlanSectionController::class);
        Route::resource('msbAppSection', MsbAppSectionController::class);
        Route::resource('socialMediaSection', SocialMediaController::class);

         Route::resource('contactMessageSection', ContactMessageController::class);
    });

    Route::get('/logout', [UserAuthController::class, 'logoutperform'])->name('logout.user');
    Route::get('/front-logout', [UserAuthController::class, 'frontlogoutperform'])->name('front.logout.user');
});

Route::middleware('guest')->group(function () {

    Route::get('/forgot-password', function () {
        return view('auth.forgot-password');
    })->name('forgot-password');

    Route::get('/admin/register', function () {
        return view('auth.register');
    })->name('register');
});

Route::get('/admin/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', [UserAuthController::class, 'loginuser'])->name('api.login');

Route::get('/homepage', function () {
    return view('homepage');
});

Route::middleware(['auth', 'permission'])->group(function () {
    Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::get('/user/{encodedUserId}/edit', [UserController::class, 'showUser'])->name('users.showEncoded');
});

Route::middleware('guest')->group(function () {
    Route::get('/{schoolSlug}', [UserAuthController::class, 'showSchoolLogin'])
        ->where('schoolSlug', '[A-Za-z0-9\\-]+')
        ->name('school.slug.login.page');

    Route::post('/{schoolSlug}/login', [UserAuthController::class, 'loginSchool'])
        ->where('schoolSlug', '[A-Za-z0-9\\-]+')
        ->name('school.slug.login');
});

// Legacy/fallback routes are handled by the dedicated slug/admin middleware now.

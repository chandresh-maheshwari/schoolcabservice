<?php

use App\Http\Controllers\AdminHomeController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\HeroController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleTypeController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/dashboard', function () {
        return view('admin_layout.admin_home');
    });

    Route::prefix('admin')->group(function () {
        Route::resource('roles', RoleController::class);
        Route::resource('users', UserController::class);
        Route::resource('permissions', PermissionController::class);
        Route::resource('profile', AdminHomeController::class)->only('edit', 'update');
        Route::get('dashboard', [AdminHomeController::class, 'index'])->name('admin_layout.index');
        Route::get('/profile', [AdminHomeController::class, 'profile'])->name('admin.profile');
        // CHERRYPIK WEBSITE ROUTES
        Route::resource('hero', HeroController::class);
        Route::resource('vehicleType', VehicleTypeController::class);
        Route::resource('vehicle', VehicleController::class);
        Route::resource('driver', DriverController::class);
        Route::resource('school', SchoolController::class);
        Route::resource('routes', RouteController::class);


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

Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

Route::middleware('permission')->group(function () {
    Route::get('/user/{encodedUserId}/edit', [UserController::class, 'showUser'])->name('User.Edit');
});

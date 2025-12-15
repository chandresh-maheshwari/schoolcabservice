<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromocodeController;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\UserController;
use App\Models\AccountInformationModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\PromocodeModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

use Illuminate\Support\Facades\Route;
use Monolog\Processor\HostnameProcessor;

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

//=========================================================================
//                  auth middleware START  
// ========================================================================
Route::group(['middleware' => 'auth'], function () {
    Route::get('/dashboard', function () {
        return view('dashboard.index');
    });
});
//=========================================================================
//                  auth middleware END  
// ========================================================================

Auth::routes();

Route::post('logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/product_add', function () {
    return view('dashboard.product_add');
});
Route::post('add', [ProductController::class, 'product_add'])->name('add');
Route::get('/product_update/{id}', [ProductController::class, 'product_update'])->name('product_update');
Route::post('update', [ProductController::class, 'update'])->name('update');
Route::get('/product_delete/{id}', [ProductController::class, 'product_delete'])->name('product_delete');
Route::get('/product_list', function () {
    return view('dashboard.product_list', ['data' => ProductModel::all()->sortByDesc("id")->where('is_deleted', 0)]);
});

Route::get('promocode_add', function () {
    return view('dashboard.promocode_add', ['data' => ProductModel::all()->sortByDesc("id")->where('is_deleted', 0)]);
});
Route::post('promocode_add_data', [PromocodeController::class, 'promocode_add_data'])->name('promocode_add_data');
Route::get('promocode_datatable', function () {
    return view('dashboard.promocode_dt', [
        'data' => PromocodeModel::all()->sortByDesc("id")->where('is_deleted', 0), 'product' => ProductModel::all()->sortByDesc("id")->where('is_deleted', 0),
    ]);
});
Route::get('/promocode_update/{id?}', [PromocodeController::class, 'promocode_update'])->name('promocode_update');
Route::post('update_promo', [PromocodeController::class, 'update_promo'])->name('update_promo');
Route::get('/promocode_delete/{id}', [PromocodeController::class, 'promocode_delete'])->name('prompcode_delete');


//=========================================================================
//                  USER middleware START  ------- FRONTEND
// ========================================================================
Route::get('/signin', function () {
    if (Session::has('userid')) {
        return redirect('/');
    }
    return view('signin');
})->name('signin');

// Route::get('/userlogin', [UserController::class], 'signin')->name('signin');

Route::post('/addaccountinfo', [UserAuthController::class, 'addaccountinformation'])->name('addaccountinformation');


Route::get('/register', function () {
    return view('register');
})->name('register');

Route::get('/', [UserAuthController::class, 'pricing']);
Route::post('/pricechange',[UserAuthController::class,'pricechange'])->name('pricechange');
Route::post('/promoverify',[UserAuthController::class,'promoverify'])->name('promoverify');

Route::post('address', [UserAuthController::class, 'address'])->name('address');

Route::post('orderconfirm', [UserAuthController::class, 'orderconfirm'])->name('orderconfirm');

Route::post('paymentmethod', [OrderController::class, 'paymentmethod'])->name('paymentmethod');

Route::group(['middleware' => 'user'], function () {
    // Route::get('/', [UserController::class, 'index']);

    Route::get('/userlogout', [UserController::class, 'userlogout'])->name('userlogout');
});
Route::get('/homepage', function(){
    return view('homepage');
});
Route::post('/loginuser', [UserAuthController::class, 'loginuser'])->name('loginuser');

//=========================================================================
//                  USER middleware END  ------- FRONTEND 
// ========================================================================

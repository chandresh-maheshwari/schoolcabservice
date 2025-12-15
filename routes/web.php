<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\FAQController;
use App\Http\Controllers\FAQCategoryController;
use Illuminate\Support\Facades\Route;
use Monolog\Processor\HostnameProcessor;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\MagazineCategoryController;
use App\Http\Controllers\MagazineController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\CMSCategoryController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\AboutUsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\WriterProfileController;
use App\Http\Controllers\FrontLayoutController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\MyAccountController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\QuotePageController;
use App\Http\Controllers\GuidelinesController;
use App\Http\Controllers\AdminHomeController;
use App\Http\Controllers\AdvanceCapabilitiesController;
use App\Http\Controllers\AlternativeController;
use App\Http\Controllers\AuthorSocialController;
use App\Http\Controllers\CallToActionController;
use App\Http\Controllers\CapabilitiesController;
use App\Http\Controllers\CherrypikPageController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FeaturesController;
use App\Http\Controllers\FooterController;
use App\Http\Controllers\FounderController;
use App\Http\Controllers\HeaderController;
use App\Http\Controllers\HeroController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SocialMediaController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\WhyUsController;
use App\Http\Controllers\NewsLetterController;
use Illuminate\Support\Facades\Response;

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
        Route::resource('cms_categories', CMSCategoryController::class);
        Route::resource('about_us', AboutUsController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('contact_messages', ContactMessageController::class);
        Route::resource('profile', AdminHomeController::class)->only('edit', 'update');
        Route::get('dashboard', [AdminHomeController::class, 'index'])->name('admin_layout.index');
        Route::resource('home_pages', HomePageController::class);
        Route::resource('socials-media', SocialMediaController::class);
        Route::get('/profile', [AdminHomeController::class, 'profile'])->name('admin.profile');
        // CHERRYPIK WEBSITE ROUTES
        Route::resource('client', ClientController::class);
        Route::resource('hero', HeroController::class);
        Route::resource('aboutUs', AboutUsController::class);
        Route::resource('stats', StatsController::class);
        Route::resource('service', ServiceController::class);
        Route::resource('alternative', AlternativeController::class);
        Route::resource('feature', FeaturesController::class);
        Route::resource('capability', CapabilitiesController::class);
        Route::resource('advance_capability', AdvanceCapabilitiesController::class);
        Route::resource('why_us', WhyUsController::class);
        Route::resource('call_to_action', CallToActionController::class);
        Route::resource('portfolio', PortfolioController::class);
        Route::resource('pricing', PricingController::class);
        Route::resource('faqs', FAQController::class);
        Route::resource('teams', TeamsController::class);
        Route::resource('contacts', ContactController::class);
        Route::resource('footer', FooterController::class);
        Route::resource('cherrypik_pages', CherrypikPageController::class);
        Route::get('/newsletter', [NewsLetterController::class, 'index'])->name('newsletter.index');
        Route::resource('header', HeaderController::class);
    });


    Route::get('/logout',  [UserAuthController::class, 'logoutperform'])->name('logout.user');
    Route::get('/front-logout',  [UserAuthController::class, 'frontlogoutperform'])->name('front.logout.user');
});

Route::middleware('guest')->group(function () {

    Route::get('/forgot-password', function () {
        return view('auth.forgot-password');
    })->name('forgot-password');


    Route::get('/register', function () {
        return view('cherrypik_front_layout.front_register');
    })->name('front.register');

    Route::get('/admin/register', function () {
        return view('auth.register');
    })->name('register');
});


Route::get('/admin/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/login', function () {
    return view('cherrypik_front_layout.front_login');
})->name('front.login');


Route::post('/follow/{userId}', [FollowController::class, 'follow'])->name('api.follow');
Route::post('/unfollow/{userId}', [FollowController::class, 'unfollow'])->name('api.unfollow');
Route::post('/login', [UserAuthController::class, 'loginuser'])->name('api.login');
Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/homepage', function () {
    return view('homepage');
});

Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
Route::get('/', [HomeController::class, 'index'])->name('front.index');

Route::middleware('permission')->group(function () {
    Route::get('/user/{encodedUserId}/edit', [UserController::class, 'showUser'])->name('User.Edit');
});

// Front page for Hero section
Route::get('/hero', [HeroController::class, 'front'])->name('front.hero');
// Simple page system - use template name directly (e.g., /page/about_us)
Route::get('/page/{template}', [\App\Http\Controllers\CherrypikPageController::class, 'showByTemplate'])->name('front.page.template');
// Generic page renderer by numeric id only (backward compatible)
Route::get('/page/{id}', [\App\Http\Controllers\PageController::class, 'show'])
    ->whereNumber('id')
    ->name('front.page');
// Cherrypik page by slug (keep for backward compatibility)
Route::get('/cp/{slug}', [\App\Http\Controllers\CherrypikPageController::class, 'showFront'])->name('front.cp.page');
Route::post('/set-theme', [App\Http\Controllers\FrontLayoutController::class, 'setTheme'])->name('set-theme');
Route::get('/portfolio-details/{id}', [\App\Http\Controllers\PortfolioController::class, 'show'])->name('portfolio.details');

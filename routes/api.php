<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserAuthController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\FAQController;
use App\Http\Controllers\FAQCategoryController;
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
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\GuidelinesController;
use App\Http\Controllers\AuthorSocialController;
use App\Http\Controllers\FounderController;
use App\Http\Controllers\AdminHomeController;
use App\Http\Controllers\AdvanceCapabilitiesController;
use App\Http\Controllers\AlternativeController;
use App\Http\Controllers\CallToActionController;
use App\Http\Controllers\CapabilitiesController;
use App\Http\Controllers\CherrypikPageController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FeaturesController;
use App\Http\Controllers\FooterController;
use App\Http\Controllers\HeaderController;
use App\Http\Controllers\HeroController;
use App\Http\Controllers\NewsLetterController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SocialMediaController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\WhyUsController;

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

// START BACKEND API 11-09-25 START //


// Hero Section
Route::post('/hero/store', [HeroController::class, 'store'])->name('api.hero.store');
Route::get('/hero/{id}/edit', [HeroController::class, 'edit'])->name('api.hero.edit');
Route::put('/hero/{id}', [HeroController::class, 'update'])->name('api.hero.update');
Route::delete('/hero/{id}', [HeroController::class, 'destroy'])->name('api.hero.destroy');
Route::delete('/hero/{id}/image', [HeroController::class, 'deleteImage'])->name('api.hero.deleteImage');
Route::post('/hero/list', [HeroController::class, 'heroList'])->name('hero.list');
Route::post('hero/multi-delete', [HeroController::class, 'multiDelete'])->name('api.hero.multi-delete');
Route::post('/hero/{id}/toggle-status', [HeroController::class, 'toggleStatus'])->name('api.hero.toggleStatus');
Route::get('/hero/active-count', [HeroController::class, 'getActiveCount']);


// Client Section Routes
Route::post('/client/store', [ClientController::class, 'store'])->name('api.client.store');
Route::get('/client/{id}/edit', [ClientController::class, 'edit'])->name('api.client.edit');
Route::put('/client/{id}', [ClientController::class, 'update'])->name('api.client.update');
Route::delete('/client/{id}', [ClientController::class, 'destroy'])->name('api.client.destroy');
Route::post('/client/list', [ClientController::class, 'clientList'])->name('client.list');
Route::post('client/multi-delete', [ClientController::class, 'multiDelete'])->name('api.client.multi-delete');
Route::delete('/client/{id}/image', [ClientController::class, 'deleteImage'])->name('api.client.deleteImage');
Route::post('/client/{id}/toggle-status', [ClientController::class, 'toggleStatus'])->name('api.client.toggleStatus');
Route::get('/client/active-count', [ClientController::class, 'getActiveCount']);


// About Us Section Api Routes
Route::post('/aboutUs/store', [AboutUsController::class, 'store'])->name('api.aboutUs.store');
Route::get('/aboutUs/{id}/edit', [AboutUsController::class, 'edit'])->name('api.aboutUs.edit');
Route::put('/aboutUs/{id}', [AboutUsController::class, 'update'])->name('api.aboutUs.update');
Route::delete('/aboutUs/{id}', [AboutUsController::class, 'destroy'])->name('api.aboutUs.destroy');
Route::post('/aboutUs/list', [AboutUsController::class, 'aboutList'])->name('aboutUs.list');
Route::post('aboutUs/multi-delete', [AboutUsController::class, 'multiDelete'])->name('api.aboutUs.multi-delete');
Route::delete('/aboutUs/{id}/image', [AboutUsController::class, 'deleteImage'])->name('api.aboutUs.deleteImage');
Route::delete('/aboutUs/{id}/profile-image', [AboutUsController::class, 'deleteProfileImage'])->name('api.aboutUs.deleteProfileImage');
Route::post('/aboutUs/{id}/toggle-status', [AboutUsController::class, 'toggleStatus'])->name('api.aboutUs.toggleStatus');
Route::get('/aboutUs/active-count', [AboutUsController::class, 'getActiveCount']);


// Stats Us Section Api Routes
Route::post('/stats/store', [StatsController::class, 'store'])->name('api.stats.store');
Route::get('/stats/{id}/edit', [StatsController::class, 'edit'])->name('api.stats.edit');
Route::put('/stats/{id}', [StatsController::class, 'update'])->name('api.stats.update');
Route::delete('/stats/{id}', [StatsController::class, 'destroy'])->name('api.stats.destroy');
Route::post('/stats/list', [StatsController::class, 'statsList'])->name('stats.list');
Route::post('stats/multi-delete', [StatsController::class, 'multiDelete'])->name('api.stats.multi-delete');
Route::delete('/stats/{id}/image', [StatsController::class, 'deleteImage'])->name('api.stats.deleteImage');
Route::post('/stats/{id}/toggle-status', [StatsController::class, 'toggleStatus'])->name('api.stats.toggleStatus');
Route::get('/stats/active-count', [StatsController::class, 'getActiveCount']);

Route::post('/contact-messages/list', [ContactMessageController::class, 'contactMessageList'])->name('api.contact_messages.list');


// Service Section
Route::post('/service/store', [ServiceController::class, 'store'])->name('api.service.store');
Route::get('/service/{id}/edit', [ServiceController::class, 'edit'])->name('api.service.edit');
Route::put('/service/{id}', [ServiceController::class, 'update'])->name('api.service.update');
Route::delete('/service/{id}', [ServiceController::class, 'destroy'])->name('api.service.destroy');
Route::post('/service/list', [ServiceController::class, 'serviceList'])->name('service.list');
Route::post('service/multi-delete', [ServiceController::class, 'multiDelete'])->name('api.service.multi-delete');
Route::delete('/service/{id}/image', [ServiceController::class, 'deleteImage'])->name('api.service.deleteImage');
Route::post('/service/{id}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('api.service.toggleStatus');
Route::get('/service/active-count', [ServiceController::class, 'getActiveCount']);


// Alternative Section
Route::post('/alternative/store', [AlternativeController::class, 'store'])->name('api.alternative.store');
Route::get('/alternative/{id}/edit', [AlternativeController::class, 'edit'])->name('api.alternative.edit');
Route::put('/alternative/{id}', [AlternativeController::class, 'update'])->name('api.alternative.update');
Route::delete('/alternative/{id}', [AlternativeController::class, 'destroy'])->name('api.alternative.destroy');
Route::post('/alternative/list', [AlternativeController::class, 'alternativeList'])->name('alternative.list');
Route::post('alternative/multi-delete', [AlternativeController::class, 'multiDelete'])->name('api.alternative.multi-delete');
Route::delete('/alternative/{id}/image', [AlternativeController::class, 'deleteImage'])->name('api.alternative.deleteImage');
Route::post('/alternative/{id}/toggle-status', [AlternativeController::class, 'toggleStatus'])->name('api.alternative.toggleStatus');
Route::get('/alternative/active-count', [AlternativeController::class, 'getActiveCount']);


// Features Section
Route::post('/feature/store', [FeaturesController::class, 'store'])->name('api.feature.store');
Route::get('/feature/{id}/edit', [FeaturesController::class, 'edit'])->name('api.feature.edit');
Route::put('/feature/{id}', [FeaturesController::class, 'update'])->name('api.feature.update');
Route::delete('/feature/{id}', [FeaturesController::class, 'destroy'])->name('api.feature.destroy');
Route::post('/feature/list', [FeaturesController::class, 'featuresList'])->name('feature.list');
Route::post('feature/multi-delete', [FeaturesController::class, 'multiDelete'])->name('api.feature.multi-delete');
Route::delete('/features/{id}/image', [FeaturesController::class, 'deleteImage'])->name('api.features.deleteImage');
Route::post('/feature/{id}/toggle-status', [FeaturesController::class, 'toggleStatus'])->name('api.feature.toggleStatus');
Route::get('/feature/active-count', [FeaturesController::class, 'getActiveCount']);


// Capabilities Section
Route::post('/capability/store', [CapabilitiesController::class, 'store'])->name('api.capability.store');
Route::get('/capability/{id}/edit', [CapabilitiesController::class, 'edit'])->name('api.capability.edit');
Route::put('/capability/{id}', [CapabilitiesController::class, 'update'])->name('api.capability.update');
Route::delete('/capability/{id}', [CapabilitiesController::class, 'destroy'])->name('api.capability.destroy');
Route::post('/capability/list', [CapabilitiesController::class, 'capabilityList'])->name('capability.list');
Route::post('capability/multi-delete', [CapabilitiesController::class, 'multiDelete'])->name('api.capability.multi-delete');
Route::delete('/capabilities/{id}/image', [CapabilitiesController::class, 'deleteImage'])->name('api.capabilities.deleteImage');
Route::post('/capability/{id}/toggle-status', [CapabilitiesController::class, 'toggleStatus'])->name('api.capability.toggleStatus');
Route::get('/capability/active-count', [CapabilitiesController::class, 'getActiveCount']);


// Advance Capabilities Section
Route::post('/advance_capability/store', [AdvanceCapabilitiesController::class, 'store'])->name('api.advance_capability.store');
Route::get('/advance_capability/{id}/edit', [AdvanceCapabilitiesController::class, 'edit'])->name('api.advance_capability.edit');
Route::put('/advance_capability/{id}', [AdvanceCapabilitiesController::class, 'update'])->name('api.advance_capability.update');
Route::delete('/advance_capability/{id}', [AdvanceCapabilitiesController::class, 'destroy'])->name('api.advance_capability.destroy');
Route::post('/advance_capability/list', [AdvanceCapabilitiesController::class, 'advanceCapabilityList'])->name('advance_capability.list');
Route::post('advance_capability/multi-delete', [AdvanceCapabilitiesController::class, 'multiDelete'])->name('api.advance_capability.multi-delete');
Route::delete('/advance_capabilities/{id}/image', [AdvanceCapabilitiesController::class, 'deleteImage'])->name('api.advance_capabilities.deleteImage');
Route::post('/advance_capability/{id}/toggle-status', [AdvanceCapabilitiesController::class, 'toggleStatus'])->name('api.advance_capability.toggleStatus');
Route::get('/advance_capability/active-count', [AdvanceCapabilitiesController::class, 'getActiveCount']);


// Why US Section
Route::post('/why_us/store', [WhyUsController::class, 'store'])->name('api.why_us.store');
Route::get('/why_us/{id}/edit', [WhyUsController::class, 'edit'])->name('api.why_us.edit');
Route::put('/why_us/{id}', [WhyUsController::class, 'update'])->name('api.why_us.update');
Route::delete('/why_us/{id}', [WhyUsController::class, 'destroy'])->name('api.why_us.destroy');
Route::post('/why_us/list', [WhyUsController::class, 'whyUsList'])->name('why_us.list');
Route::post('why_us/multi-delete', [WhyUsController::class, 'multiDelete'])->name('api.why_us.multi-delete');
Route::post('/why_us/{id}/toggle-status', [WhyUsController::class, 'toggleStatus'])->name('api.why_us.toggleStatus');
Route::get('/why_us/active-count', [WhyUsController::class, 'getActiveCount']);


// Call To Action Section
Route::post('/call_to_action/store', [CallToActionController::class, 'store'])->name('api.call_to_action.store');
Route::get('/call_to_action/{id}/edit', [CallToActionController::class, 'edit'])->name('api.call_to_action.edit');
Route::put('/call_to_action/{id}', [CallToActionController::class, 'update'])->name('api.call_to_action.update');
Route::delete('/call_to_action/{id}', [CallToActionController::class, 'destroy'])->name('api.call_to_action.destroy');
Route::post('/call_to_action/list', [CallToActionController::class, 'callToActionList'])->name('call_to_action.list');
Route::post('call_to_action/multi-delete', [CallToActionController::class, 'multiDelete'])->name('api.call_to_action.multi-delete');
Route::post('/call_to_action/{id}/toggle-status', [CallToActionController::class, 'toggleStatus'])->name('api.call_to_action.toggleStatus');
Route::get('/call_to_action/active-count', [CallToActionController::class, 'getActiveCount']);


// Portfolio Section
Route::post('/portfolio/store', [PortfolioController::class, 'store'])->name('api.portfolio.store');
Route::get('/portfolio/{id}/edit', [PortfolioController::class, 'edit'])->name('api.portfolio.edit');
Route::put('/portfolio/{id}', [PortfolioController::class, 'update'])->name('api.portfolio.update');
Route::delete('/portfolio/{id}', [PortfolioController::class, 'destroy'])->name('api.portfolio.destroy');
Route::post('/portfolio/list', [PortfolioController::class, 'portfolioList'])->name('portfolio.list');
Route::post('portfolio/multi-delete', [PortfolioController::class, 'multiDelete'])->name('api.portfolio.multi-delete');
Route::delete('/portfolio/{id}/image', [PortfolioController::class, 'deleteImage'])->name('api.portfolio.deleteImage');
Route::post('/portfolio/{id}/toggle-status', [PortfolioController::class, 'toggleStatus'])->name('api.portfolio.toggleStatus');
Route::get('/portfolio/active-count', [PortfolioController::class, 'getActiveCount']);



// Pricing Section
Route::post('/pricing/store', [PricingController::class, 'store'])->name('api.pricing.store');
Route::get('/pricing/{id}/edit', [PricingController::class, 'edit'])->name('api.pricing.edit');
Route::put('/pricing/{id}', [PricingController::class, 'update'])->name('api.pricing.update');
Route::delete('/pricing/{id}', [PricingController::class, 'destroy'])->name('api.pricing.destroy');
Route::post('/pricing/list', [PricingController::class, 'pricingList'])->name('pricing.list');
Route::post('pricing/multi-delete', [PricingController::class, 'multiDelete'])->name('api.pricing.multi-delete');
Route::post('/pricing/{id}/toggle-status', [PricingController::class, 'toggleStatus'])->name('api.pricing.toggleStatus');
Route::get('/pricing/active-count', [PricingController::class, 'getActiveCount']);
Route::post('/pricing/package', [PricingController::class, 'packageStore'])->name('api.pricing.packageStore');



// FAQ Section
Route::post('/faqs/store', [FAQController::class, 'store'])->name('api.faqs.store');
Route::get('/faqs/{id}/edit', [FAQController::class, 'edit'])->name('api.faqs.edit');
Route::put('/faqs/{id}', [FAQController::class, 'update'])->name('api.faqs.update');
Route::delete('/faqs/{id}', [FAQController::class, 'destroy'])->name('api.faqs.destroy');
Route::post('/faqs/list', [FAQController::class, 'faqList'])->name('api.faqs.list');
Route::post('faqs/multi-delete', [FAQController::class, 'multiDelete'])->name('api.faqs.multi-delete');
Route::post('/faqs/{id}/toggle-status', [FAQController::class, 'toggleStatus'])->name('api.faqs.toggleStatus');
Route::get('/faqs/active-count', [FAQController::class, 'getActiveCount']);


// Teams Section
Route::post('/teams/store', [TeamsController::class, 'store'])->name('api.teams.store');
Route::get('/teams/{id}/edit', [TeamsController::class, 'edit'])->name('api.teams.edit');
Route::put('/teams/{id}', [TeamsController::class, 'update'])->name('api.teams.update');
Route::delete('/teams/{id}', [TeamsController::class, 'destroy'])->name('api.teams.destroy');
Route::post('/teams/list', [TeamsController::class, 'teamsList'])->name('teams.list');
Route::post('teams/multi-delete', [TeamsController::class, 'multiDelete'])->name('api.teams.multi-delete');
Route::delete('/teams/{id}/image', [TeamsController::class, 'deleteImage'])->name('api.teams.deleteImage');
Route::post('/teams/{id}/toggle-status', [TeamsController::class, 'toggleStatus'])->name('api.teams.toggleStatus');
Route::get('/teams/active-count', [TeamsController::class, 'getActiveCount']);


// Contact Section
Route::post('/contacts/store', [ContactController::class, 'store'])->name('api.contacts.store');
Route::get('/contacts/{id}/edit', [ContactController::class, 'edit'])->name('api.contacts.edit');
Route::put('/contacts/{id}', [ContactController::class, 'update'])->name('api.contacts.update');
Route::delete('/contacts/{id}', [ContactController::class, 'destroy'])->name('api.contacts.destroy');
Route::post('/contacts/list', [ContactController::class, 'contactList'])->name('contacts.list');
Route::post('contacts/multi-delete', [ContactController::class, 'multiDelete'])->name('api.contacts.multi-delete');
Route::post('/contacts/{id}/toggle-status', [ContactController::class, 'toggleStatus'])->name('api.contacts.toggleStatus');
Route::get('/contacts/active-count', [ContactController::class, 'getActiveCount']);


// Header Section
Route::post('/header/store', [HeaderController::class, 'store'])->name('api.header.store');
Route::get('/header/{id}/edit', [HeaderController::class, 'edit'])->name('api.header.edit');
Route::put('/header/{id}', [HeaderController::class, 'update'])->name('api.header.update');
Route::delete('/header/{id}', [HeaderController::class, 'destroy'])->name('api.header.destroy');
Route::delete('/header/{id}/image', [HeaderController::class, 'deleteImage'])->name('api.header.deleteImage');
Route::post('/header/list', [HeaderController::class, 'headerList'])->name('header.list');
Route::post('header/multi-delete', [HeaderController::class, 'multiDelete'])->name('api.header.multi-delete');
Route::post('/header/{id}/toggle-status', [HeaderController::class, 'toggleStatus'])->name('api.header.toggleStatus');
Route::get('/header/active-count', [HeaderController::class, 'getActiveCount']);


// Footer Section Routes
Route::post('/footer/store', [FooterController::class, 'store'])->name('api.footer.store');
Route::get('/footer/{id}/edit', [FooterController::class, 'edit'])->name('api.footer.edit');
Route::put('/footer/{id}', [FooterController::class, 'update'])->name('api.footer.update');
Route::delete('/footer/{id}', [FooterController::class, 'destroy'])->name('api.footer.destroy');
Route::post('/footer/list', [FooterController::class, 'contactList'])->name('footer.list');
Route::post('footer/multi-delete', [FooterController::class, 'multiDelete'])->name('api.footer.multi-delete');
Route::post('/footer/{id}/toggle-status', [FooterController::class, 'toggleStatus'])->name('api.footer.toggleStatus');
Route::get('/footer/active-count', [FooterController::class, 'getActiveCount']);


// Newslatter Route
Route::post('/news/list',[NewsLetterController::class,'newsList'])->name('newsletter.list');

//  Social Media Routes

Route::post('/socials-media/store', [SocialMediaController::class, 'store'])->name('api.socials-media.store');
Route::get('/socials-media/{id}/edit', [SocialMediaController::class, 'edit'])->name('api.socials-media.edit');
Route::put('/socials-media/{id}', [SocialMediaController::class, 'update'])->name('api.socials-media.update');
Route::delete('/socials-media/{id}', [SocialMediaController::class, 'destroy'])->name('api.socials-media.destroy');
Route::post('/api/socials-media/list', [SocialMediaController::class, 'socialMediaList'])->name('api.socials-media.list');
Route::post('/socials-media/{id}/toggle-status', [SocialMediaController::class, 'toggleStatus'])->name('api.socials-media.toggleStatus');
Route::get('socials-media/all', [SocialMediaController::class, 'getAllAuthorSocials'])->name('api.socials-media.all');


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


// Category routes

Route::post('/categories/store', [CategoryController::class, 'store'])->name('api.categories.store');
Route::get('/categories/{id}/edit', [CategoryController::class, 'edit'])->name('api.categories.edit');
Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('api.categories.update');
Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('api.categories.destroy');
Route::post('/api/categories/list', [CategoryController::class, 'categoryList'])->name('api.categories.list');
Route::post('/categories/{id}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('api.categories.toggleStatus');


// Cherrypik Pages
Route::post('/cherrypik_pages/store', [CherrypikPageController::class, 'store'])->name('api.cherrypik_pages.store');
Route::get('/cherrypik_pages/{id}/edit', [CherrypikPageController::class, 'edit'])->name('api.cherrypik_pages.edit');
Route::put('/cherrypik_pages/{id}', [CherrypikPageController::class, 'update'])->name('api.cherrypik_pages.update');
Route::delete('/cherrypik_pages/{id}', [CherrypikPageController::class, 'destroy'])->name('api.cherrypik_pages.destroy');
Route::post('/cherrypik_pages/list', [CherrypikPageController::class, 'cherrypikPagesList'])->name('cherrypik_pages.list');
Route::post('cherrypik_pages/multi-delete', [CherrypikPageController::class, 'multiDelete'])->name('api.cherrypik_pages.multi-delete');
Route::delete('/cherrypik_pages/{id}/image', [CherrypikPageController::class, 'deleteImage'])->name('api.cherrypik_pages.deleteImage');
Route::post('/cherrypik_pages/{id}/toggle-status', [CherrypikPageController::class, 'toggleStatus'])->name('api.cherrypik_pages.toggleStatus');
Route::post('/cherrypik_pages/{id}/toggle-inner-page-status', [CherrypikPageController::class, 'toggleInnerPageStatus'])->name('api.cherrypik_pages.toggleStatus');

// END BACKEND API 11-09-25 END //


// START - Frontend API


// Route::get('/hero/test', [HeroController::class, 'testHeroApi'])->name('api.hero.test');
Route::get('/frontend/hero', [HeroController::class, 'getHeroForFrontend'])->name('api.hero.frontend');
Route::get('/frontend/clients', [ClientController::class, 'getClientsForFrontend'])->name('api.clients.frontend');
Route::get('/frontend/about-us', [AboutUsController::class, 'getAboutUsForFrontend'])->name('api.about.frontend');
Route::get('/frontend/stats', [StatsController::class, 'getStatsForFrontend'])->name('api.stats.frontend');
Route::get('/frontend/services/', [ServiceController::class, 'getServicesForFrontend'])->name('api.services.frontend');
Route::get('/frontend/alternatives', [AlternativeController::class, 'getAlternativesForFrontend'])->name('api.alternative.frontend');
Route::get('/frontend/features', [FeaturesController::class, 'getFeaturesForFrontend'])->name('api.features.frontend');
Route::get('/frontend/capabilities', [CapabilitiesController::class, 'getCapabilitiesForFrontend'])->name('api.capabilities.frontend');
Route::get('/frontend/advance-capabilities', [AdvanceCapabilitiesController::class, 'getAdvanceCapabilitiesForFrontend'])->name('api.advance_capabilities.frontend');
Route::get('/frontend/why-us', [WhyUsController::class, 'getWhyUsForFrontend'])->name('api.whyus.frontend');
Route::get('/frontend/call-to-action', [CallToActionController::class, 'getCallToActionForFrontend'])->name('api.cta.frontend');
Route::get('/frontend/portfolio-and-categories', [PortfolioController::class, 'getPortfolioAndCategoriesForFrontend'])->name('api.portfolio.frontend');
Route::get('/frontend/portfolio/{id}', [PortfolioController::class, 'getPortfolioById'])->name('api.portfolio.by.id');
Route::get('/frontend/portfolio-detail', [PortfolioController::class, 'getPortfolioAndCategoriesDetails'])->name('api.portfolio.detail');
Route::get('/frontend/pricing', [PricingController::class, 'getpricingForFrontend'])->name('api.pricing.frontend');
Route::get('/frontend/faq', [FAQController::class, 'getFaqForFrontend'])->name('api.faq.frontend');
Route::get('/frontend/teams', [TeamsController::class, 'getTeamsForFrontend'])->name('api.teams.frontend');
Route::get('/frontend/contacts', [ContactController::class, 'getContactsForFrontend'])->name('api.contacts.frontend');
Route::post('/frontend/contact-message', [ContactController::class, 'storeContactMessage'])->name('api.contact.message.store');
Route::get('/frontend/footer', [App\Http\Controllers\FooterController::class, 'getFooterForFrontend'])->name('api.footer.frontend');
Route::post('/frontend/subscribe', [FooterController::class, 'subscribeStore'])->name('api.subscribeStore.store');

 // Google reviews for frontend
 Route::get('/frontend/google-reviews', [App\Http\Controllers\GoogleReviewsController::class, 'index'])->name('api.google-reviews.frontend');

// END - Frontend API END

// Admin Layout routes

Route::post('/sendOtp', [UserAuthController::class, 'sendOtp'])->name('api.sendOtp');
Route::post('/verifyOtp', [UserAuthController::class, 'verifyOtp'])->name('api.verifyOtp');
Route::post('/resetnewPassword', [UserAuthController::class, 'resetnewPassword'])->name('api.resetnewPassword');
Route::get('/user-data/{id}', [UserAuthController::class, 'getUserDataById'])->name('api.user-data.get');
Route::get('/user-followers/{id}', [UserAuthController::class, 'getUserFollowers']);
Route::get('/user-following/{id}', [UserAuthController::class, 'getUserFollowing']);
Route::get('/dashboard-stats', [UserAuthController::class, 'getDashboardStats']);
Route::post('/refreshToken', [UserAuthController::class, 'refreshToken'])->name('api.refreshToken');
Route::get('/analytics-data', [UserAuthController::class, 'getAnalyticsData'])->name('api.analytics-data');
Route::get('/visitor-count', [UserAuthController::class, 'getVisitorCountApi'])->name('api.visitor.count');
Route::get('/analytics-key', [UserAuthController::class, 'getAnalyticsKey'])->name('api.analytics-key');
Route::post('/profile/{id}', [AdminHomeController::class, 'update'])->name('api.profile.update');
Route::get('/profile/{id}/edit', [AdminHomeController::class, 'edit'])->name('api.profile.edit');
Route::post('/profile/{id}/update-photo', [AdminHomeController::class, 'updatePhoto'])->name('api.profile.update-photo');
Route::post('categories/multi-delete', [CategoryController::class, 'multiDelete'])->name('api.categories.multi-delete');
Route::post('roles/multi-delete', [RoleController::class, 'multiDelete'])->name('api.roles.multi-delete');
Route::post('users/multi-delete', [UserAuthController::class, 'multiDelete'])->name('api.users.multi-delete');
Route::post('permissions/multi-delete', [PermissionController::class, 'multiDelete'])->name('api.permissions.multi-delete');
Route::get('/frontend/header-config', [App\Http\Controllers\HeaderController::class, 'getHeaderConfig'])->name('api.frontend.header');


// END BACKEND API 11-09-25 END //


Route::get('/frontend/navbar-pages', [App\Http\Controllers\CherrypikPageController::class, 'getNavbarPagesForFrontend']);
Route::delete('/frontend/navbar-pages/{id}', [App\Http\Controllers\CherrypikPageController::class, 'deleteNavbarPageFrontend']);

Route::get('/frontend/section-menus', function() {
    return [
        [
            'name' => 'Hero Section',
            'submenu' => [
                ['label' => 'Add Data', 'url' => '/admin/hero/create'],
                ['label' => 'Show Listing', 'url' => '/admin/hero']
            ]
        ],
        [
            'name' => 'Clients Section',
            'submenu' => [
                ['label' => 'Add Data', 'url' => '/admin/client/create'],
                ['label' => 'Show Listing', 'url' => '/admin/client']
            ]
        ],
        [
            'name' => 'About Us Section',
            'submenu' => [
                ['label' => 'Add Data', 'url' => '/admin/aboutUs/create'],
                ['label' => 'Show Listing', 'url' => '/admin/aboutUs']
            ]
        ]
        // (add more sections as needed)
    ];
});

// END - Frontend API END






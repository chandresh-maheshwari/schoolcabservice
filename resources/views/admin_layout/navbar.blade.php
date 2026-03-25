     <div class="horizontal-menu">
         <style>
             /* Keep header compact and consistent on both /admin/* and /{schoolSlug}/* pages. */
             .top-navbar .navbar-brand-wrapper {
                 min-height: 64px;
             }

             .top-navbar .navbar-brand-wrapper .brand-logo img,
             .top-navbar .navbar-brand-wrapper .brand-logo-mini img {
                 max-height: 44px;
                 width: auto;
                 object-fit: contain;
             }

             .top-navbar .nav-profile-img img {
                 width: 36px;
                 height: 36px;
                 border-radius: 50%;
                 object-fit: cover;
             }

             .top-navbar .nav-profile-text {
                 max-width: 220px;
                 overflow: hidden;
                 text-overflow: ellipsis;
                 white-space: nowrap;
             }

             .horizontal-menu .bottom-navbar .container {
                 position: relative;
                 overflow: visible;
             }

             .horizontal-menu .bottom-navbar .page-navigation {
                 position: relative;
                 overflow: visible;
             }

             .horizontal-menu .bottom-navbar .page-navigation > .nav-item.mega-menu {
                 position: static;
                 overflow: visible;
             }

             .horizontal-menu .bottom-navbar .page-navigation > .nav-item.mega-menu > .submenu {
                 width: min(1040px, calc(100% - 24px));
                 max-width: calc(100vw - 32px);
                 left: 0;
                 right: 0;
                 margin-left: auto;
                 margin-right: auto;
                 transform: none;
             }

             .horizontal-menu .bottom-navbar .page-navigation > .nav-item.mega-menu > .submenu > .row,
             .horizontal-menu .bottom-navbar .page-navigation > .nav-item.mega-menu > .submenu > .col-group-wrapper.row {
                 margin-left: 0;
                 margin-right: 0;
             }

             .horizontal-menu .bottom-navbar .page-navigation.school-page-navigation {
                 justify-content: center;
                 align-items: stretch;
                 flex-wrap: wrap;
                 gap: 0;
             }

             .horizontal-menu .bottom-navbar .page-navigation.school-page-navigation > .nav-item {
                 flex: 0 0 auto;
             }

             .horizontal-menu .bottom-navbar .page-navigation.school-page-navigation > .nav-item > .nav-link {
                 min-width: 190px;
                 justify-content: center;
                 text-align: center;
             }

             @media (max-width: 991.98px) {
                 .horizontal-menu .bottom-navbar .page-navigation.school-page-navigation {
                     justify-content: flex-start;
                 }

                 .horizontal-menu .bottom-navbar .page-navigation > .nav-item.mega-menu {
                     position: relative;
                 }

                 .horizontal-menu .bottom-navbar .page-navigation > .nav-item.mega-menu > .submenu {
                     width: auto;
                     max-width: none;
                     left: 0;
                     right: 0;
                     margin-left: 0;
                     margin-right: 0;
                     transform: none;
                 }

                 .horizontal-menu .bottom-navbar .page-navigation.school-page-navigation > .nav-item > .nav-link {
                     min-width: auto;
                 }
             }
         </style>
         <nav class="navbar top-navbar col-lg-12 col-12 p-0 sticky-top">
             <div class="container">
                 @php
                     $authUser = Auth::user();
                     $isAdminUser = $authUser && $authUser->isAdmin();
                     $isSchoolUser = $authUser && method_exists($authUser, 'isSchool') && $authUser->isSchool();
                     $schoolSlug = $currentSchoolSlug ?? request()->route('schoolSlug');
                     $panelDashboardUrl = $isSchoolUser && $schoolSlug
                         ? route('school.dashboard', ['schoolSlug' => $schoolSlug])
                         : route('admin_layout.index');
                     $profileUrl = $isSchoolUser && $schoolSlug
                         ? route('school.profile', ['schoolSlug' => $schoolSlug])
                         : route('admin.profile');
                 @endphp
                 <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
                     <a class="navbar-brand brand-logo" href="{{ $panelDashboardUrl }}">
                        <img src="{{ $schoolBranding['logo_url'] ?? asset('images/for-schools.png') }}" alt="logo">
                     </a>
                     <a class="navbar-brand brand-logo-mini" href="{{ $panelDashboardUrl }}"><img
                             src="{{ $schoolBranding['logo_mini_url'] ?? asset('assets/images/cherrypik_logo.png') }}" alt="logo" /></a>
                 </div>
                 <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
                     <ul class="navbar-nav navbar-nav-right">
                         <li class="nav-item nav-profile dropdown">
                             <a class="nav-link" id="profileDropdown" href="#" data-bs-toggle="dropdown"
                                 aria-expanded="false">
                                 <div class="nav-profile-img">
                                     @php
                                         $authPhoto = ltrim((string) (Auth::user()->photo ?? ''), '/');
                                         if ($authPhoto !== '' && !\Illuminate\Support\Str::startsWith($authPhoto, 'storage/')) {
                                             $authPhoto = 'storage/' . $authPhoto;
                                         }
                                     @endphp
                                     <img src="{{ $authPhoto !== '' ? asset($authPhoto) : asset('assets/images/person.jpg') }}"
                                         alt="author-image"
                                         onerror="this.onerror=null;this.src='{{ asset('assets/images/person.jpg') }}';">
                                 </div>
                                 <div class="nav-profile-text">
                                     <p class="text-black font-weight-semibold m-0">
                                         {{ Auth::user()->first_name ?? 'Guest' }} </p>
                                 </div>
                             </a>
                             <div class="dropdown-menu navbar-dropdown" aria-labelledby="profileDropdown">
                                 <a class="dropdown-item" href="{{ $profileUrl }}">
                                     <i class="mdi mdi-account me-2 top_nav_icon"></i> Profile </a>
                                 <div class="dropdown-divider"></div>
                                 <a class="dropdown-item" href="{{ route('logout.user') }}">
                                     <i class="mdi mdi-logout me-2 top_nav_icon"></i> Signout </a>
                             </div>
                         </li>
                     </ul>
                     <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
                         data-toggle="horizontal-menu-toggle">
                         <span class="mdi mdi-menu"></span>
                     </button>
                 </div>
             </div>
         </nav>

         <nav class="bottom-navbar">
             <div class="container">
                 <ul class="nav page-navigation {{ $isSchoolUser ? 'school-page-navigation' : '' }}">
                     @php
                         $panelPrefix = ($isSchoolUser && $schoolSlug) ? trim($schoolSlug, '/') : 'admin';

                         $can = function (?string $ability) use ($authUser): bool {
                             $ability = trim((string) $ability);
                             return $ability !== ''
                                 && $authUser
                                 && method_exists($authUser, 'canAccessAdminRoute')
                                 && $authUser->canAccessAdminRoute($ability);
                         };

                         $schoolCabMenuAbilities = [
                             'vehicleType.index',
                             'vehicle.index',
                             'driver.index',
                             'school.index',
                             'routes.index',
                             'packageDetails.index',
                             'booking.index',
                             'emergency.index',
                             'rating.index',
                             'stopPickup.index',
                             'driverHistoryList.index',
                             'parent.index',
                             'child.index',
                         ];

                         $showSchoolCabMenu = false;
                         foreach ($schoolCabMenuAbilities as $ability) {
                             if ($can($ability)) {
                                 $showSchoolCabMenu = true;
                                 break;
                             }
                         }

                         $mobileRequestsAbilities = ['leaveRequests.index', 'supportRequests.index'];
                         $showMobileRequestsMenu = false;
                         foreach ($mobileRequestsAbilities as $ability) {
                             if ($can($ability)) {
                                 $showMobileRequestsMenu = true;
                                 break;
                             }
                         }

                         $usersMenuAbilities = ['users.index', 'roles.index', 'permissions.index'];
                         $showUsersMenu = false;
                         foreach ($usersMenuAbilities as $ability) {
                             if ($can($ability)) {
                                 $showUsersMenu = true;
                                 break;
                             }
                         }

                         $cmsMenuAbilities = [
                             'aboutSection.index',
                             'service.index',
                             'howItWorks.index',
                             'clientSection.index',
                             'benefitSection.index',
                             'testimonialSection.index',
                             'faqSection.index',
                             'priceSection.index',
                             'msbAppSection.index',
                             'socialMediaSection.index',
                             'contactMessageSection.index',
                         ];
                         $showCmsMenu = false;
                         foreach ($cmsMenuAbilities as $ability) {
                             if ($can($ability)) {
                                 $showCmsMenu = true;
                                 break;
                             }
                         }
                     @endphp
                     <li class="nav-item">
                         <a href="{{ $panelDashboardUrl }}" class="nav-link">
                             <i class="la la-home menu-icon"></i>
                             <span class="menu-title{{ request()->is($panelPrefix . '/dashboard') ? ' active' : '' }}">Dashboard</span>
                         </a>
                     </li>

                     @if ($showSchoolCabMenu)
                     <li class="nav-item mega-menu">
                         <a href="#" class="nav-link">
                             <i class="la la-cogs menu-icon"></i>
                             <span
                                 class="menu-title{{ request()->is($panelPrefix . '/vehicle*') ||
                                     request()->is($panelPrefix . '/vehicleType*') ||
                                     request()->is($panelPrefix . '/driver*') ||
                                     request()->is($panelPrefix . '/school*') ||
                                     request()->is($panelPrefix . '/routes*') ||
                                     request()->is($panelPrefix . '/packageDetails*') ||
                                     request()->is($panelPrefix . '/booking*') ||
                                     request()->is($panelPrefix . '/emergency*') ||
                                     request()->is($panelPrefix . '/rating*') ||
                                     request()->is($panelPrefix . '/stopPickup*') ||
                                     request()->is($panelPrefix . '/driverHistoryList*') ||
                                     request()->is($panelPrefix . '/parent*') ||
                                     request()->is($panelPrefix . '/child*')
                                     ? ' active'
                                     : '' }}">
                             School Cab Services</span>
                             <i class="menu-arrow"></i></a>

                         @if ($isAdminUser)
                             <div class="submenu" aria-labelledby="sectionDropdown">
                             <div class="row">
                                 <!-- Column 1 -->
                                 <div class="col-md-4">
                                     @if ($can('vehicleType.index'))
                                     <a href="{{ $isSchoolUser && $schoolSlug ? route('school.vehicleType.index', ['schoolSlug' => $schoolSlug]) : route('vehicleType.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-blue"><i class=" fa fa-car"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Vehicle Type</h6>
                                             <p>Listing of Vehicle Type</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('vehicle.index'))
                                     <a href="{{ $isSchoolUser && $schoolSlug ? route('school.vehicle.index', ['schoolSlug' => $schoolSlug]) : route('vehicle.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-green"><i class="fa fa-cab"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Vehicle </h6>
                                             <p>Listing of Vehicle</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('driver.index'))
                                     <a href="{{ $isSchoolUser && $schoolSlug ? route('school.driver.index', ['schoolSlug' => $schoolSlug]) : route('driver.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-yellow"><i class="fa fa-user-tie"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Driver </h6>
                                             <p>Listing of Driver</p>
                                         </div>
                                     </a>
                                     @endif
                                      @if ($can('school.index'))
                                      <a href="{{ $isSchoolUser && $schoolSlug ? route('school.school.index', ['schoolSlug' => $schoolSlug]) : route('school.index') }}"
                                          class="menu-item text-decoration-none">
                                          <div class="menu-icon icon-yellow"><i class="fa fa-school"></i>
                                          </div>
                                          <div class="menu-content">
                                              <h6>School </h6>
                                              <p>Listing of School</p>
                                          </div>
                                      </a>
                                      @endif
                                     @if ($can('routes.index'))
                                     <a href="{{ $isSchoolUser && $schoolSlug ? route('school.routes.index', ['schoolSlug' => $schoolSlug]) : route('routes.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-red"><i class="fa fa-route"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Route </h6>
                                             <p>Listing of Route</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('packageDetails.index'))
                                     <a href="{{ $isSchoolUser && $schoolSlug ? route('school.packageDetails.index', ['schoolSlug' => $schoolSlug]) : route('packageDetails.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-yellow"><i class="fa fa-box"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Package Detail </h6>
                                             <p>Listing of Package Detail</p>
                                         </div>
                                     </a>
                                     @endif
                                 </div>
                                 <!-- Column 2 -->
                                 <div class="col-md-4">
                                     @if ($can('emergency.index'))
                                     <a href="{{ $isSchoolUser && $schoolSlug ? route('school.emergency.index', ['schoolSlug' => $schoolSlug]) : route('emergency.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-black"><i class=" fa fa-exclamation"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Emergency </h6>
                                             <p>Listing of Emergency</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('rating.index'))
                                     <a href="{{ $isSchoolUser && $schoolSlug ? route('school.rating.index', ['schoolSlug' => $schoolSlug]) : route('rating.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-red"><i class=" fa fa-star"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Feedback/Rating </h6>
                                             <p>Listing of Rating</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('stopPickup.index'))
                                     <a href="{{ $isSchoolUser && $schoolSlug ? route('school.stopPickup.index', ['schoolSlug' => $schoolSlug]) : route('stopPickup.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-Orange  "><i class=" fa fa-stop-circle"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Stop Or Pickup Point </h6>
                                             <p>Listing of Stop Or Pickup Point</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('driverHistoryList.index'))
                                     <a href="{{ $isSchoolUser && $schoolSlug ? route('school.driverHistoryList.index', ['schoolSlug' => $schoolSlug]) : route('driverHistoryList.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-green"><i class=" fa fa-history"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Driver History </h6>
                                             <p>Listing of Driver History</p>
                                         </div>
                                     </a>
                                     @endif

                                 </div>
                                 <div class="col-md-4">
                                     @if ($can('child.index'))
                                     <a href="{{ $isSchoolUser && $schoolSlug ? route('school.child.index', ['schoolSlug' => $schoolSlug]) : route('child.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-red"><i class=" fa fa-child"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Child </h6>
                                             <p>Child, Parents and Booking tabs</p>
                                         </div>
                                     </a>
                                     @endif
                                 </div>
                             </div>
                         </div>
                         @else
                             <div class="submenu" aria-labelledby="sectionDropdown">
                                 <div class="row">
                                     <div class="col-md-4">
                                         @if ($can('vehicleType.index'))
                                         <a href="{{ $isSchoolUser && $schoolSlug ? route('school.vehicleType.index', ['schoolSlug' => $schoolSlug]) : route('vehicleType.index') }}" class="menu-item text-decoration-none">
                                             <div class="menu-icon icon-blue"><i class="fa fa-car"></i></div>
                                             <div class="menu-content">
                                                 <h6>Vehicle Type</h6>
                                                 <p>Listing of Vehicle Type</p>
                                             </div>
                                         </a>
                                         @endif
                                         @if ($can('vehicle.index'))
                                         <a href="{{ $isSchoolUser && $schoolSlug ? route('school.vehicle.index', ['schoolSlug' => $schoolSlug]) : route('vehicle.index') }}" class="menu-item text-decoration-none">
                                             <div class="menu-icon icon-green"><i class="fa fa-cab"></i></div>
                                             <div class="menu-content">
                                                 <h6>Vehicle</h6>
                                                 <p>Listing of Vehicle</p>
                                             </div>
                                         </a>
                                         @endif
                                         @if ($can('driver.index'))
                                         <a href="{{ $isSchoolUser && $schoolSlug ? route('school.driver.index', ['schoolSlug' => $schoolSlug]) : route('driver.index') }}" class="menu-item text-decoration-none">
                                             <div class="menu-icon icon-yellow"><i class="fa fa-user-tie"></i></div>
                                             <div class="menu-content">
                                                 <h6>Driver</h6>
                                                 <p>Listing of Driver</p>
                                             </div>
                                         </a>
                                         @endif
                                         @if ($can('routes.index'))
                                         <a href="{{ $isSchoolUser && $schoolSlug ? route('school.routes.index', ['schoolSlug' => $schoolSlug]) : route('routes.index') }}" class="menu-item text-decoration-none">
                                             <div class="menu-icon icon-red"><i class="fa fa-route"></i></div>
                                             <div class="menu-content">
                                                 <h6>Route</h6>
                                                 <p>Listing of Route</p>
                                             </div>
                                         </a>
                                         @endif
                                         @if ($can('stopPickup.index'))
                                         <a href="{{ $isSchoolUser && $schoolSlug ? route('school.stopPickup.index', ['schoolSlug' => $schoolSlug]) : route('stopPickup.index') }}" class="menu-item text-decoration-none">
                                             <div class="menu-icon icon-Orange"><i class="fa fa-stop-circle"></i></div>
                                             <div class="menu-content">
                                                 <h6>Stop / Pickup</h6>
                                                 <p>Listing of Stop / Pickup</p>
                                             </div>
                                         </a>
                                         @endif
                                     </div>
                                     <div class="col-md-4">
                                         @if ($can('child.index'))
                                         <a href="{{ $isSchoolUser && $schoolSlug ? route('school.child.index', ['schoolSlug' => $schoolSlug]) : route('child.index') }}" class="menu-item text-decoration-none">
                                             <div class="menu-icon icon-red"><i class="fa fa-child"></i></div>
                                             <div class="menu-content">
                                                 <h6>Child</h6>
                                                 <p>Child, Parents and Booking tabs</p>
                                             </div>
                                         </a>
                                         @endif
                                         @if ($can('emergency.index'))
                                         <a href="{{ $isSchoolUser && $schoolSlug ? route('school.emergency.index', ['schoolSlug' => $schoolSlug]) : route('emergency.index') }}" class="menu-item text-decoration-none">
                                             <div class="menu-icon icon-black"><i class="fa fa-exclamation"></i></div>
                                             <div class="menu-content">
                                                 <h6>Emergency</h6>
                                                 <p>Listing of Emergency</p>
                                             </div>
                                         </a>
                                         @endif
                                         @if ($can('rating.index'))
                                         <a href="{{ $isSchoolUser && $schoolSlug ? route('school.rating.index', ['schoolSlug' => $schoolSlug]) : route('rating.index') }}" class="menu-item text-decoration-none">
                                             <div class="menu-icon icon-red"><i class="fa fa-star"></i></div>
                                             <div class="menu-content">
                                                 <h6>Feedback / Rating</h6>
                                                 <p>Listing of Rating</p>
                                             </div>
                                         </a>
                                         @endif
                                     </div>
                                     <div class="col-md-4">
                                         @if ($can('driverHistoryList.index'))
                                         <a href="{{ $isSchoolUser && $schoolSlug ? route('school.driverHistoryList.index', ['schoolSlug' => $schoolSlug]) : route('driverHistoryList.index') }}" class="menu-item text-decoration-none">
                                             <div class="menu-icon icon-green"><i class="fa fa-history"></i></div>
                                             <div class="menu-content">
                                                 <h6>Driver History</h6>
                                                 <p>Listing of Driver History</p>
                                             </div>
                                         </a>
                                         @endif
                                          @if ($can('school.index'))
                                          <a href="{{ $isSchoolUser && $schoolSlug ? route('school.school.index', ['schoolSlug' => $schoolSlug]) : route('school.index') }}"
                                              class="menu-item text-decoration-none">
                                              <div class="menu-icon icon-yellow"><i class="fa fa-school"></i></div>
                                              <div class="menu-content">
                                                  <h6>School</h6>
                                                  <p>Listing of School</p>
                                              </div>
                                          </a>
                                          @endif
                                      </div>
                                  </div>
                              </div>
                         @endif
                     </li>
                     @endif
                     @if ($showMobileRequestsMenu)
                     <li class="nav-item mega-menu">
                         <a href="#" class="nav-link">
                             <i class="la la-mobile menu-icon"></i>
                             <span
                                 class="menu-title{{ request()->is($panelPrefix . '/leaveRequests*') || request()->is($panelPrefix . '/supportRequests*') ? ' active' : '' }}">
                                 Mobile Requests</span>
                             <i class="menu-arrow"></i></a>
                         <div class="submenu user-management">
                             <div class="col-group-wrapper row">
                                 @if ($can('leaveRequests.index'))
                                 <a href="{{ $isSchoolUser && $schoolSlug ? route('school.leaveRequests.index', ['schoolSlug' => $schoolSlug]) : route('leaveRequests.index') }}" class="menu-item text-decoration-none">
                                     <div class="menu-icon icon-orange">
                                         <i class="la la-calendar-check-o"></i>
                                     </div>
                                     <div class="menu-content">
                                         <h6>Leave Requests</h6>
                                         <p>Review parent leave approvals</p>
                                     </div>
                                 </a>
                                 @endif
                                 @if ($can('supportRequests.index'))
                                 <a href="{{ $isSchoolUser && $schoolSlug ? route('school.supportRequests.index', ['schoolSlug' => $schoolSlug]) : route('supportRequests.index') }}" class="menu-item text-decoration-none">
                                     <div class="menu-icon icon-teal">
                                         <i class="la la-life-ring"></i>
                                     </div>
                                     <div class="menu-content">
                                         <h6>Support Requests</h6>
                                         <p>Track and resolve parent tickets</p>
                                     </div>
                                 </a>
                                 @endif
                             </div>
                         </div>
                     </li>
                     @endif
                     @if ($isAdminUser && $showUsersMenu)
                     <li class="nav-item mega-menu">
                         <a href="#" class="nav-link">
                             <i class="la la-users menu-icon"></i>
                             <span
                                 class="menu-title{{ request()->is('admin/users*') || request()->is('admin/roles*') || request()->is('admin/permissions*') ? ' active' : '' }}">Users</span>
                             <i class="menu-arrow"></i></a>
                         <div class="submenu user-management">
                             <div class="col-group-wrapper row">
                                 @if ($can('users.index'))
                                 <a href="{{ route('users.index') }}" class="menu-item text-decoration-none">
                                     <div class="menu-icon icon-blue">
                                         <i class="la la-users"></i>
                                     </div>
                                     <div class="menu-content">
                                         <h6>Users</h6>
                                         <p>View and manage all users</p>
                                     </div>
                                 </a>
                                 @endif
                                 @if ($can('roles.index'))
                                 <a href="{{ route('roles.index') }}" class="menu-item text-decoration-none">
                                     <div class="menu-icon icon-orange">
                                         <i class="la la-id-badge"></i>
                                     </div>
                                     <div class="menu-content">
                                         <h6>Roles</h6>
                                         <p>View and manage roles</p>
                                     </div>
                                 </a>
                                 @endif
                                 @if ($can('permissions.index'))
                                 <a href="{{ route('permissions.index') }}" class="menu-item text-decoration-none">
                                     <div class="menu-icon icon-teal">
                                         <i class="la la-lock"></i>
                                     </div>
                                     <div class="menu-content">
                                         <h6>Permissions</h6>
                                         <p>View and manage permissions</p>
                                     </div>
                                 </a>
                                 @endif
                             </div>
                         </div>
                     </li>
                     @endif
                     @if ($isAdminUser && $showCmsMenu)
                     <li class="nav-item mega-menu">
                         <a href="#" class="nav-link">
                             <i class="la la-cogs menu-icon"></i>
                             <span
                                 class="menu-title{{ request()->is('cms/aboutSection*') ||
                                     request()->is('cms/service*') ||
                                     request()->is('cms/driver*') ||
                                     request()->is('cms/school*') ||
                                     request()->is('cms/routes*') ||
                                     request()->is('cms/packageDetails*') ||
                                     request()->is('cms/booking*') ||
                                     request()->is('cms/emergency*') ||
                                     request()->is('cms/rating*') }}">
                                 ADMIN/CMS</span>
                             <i class="menu-arrow"></i></a>

                         <div class="submenu" aria-labelledby="sectionDropdown">
                             <div class="row">
                                 <!-- Column 1 -->
                                 <div class="col-md-4">
                                     @if ($can('aboutSection.index'))
                                     <a href="{{ route('aboutSection.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-yellow"><i class=" fa fa-info-circle"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>About Section</h6>
                                             <p>Listing of About Section</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('service.index'))
                                     <a href="{{ route('service.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-green"><i class=" fa fa-cogs"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Service</h6>
                                             <p>Listing of Service</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('howItWorks.index'))
                                     <a href="{{ route('howItWorks.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-red"><i class=" fa fa-solid fa-briefcase"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>How It Works</h6>
                                             <p>Listing of How It Works</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('clientSection.index'))
                                     <a href="{{ route('clientSection.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-blue"><i class=" fa fa-solid fa-id-badge"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Client Section</h6>
                                             <p>Listing of Client Section</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('benefitSection.index'))
                                     <a href="{{ route('benefitSection.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-green"><i class=" fa fa-check"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Benefit Section</h6>
                                             <p>Listing of Benefit Section</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('testimonialSection.index'))
                                     <a href="{{ route('testimonialSection.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-red"><i class=" fa fa-cogs"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Testimonail Section</h6>
                                             <p>Listing of Testimonail Section</p>
                                         </div>
                                     </a>
                                     @endif
                                 </div>
                                 <div class="col-md-4">
                                     @if ($can('faqSection.index'))
                                     <a href="{{ route('faqSection.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-blue"><i class=" fa fa-question-circle"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>FAQ Section</h6>
                                             <p>Listing of FAQ Section</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('priceSection.index'))
                                     <a href="{{ route('priceSection.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-yellow"><i class=" fa fa-tag"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Price Section</h6>
                                             <p>Listing of Price Section</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('msbAppSection.index'))
                                     <a href="{{ route('msbAppSection.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-red"><i class=" fa fa-solid fa-credit-card"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>MSB App Section</h6>
                                             <p>Listing of MSB App Section</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('socialMediaSection.index'))
                                     <a href="{{ route('socialMediaSection.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-purple">
                                             <i class="la la-share-alt"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Social Media Details</h6>
                                             <p>Manage all your social media links.</p>
                                         </div>
                                     </a>
                                     @endif
                                     @if ($can('contactMessageSection.index'))
                                     <a href="{{ route('contactMessageSection.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-red"><i class=" fa fa-link"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Contact Message Section</h6>
                                             <p>Listing of Contact Message Section</p>
                                         </div>
                                     </a>
                                     @endif

                                 </div>
                             </div>
                     </li>
                     @endif
                 </ul>
             </div>
         </nav>
     </div>

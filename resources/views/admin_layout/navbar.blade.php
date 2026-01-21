     <div class="horizontal-menu">
         <nav class="navbar top-navbar col-lg-12 col-12 p-0 sticky-top">
             <div class="container">
                 <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
                     <a class="navbar-brand brand-logo" href="{{ route('admin_layout.index') }}">
                         <img src="{{ asset('assets/images/fav-icon/cherrypikFavicon.png') }}" alt="logo">
                     </a>
                     <a class="navbar-brand brand-logo-mini" href="{{ route('admin_layout.index') }}"><img
                             src="{{ asset('assets/images/cherrypik_logo.png') }}" alt="logo" /></a>
                 </div>
                 <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
                     <ul class="navbar-nav navbar-nav-right">
                         <li class="nav-item nav-profile dropdown">
                             <a class="nav-link" id="profileDropdown" href="#" data-bs-toggle="dropdown"
                                 aria-expanded="false">
                                 <div class="nav-profile-img">
                                     <img src="{{ Auth::check() && Auth::user()->photo ? asset(Auth::user()->photo) : asset('assets/images/person.jpg') }}"
                                         alt="author-image">
                                 </div>
                                 <div class="nav-profile-text">
                                     <p class="text-black font-weight-semibold m-0">
                                         {{ Auth::user()->first_name ?? 'Guest' }} </p>
                                 </div>
                             </a>
                             <div class="dropdown-menu navbar-dropdown" aria-labelledby="profileDropdown">
                                 <a class="dropdown-item" href="{{ route('admin.profile') }}">
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
                 <ul class="nav page-navigation">
                     <li class="nav-item mega-menu">
                         <a href="#" class="nav-link">
                             <i class="la la-cogs menu-icon"></i>
                             <span
                                 class="menu-title{{ request()->is('admin/vehicle*') ||
                                     request()->is('admin/vehicleType*') ||
                                     request()->is('admin/driver*') ||
                                     request()->is('admin/school*') ||
                                     request()->is('admin/routes*') ||
                                     request()->is('admin/packageDetails*') ||
                                     request()->is('admin/booking*') ||
                                     request()->is('admin/emergency*') ||
                                     request()->is('admin/rating*') }}">
                                 School Cab Services</span>
                             <i class="menu-arrow"></i></a>

                         <div class="submenu" aria-labelledby="sectionDropdown">
                             <div class="row">
                                 <!-- Column 1 -->
                                 <div class="col-md-4">
                                     <a href="{{ route('vehicleType.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-blue"><i class=" fa fa-car"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Vehicle Type</h6>
                                             <p>Listing of Vehicle Type</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('vehicle.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-green"><i class="fa fa-cab"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Vehicle </h6>
                                             <p>Listing of Vehicle</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('driver.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-yellow"><i class="fa fa-user-tie"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Driver </h6>
                                             <p>Listing of Driver</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('school.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-yellow"><i class="fa fa-school"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>School </h6>
                                             <p>Listing of School</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('routes.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-red"><i class="fa fa-route"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Route </h6>
                                             <p>Listing of Route</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('packageDetails.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-yellow"><i class="fa fa-box"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Package Detail </h6>
                                             <p>Listing of Package Detail</p>
                                         </div>
                                     </a>
                                 </div>
                                 <!-- Column 2 -->
                                 <div class="col-md-4">
                                     <a href="{{ route('booking.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-blue"><i class=" fa fa-bus"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Booking </h6>
                                             <p>Listing of Booking</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('emergency.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-black"><i class=" fa fa-exclamation"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Emergency </h6>
                                             <p>Listing of Emergency</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('rating.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-red"><i class=" fa fa-star"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Feedback/Rating </h6>
                                             <p>Listing of Rating</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('stopPickup.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-Orange  "><i class=" fa fa-stop-circle"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Stop Or Pickup Point </h6>
                                             <p>Listing of Stop Or Pickup Point</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('driverHistoryList.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-green"><i class=" fa fa-history"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Driver History </h6>
                                             <p>Listing of Driver History</p>
                                         </div>
                                     </a>

                                     <a href="{{ route('parent.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-yellow"><i class=" fa fa-home"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Parent </h6>
                                             <p>Listing of Parent</p>
                                         </div>
                                     </a>
                                 </div>
                                 <div class="col-md-4">
                                     <a href="{{ route('child.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-red"><i class=" fa fa-child"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Child </h6>
                                             <p>Listing of Child</p>
                                         </div>
                                     </a>
                                 </div>
                             </div>
                         </div>
                     </li>
                     <li class="nav-item mega-menu">
                         <a href="#" class="nav-link">
                             <i class="la la-users menu-icon"></i>
                             <span
                                 class="menu-title{{ request()->is('admin/users*') || request()->is('admin/roles*') || request()->is('admin/permissions*') ? ' active' : '' }}">Users</span>
                             <i class="menu-arrow"></i></a>
                         <div class="submenu user-management">
                             <div class="col-group-wrapper row">
                                 <a href="{{ route('users.index') }}" class="menu-item text-decoration-none">
                                     <div class="menu-icon icon-blue">
                                         <i class="la la-users"></i>
                                     </div>
                                     <div class="menu-content">
                                         <h6>Users</h6>
                                         <p>View and manage all users</p>
                                     </div>
                                 </a>
                                 <a href="{{ route('roles.index') }}" class="menu-item text-decoration-none">
                                     <div class="menu-icon icon-orange">
                                         <i class="la la-id-badge"></i>
                                     </div>
                                     <div class="menu-content">
                                         <h6>Roles</h6>
                                         <p>View and manage roles</p>
                                     </div>
                                 </a>
                                 <a href="{{ route('permissions.index') }}" class="menu-item text-decoration-none">
                                     <div class="menu-icon icon-teal">
                                         <i class="la la-lock"></i>
                                     </div>
                                     <div class="menu-content">
                                         <h6>Permissions</h6>
                                         <p>View and manage permissions</p>
                                     </div>
                                 </a>
                             </div>
                         </div>
                     </li>
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
                                     <a href="{{ route('aboutSection.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-yellow"><i class=" fa fa-info-circle"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>About Section</h6>
                                             <p>Listing of About Section</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('service.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-green"><i class=" fa fa-cogs"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Service</h6>
                                             <p>Listing of Service</p>
                                         </div>
                                     </a>
                                      <a href="{{ route('howItWorks.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-red"><i class=" fa fa-solid fa-briefcase"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>How It Works</h6>
                                             <p>Listing of How It Works</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('clientSection.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-blue"><i class=" fa fa-solid fa-id-badge"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Client Section</h6>
                                             <p>Listing of Client Section</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('benefitSection.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-blue"><i class=" fa fa-check"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Benefit Section</h6>
                                             <p>Listing of Benefit Section</p>
                                         </div>
                                     </a>
                                 </div>
                             </div>
                     </li>
                 </ul>
             </div>
         </nav>
     </div>

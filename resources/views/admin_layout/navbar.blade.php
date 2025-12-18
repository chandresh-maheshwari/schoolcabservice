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
                                 class="menu-title{{ request()->is('admin/hero*') || request()->is('admin/vehicleType*') }}">
                                 Sections Management</span>
                             <i class="menu-arrow"></i></a>

                         <div class="submenu" aria-labelledby="sectionDropdown">
                             <div class="row">
                                 <!-- Column 1 -->
                                 <div class="col-md-4">
                                     <a href="{{ route('hero.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-red"><i class="la la-star"></i></div>
                                         <div class="menu-content">
                                             <h6>Hero Section</h6>
                                             <p>Listing of Hero Section</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('vehicleType.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-blue"><i class=" fa fa-car
"></i></div>
                                         <div class="menu-content">
                                             <h6>Vehicle Type</h6>
                                             <p>Listing of Vehicle Type</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('vehicle.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-green"><i class="fa fa-cab
"></i></div>
                                         <div class="menu-content">
                                             <h6>Vehicle </h6>
                                             <p>Listing of Vehicle</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('driver.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-yellow"><i class="fa fa-cab
"></i></div>
                                         <div class="menu-content">
                                             <h6>Driver </h6>
                                             <p>Listing of Driver</p>
                                         </div>
                                     </a>


                                 </div>

                                 <!-- Column 2 -->
                                 <div class="col-md-4">
                                 </div>
                                 <div class="col-md-4">
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
                 </ul>
             </div>
         </nav>
     </div>

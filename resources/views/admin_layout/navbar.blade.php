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
                                 class="menu-title{{ request()->is('admin/hero*') ||
                                 request()->is('admin/client*') ||
                                 request()->is('admin/aboutUs*') ||
                                 request()->is('admin/stats*') ||
                                 request()->is('admin/service*') ||
                                 request()->is('admin/alternative*') ||
                                 request()->is('admin/feature*') ||
                                 request()->is('admin/capability*') ||
                                 request()->is('admin/advance_capability*') ||
                                 request()->is('admin/why_us*') ||
                                 request()->is('admin/call_to_action*') ||
                                 request()->is('admin/portfolio*') ||
                                 request()->is('admin/pricing*') ||
                                 request()->is('admin/faqs*') ||
                                 request()->is('admin/teams*') ||
                                 request()->is('admin/contacts*')
                                     ? ' active'
                                     : '' }}">
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

                                     <a href="{{ route('client.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-green"><i class="la la-users"></i></div>
                                         <div class="menu-content">
                                             <h6>Clients Section</h6>
                                             <p>Manage client logos and details.</p>
                                         </div>
                                     </a>

                                     <a href="{{ route('aboutUs.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-blue"><i class="la la-info-circle"></i></div>
                                         <div class="menu-content">
                                             <h6>About Us Section</h6>
                                             <p>Edit your about section content.</p>
                                         </div>
                                     </a>

                                     <a href="{{ route('stats.index') }}" class="menu-item">
                                         <div class="menu-icon icon-orange"><i class="la la-chart-bar"></i></div>
                                         <div class="menu-content">
                                             <h6>Stats Section</h6>
                                             <p>Manage company statistics and milestones.</p>
                                         </div>
                                     </a>

                                     <a href="{{ route('service.index') }}" class="menu-item">
                                         <div class="menu-icon icon-teal"><i class="la la-briefcase"></i></div>
                                         <div class="menu-content">
                                             <h6>Service Section</h6>
                                             <p>Add or manage your services list.</p>
                                         </div>
                                     </a>

                                     <a href="{{ route('alternative.index') }}" class="menu-item">
                                         <div class="menu-icon icon-purple"><i class="la la-random"></i></div>
                                         <div class="menu-content">
                                             <h6>Alternative Section</h6>
                                             <p>Show alternative solutions or comparisons.</p>
                                         </div>
                                     </a>
                                 </div>

                                 <!-- Column 2 -->
                                 <div class="col-md-4">

                                     <a href="{{ route('feature.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-teal">
                                             <i class="la la-cube"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Features Section</h6>
                                             <p>Highlight your main product features.</p>
                                         </div>
                                     </a>

                                     <a href="{{ route('capability.index') }}" class="menu-item">
                                         <div class="menu-icon icon-orange"><i class="la la-tools"></i></div>
                                         <div class="menu-content">
                                             <h6>Capabilities Section</h6>
                                             <p>Showcase your team or system capabilities.</p>
                                         </div>
                                     </a>

                                     <a href="{{ route('advance_capability.index') }}" class="menu-item">
                                         <div class="menu-icon icon-blue"><i class="la la-rocket"></i></div>
                                         <div class="menu-content">
                                             <h6>Advance Capabilities Section</h6>
                                             <p>Manage advanced company skills and features.</p>
                                         </div>
                                     </a>

                                     <a href="{{ route('why_us.index') }}" class="menu-item">
                                         <div class="menu-icon icon-green"><i class="la la-question-circle"></i></div>
                                         <div class="menu-content">
                                             <h6>Why Us Section</h6>
                                             <p>Highlight reasons clients choose you.</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('call_to_action.index') }}" class="menu-item">
                                         <div class="menu-icon icon-orange"><i class="la la-bullhorn"></i></div>
                                         <div class="menu-content">
                                             <h6>Call To Action Section</h6>
                                             <p>Manage promotional call-to-action banners.</p>
                                         </div>
                                     </a>

                                     <a href="{{ route('portfolio.index') }}" class="menu-item">
                                         <div class="menu-icon icon-purple"><i class="la la-image"></i></div>
                                         <div class="menu-content">
                                             <h6>Portfolio Section</h6>
                                             <p>Showcase your project portfolio.</p>
                                         </div>
                                     </a>

                                 </div>
                                 <div class="col-md-4">


                                     <a href="{{ route('pricing.index') }}" class="menu-item">
                                         <div class="menu-icon icon-red"><i class="la la-tags"></i></div>
                                         <div class="menu-content">
                                             <h6>Pricing Section</h6>
                                             <p>List and manage pricing plans or packages.</p>
                                         </div>
                                     </a>

                                     <a href="{{ route('faqs.index') }}" class="menu-item">
                                         <div class="menu-icon icon-teal"><i class="la la-question"></i></div>
                                         <div class="menu-content">
                                             <h6>FAQ Management</h6>
                                             <p>Add and manage frequently asked questions.</p>
                                         </div>
                                     </a>


                                     <a href="{{ route('teams.index') }}" class="menu-item">
                                         <div class="menu-icon icon-green"><i class="la la-user-friends"></i></div>
                                         <div class="menu-content">
                                             <h6>Teams Section</h6>
                                             <p>Manage team member profiles and roles.</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('contacts.index') }}" class="menu-item">
                                         <div class="menu-icon icon-blue"><i class="la la-envelope"></i></div>
                                         <div class="menu-content">
                                             <h6>Contact Section</h6>
                                             <p>Manage contact details and inquiries.</p>
                                         </div>
                                     </a>
                                     <a href="{{ route('contact_messages.index') }}" class="menu-item">
                                         <div class="menu-icon icon-blue"><i class="la la-envelope"></i></div>
                                         <div class="menu-content">
                                             <h6>Frontend Contact Section</h6>
                                             <p>Manage contact details and inquiries.</p>
                                         </div>
                                     </a>
                                 </div>
                             </div>
                         </div>
                     </li>

                     <li class="nav-item mega-menu">
                         <a href="#" class="nav-link">
                             <i class="la la-book menu-icon"></i>
                             <span
                                 class="menu-title{{ request()->is('admin/header*') || request()->is('admin/newsletter*') || request()->is('admin/footer*') || request()->is('admin/socials-media*') ? ' active' : '' }}">
                                 Management</span>
                             <i class="menu-arrow"></i></a>
                         <div class="submenu cherrypik-management">
                             <div class="row">
                                 <div class="col-md">
                                     <a href="{{ route('header.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-blue">
                                             <i class="la la-heading"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Header Details</h6>
                                             <p>Add Header Details</p>
                                         </div>
                                     </a>

                                     <a href="{{ route('newsletter.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-teal">
                                             <i class="la la-envelope-open-text"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Newsletter Details</h6>
                                             <p>News Letter Listing</p>
                                         </div>
                                     </a>
                                 </div>

                                 <div class="col-md">
                                     <a href="{{ route('footer.index') }}" class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-orange">
                                             <i class="la la-columns"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Footer Details</h6>
                                             <p>Footer Detail Listing</p>
                                         </div>
                                     </a>

                                     <a href="{{ route('socials-media.index') }}"
                                         class="menu-item text-decoration-none">
                                         <div class="menu-icon icon-purple">
                                             <i class="la la-share-alt"></i>
                                         </div>
                                         <div class="menu-content">
                                             <h6>Social Media Details</h6>
                                             <p>Manage all your social media links.</p>
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
                             <i class="la la-tags menu-icon"></i>
                             <span
                                 class="menu-title{{ request()->is('admin/categories*') ? ' active' : '' }}">Category</span>
                             <i class="menu-arrow"></i></a>
                         <div class="submenu category-management">
                             <div class="col-group-wrapper row">
                                 <a href="{{ route('categories.index') }}" class="menu-item text-decoration-none">
                                     <div class="menu-icon icon-teal">
                                         <i class="la la-lock"></i>
                                     </div>
                                     <div class="menu-content">
                                         <h6>Category</h6>
                                         <p>View and manage Category</p>
                                     </div>
                                 </a>
                             </div>
                         </div>
                     </li>
                     <li class="nav-item mega-menu">
                         <a href="#" class="nav-link">
                             <i class="la la-eye menu-icon"></i>
                             <span
                                 class="menu-title{{ request()->is('admin/cherrypik_pages*') ? ' active' : '' }}">Pages</span>
                             <i class="menu-arrow"></i></a>
                         </a>
                         <div class="submenu page-management">
                             <div class="col-group-wrapper row">
                                 <a href="{{ route('cherrypik_pages.index') }}"
                                     class="menu-item text-decoration-none">
                                     <div class="menu-icon icon-blue">
                                         <i class="la la-file-alt"></i>
                                     </div>
                                     <div class="menu-content">
                                         <h6>Pages</h6>
                                         <p>View and manage all pages</p>
                                     </div>
                                 </a>
                             </div>
                         </div>
                     </li>
                 </ul>
             </div>
         </nav>
     </div>

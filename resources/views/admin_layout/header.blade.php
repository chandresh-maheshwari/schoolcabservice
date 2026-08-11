<!DOCTYPE html>
<html lang="en">
<head>

    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets/img/apple-icon.png') }}">
    @php
        $branding = $schoolBranding ?? \App\Helpers\SchoolBranding::current();
    @endphp
    <link rel="icon" type="image/png" href="{{ $branding['favicon_url'] ?? asset('assets/images/fav-icon/Tahukar Magazine logo vv [Recovered].png') }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="auth-user-id" content="{{ auth()->id() ?? '' }}" />
    <meta name="school-slug" content="{{ $currentSchoolSlug ?? request()->route('schoolSlug') ?? '' }}" />
    <meta name="auth-is-admin" content="{{ auth()->check() && method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin() ? '1' : '0' }}" />
    <meta name="auth-is-school" content="{{ auth()->check() && method_exists(auth()->user(), 'isSchool') && auth()->user()->isSchool() ? '1' : '0' }}" />
    <meta name="auth-is-superadmin" content="{{ !empty($authIsSuperAdmin) ? '1' : '0' }}" />
    <meta name="auth-can-access-all-admin-routes" content="{{ !empty($authCanAccessAllAdminRoutes) ? '1' : '0' }}" />
    @php
    $route = Route::currentRouteName();
    $titles = [
        // 'admin_layout.index' => 'Home | Admin - CherrypikSoftware.com',
        // 'home_pages.create' => 'Add Home Page | Admin - CherrypikSoftware.com',
        // 'home_pages.edit' => 'Edit Home Page | Admin - CherrypikSoftware.com',
        // 'home_pages.index' => 'Home Pages | Admin - CherrypikSoftware.com',
        // 'about_us.create' => 'Add About Us Page | Admin - CherrypikSoftware.com',
        // 'about_us.edit' => 'Edit About Us Page | Admin - CherrypikSoftware.com',
        // 'about_us.index' => 'About Us Pages | Admin - CherrypikSoftware.com',
        // 'cms_categories.create' => 'Add CMS Category | Admin - CherrypikSoftware.com',
        // 'clients.create' => 'Add CMS Category | Admin - CherrypikSoftware.com',
        // 'cms_categories.edit' => 'Edit CMS Category | Admin - CherrypikSoftware.com',
        // 'cms_categories.index' => 'CMS Categories | Admin - CherrypikSoftware.com',
        // 'author_socials.create' => 'Add Socials | Admin - CherrypikSoftware.com',
        // 'author_socials.edit' => 'Edit Socials | Admin - CherrypikSoftware.com',
        // 'author_socials.index' => 'Socials | Admin - CherrypikSoftware.com',
        // 'magazine_categories.create' => 'Add Magazine Category | Admin - CherrypikSoftware.com',
        // 'magazine_categories.edit' => 'Edit Magazine Category | Admin - CherrypikSoftware.com',
        // 'magazine_categories.index' => 'Magazine Categories | Admin - CherrypikSoftware.com',
        // 'magazines.create' => 'Add Magazine | Admin - CherrypikSoftware.com',
        // 'magazines.edit' => 'Edit Magazine | Admin - CherrypikSoftware.com',
        // 'magazines.index' => 'Magazines | Admin - CherrypikSoftware.com',
        // 'users.create' => 'Add User | Admin - CherrypikSoftware.com',
        // 'users.edit' => 'Edit User | Admin - CherrypikSoftware.com',
        // 'users.index' => 'Users | Admin - CherrypikSoftware.com',
        // 'roles.create' => 'Add Role | Admin - CherrypikSoftware.com',
        // 'roles.edit' => 'Edit Role | Admin - CherrypikSoftware.com',
        // 'roles.index' => 'Roles | Admin - CherrypikSoftware.com',
        // 'permissions.create' => 'Add Permission | Admin - CherrypikSoftware.com',
        // 'permissions.edit' => 'Edit Permission | Admin - CherrypikSoftware.com',
        // 'permissions.index' => 'Permissions | Admin - CherrypikSoftware.com',
        // 'categories.create' => 'Add Category | Admin - CherrypikSoftware.com',
        // 'categories.edit' => 'Edit Category | Admin - CherrypikSoftware.com',
        // 'categories.index' => 'Categories | Admin - CherrypikSoftware.com',
        // 'writers.index' => 'Writers | Admin - CherrypikSoftware.com',
        // 'quotes.create' => 'Add Quote | Admin - CherrypikSoftware.com',
        // 'quotes.edit' => 'Edit Quote | Admin - CherrypikSoftware.com',
        // 'quotes.index' => 'Quotes | Admin - CherrypikSoftware.com',
        // 'guidelines.create' => 'Add Guideline | Admin - CherrypikSoftware.com',
        // 'guidelines.edit' => 'Edit Guideline | Admin - CherrypikSoftware.com',
        // 'guidelines.index' => 'Guidelines | Admin - CherrypikSoftware.com',
        // 'blogs.create' => 'Add Blog | Admin - CherrypikSoftware.com',
        // 'blogs.edit' => 'Edit Blog | Admin - CherrypikSoftware.com',
        // 'blogs.index' => 'Blogs | Admin - CherrypikSoftware.com',
        // 'blog_categories.create' => 'Add Blog Category | Admin - CherrypikSoftware.com',
        // 'blog_categories.edit' => 'Edit Blog Category | Admin - CherrypikSoftware.com',
        // 'blog_categories.index' => 'Blogs Categories | Admin - CherrypikSoftware.com',
        // 'faq_categories.create' => 'Add FAQ Category | Admin - CherrypikSoftware.com',
        // 'faq_categories.edit' => 'Edit FAQ Category | Admin - CherrypikSoftware.com',
        // 'faq_categories.index' => 'FAQs Categories | Admin - CherrypikSoftware.com',
        // 'faqs.create' => 'Add FAQ | Admin - CherrypikSoftware.com',
        // 'faqs.edit' => 'Edit FAQ | Admin - CherrypikSoftware.com',
        // 'faqs.index' => 'FAQs | Admin - CherrypikSoftware.com',
        // 'contacts.create' => 'Add Contact | Admin - CherrypikSoftware.com',
        // 'contacts.edit' => 'Edit Contact | Admin - CherrypikSoftware.com',
        // 'contacts.index' => 'Contacts | Admin - CherrypikSoftware.com',
        // 'contact_messages.index' => 'Contacted Users | Admin - CherrypikSoftware.com',


    ];
    $title = $titles[$route] ?? 'Admin - schoolcabservice.com';
@endphp
<title>{{ $title }}</title>
    <!-- plugins:css -->
    {{-- <link rel="stylesheet" href="{{ asset('assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css')}}" /> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/vendors/select2/select2.min.js')}}" /> --}}

    <!-- <link rel="stylesheet" href="{{ asset('assets/vendors/datatables.net-bs4/dataTables.bootstrap4.css')}}"> -->
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> --}}


    <!-- endinject -->
    <!-- Plugin css for this page -->
    {{-- <link rel="stylesheet" href="../assets/css/demo_2/style.css" /> --}}
    <link href="{{ asset('assets/css/categories.css') }}?v={{ filemtime(public_path('assets/css/categories.css')) }}" rel="stylesheet" />

    <link href="{{ asset('assets/vendors/mdi/css/materialdesignicons.min.css') }}?v={{ filemtime(public_path('assets/vendors/mdi/css/materialdesignicons.min.css')) }}" rel="stylesheet" />

    <link href="{{ asset('assets/vendors/flag-icon-css/css/flag-icon.min.css') }}?v={{ filemtime(public_path('assets/vendors/flag-icon-css/css/flag-icon.min.css')) }}" rel="stylesheet" />

    <link href="{{ asset('assets/vendors/css/vendor.bundle.base.css') }}?v={{ filemtime(public_path('assets/vendors/css/vendor.bundle.base.css')) }}" rel="stylesheet" />
    {{-- select 2 --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css')}}" /> --}}
    <link rel="stylesheet" href="{{ asset('assets/vendors/select2/select2.min.css')}}?v={{ filemtime(public_path('assets/vendors/select2/select2.min.css')) }}" />
    {{-- <link rel="stylesheet" href="{{ asset('assets/vendors/select2/select2.min.js')}}" /> --}}

    <!-- <link rel="stylesheet" href="{{ asset('assets/vendors/datatables.net-bs4/dataTables.bootstrap4.css')}}"> -->
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> --}}


    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link href="{{ asset('assets/vendors/jquery-bar-rating/css-stars.css') }}?v={{ filemtime(public_path('assets/vendors/jquery-bar-rating/css-stars.css')) }}" rel="stylesheet" />


    <link href="{{ asset('assets/vendors/font-awesome/css/font-awesome.min.css') }}?v={{ filemtime(public_path('assets/vendors/font-awesome/css/font-awesome.min.css')) }}" rel="stylesheet" />
    <!-- End plugin css for this page -->
    <!-- Layout styles -->
    {{-- <link rel="stylesheet" href="../assets/css/demo_2/style.css" /> --}}
    <link href="{{ asset('assets/css/adminStyle.css') }}?v={{ filemtime(public_path('assets/css/adminStyle.css')) }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/school-theme.css') }}?v={{ filemtime(public_path('assets/css/school-theme.css')) }}" rel="stylesheet" />

    <!-- End layout styles -->

    {{-- <link href="{{ asset('assets/images/favicon.png') }}" rel="stylesheet" /> --}}
    <link rel="shortcut icon" type="images/png" href="{{ $branding['favicon_url'] ?? asset('assets/images/fav-icon/cherrypikFavicon.png') }}">


    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">

    {{-- ckeditor js --}}
    <script src="{{ asset('js/ckeditor/ckeditor.js') }}"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    {{-- jquery --}}
    <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">



    <!-- FontAwesome Icon Picker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fontawesome-iconpicker@3.2.0/dist/css/fontawesome-iconpicker.min.css">

    <!-- FontAwesome Icon Picker JS -->
    <script src="https://cdn.jsdelivr.net/npm/fontawesome-iconpicker@3.2.0/dist/js/fontawesome-iconpicker.min.js"></script>


    <style>
        :root {
            --school-primary: {{ $branding['primary_color'] ?? '#2D336B' }};
            --school-secondary: {{ $branding['secondary_color'] ?? '#7886c7' }};
            --bs-primary: var(--school-primary);
            --bs-primary-rgb: {{ $branding['primary_rgb'] ?? '45,51,107' }};
            --bs-link-color: var(--school-secondary);
            --bs-link-hover-color: var(--school-secondary);
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #aaa;
            border-radius: 3px;
            padding: 9px;
            background-color: transparent;
            margin-left: 3px;
            margin-right: 5px;
        }

        .dataTables_wrapper .dataTables_info {
            clear: none;
            float: left;
            padding-top: 10px;
            margin-left: 12px;
            font-size: 15px;
            line-height: 1.4;
            color: #2D336B !important;
            font-family: inherit;
            font-weight: 500;
        }
        .dataTables_wrapper .dataTables_length {
            float: left;
            margin-top: 0 !important;
            display: flex;
            align-items: center;
        }
        .dataTables_wrapper .dataTables_length select {
            width: 72px !important;
            min-width: 72px !important;
            height: 34px !important;
            padding: 6px 22px 6px 10px !important;
            line-height: 1.3 !important;
            font-size: 14px !important;
            border-radius: 8px !important;
            font-family: inherit !important;
        }

        .dataTables_wrapper .bottom,
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            min-height: 0 !important;
        }

        .dataTables_wrapper .bottom {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 16px;
            padding-top: 4px;
            flex-wrap: wrap;
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: 10px !important;
            margin-left: 14px !important;
            display: flex;
            align-items: center;
        }

        .dataTables_wrapper .dataTables_paginate {
            padding-top: 0 !important;
            display: flex !important;
            align-items: center;
            gap: 8px;
            font-family: inherit;
            margin-left: auto;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            min-width: 36px !important;
            height: 36px !important;
            padding: 7px 12px !important;
            margin-left: 0 !important;
            border-radius: 10px !important;
            line-height: 1.3 !important;
            font-size: 15px !important;
            font-weight: 500 !important;
            font-family: inherit !important;
            color: #2D336B !important;
            border: 1px solid transparent !important;
            background: transparent !important;
            transition: all 0.18s ease;
            opacity: 1 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.previous,
        .dataTables_wrapper .dataTables_paginate .paginate_button.next {
            min-width: auto !important;
            padding: 7px 8px !important;
            color: #2D336B !important;
            font-weight: 500 !important;
            opacity: 1 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            color: #2D336B !important;
            background: #eef2ff !important;
            border-color: #c7d2fe !important;
            opacity: 1 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            color: #1e293b !important;
            background: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            color: #5b628f !important;
            background: transparent !important;
            border-color: transparent !important;
            box-shadow: none !important;
            opacity: 1 !important;
        }

        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_info * ,
        .dataTables_wrapper .dataTables_paginate .paginate_button,
        .dataTables_wrapper .dataTables_paginate .paginate_button:link,
        .dataTables_wrapper .dataTables_paginate .paginate_button:visited,
        .dataTables_wrapper .dataTables_paginate .paginate_button span {
            color: #2D336B !important;
        }

        .dataTables_wrapper .dataTables_length .select2,
        .dataTables_wrapper .dataTables_length .select2-container {
            display: none !important;
        }

        .dataTables_wrapper .dataTables_length select.select2-hidden-accessible {
            position: static !important;
            width: 72px !important;
            min-width: 72px !important;
            height: 34px !important;
            margin: 1px 6px 1px 0 !important;
            clip: auto !important;
            clip-path: none !important;
            overflow: visible !important;
            opacity: 1 !important;
        }

        @media (max-width: 768px) {
            .dataTables_wrapper .bottom {
                gap: 10px;
            }

            .dataTables_wrapper .dataTables_info {
                margin-left: 8px !important;
            }

            .dataTables_wrapper .dataTables_paginate {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>

    <script>
        window.__permissionNames = @json($authPermissionNames ?? []);
        window.__authIsSuperAdmin = (document.querySelector('meta[name="auth-is-superadmin"]')?.getAttribute('content') === '1');
        window.__authCanAccessAllAdminRoutes = (document.querySelector('meta[name="auth-can-access-all-admin-routes"]')?.getAttribute('content') === '1');

        window.__canRoute = function(routeName) {
            if (!routeName) return false;
            let name = String(routeName).trim();
            if (!name) return false;

            // Only strip `school.` for nested school-panel routes like `school.vehicle.index`.
            // Keep top-level routes like `school.index` intact.
            if (name.startsWith('school.') && name.split('.').length >= 3) {
                name = name.slice('school.'.length);
            }
            if (name.startsWith('api.')) name = name.slice('api.'.length);

            const alwaysAllowed = new Set([
                'logout.user',
                'admin.profile',
                'school.profile',
                'profile.edit',
                'profile.update',
                'admin_layout.index',
                'school.dashboard',
                'school.profile.edit',
                'school.profile.update',
            ]);
            if (alwaysAllowed.has(name)) return true;
            if (window.__authCanAccessAllAdminRoutes) return true;

            const singleNameMap = {
                'rolelist': 'roles.index',
                'userlist': 'users.index',
                'toggle-user-status': 'users.update',
            };
            if (singleNameMap[name]) name = singleNameMap[name];

            const parts = name.split('.');
            if (parts.length >= 2) {
                const action = parts[parts.length - 1].toLowerCase();
                const actionMap = {
                    'list': 'index',
                    'multi-delete': 'destroy',
                    'deleted-list': 'index',
                    'togglestatus': 'update',
                    'toggle-status': 'update',
                    'update-photo': 'update',
                    'deleteimage': 'update',
                    'vehicleimage': 'update',
                    'rcimage': 'update',
                    'insuranceimage': 'update',
                    'licenseimage': 'update',
                    'adharcardimage': 'update',
                    'childimage': 'update',
                    'childadhaarimage': 'update',
                    'aboutimage': 'update',
                    'changepassword': 'update',
                    'delete': 'destroy',
                    'delete-all': 'destroy',
                    'force-delete': 'destroy',
                };
                if (actionMap[action]) {
                    parts[parts.length - 1] = actionMap[action];
                    name = parts.join('.');
                }
            }

            // Mirror backend behavior: Super Admin can always access role/permission management.
            if (window.__authIsSuperAdmin && (name.startsWith('roles.') || name.startsWith('permissions.'))) {
                return true;
            }

            const perms = Array.isArray(window.__permissionNames) ? window.__permissionNames : [];
            return perms.includes(name);
        };
    </script>
</head>





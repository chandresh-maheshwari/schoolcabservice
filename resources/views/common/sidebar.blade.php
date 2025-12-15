<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
    <style>


        .logo {
            display: flex;
            align-items: center;
            padding: 10px;
        }

        .logo img {
            /* width: 30px; */
            width: 200px !important;
            height: 70px !important;
            margin-right: 19px !important;
            margin-left: -25px !important;
            transition: opacity 0.3s;
            /* margin-bottom: -28px !important;
            margin-top: -20px !important; */
        }

        .sidebar.collapsed .logo img {
            opacity: 0;
        }

        .nav {
            list-style-type: none;
            padding: 0;
        }


        .nav-link {
            color: #ecf0f1;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-link:hover {
            background-color: #34495e;
        }

        .nav-icon {
            margin-right: 10px;
            color: #F9EDED !important;
        }

        .nav-dropdown-items {
            list-style-type: none;
            padding-left: 20px;
        }

        .nav-dropdown-toggle.active {
            background-color: #34495e;
        }

        .nav-dropdown-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-dropdown-toggle::after {
            content: '\f0da'; /* FontAwesome down arrow */
            font-family: 'FontAwesome';
            margin-left: 10px;
            transition: transform 0.3s;
        }

        .nav-dropdown-toggle.active::after {
            transform: rotate(90deg); /* Rotate to right */
        }
        
        .sidebar {
            transition: width 0.3s;
            width: 260px;
        }

        .sidebar.collapsed {
            width: 83px;
        }

        .sidebar .nav-link {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-wrapper {
            transition: width 0.3s;
            width: 250px; 
        }

        .sidebar-wrapper.collapsed {
            width: 83px; 
            height: calc(150vh - 75px);
        }

        .main-panel {
            transition: margin-left 0.3s;
            margin-left: 260px !important;
            position: relative !important;
            float: none !important;
            width: auto !important;
        }

        .main-panel.collapsed {
            margin-left: 83px !important;
        }

        .navbar {
            transition: margin-left 0.3s;
            /* margin-left: 250px;  */
        }

        .navbar.collapsed {
            margin-right: 40px; 
        }

        .main-content {
            transition: margin-left 0.3s;
            margin-left: 260px;
        }

        .main-content.collapsed {
            margin-left: 83px;
        }

        .sidebar.collapsed .nav-dropdown-items {
            position: relative;
            left: -20px;
            /* background-color: #2c3e50; */
            width: 130%;
            z-index: 1000;
            display: none;
        }

        .sidebar.collapsed .nav-item.nav-dropdown.active .nav-dropdown-items {
            display: block;
        }

        .sidebar.collapsed .nav-dropdown-items .nav-link {
            justify-content: flex-start;
            padding-left: 20px;
        }

        .nav-dropdown-toggle.active::after {
            transform: rotate(90deg);
        }

        #sidebarToggle {
            color: white;
            background-color: transparent;
            margin-left: auto;
        }

        .sidebar.collapsed #sidebarToggle {
            transform: translateX(-50px) !important;
        }

   

        .toggle-logo {
            display: none;
            width: 20px !important;
            height: 20px !important;
            transition: transform 0.3s !important;
        }

        .sidebar.collapsed .toggle-logo {
            display: block !important;
            max-width: 88% !important;
            opacity: 1 !important;
            height: 80px !important;
            margin-left: 175px !important;
            margin-right: -3px !important;
        }

        /* Add this style to hide the arrow icon when collapsed */
        .sidebar.collapsed #arrowIcon {
            display: none;
        }

    </style>
</head>
<body>
    <div class="sidebar" data-color="orange" id="sidebar">
    
        <div class="logo" style="padding: 29px !important;">
          <img src="{{ asset('assets/images/name-white-logo.png') }}" width="100%">
          {{-- <a href="http://www.creative-tim.com" class="simple-text logo-normal">
            Modern Technology
          </a> --}}
          <button id="sidebarToggle" class="btn btn-primary" style="position: absolute; right: 10px; color: white; background-color: transparent;">
            <i class="la la-chevron-left" id="arrowIcon" style="display: inline-block; margin-left: 5px;"></i>
          </button>
          <img src="{{ asset('assets/images/new_logo.png') }}" alt="Logo" class="toggle-logo" style="display: none; position: absolute; right: 10px;">
        </div>
        <div class="sidebar-wrapper" id="sidebar-wrapper">
            <ul class="nav">
                <!-- CMS Management -->
                <li class="nav-item nav-dropdown">
                    <a class="nav-link nav-dropdown-toggle" href="#" title="CMS Management"><i class="nav-icon la la-cogs"></i> CMS Management</a>
                    <ul class="nav-dropdown-items" style="display: none;">
                        <li class="nav-item">
                            <a class="nav-link nav-dropdown-toggle" href="#" title="Home"><i class="nav-icon la la-home"></i>Home</a>
                            <ul class="nav-dropdown-items" style="display: none;">
                                <li class="nav-item"><a class="nav-link" href="{{ route('home_pages.create') }}" title="Add Home Page Details"><i class="nav-icon la la-plus-circle"></i>Add Home Page Details</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('home_pages.index') }}" title="Home Page Listing"><i class="nav-icon la la-list"></i>Home Page Listing</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link nav-dropdown-toggle" href="#" title="About Us"><i class="nav-icon la la-info-circle"></i>About Us</a>
                            <ul class="nav-dropdown-items" style="display: none;">
                                <li class="nav-item"><a class="nav-link" href="{{ route('about_us.create') }}" title="Add About Us Details"><i class="nav-icon la la-plus-circle"></i>Add About Us Details</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('about_us.index') }}" title="About Us Listing"><i class="nav-icon la la-list"></i>About Us Listing</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link nav-dropdown-toggle" href="#" title="CMS Category"><i class="nav-icon la la-folder"></i>CMS Category</a>
                            <ul class="nav-dropdown-items" style="display: none;">
                                <li class="nav-item"><a class="nav-link" href="{{ route('cms_categories.create') }}" title="Add Category"><i class="nav-icon la la-plus-circle"></i>Add Category</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('cms_categories.index') }}" title="Listing of Category"><i class="nav-icon la la-list"></i>Listing of Category</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>

                <!-- Magazine Management -->
                <li class="nav-item nav-dropdown">
                    <a class="nav-link nav-dropdown-toggle" href="#" title="Magazine Management"><i class="nav-icon la la-book"></i>Magazine Management</a>
                    <ul class="nav-dropdown-items" style="display: none;">
                        <li class="nav-item"><a class="nav-link" href="{{ route('magazine_categories.create') }}" title="Add Magazine Category"><i class="nav-icon la la-plus-circle"></i>Add Magazine Category</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('magazine_categories.index') }}" title="Magazine Category Listing"><i class="nav-icon la la-list"></i>Magazine Category Listing</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('magazines.create') }}" title="Add Magazine"><i class="nav-icon la la-plus-circle"></i>Add Magazine</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('magazines.index') }}" title="Magazine Listing"><i class="nav-icon la la-list"></i>Magazine Listing</a></li>
                    </ul>
                </li>

                <!-- Users -->
                <li class="nav-item nav-dropdown">
                    <a class="nav-link nav-dropdown-toggle" href="#" title="Users"><i class="nav-icon la la-users"></i>Users</a>
                    <ul class="nav-dropdown-items" style="display: none;">
                        <li class="nav-item"><a class="nav-link" href="{{ route('users.create') }}" title="Add User"><i class="nav-icon la la-user-plus"></i>Add User</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('users.index') }}" title="List of All Users"><i class="nav-icon la la-list"></i>List of All Users</a></li>
                    </ul>
                </li>

                <!-- Roles -->
                <li class="nav-item nav-dropdown">
                    <a class="nav-link nav-dropdown-toggle" href="#" title="Roles"><i class="nav-icon la la-key"></i>Roles</a>
                    <ul class="nav-dropdown-items" style="display: none;">
                        <li class="nav-item"><a class="nav-link" href="{{ route('roles.create') }}" title="Add Role"><i class="nav-icon la la-plus-circle"></i>Add Role</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('roles.index') }}" title="Roles Listing"><i class="nav-icon la la-list"></i>Roles Listing</a></li>
                    </ul>
                </li>

                <!-- Permission -->
                <li class="nav-item nav-dropdown">
                    <a class="nav-link nav-dropdown-toggle" href="#" title="Permission"><i class="nav-icon la la-lock"></i>Permission</a>
                    <ul class="nav-dropdown-items" style="display: none;">
                        <li class="nav-item"><a class="nav-link" href="{{ route('permissions.create') }}" title="Add Permission"><i class="nav-icon la la-plus-circle"></i>Add Permission</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('permissions.index') }}" title="Permissions Listing"><i class="nav-icon la la-list"></i>Permissions Listing</a></li>
                    </ul>
                </li>

                <!-- Menu -->
                <li class="nav-item">
                    <a class="nav-link" href="#" title="Header Menu"><i class="nav-icon la la-bars"></i>Header Menu</a>
                </li>

                <!-- Category -->
                <li class="nav-item nav-dropdown">
                    <a class="nav-link nav-dropdown-toggle" href="#" title="Category"><i class="nav-icon la la-tags"></i>Category</a>
                    <ul class="nav-dropdown-items" style="display: none;">
                        <li class="nav-item"><a class="nav-link" href="{{ route('categories.create') }}" title="Add Category"><i class="nav-icon la la-plus-circle"></i>Add Category</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('categories.index') }}" title="Category Listing"><i class="nav-icon la la-list"></i>Category Listing</a></li>
                    </ul>
                </li>

                <!-- Pages -->
                <li class="nav-item nav-dropdown">
                    <a class="nav-link nav-dropdown-toggle" href="#" title="Pages"><i class="nav-icon la la-file"></i>Pages</a>
                    <ul class="nav-dropdown-items" style="display: none;">
                        <li class="nav-item"><a class="nav-link" href="{{ route('cherrypik_pages.create') }}" title="Add Page"><i class="nav-icon la la-plus-circle"></i>Add Page</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('cherrypik_pages.index') }}" title="Page Listing"><i class="nav-icon la la-list"></i>Page Listing</a></li>
                    </ul>
                </li>

                <!-- Author Social -->
                <li class="nav-item nav-dropdown">
                    <a class="nav-link nav-dropdown-toggle" href="#" title="Author Social"><i class="nav-icon la la-tags"></i>Author Social</a>
                    <ul class="nav-dropdown-items" style="display: none;">
                        <li class="nav-item"><a class="nav-link" href="{{ route('author_socials.create') }}" title="Add Author Social"><i class="nav-icon la la-plus-circle"></i>Add Author Social</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('author_socials.index') }}" title="Author Social Listing"><i class="nav-icon la la-list"></i>Author Social Listing</a></li>
                    </ul>
                </li>

                <!-- View More Management -->
                <li class="nav-item nav-dropdown">
                    <a class="nav-link nav-dropdown-toggle" href="#" title="View More Management"><i class="nav-icon la la-eye"></i>View More Management</a>
                    <ul class="nav-dropdown-items" style="display: none;">
                        <li class="nav-item nav-dropdown">
                            <a class="nav-link nav-dropdown-toggle" href="#" title="Writer Management"><i class="nav-icon la la-pencil"></i>Writer Management</a>
                            <ul class="nav-dropdown-items" style="display: none;">
                                <li class="nav-item"><a class="nav-link" href="{{ route('writers.index') }}" title="Writers Listing"><i class="nav-icon la la-list"></i>Writers Listing</a></li>
                            </ul>
                        </li>
                        <li class="nav-item nav-dropdown">
                            <a class="nav-link nav-dropdown-toggle" href="#" title="Quotes Management"><i class="nav-icon la la-quote-right"></i> Quotes Management</a>
                            <ul class="nav-dropdown-items" style="display: none;">
                                <li class="nav-item"><a class="nav-link" href="{{ route('quotes.create') }}" title="Add Quote"><i class="nav-icon la la-plus-circle"></i>Add Quote</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('quotes.index') }}" title="Listing of Quote"><i class="nav-icon la la-list"></i>Listing of Quote</a></li>
                            </ul>
                        </li>
                        <li class="nav-item nav-dropdown">
                            <a class="nav-link nav-dropdown-toggle" href="#" title="Guidelines Management"><i class="nav-icon la la-bookmark"></i> Guidelines Management</a>
                            <ul class="nav-dropdown-items" style="display: none;">
                                <li class="nav-item"><a class="nav-link" href="{{ route('guidelines.create') }}" title="Add Guidelines"><i class="nav-icon la la-plus-circle"></i>Add Guidelines</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('guidelines.index') }}" title="Listing of Guidelines"><i class="nav-icon la la-list"></i>Listing of Guidelines</a></li>
                            </ul>
                        </li>
                        <li class="nav-item nav-dropdown">
                            <a class="nav-link nav-dropdown-toggle" href="#" title="Blog Management"><i class="nav-icon la la-blog"></i> Blog Management</a>
                            <ul class="nav-dropdown-items" style="display: none;">
                                <li class="nav-item"><a class="nav-link" href="{{ route('blogs.create') }}" title="Add Blog"><i class="nav-icon la la-plus-circle"></i>Add Blog</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('blogs.index') }}" title="Listing of Blog"><i class="nav-icon la la-list"></i>Listing of Blog</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('blog_categories.create') }}" title="Add Blog Category"><i class="nav-icon la la-plus-circle"></i>Add Blog Category</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('blog_categories.index') }}" title="Listing of Blog Category"><i class="nav-icon la la-list"></i>Listing of Blog Category</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>

                <!-- FAQ Management -->
                <li class="nav-item nav-dropdown">
                    <a class="nav-link nav-dropdown-toggle" href="#" title="FAQ Management"><i class="nav-icon la la-question-circle"></i>FAQ Management</a>
                    <ul class="nav-dropdown-items" style="display: none;">
                        <li class="nav-item"><a class="nav-link" href="{{ route('faq_categories.create') }}" title="Add FAQ Category"><i class="nav-icon la la-plus-circle"></i>Add FAQ Category</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('faq_categories.index') }}" title="Listing of FAQ Categories"><i class="nav-icon la la-list"></i>Listing of FAQ Categories</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('faqs.create') }}" title="Add FAQ"><i class="nav-icon la la-plus-circle"></i>Add FAQ</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('faqs.index') }}" title="Listing of FAQ"><i class="nav-icon la la-list"></i>Listing of FAQ</a></li>
                    </ul>
                </li>

                <!-- Contact Management -->
                <li class="nav-item nav-dropdown">
                    <a class="nav-link nav-dropdown-toggle" href="#" title="Contact Management"><i class="nav-icon la la-envelope"></i>Contact Management</a>
                    <ul class="nav-dropdown-items" style="display: none;">
                        <li class="nav-item"><a class="nav-link" href="{{ route('contacts.create') }}" title="Add Contact Info"><i class="nav-icon la la-plus-circle"></i>Add Contact Info</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('contacts.index') }}" title="Contact Info Listing"><i class="nav-icon la la-list"></i>Contact Info Listing</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('contact_messages.index') }}" title="Contacted Users"><i class="nav-icon la la-users"></i>Contacted Users</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('sidebar-wrapper').classList.toggle('collapsed');
            document.getElementById('main-panel').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('collapsed');
            
            // Change icon when sidebar is collapsed
            const arrowIcon = document.getElementById('arrowIcon');
            if (document.getElementById('sidebar').classList.contains('collapsed')) {
                arrowIcon.classList.remove('la-chevron-left');
                arrowIcon.classList.add('la-chevron-right'); // Use chevron icon
            } else {
                arrowIcon.classList.remove('la-chevron-right');
                arrowIcon.classList.add('la-chevron-left');
            }
        });

        document.querySelectorAll('.nav-dropdown-toggle').forEach(item => {
            item.addEventListener('click', event => {
                const dropdown = item.nextElementSibling;

                if (dropdown.style.display === 'none' || dropdown.style.display === '') {
                    dropdown.style.display = 'block';
                    item.classList.add('active');
                } else {
                    dropdown.style.display = 'none';
                    item.classList.remove('active');
                }
            });
        });

        // Ensure clicking the new_logo.png image toggles the sidebar
        document.querySelector('.toggle-logo').addEventListener('click', function() {
            document.getElementById('sidebarToggle').click();
        });
    </script>
</body>
</html>
<STYle>
    /* .return-bar{margin-top: 97px; height: 73px;} */
</STYle>
@extends('header')

<body class="home page-template-default page page-id-35"> <noscript> <iframe
            src="https://www.googletagmanager.com/ns.html?id=GTM-WJZNXJQ" height="0" width="0"
            style="display:none;visibility:hidden"></iframe> </noscript>
    <div class="text-center col-brand-6 hidden" id="cookies">
        <div class="container">
            <h3 id="cookie-header" class="text-center dark-grey"><strong>We use cookies</strong></h3>
            <h3 id="cookie-header-opened" class="text-center dark-grey hidden"><strong>Why do we use cookies?</strong>
                <p class="cookie-arrow">↓</p>
            </h3>
            <div class="mb5"><a id="cookie-read-more" rel="nofollow noopener external bookmark">read more</a></div>
        </div>
        <div id="cookie-main-text" class="hidden mb5">
            <p>We use cookies to offer you our web-based service. A Cookie is a small text based file given to you, that
                helps identify you to our site. Specifically, we use cookies to optimise site functionality for your use
                and resolve any website issues. By using this website or accepting this message you agree to our use of
                cookies. Alternatively, if you do not consent, you can manage your cookie settings in your browser.</p>
            <a href="https://lovespace.co.uk/privacy/">Legal and privacy agreement here</a>
        </div>
        <div><a id="cookie-accept" rel="nofollow noopener external bookmark" class="btn btn-primary">Accept</a><a
                id="cookie-accept-opened" rel="nofollow noopener external bookmark" class="btn btn-primary hidden">I
                accept cookies</a></div>
    </div>
    <header class="uk-only">
        <nav id="navbar" class="navbar navbar-default" role="navigation">
            <div class="navbar-header"> <button type="button" class="navbar-toggle" id="mobile-menu-toggle"
                    data-toggle="collapse" data-target=".navbar-ex1-collapse">
                    <div class="menu-icon-container closed">
                        <div class="menu-line one"></div>
                        <div class="menu-line two"></div>
                        <div class="menu-line three"></div>
                        <div class="menu-line four"></div>
                    </div> <span class="sr-only">Why are you using LOVESPACE?*Toggle navigation</span>
                </button> <button type="button" class="navbar-toggle contact-us" title="Contact us"
                    aria-label="Contact us" data-toggle="collapse" data-target=".navbar-contact-collapse"></button> <a
                    id="header-logo" class="navbar-brand hide-text" href="https://lovespace.co.uk/"
                    title="Home">LOVESPACE</a> </div>
            <div class="collapse navbar-collapse navbar-contact-collapse">
                <div class="menu-container contact">
                    <ul class="nav navbar-nav pull-right" style="background-color: transparent;">
                        <li class="dropdown">
                            <ul style="width: 100px; min-width: 100px;" class="dropdown-menu">
                                <li><a href="tel:08008021018" title="Call us" class="orange-3" data-toggle="collapse"
                                        data-target=".navbar-contact-collapse"><i class="fa fa-phone"></i> Call</a></li>
                                <li><a class="orange-3" data-toggle="collapse" data-target=".navbar-contact-collapse"
                                        href="javascript:$zopim.livechat.window.show();"><i class="fa fa-comment"></i>
                                        Chat</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="collapse navbar-collapse navbar-ex1-collapse">
                <div class="menu-container">
                    <ul class="nav navbar-nav pull-right" style="background-color: transparent;">
                    @if (session()->has('userid'))
                        <li id="guest-user" class=""><a href="{{ route('logout.user') }}" id="navbar-sign-in"
                                class="btn btn-warning" ssstyle="border: 4px solid orange;" title="Logout">Logout</a>
                        </li>
                        @endif

                        @if (!session()->has('userid'))
                            <li id="guest-user" class=""><a href="{{ route('signin') }}" id="navbar-sign-in"
                                    class="btn btn-warning" ssstyle="border: 4px solid orange;" title="Sign in">Sign
                                    in</a></li>
                        @endif
                        <div class="mobile-menu visible-xs visible-sm">
                            <div id="mobile-dropdown" class="manage-dropdown open hide">
                                <div class="manage-toggle" id="manage-mobile">
                                    <div>My LOVESPACE</div> <img
                                        src="/wp-content/themes/lovespace/assets/icons/dropdown-arrow.svg"
                                        class="icon">
                                </div>
                                <ul class="manage-list">
                                    <li><a href="https://lovespace.co.uk/apps#/my-stuff" title="My stuff">My stuff</a>
                                    </li>
                                    <li><a href="https://lovespace.co.uk/apps#/account" title="My details">My
                                            details</a></li>
                                    <li><a href="https://lovespace.co.uk/apps#/requests" title="My requests">My
                                            requests</a></li>
                                    <li><a href="https://lovespace.co.uk/apps#/orders" title="Orders">Orders</a></li>
                                    <li><a href="https://lovespace.co.uk/apps#/cards" title="Cards">Cards</a></li>
                                    <li><a href="https://lovespace.co.uk/apps#/payments" title="Payments">Payments</a>
                                    </li>
                                    <li><a href="https://lovespace.co.uk/apps#/referral" title="Earn £10"
                                            id="ls-refferal-link">Earn £10</a></li>
                                    <li><a href="https://lovespace.co.uk/apps#/signout" title="Sign out">Sign out</a>
                                    </li>
                                </ul>
                            </div>
                            <ul class="main-section">
                                <li class="green-button"> <a href="/lockers/" title="Drive-up storage"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/storage.svg"
                                            class="icon">
                                        Drive-up storage </a> </li>
                                <li class="green-button"> <a href="/apps#/order/" title="By-the-box storage"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/storage.svg"
                                            class="icon">
                                        By-the-box storage </a> </li>
                                <li class="green-button"> <a href="/apps#/tiered/" title="Storage unit"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/storage.svg"
                                            class="icon">
                                        Storage unit </a> </li>
                                <li class=""> <a href="/storage-near-me/" title="Areas"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/storage.svg"
                                            class="icon">
                                        Areas </a> </li>
                                <li class=""> <a href="/apps#/order?m=true" title="Materials"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/materials.svg"
                                            class="icon">
                                        Materials </a> </li>
                                <li class="green-button new"> <a href="/apps#/removals" title="Removals"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/shipping.svg"
                                            class="icon">
                                        Removals </a> </li>
                                <li class=""> <a href="/apps#/shipping" title="Shipping"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/shipping.svg"
                                            class="icon">
                                        Shipping </a> </li>
                                <li class=""> <a href="/how-it-works/" title="How storage works"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/question-mark.svg"
                                            class="icon"> How storage works </a> </li>
                                <li class=""> <a href="/domestic-shipping/" title="How shipping works"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/question-mark.svg"
                                            class="icon"> How shipping works </a> </li>
                                <li class=""> <a href="/student-storage/" title="Student storage"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/question-mark.svg"
                                            class="icon"> Student storage </a> </li>
                                <li class=""> <a href="/student-packing/" title="Student packing"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/packing.svg"
                                            class="icon">
                                        Student packing </a> </li>
                                <li class=""> <a href="/removals/" title="Student removals"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/packing.svg"
                                            class="icon">
                                        Student removals </a> </li>
                                <li class=""> <a href="/moving-house/" title="House moving"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/packing.svg"
                                            class="icon">
                                        House moving </a> </li>
                                <li class=""> <a href="/packing-and-storage/" title="Packing &amp; storage">
                                        <img src="/wp-content/themes/lovespace/assets/icons/storage.svg"
                                            class="icon">
                                        Packing &amp; storage </a> </li>
                                <li class=""> <a href="/faqs/" title="FAQ"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/faq.svg" class="icon">
                                        FAQ
                                    </a> </li>
                                <li class=""> <a href="/contact/" title="Contact"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/contact.svg"
                                            class="icon">
                                        Contact </a> </li>
                                <li class=""> <a href="/pricing-help/" title="Pricing"> <img
                                            src="/wp-content/themes/lovespace/assets/icons/question-mark.svg"
                                            class="icon"> Pricing </a> </li>
                            </ul>
                        </div>
                    </ul>
                </div>
                <div class="overlay" data-toggle="collapse" data-target=".navbar-ex1-collapse"></div>
            </div>
            <div class="desktop-menu " style="background-color: orange;">
                <ul class="nav navbar-nav  navbar-center" style="background-color: orange;">
                    <li class="hover-dropdown">
                        <div class="title"><a class="" href="" style="color: white;">Storage</a></div>
                        <div class="content"> <a href="order.html" class="col-brand-2-over "
                                title="By-the-box">By-the-box</a> <a href="storageunit.html"
                                class="col-brand-2-over " title="Storage units">Storage units</a> <a
                                href="birmingham.html" class="col-brand-2-over " title="Birmingham">Birmingham</a> <a
                                href="manchester.html" class="col-brand-2-over " title="Manchester">Manchester</a> <a
                                href="nottingham.html" class="col-brand-2-over " title="Nottingham">Nottingham</a> <a
                                href="sheffield.html" class="col-brand-2-over " title="Sheffield">Sheffield</a> <a
                                href="edinburgh.html" class="col-brand-2-over " title="Edinburgh">Edinburgh</a> <a
                                href="https://lovespace.co.uk/student-storage/oxford/" class="col-brand-2-over "
                                title="Oxford">Oxford</a> <a href="alllocation.html" class="col-brand-2-over "
                                title="All locations">All locations</a> </div>
                    </li>
                    <li class="hover-dropdown">
                        <div class="title"><a class="" style="color: white;">For students</a></div>
                        <div class="content"> <a href="studentstorage.html" class="col-brand-2-over "
                                title="Student storage">Student storage</a> <a href="studentpacking.html"
                                class="col-brand-2-over " title="Student packing">Student packing</a> <a
                                href="studentremovals.html" class="col-brand-2-over "
                                title="Student removals">Student
                                removals</a> </div>
                    </li>
                    <li class="hover-dropdown">
                        <div class="title"><a class="" style="color: white;">Removals</a></div>
                        <div class="content"> <a href="removalsandstorage.html" class="col-brand-2-over "
                                title="Removals &amp; storage">Removals &amp; storage</a> <a href="movinghouse.html"
                                class="col-brand-2-over " title="Home moves">Home moves</a> <a
                                href="https://lovespace.co.uk/office-removals/" class="col-brand-2-over "
                                title="Office moves">Office moves</a> <a href="smallremovals.html"
                                class="col-brand-2-over " title="Small moves">Small moves</a> <a
                                href="longdistanceremovals.html" class="col-brand-2-over "
                                title="Long distance moves">Long distance moves</a> <a href="nationwideremovals.html"
                                class="col-brand-2-over " title="Nationwide moves">Nationwide moves</a> </div>
                    </li>
                    <li><a href="/lockers/" class="col-brand-2-over " style="color: white;"
                            title="Drive-up storage">Drive-up storage</a></li>
                    <li class="hover-dropdown">
                        <div class="title"><a class="" style="color: white;">Other services</a></div>
                        <div class="content"> <a href="/apps#/order?m=true" class="col-brand-2-over "
                                title="Materials">Materials</a> <a href="/domestic-shipping/"
                                class="col-brand-2-over " title="Shipping">Shipping</a> <a
                                href="/packing-and-storage/" class="col-brand-2-over "
                                title="Packing &amp; storage">Packing &amp; storage</a> </div>
                    </li>
                    <li class="hover-dropdown">
                        <div class="title"><a class="" style="color: white;">Support</a></div>
                        <div class="content"> <a href="/faqs/" class="col-brand-2-over " title="FAQ">FAQ</a> <a
                                href="/contact/" class="col-brand-2-over " title="Contact">Contact</a> <a
                                href="https://lovespace.co.uk/about/" class="col-brand-2-over "
                                title="About us">About
                                us</a> </div>
                    </li>
                </ul>
            </div>
            <div class="overlay" data-toggle="collapse" data-target=".navbar-ex1-collapse"></div>
        </nav>
    </header>
    <style>
        @keyframes movetext {
            0% {
                transform: scale(1.3);
            }

            50% {
                transform: scale(1);
            }

            100% {
                transform: scale(1.3);
            }
        }

        .myanimation {
            font-size: 50px;
            animation-name: movetext;
            animation-duration: 3s;
            animation-iteration-count: infinite;
            transition: all;
            margin-left: 40px;
            color: green;
        }
    </style>
    <div class="parallax-container">
        <div class="return-bar" style="background-color: white;">
            <h4 class="text-success" style="color:green">PRICE MATCH GUARANTEE: We'll match any storage price you find
                that's cheaper - <a href="https://lovespace.co.uk/blogs/our-price-match-guarantee/"
                    style="color: orange;">How it works</a><a href=""></a></h4>
        </div>
        <div class="home-hero-c" style="background-color: white;">
            <div class="content" style="background-color: orange;">
                <div class="text">
                    <h1 class="myanimation">Self Storage</h1>
                    <h2 class="subtitle">We collect. We store. We deliver.</h2>
                    <h1>Self storage, but better.</h1>
                    <div>
                        <ul style="padding-left: 20px;margin-bottom: 20px;">
                            <li>We are the UK's number one by-the-box storage company</li>
                            <li>Trusted by more than 80,000 customers</li>
                            <li>Rated excellent on Trustpilot</li>
                        </ul>
                    </div>
                    <div class="trustpilot-widget" data-locale="en-GB" data-template-id="5419b732fbfb950b10de65e5"
                        data-businessunit-id="504d324000006400051b05f0" data-style-height="24px"
                        data-style-width="265px" data-theme="dark" data-text-color="#ffffff"
                        style="position: relative;"><iframe title="Customer reviews powered by Trustpilot"
                            loading="auto"
                            src="https://widget.trustpilot.com/trustboxes/5419b732fbfb950b10de65e5/index.html?templateId=5419b732fbfb950b10de65e5&amp;businessunitId=504d324000006400051b05f0#locale=en-GB&amp;styleHeight=24px&amp;styleWidth=265px&amp;theme=dark&amp;textColor=%23ffffff"
                            style="position: relative; height: 24px; width: 265px; border-style: none; display: block; overflow: hidden;"></iframe>
                    </div> <button class="btn btn-orange round" onclick="scrollToServices()" id="hero-btn-browse">
                        <span>Browse services</span> <img alt="dropdown arrow" class="lazy"
                            src="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/icons/dropdown-arrow.svg">
                    </button>
                </div> <img alt="hero image" class="hero-image lazy" style="min-height:360px;"
                    src="https://www.a.ubuy.com.kw/skin/frontend/default/ubuycom-v1/images/ubuy-bulk-purchase.png">
            </div>
        </div>
        <div class="hero-c">
            <div class="hero-usp-section" style="background-color: black;">
                <div class="content">
                    <div class="usp">
                        <h2 class="text-light">We collect.</h2>
                        <p class="text-light">From any UK address as soon as the next day.</p>
                    </div>
                    <div class="usp-separator"></div>
                    <div class="usp">
                        <h2 class="text-light">We store.</h2>
                        <p class="text-light">Safely in one of our secure storage facilities.</p>
                    </div>
                    <div class="usp-separator"></div>
                    <div class="usp">
                        <h2 class="text-light">We deliver.</h2>
                        <p class="text-light">Back to any UK address as soon as the next day.</p>
                    </div>
                </div>
            </div>
            <div class="main-services">
                <div class="main-service-card">
                    <div class="content">
                        <div class="text">
                            <h2>Storage by-the-box</h2>
                            <p>Only pay for what you store. Perfect if you've got boxes, suitcases, or other small
                                items. Book a collection and we'll handle the rest.</p>
                            <div class="service-actions"> <a href="/apps#/order" class="btn btn-orange"
                                    id="hero-c-btb">Book storage by-the-box</a> <a href="/apps#/order"
                                    class="btn btn-primary">From £3.25/month</a> </div>
                        </div> <img alt="by-the-box storage" class="hero-image lazy"
                            src="https://widgets.g5dxm.com/storage-size-guide/size-guide-reality/10x15-Final.png"
                            style="height: 400px;width:600px;
                            ">
                    </div>
                    <div class="border"></div>
                </div>
                <div class="main-service-card reversed">
                    <div class="content">
                        <div class="text">
                            <h2>Storage units</h2>
                            <p>Ideal if you've got home or office furniture or any other stuff that doesn't fit into a
                                box. We pack and collect, saving you time and money.</p>
                            <div class="service-actions"> <a href="/apps#/tiered" class="btn btn-orange"
                                    id="hero-c-vsu">Book a storage unit</a> <a href="/apps#/tiered"
                                    class="btn btn-primary">From £79/month</a> </div>
                        </div> <img alt="by-the-box storage" class="hero-image lazy"
                            src="https://flexible-storage.co.uk/wp-content/uploads/2020/12/banner-left.png"
                            style="height: 400px;width:600px;
                            ">
                    </div>
                    <div class="border"></div>
                </div>
                <div class="main-service-card">
                    <div class="content">
                        <div class="text">
                            <h2>Drive-up storage</h2>
                            <p>With on-demand access, you can move your stuff in when it suits you. We'll then collect
                                your stuff and store it in one of our secure storage facilities.</p>
                            <div class="service-actions"> <a href="/lockers/" class="btn btn-orange"
                                    id="hero-c-ll">Book
                                    drive-up storage</a> <a href="/lockers/" class="btn btn-primary">From
                                    £40/month</a>
                            </div>
                        </div> <img alt="by-the-box storage" class="hero-image lazy"
                            src="https://locknloadstorage.co.uk/wp-content/uploads/revslider/slider-3-1/archives.png"
                            style="height: 400px;width:600px;
                            ">
                    </div>
                    <div class="border"></div>
                </div>
                <div class="mobile-bg"></div>
            </div>
            <div class="hero-usp-section-mobile">
                <div class="usp">
                    <h2>We collect.</h2>
                    <p>From any UK address as soon as the next day.</p>
                </div>
                <div class="usp-separator"></div>
                <div class="usp">
                    <h2>We store.</h2>
                    <p>Safely in one of our secure storage facilities.</p>
                </div>
                <div class="usp-separator"></div>
                <div class="usp">
                    <h2>We deliver.</h2>
                    <p>Back to any UK address as soon as the next day.</p>
                </div>
            </div>
        </div>
        <style>
            @keyframes hoverEffect {
                0% {
                    transform: translate(0px);
                }

                50% {
                    transform: translate(-120px);
                }

                100% {
                    transform: translate(0px);
                }
            }

            .hover {
                transition: all;
                animation-name: hoverEffect;
                animation-duration: 5s;
                animation-iteration-count: infinite;
            }
        </style>
        <section class="cta-section highlight" style="background-color: orange;">
            <div class="container">
                <div class="with-image ">
                    <div class="image hover" style="border: 4px solid green;"> <img class="img-responsive lazy"
                            src="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/images/removals.svg">
                    </div>
                    <div class="content">
                        <h2 class="cta-section-text">Removals</h2>
                        <p>Book a removal on-demand to help your house move go smoothly, from a single room to entire
                            business moves. Prices start from £99.</p> <a class="cta-section-button"
                            href="/removals/">
                            Find out more </a>
                    </div>
                </div>
            </div>
        </section>
        <div class="section grey">
            <div class="container-wide">
                <h2 class="mt0">Other services</h2>
                <div class="services">
                    <p></p>
                    <div class="card-thin">
                        <h3 class="mt0 mb0">Materials</h3>
                        <p class="grey">Order packing materials</p>
                        <div class="template-img"> <img
                                lazy="https://ezebox.com.au/wp-content/uploads/2017/05/problem.png"
                                style="height: 200px;width: 200px;" title="Materials" class="img-responsive lazy">
                        </div> <a class="btn btn-warning" href="/apps#/order/materials">Order materials</a>
                    </div>
                    <div class="card-thin">
                        <h3 class="mt0 mb0">Shipping</h3>
                        <p class="grey">Ship around the UK within a week</p>
                        <div class="template-img"> <img
                                lazy="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/images/services/service-shipping.png"
                                title="Shipping" class="img-responsive lazy"> </div> <a class="btn btn-warning"
                            href="/apps#/shipping">Book shipping</a>
                    </div>
                    <div class="card-thin">
                        <h3 class="mt0 mb0">Packing &amp; storage</h3>
                        <p class="grey">Order storage with professional packing</p>
                        <div class="template-img"> <img
                                lazy="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/images/services/service-packing.png"
                                title="Packing &amp; storage" class="img-responsive lazy"> </div> <a
                            class="btn btn-warning" href="/apps#/tiered">Book packing</a>
                    </div>
                    <div class="card-thin">
                        <h3 class="mt0 mb0">Removals</h3>
                        <p class="grey">Removals with packing and materials</p>
                        <div class="template-img"> <img
                                lazy="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/images/services/service-removals.png"
                                title="Removals" class="img-responsive lazy"> </div> <a class="btn btn-warning"
                            href="/apps#/removals">Book a removal</a>
                    </div>
                </div>
            </div>
        </div>
        <section id="trustpilot-list">
            <div class="container">
                <h2>Used by over 80,000 customers</h2>
                <p class="mb40 larger text-center">From London to Edinburgh, we offer self-storage across the UK. Check
                    out what our customers have to say about our storage pick up and delivery services.</p>
                <div class="trustbox">
                    <div class="trustpilot-widget large-widget" data-locale="en-GB"
                        data-template-id="53aa8912dec7e10d38f59f36" data-businessunit-id="504d324000006400051b05f0"
                        data-style-height="140px" data-style-width="100%" data-theme="light" data-stars="4,5"
                        data-review-languages="en" data-font-family="Poppins" style="position: relative;"><iframe
                            title="Customer reviews powered by Trustpilot" loading="auto"
                            src="https://widget.trustpilot.com/trustboxes/53aa8912dec7e10d38f59f36/index.html?templateId=53aa8912dec7e10d38f59f36&amp;businessunitId=504d324000006400051b05f0#locale=en-GB&amp;styleHeight=140px&amp;styleWidth=100%25&amp;theme=light&amp;stars=4%2C5&amp;reviewLanguages=en&amp;fontFamily=Poppins"
                            style="position: relative; height: 140px; width: 100%; border-style: none; display: block; overflow: hidden;"></iframe>
                    </div>
                </div>
            </div>
        </section>
        <div class="section green student-service">
            <div class="container-wide">
                <h2 class="mt0">For students</h2>
                <p class="restrict-width fs20 mx-2">We've served over 30,000 students and can pick your stuff up from
                    any mainland UK university. So whether you're going home for the summer, or moving into your new
                    student home - we've got a service for you.</p>
                <div class="services">
                    <div class="card-thin">
                        <div class="template-img mb30"> <img
                                lazy="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/images/student-1.png"
                                title="Student packing" class="img-responsive lazy green-border"> </div>
                        <h3 class="mt0">Student packing</h3>
                        <p class="grey">For when you're in need of our team to<br>come and pack your stuff safely
                        </p>
                        <a class="btn btn-primary" href="/student-packing/">Book student packing</a>
                    </div>
                    <div class="card-thin">
                        <div class="template-img mb30"> <img
                                lazy="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/images/student-4.png"
                                title="Student removals" class="img-responsive lazy green-border"> </div>
                        <h3 class="mt0">Student removals</h3>
                        <p class="grey">For when you need us to help you move<br>into your brand new student home</p>
                        <a class="btn btn-primary" href="/student-removals/">Book student removals</a>
                    </div>
                    <div class="card-thin">
                        <div class="template-img mb30"> <img
                                lazy="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/images/student-2.png"
                                title="Student storage" class="img-responsive lazy green-border"> </div>
                        <h3 class="mt0">Student storage</h3>
                        <p class="grey">For when you need us to safely store<br> your stuff</p> <a
                            class="btn btn-primary" href="/student-storage/">Book student storage</a>
                    </div>
                </div>
            </div>
        </div>
        <section class="cta-section white highlight reverse review mt-off landing">
            <div class="container grey mt-off">
                <div class="with-image">
                    <div class="content">
                        <div class="mt-4 mr-2">
                            <h2 class="cta-section-text about">Self-storage, but better</h2>
                            <p class="fs20">From storage to removals, we've been taking care of our customers' stuff
                                for
                                over a decade.</p> <br>
                        </div> <a class="btn btn-primary wide" href="/about/">About us</a>
                    </div>
                    <div class="image removal-block long"> <img
                            src="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/images/van.jpg"
                            class="img-responsive lazy"> </div>
                </div>
            </div>
        </section>
        <script type="text/javascript">
            const toggleDropdown = function(dropdownId) {
                const dropdown = document.getElementById(dropdownId);
                if (dropdown.classList.contains('open')) {
                    dropdown.classList.remove('open')
                } else {
                    dropdown.classList.add('open')
                }
            }
        </script>
        <section class="grey">
            <div class="container">
                <h2 class="mb50">Frequently asked questions</h2>
                <div class="dropdowns-container">
                    <div class="faq-dropdown" id="dropdown0">
                        <div class="title-container" onclick="toggleDropdown('dropdown0')">
                            <div class="tilte">How does it work?</div> <img
                                src="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/icons/dropdown-arrow-grey.svg"
                                alt="arrow" class="dropdown-arrow lazy">
                        </div>
                        <div class="dropdown-separator"></div>
                        <div class="body-container">
                            <p> <a href="/how-it-works/">Storage by-the-box</a> and <a
                                    href="/how-it-works/?vsu">storage
                                    units</a> - we collect from your doorstep, store as much as you've got for as long
                                as you want in one of our secure storage facilities, then deliver it back to your
                                doorstep. <br><br><a href="/lockers/">Drive-up storage</a> - you drop off your stuff at
                                your chosen location, we store it elsewhere in a secure storage facility, then deliver
                                it back to where you dropped it off. <br><br><a href="/removals/">Removals</a> - we
                                collect, transport and deliver all your stuff securely to your new home or business. We
                                can disassemble, wrap and pack it beforehand too. <br><br><a
                                    href="/domestic-shipping/">Shipping</a> - we collect, transport and deliver all
                                your
                                stuff securely to your chosen address. </p>
                        </div>
                    </div>
                    <div class="faq-dropdown" id="dropdown1">
                        <div class="title-container" onclick="toggleDropdown('dropdown1')">
                            <div class="tilte">What locations do you service?</div> <img
                                src="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/icons/dropdown-arrow-grey.svg"
                                alt="arrow" class="dropdown-arrow lazy">
                        </div>
                        <div class="dropdown-separator"></div>
                        <div class="body-container">
                            <p> With <a href="/how-it-works/">storage by-the-box</a>, <a
                                    href="/how-it-works/?vsu">storage units</a>, <a
                                    href="/domestic-shipping/">shipping</a> and <a href="/removals/">removals</a>, we
                                will collect and deliver from and to any mainland UK address. <br><br> <a
                                    href="/lockers/">Drive-up storage</a> is a bit different. You drop your stuff off
                                at
                                specific locations in the UK. </p>
                        </div>
                    </div>
                    <div class="faq-dropdown" id="dropdown2">
                        <div class="title-container" onclick="toggleDropdown('dropdown2')">
                            <div class="tilte">How quickly will my stuff be collected and delivered?</div> <img
                                src="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/icons/dropdown-arrow-grey.svg"
                                alt="arrow" class="dropdown-arrow lazy">
                        </div>
                        <div class="dropdown-separator"></div>
                        <div class="body-container">
                            <p> You can choose when you'd like your stuff to be collected when booking through our
                                website. But each service is slightly different! <br><br><a
                                    href="/apps#/order/">Storage
                                    by-the-box</a>, after you book: </p>
                            <ul class="mt-2">
                                <li>We can collect as soon as the next day</li>
                                <li>We can deliver back to you as soon as the next day</li>
                            </ul> <a href="/apps#/tiered/">Storage units</a>
                            <ul class="mt-2">
                                <li>You can book for your stuff to be collected 3 working days in advance, if you place
                                    your order before 11am.</li>
                                <li>We can then deliver back to you from the next working day if you just need boxes
                                    returned, or 3 working days if it's furniture or larger deliveries.</li>
                            </ul> <a href="/removals/">Removals</a>
                            <ul class="mt-2">
                                <li>We'll usually need to receive your booking at least 3 days before your move, but
                                    give us a call if you need something shorter notice and we'll try to make it happen
                                </li>
                                <li>We deliver on the same day</li>
                            </ul> <a href="/domestic-shipping/">Shipping</a>
                            <ul class="mt-2">
                                <li>We can collect as soon as the next day after you book</li>
                                <li>We'll deliver to your chosen UK address within 1 week</li>
                            </ul> <a href="/lockers/">Drive-up storage</a> - we don't collect, instead you drop your
                            stuff off at your chosen location as soon as the same day. <br><br>Exact timings are subject
                            to availability of our drivers and movers. <p></p>
                        </div>
                    </div>
                    <div class="faq-dropdown" id="dropdown3">
                        <div class="title-container" onclick="toggleDropdown('dropdown3')">
                            <div class="tilte">Will you collect and store my furniture?</div> <img
                                src="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/icons/dropdown-arrow-grey.svg"
                                alt="arrow" class="dropdown-arrow lazy">
                        </div>
                        <div class="dropdown-separator"></div>
                        <div class="body-container">
                            <p> Absolutely! Book a <a href="/how-it-works/?vsu">storage unit</a> and we'll store all
                                your furniture securely til you need it delivered back. </p>
                        </div>
                    </div>
                    <div class="faq-dropdown" id="dropdown4">
                        <div class="title-container" onclick="toggleDropdown('dropdown4')">
                            <div class="tilte">How is LOVESPACE storage different from self-storage?</div> <img
                                src="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/icons/dropdown-arrow-grey.svg"
                                alt="arrow" class="dropdown-arrow lazy">
                        </div>
                        <div class="dropdown-separator"></div>
                        <div class="body-container">
                            <p> Our storage is much more convenient than traditional self-storage because you don't have
                                to leave home or drive to a storage unit. We can do all the work for you! <br><br>It's
                                also cheaper because you can pay based on exactly how much stuff you have, rather than a
                                standard storage unit that you might not fill. </p>
                        </div>
                    </div>
                    <div class="faq-dropdown" id="dropdown5">
                        <div class="title-container" onclick="toggleDropdown('dropdown5')">
                            <div class="tilte">My business needs to relocate. Do you provide office moves?</div> <img
                                src="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/icons/dropdown-arrow-grey.svg"
                                alt="arrow" class="dropdown-arrow lazy">
                        </div>
                        <div class="dropdown-separator"></div>
                        <div class="body-container">
                            <p> Yes, our <a href="/removals/">removals</a> service is here to help! Book online or call
                                our friendly customer service team on 01325 952440 and we'll put a plan together to fit
                                your needs. </p>
                        </div>
                    </div>
                    <div class="faq-dropdown" id="dropdown6">
                        <div class="title-container" onclick="toggleDropdown('dropdown6')">
                            <div class="tilte">Can you help with a partial house move?</div> <img
                                src="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/icons/dropdown-arrow-grey.svg"
                                alt="arrow" class="dropdown-arrow lazy">
                        </div>
                        <div class="dropdown-separator"></div>
                        <div class="body-container">
                            <p> Yes. With our <a href="/removals/">removals</a>, we can move however much stuff you
                                have, even if that only means a few items. </p>
                        </div>
                    </div>
                    <div class="faq-dropdown" id="dropdown7">
                        <div class="title-container" onclick="toggleDropdown('dropdown7')">
                            <div class="tilte">Can I drop my stuff off for storage if I want?</div> <img
                                src="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/icons/dropdown-arrow-grey.svg"
                                alt="arrow" class="dropdown-arrow lazy">
                        </div>
                        <div class="dropdown-separator"></div>
                        <div class="body-container">
                            <p> Yes, you can drop your stuff off at any of our drive-up storage units as soon as the
                                same day as you book. They're in specific locations, <a href="/lockers/">take a look
                                    where</a>. </p>
                        </div>
                    </div>
                    <div class="faq-dropdown" id="dropdown8">
                        <div class="title-container" onclick="toggleDropdown('dropdown8')">
                            <div class="tilte">Can you do my packing?</div> <img
                                src="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/icons/dropdown-arrow-grey.svg"
                                alt="arrow" class="dropdown-arrow lazy">
                        </div>
                        <div class="dropdown-separator"></div>
                        <div class="body-container">
                            <p> Yes. When you book a <a href="/how-it-works/?vsu">storage unit</a> or a <a
                                    href="/removals/">removal</a>, you can add on optional packing as well. Our
                                professional packing team will wrap and pack your items carefully, whether that's bulky
                                furniture or small delicate items. </p>
                        </div>
                    </div>
                    <div class="faq-dropdown" id="dropdown9">
                        <div class="title-container" onclick="toggleDropdown('dropdown9')">
                            <div class="tilte">What are your student services?</div> <img
                                src="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/icons/dropdown-arrow-grey.svg"
                                alt="arrow" class="dropdown-arrow lazy">
                        </div>
                        <div class="dropdown-separator"></div>
                        <div class="body-container">
                            <p> University students, we can help you with: <br><br> <a
                                    href="/student-storage/">Storage</a> – we can collect right from your UK university
                                address, store as much as you've got for as long as you want in one of our secure
                                storage facilities, then deliver it back to your doorstep, even if you've moved.
                                <br><br>Packing – when you book a <a href="/how-it-works/?vsu">storage unit</a> or a <a
                                    href="/removals/">removal</a>, you can add on optional packing as well. Our
                                professional packing team will wrap and pack your stuff carefully. <br><br>Moving home –
                                with <a href="/removals/">removals</a>, we'll collect, transport and deliver all your
                                stuff securely to your new home, whether that's a university address or a new graduate
                                pad!
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section id="contact-home">
            <div class="container">
                <h2>Always here to help</h2>
                <p class="mb40 larger text-center">We make self storage easy - so give us a call, email or contact us
                    on
                    live chat if you've got any questions about our storage units, self storage or anything else we
                    might help you with!</p>
                <div class="flex">
                    <div class="item" onclick="Lovespace.contactSection('phone')">
                        <div class="picture"> <img
                                lazy="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/img/Phone_Black.svg"
                                alt="0800 802 1018" class="lazy"> </div>
                        <div class="details">
                            <h3>0800 802 1018</h3>
                            <p>Chat with a storage <br>expert about your needs</p>
                        </div>
                    </div>
                    <div class="item" onclick="Lovespace.contactSection('mail')">
                        <div class="picture"> <img
                                lazy="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/img/Mail_Black.svg"
                                alt="info@lovespace.co.uk" class="lazy"> </div>
                        <div class="details">
                            <h3>info@lovespace.co.uk</h3>
                            <p>Get support or request a <br>quote via email</p>
                        </div>
                    </div>
                    <div class="item" onclick="Lovespace.contactSection('chat')">
                        <div class="picture"> <img
                                lazy="https://lovespace.co.uk/wp-content/themes/lovespace.9.9.3/assets/img/Chat_Black.svg"
                                alt="Live chat" class="lazy"> </div>
                        <div class="details">
                            <h3>Live chat</h3>
                            <p>Talk to the team for help <br>booking your storage</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <script>
            const scrollToServices = function() {
                document.querySelector(".parallax-container").scroll({
                    top: window.innerHeight,
                    behavior: 'smooth'
                })
            }
        </script>
        <div id="tracking-cont-gumtree" style="display:none"></div>

        @extends('footer')

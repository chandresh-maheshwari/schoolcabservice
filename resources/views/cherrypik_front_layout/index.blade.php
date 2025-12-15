@php
    $theme = session('theme', 'dark');
@endphp
<!DOCTYPE html>
<html lang="en" data-bs-theme="{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Exploreist is your ultimate destination for travel inspiration, tips, and guides. Discover hidden gems, plan your next adventure, and make the most out of your travels with our expertly curated content. Join our community of wanderers and explore the world with Exploreist.">
    <meta name="keywords" content="blog, blogging, blogger, articles, posts, content, writing, writers, blogosphere, online journal, web log, topics, ideas, tips, advice">
    <meta name="author" content="themeperch">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        window.serverTheme = "{{ $theme }}";
    </script>

    <title>Cherrypik Website</title>

    <link rel="shortcut icon" type="images/png" href="{{ asset('assets/images/fav-icon/cherrypikFavicon.png') }}">
    <!-- Google fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Winky+Sans:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Righteous&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    {{-- <link rel="stylesheet" href="{{ asset('assets/css/fontawesome.min.css') }}?v={{ filemtime(public_path('assets/css/fontawesome.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}?v={{ filemtime(public_path('assets/css/all.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/odometer.min.css') }}?v={{ filemtime(public_path('assets/css/odometer.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/venobox.min.css') }}?v={{ filemtime(public_path('assets/css/venobox.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/animate.css') }}?v={{ filemtime(public_path('assets/css/animate.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/swiper-bundle.min.css') }}?v={{ filemtime(public_path('assets/css/swiper-bundle.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/owl.carousel.min.css') }}?v={{ filemtime(public_path('assets/css/owl.carousel.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/owl.theme.default.min.css') }}?v={{ filemtime(public_path('assets/css/owl.theme.default.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/all-custom.css') }}?v={{ filemtime(public_path('css/all-custom.css')) }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/header-custom.css') }}?v={{ filemtime(public_path('assets/css/header-custom.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/footer-custom.css') }}?v={{ filemtime(public_path('assets/css/footer-custom.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/sidebar-custom.css') }}?v={{ filemtime(public_path('assets/css/sidebar-custom.css')) }}"> --}}


    <link rel="stylesheet" href="{{ asset('assets/css/default_css/main.css') }}?v={{ filemtime(public_path('assets/css/default_css/main.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/cherrypik-custom-css/custom.css') }}?v={{ filemtime(public_path('assets/css/cherrypik-custom-css/custom.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom-css/style.css') }}?v={{ filemtime(public_path('assets/css/custom-css/style.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom-styles.css') }}?v={{ filemtime(public_path('assets/css/custom-styles.css')) }}">

    {{-- Do NOT load SCSS files directly in the browser --}}
    <link rel="stylesheet" href="{{ asset('assets/scss/_home.scss') }}">
    <link rel="stylesheet" href="{{ asset('assets/scss/style.css') }}">
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"> --}}



    {{-- <link rel="stylesheet" href="{{ asset('assets/scss/now-ui-dashboard.scss') }}?v={{ filemtime(public_path('assets/scss/now-ui-dashboard.scss')) }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/scss/_variables.scss') }}?v={{ filemtime(public_path('assets/scss/_variables.scss')) }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/scss/_responsive.scss') }}?v={{ filemtime(public_path('assets/scss/_responsive.scss')) }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/scss/_reset.scss') }}?v={{ filemtime(public_path('assets/scss/_reset.scss')) }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/scss/_predefine.scss') }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/scss/_mixins.scss') }}?v={{ filemtime(public_path('assets/scss/_mixins.scss')) }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/scss/_res_header.scss') }}?v={{ filemtime(public_path('assets/scss/_header.scss')) }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/scss/_footer.scss') }}?v={{ filemtime(public_path('assets/scss/_footer.scss')) }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/scss/_elements.scss') }}?v={{ filemtime(public_path('assets/scss/_elements.scss')) }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/scss/_contact.scss') }}?v={{ filemtime(public_path('assets/scss/_contact.scss')) }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/scss/_blog.scss') }}?v={{ filemtime(public_path('assets/scss/_blog.scss')) }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/scss/_banner.scss') }}?v={{ filemtime(public_path('assets/scss/_banner.scss')) }}"> --}}

    {{-- <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}?v={{ filemtime(public_path('assets/css/bootstrap.css')) }}"> --}}
    <link rel="stylesheet" href="{{ asset('assets/vendors/linericon/style.css') }}?v={{ filemtime(public_path('assets/vendors/linericon/style.css')) }}">
    {{-- <link rel="stylesheet" href="{{ asset('assets/css/font-awesome.min.css') }}?v={{ filemtime(public_path('assets/css/font-awesome.min.css')) }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/vendors/owl-carousel/owl.carousel.min.css') }}?v={{ filemtime(public_path('assets/vendors/owl-carousel/owl.carousel.min.css')) }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/css/magnific-popup.css') }}?v={{ filemtime(public_path('assets/css/magnific-popup.css')) }}"> --}}
    {{-- <link rel="stylesheet" href="{{ asset('assets/vendors/nice-select/css/nice-select.css') }}?v={{ filemtime(public_path('assets/vendors/nice-select/css/nice-select.css')) }}"> --}}




    {{-- <link rel="stylesheet" href="css/bootstrap.css"> --}}
	{{-- <link rel="stylesheet" href="vendors/linericon/style.css"> --}}
	{{-- <link rel="stylesheet" href="css/font-awesome.min.css"> --}}
	{{-- <link rel="stylesheet" href="vendors/owl-carousel/owl.carousel.min.css"> --}}
	{{-- <link rel="stylesheet" href="css/magnific-popup.css"> --}}
	{{-- <link rel="stylesheet" href="vendors/nice-select/css/nice-select.css"> --}}




    <!-- <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}"> -->

    {{-- <link rel="stylesheet" href="{{ asset('assets/css/front.css') }}?v={{ filemtime(public_path('assets/css/front.css')) }}"> --}}

    <!-- Swiper CSS -->
    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css"/> --}}

    <!-- Toastr CSS -->
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"> --}}

</head>
<body>
    @php
        $routes = [
            // 'home' => route('front.index'),
            // 'blog' => route('Blog.Shows'),
            // 'about' => route('About.Shows'),
            // 'contact' => route('Contact.Shows'),
            // 'Blog.Add' => route('Blog.Add'),
            // 'Guidelines.Show' => route('Guidelines.Show'),
            // 'Magazine.Show' => route('Magazine.Show'),
        ];
    @endphp

@include('cherrypik_front_layout.header')

    <main class="main">

        <!-- Inner pages provide a 'content' section; render it when present -->
        @if (View::hasSection('content'))
            @yield('content')
        @else
            <!-- Home composition (requires $pages). Guard when undefined. -->
            {{-- @isset($pages)
                @if ($pages->isNotEmpty())
                    @foreach ($pages as $page)
                        @php
                            $template = $page->template;
                            $image = $page->image;


                        @endphp
                        @if ($page->status == 1)
                            @if (view()->exists('templates.' . $template))
                                @includeIf('templates.' . $template, [
                                    // 'data' => $templateData[$template] ?? null,
                                    'page' => $page
                                ])
                            @endif
                        @endif
                    @endforeach
                @endif
            @endisset --}}
            @isset($pages)
    @if ($pages->isNotEmpty())
        @foreach ($pages as $page)
            @php
                $template = $page->template;
                $image = $page->image;

                // Default image path
                $defaultImage = asset('images/Default.jpg');

                // Resolve actual image path (handle both storage URLs & relative paths)
                $imagePath = null;

                if (!empty($image)) {
                    // Convert URL to relative path if needed
                    $relativePath = str_replace(url('/') . '/', '', $image);
                    $absolutePath = public_path($relativePath);

                    // Check if file exists in public folder
                    if (file_exists($absolutePath)) {
                        $imagePath = asset($relativePath);
                    } else {
                        $imagePath = $defaultImage;
                    }
                } else {
                    $imagePath = $defaultImage;
                }

                // Assign checked image path back to $page->image for use inside included template
                $page->image = $imagePath;
            @endphp

            @if ($page->status == 1)
                @if (view()->exists('templates.' . $template))
                    @includeIf('templates.' . $template, [
                        'page' => $page
                    ])
                @endif
            @endif
        @endforeach
    @endif
@endisset

        @endif
         </main>


        @include('cherrypik_front_layout.footer')

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Preloader -->
    <div id="preloader"></div>
{{-- @include('cherrypik_front_layout.sidebar') --}}
    {{-- <div class="page">

        @include('cherrypik_front_layout.header', ['routes' => $routes])

        <div class="main" data-bs-spy="scroll" data-bs-target="#navContentmenu" data-bs-root-margin="0px 0px -50%" data-bs-smooth-scroll="true">

            @yield('content')
        </div>

        @include('cherrypik_front_layout.footer')
    </div> --}}

    {{-- ccccccc --}}
    <!-- Vendor JS Files -->
    {{-- <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script> --}}
    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    {{-- <script src="assets/vendor/php-email-form/validate.js"></script>  Send message from contact form --}}
    {{-- <script src="assets/vendor/aos/aos.js"></script> --}}
    <script src="{{ asset('assets/vendor/aos/aos.js') }}"></script>
    {{-- <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script> --}}
    <script src="{{ asset('assets/vendor/purecounter/purecounter_vanilla.js') }}"></script>
    {{-- <script src="assets/vendor/swiper/swiper-bundle.min.js"></script> --}}
    <script src="{{ asset('assets/vendor/swiper/swiper-bundle.min.js') }}"></script>
    {{-- <script src="assets/vendor/glightbox/js/glightbox.min.js"></script> --}}
    <script src="{{ asset('assets/vendor/glightbox/js/glightbox.min.js') }}"></script>
    {{-- <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script> --}}
    <script src="{{ asset('assets/vendor/imagesloaded/imagesloaded.pkgd.min.js') }}"></script>
    {{-- <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script> --}}
    <script src="{{ asset('assets/vendor/isotope-layout/isotope.pkgd.min.js') }}"></script>


    <!-- Main JS File -->
    {{-- <script src="assets/js/default_js/js/main.js"></script> --}}


    <!-- Favicons -->
    <script src="{{ asset('assets/js/default_js/main.js') }}"></script>
    {{-- <script src="{{ asset('js/common_front.js') }}"></script> --}}
    <script src="{{ asset('js/common_js.js') }}"></script>
    <link href="assets/img/favicon.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/aos/aos.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/swiper/swiper-bundle.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/glightbox/css/glightbox.min.css') }}" rel="stylesheet">


    {{-- OLD LINKE --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    {{-- ccccccc --}}


    {{-- portfolio --}}
    {{-- <script src="assets/js/jquery-3.2.1.min.js"></script>
	<script src="assets/js/popper.js"></script>
	<script src="assets/js/bootstrap.min.js"></script>
	<script src="assets/js/stellar.js"></script>
	<script src="assets/js/jquery.magnific-popup.min.js"></script>
	<script src="assets/vendors/nice-select/js/jquery.nice-select.min.js"></script>
	<script src="assets/vendors/isotope/imagesloaded.pkgd.min.js"></script>
	<script src="assets/vendors/isotope/isotope-min.js"></script>
	<script src="assets/vendors/owl-carousel/owl.carousel.min.js"></script>
	<script src="assets/js/jquery.ajaxchimp.min.js"></script>
	<script src="assets/js/mail-script.js"></script>
	<!--gmaps Js-->
	<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCjCGmQ0Uq4exrzdcL6rvxywDDOvfAu6eE"></script>
	<script src="js/gmaps.min.js"></script>
	<script src="js/theme.js"></script> --}}

    {{-- portfolio --}}


    <!-- JS -->
    {{-- <script src="{{ asset('assets/js/jquery-3.7.0.min.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/wow.js') }}"></script>
    <script src="{{ asset('assets/js/venobox.min.js') }}"></script>
    <script src="{{ asset('assets/js/odometer.min.js') }}"></script>
    <script src="{{ asset('assets/js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('assets/js/gsap/gsap.min.js') }}"></script>
    <script src="{{ asset('assets/js/gsap/SplitText.min.js') }}"></script>
    <script src="{{ asset('assets/js/gsap/ScrollTrigger.min.js') }}"></script>
    <script src="{{ asset('assets/js/gsap/split-type-0.3.3.min.js') }}"></script>
    <script src="{{ asset('assets/js/appear.min.js') }}"></script>
    <script src="{{ asset('assets/js/lazy.image.js') }}"></script>
    <script src="{{ asset('assets/js/script.js') }}"></script>
    <script src="{{ asset('js/ckeditor/ckeditor.js') }}"></script>
    <script src="{{ asset('assets/js/adminJs/notify.js') }}"></script>
    <!-- <script src="https://cdn.ckeditor.com/4.25.1/standard/ckeditor.js"></script> -->
    <!-- <script src="https://cdn.ckeditor.com/4.20.0/standard/ckeditor.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <!-- FontAwesome Icon Picker JS -->
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <!-- FontAwesome Icon Picker JS -->
    <script src="https://cdn.jsdelivr.net/npm/fontawesome-iconpicker@3.2.0/dist/js/fontawesome-iconpicker.min.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script> --}}



</body>
</html>


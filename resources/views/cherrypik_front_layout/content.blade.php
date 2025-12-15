@php
    // use App\Helpers\IdEncoder;
    // $encodedId = auth()->check() ? IdEncoder::encode(auth()->user()->id) : null;
@endphp
@extends('cherrypik_front_layout.index')

<body class="index-page">
    @php
        // $routes = [
        //     'home' => route('front.index'),
        //     'blog' => route('Blog.Shows'),
        //     'about' => route('About.Shows'),
        //     'contact' => route('Contact.Shows'),
        //     'Blog.Add' => route('Blog.Add'),
        //     'Guidelines.Show' => route('Guidelines.Show'),
        //     'Magazine.Show' => route('Magazine.Show'),
        // ];
    @endphp
    @include('cherrypik_front_layout.header')

    <main class="main">

        <!-- ero Section (Rendered by JS) -->
        @if ($pages->isNotEmpty())
            @foreach ($pages as $page)
                @php
                    $template = $page->template;
                @endphp
                @if ($page->status == 1)
                    @if (view()->exists('templates.' . $template))
                        @includeIf('templates.' . $template, [
                            'data' => $templateData[$template] ?? null,
                            'page' => $page,
                        ])
                    @endif
                @endif
            @endforeach
        @endif
        {{-- @else
        <p>No pages found.</p>
    @endif --}}

    </main>
    @include('cherrypik_front_layout.footer')

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Preloader -->
    <div id="preloader"></div>

    {{-- <!-- Vendor JS Files -
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    {{-- <script src="assets/vendor/php-email-form/validate.js"></script>  Send message from contact form --}
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
    <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>

    <!-- Main JS File -
    {{-- <script src="assets/js/default_js/js/main.js"></script> --}
    <script src="{{ asset('assets/js/default_js/main.js') }}"></script>
    <link href="assets/img/favicon.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Fonts -
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <!-- Vendor CSS Files -
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">

    {{-- OLD LINKE --
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" /> --}}

    <!-- START - Clients slider init (loads after page ready) -->
    <script></script>
    <!-- END - Clients slider init -->


    <script></script>

</body>

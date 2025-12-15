@extends('cherrypik_front_layout.index')

@section('content')
    <style>
        .portfolio-description .description-content {
            line-height: 1.8;
        }
        .portfolio-description .description-content p {
            margin-bottom: 1.5rem;
            color: #333;
        }
        .portfolio-description .description-content h2,
        .portfolio-description .description-content h3,
        .portfolio-description .description-content h4 {
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .portfolio-description .description-content ul,
        .portfolio-description .description-content ol {
            margin-bottom: 1.5rem;
            padding-left: 2rem;
        }
        .portfolio-description .description-content li {
            margin-bottom: 0.5rem;
        }
        .portfolio-description .description-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .portfolio-description .description-content a {
            color: #2C9DD4;
            text-decoration: underline;
        }
    </style>
    <!-- Page Title -->
    <div class="page-title dark-background">
      <div class="container position-relative">
        <h1>Portfolio Details</h1>
        {{-- <h1>{{ $portfolio->title ?? 'Portfolio Details' }}</h1> --}}
        <p>{{ $portfolio->short_desc ?? 'Portfolio description' }}</p>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="{{ route('front.index') }}">Home</a></li>
            <li class="current">{{ $portfolio->title ?? 'Portfolio Details' }}</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->

    <!-- Portfolio Details Section -->
    <section id="portfolio-details" class="portfolio-details section">

      <div class="container" data-aos="fade-up">

        @if($portfolio->images && $portfolio->images->count() > 0)
        <div class="portfolio-details-slider swiper init-swiper">
          <script type="application/json" class="swiper-config">
            {
              "loop": true,
              "speed": 600,
              "autoplay": {
                "delay": 5000
              },
              "slidesPerView": 1,
              "navigation": {
                "nextEl": ".swiper-button-next",
                "prevEl": ".swiper-button-prev"
              },
              "pagination": {
                "el": ".swiper-pagination",
                "type": "bullets",
                "clickable": true
              }
            }
          </script>
          <div class="swiper-wrapper align-items-center">
            @foreach($portfolio->images as $img)
    <div class="swiper-slide">
        {{-- <img
            src="{{ file_exists(public_path('storage/' . $img->image_path))
                        ? asset('storage/' . $img->image_path)
                        : asset('images/Default.jpg') }}"
            alt="{{ $portfolio->title }}"> --}}
            <img
    src="{{ file_exists(public_path($img->image_path))
                ? asset($img->image_path)
                : asset('images/Default.jpg') }}"
    alt="{{ $portfolio->title }}">
    </div>
@endforeach

          </div>
          <div class="swiper-button-prev"></div>
          <div class="swiper-button-next"></div>
          <div class="swiper-pagination"></div>
        </div>
        @else
          <div class="portfolio-details-slider-empty">
            <img src="{{ asset('images/Default.jpg') }}" alt="No Images" style="max-width:240px;border-radius:8px;">
          </div>
        @endif

        <div class="row justify-content-between gy-4 mt-4">

          <div class="col-lg-8" data-aos="fade-up">
            <div class="portfolio-description">
              <h2>{{ $portfolio->title }}</h2>
              <div class="description-content">
                {!! $portfolio->description !!}
              </div>
            </div>
          </div>

          <div class="col-lg-3" data-aos="fade-up" data-aos-delay="100">
            <div class="portfolio-info">
              <h3>Project information</h3>
              <ul>
                @if($portfolio->portfolio_info_title_1 && $portfolio->portfolio_info_1)
                <li><strong>{{ $portfolio->portfolio_info_title_1 }}</strong> {{ $portfolio->portfolio_info_1 }}</li>
                @endif
                @if($portfolio->portfolio_info_title_2 && $portfolio->portfolio_info_2)
                <li><strong>{{ $portfolio->portfolio_info_title_2 }}</strong> {{ $portfolio->portfolio_info_2 }}</li>
                @endif
                @if($portfolio->portfolio_info_title_3 && $portfolio->portfolio_info_3)
                <li><strong>{{ $portfolio->portfolio_info_title_3 }}</strong> {{ $portfolio->portfolio_info_3 }}</li>
                @endif
                @if($portfolio->portfolio_info_title_4 && $portfolio->portfolio_info_4)
                <li><strong>{{ $portfolio->portfolio_info_title_4 }}</strong> {{ $portfolio->portfolio_info_4 }}</li>
                @endif
                @if($portfolio->button_title && $portfolio->button_link)
                <li><a href="{{ $portfolio->button_link }}" target="_blank" class="btn-visit align-self-start">{{ $portfolio->button_title }}</a></li>
                @endif
              </ul>
            </div>
          </div>

        </div>

      </div>

    </section><!-- /Portfolio Details Section -->
@endsection

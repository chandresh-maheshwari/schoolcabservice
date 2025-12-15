{{-- @extends('cherrypik_front_layout.index')

@section('content')
<section class="py-60">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <h1 class="mb-3" style="font-weight:800; color:#0F263C;">
                    {{ $hero->title ?? 'Strategic Solutions for Business Growth' }}
                </h1>
                <p class="mb-4" style="color:#4F5B69;">
                    {!! $hero->description ?? 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.' !!}
                </p>
                <div class="d-flex gap-3 mb-5">
                    <a href="#" class="btn btn-primary">{{ $hero->button_title_1 ?? 'Get a Free Consultation' }}</a>
                    <a href="#" class="btn btn-outline-primary">{{ $hero->button_title_2 ?? 'Our Services' }}</a>
                </div>

                <div class="row g-3">
                    <div class="col-4">
                        <div>
                            <div class="fs-4 fw-bold">{{ $hero->stat_counter_1 ?? 15 }}+</div>
                            <div class="text-muted">
                                @if(!empty($hero->stat_icon_1)) <i class="{{ $hero->stat_icon_1 }} me-1"></i>@endif
                                {{ $hero->stat_title_1 ?? 'Years Experience' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div>
                            <div class="fs-4 fw-bold">{{ $hero->stat_counter_2 ?? 500 }}+</div>
                            <div class="text-muted">
                                @if(!empty($hero->stat_icon_2)) <i class="{{ $hero->stat_icon_2 }} me-1"></i>@endif
                                {{ $hero->stat_title_2 ?? 'Clients Worldwide' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div>
                            <div class="fs-4 fw-bold">{{ $hero->stat_counter_3 ?? 98 }}%</div>
                            <div class="text-muted">
                                @if(!empty($hero->stat_icon_3)) <i class="{{ $hero->stat_icon_3 }} me-1"></i>@endif
                                {{ $hero->stat_title_3 ?? 'Success Rate' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="{{ $hero && $hero->image ? asset($hero->image) : asset('assets/img/about/about-8.webp') }}" class="img-fluid rounded-3 shadow-sm" alt="hero image">
            </div>
        </div>
    </div>
</section>
@endsection

 --}}

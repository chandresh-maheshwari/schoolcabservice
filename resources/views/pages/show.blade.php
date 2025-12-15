@extends('cherrypik_front_layout.index')

@section('content')
    @php
        $templateMap = [
            'hero' => 'templates.hero',
            'about_us' => 'templates.about_us',
            'services' => 'templates.services',
            'portfolio' => 'templates.portfolio',
            'pricing' => 'templates.pricing',
            'teams' => 'templates.teams',
            'contacts' => 'templates.contacts',
            'faq' => 'templates.faq',
            'clients' => 'templates.clients',
            'stats' => 'templates.stats',
            'feature' => 'templates.feature',
            'capabilities' => 'templates.capabilities',
            'advance_capabilities' => 'templates.advance_capabilities',
            'alternative' => 'templates.alternative',
            'call_to_action' => 'templates.call_to_action',
            'why_us' => 'templates.why_us',
            'footer' => 'templates.footer',
        ];
        $view = $templateMap[$template] ?? null;
    @endphp

    <script>
        window.activePageStatus = {{ (int) ($page->status ?? 1) }};
    </script>

    @if($view)
        @include($view, ['data' => $page->data ?? [], 'page' => $page])
    @else
        <section class="py-60">
            <div class="container">
                <h1 class="mb-3">{{ $page->title }}</h1>
                <div>{!! $page->description !!}</div>
            </div>
        </section>
    @endif
@endsection

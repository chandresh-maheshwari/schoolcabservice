@extends('admin_layout.index')

@section('content')
    @php
        $dashboardRoute = $panel['is_school_panel']
            ? route('school.dashboard', ['schoolSlug' => $panel['school_slug']])
            : route('admin_layout.index');
        $indexRoute = $panel['is_school_panel']
            ? route('school.supportRequests.index', ['schoolSlug' => $panel['school_slug']])
            : route('supportRequests.index');
    @endphp

        <div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ $dashboardRoute }}">Dashboard</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">{{ $pageTitle }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

    <div class="container-fluid">

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h3 class="mb-1">{{ $pageTitle }}</h3>
                        <p class="text-muted mb-0">{{ $pageDescription }}</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge rounded-pill bg-light text-dark px-3 py-2">Total {{ $requests->total() }}</span>
                        @if ($panel['is_school_panel'])
                            <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">School Scope</span>
                        @else
                            <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">Admin Scope</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ $indexRoute }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Search</label>
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                            placeholder="Subject, message, category, parent, email">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All statuses</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if (! $panel['is_school_panel'])
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">School</label>
                            <select class="form-select" name="school_id">
                                <option value="">All schools</option>
                                @foreach ($schoolOptions as $school)
                                    <option value="{{ $school->id }}" @selected((string) request('school_id') === (string) $school->id)>{{ $school->school_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Apply</button>
                        <a href="{{ $indexRoute }}" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            @forelse ($requests as $supportRequest)
                @php
                    $reviewRoute = $panel['is_school_panel']
                        ? route('school.supportRequests.review', ['schoolSlug' => $panel['school_slug'], 'id' => $supportRequest->id])
                        : route('supportRequests.review', ['id' => $supportRequest->id]);
                    $statusClass = match ($supportRequest->status) {
                        'closed' => 'bg-success-subtle text-success',
                        'in_progress' => 'bg-warning-subtle text-warning',
                        default => 'bg-info-subtle text-info',
                    };
                @endphp
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                                <div>
                                    <div class="text-muted small mb-1">Ticket #{{ $supportRequest->id }}</div>
                                    <h5 class="mb-1">{{ $supportRequest->subject ?: 'Support Request' }}</h5>
                                    <div class="text-muted">
                                        {{ $supportRequest->requester_name }}
                                        <span class="mx-1">|</span>{{ $supportRequest->email ?: '-' }}
                                        @if ($supportRequest->requester_contact !== '-')
                                            <span class="mx-1">|</span>{{ $supportRequest->requester_contact }}
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge rounded-pill {{ $statusClass }} px-3 py-2">{{ strtoupper(str_replace('_', ' ', $supportRequest->status ?: 'open')) }}</span>
                                    <div class="text-muted small mt-2">Raised {{ optional($supportRequest->created_at)->format('d M Y, h:i A') ?: '-' }}</div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <div class="border rounded-3 p-3 h-100 bg-light">
                                        <div class="text-muted small text-uppercase mb-1">Category</div>
                                        <div class="fw-semibold">{{ $supportRequest->category ?: '-' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded-3 p-3 h-100 bg-light">
                                        <div class="text-muted small text-uppercase mb-1">School</div>
                                        <div class="fw-semibold">{{ $supportRequest->school_name }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded-3 p-3 h-100 bg-light">
                                        <div class="text-muted small text-uppercase mb-1">Children</div>
                                        <div class="fw-semibold">{{ $supportRequest->child_summary }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded-3 p-3 h-100 bg-light">
                                        <div class="text-muted small text-uppercase mb-1">Reviewer</div>
                                        <div class="fw-semibold">{{ optional($supportRequest->reviewer)->first_name ?: '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="border rounded-3 p-3 mb-3">
                                <div class="text-muted small text-uppercase mb-2">Message</div>
                                <div>{{ $supportRequest->message ?: '-' }}</div>
                            </div>

                            <form method="POST" action="{{ $reviewRoute }}">
                                @csrf
                                @method('PATCH')
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-7">
                                        <label class="form-label fw-semibold">Review Notes</label>
                                        <textarea class="form-control" name="admin_notes" rows="2"
                                            placeholder="Add an update for the internal team or parent.">{{ old('admin_notes') }}</textarea>
                                        @if (! empty($supportRequest->admin_notes))
                                            <div class="text-muted small mt-2">Last note: {{ $supportRequest->admin_notes }}</div>
                                        @endif
                                    </div>
                                    <div class="col-lg-5">
                                        <label class="form-label fw-semibold d-block">Quick Actions</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="submit" name="status" value="in_progress" class="btn btn-warning text-white">Mark In Progress</button>
                                            <button type="submit" name="status" value="closed" class="btn btn-success">Close</button>
                                            <button type="submit" name="status" value="open" class="btn btn-outline-secondary">Reopen</button>
                                        </div>
                                        @if ($supportRequest->reviewer || $supportRequest->reviewed_at)
                                            <div class="text-muted small mt-2">
                                                Reviewed by {{ optional($supportRequest->reviewer)->first_name ?: 'Panel User' }}
                                                @if ($supportRequest->reviewed_at)
                                                    on {{ optional($supportRequest->reviewed_at)->format('d M Y, h:i A') }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body py-5 text-center text-muted">
                            No support requests found for the current filters.
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($requests->hasPages())
            <div class="d-flex justify-content-end">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
@endsection

@extends('admin_layout.index')

@section('content')
    @php
        $dashboardRoute = $panel['is_school_panel']
            ? route('school.dashboard', ['schoolSlug' => $panel['school_slug']])
            : route('admin_layout.index');
        $indexRoute = $panel['is_school_panel']
            ? route('school.pushNotifications.index', ['schoolSlug' => $panel['school_slug']])
            : route('pushNotifications.index');
        $sendRoute = $panel['is_school_panel']
            ? route('school.pushNotifications.send', ['schoolSlug' => $panel['school_slug']])
            : route('pushNotifications.send');
        $settingsRoute = $panel['is_school_panel']
            ? route('school.pushNotifications.settings', ['schoolSlug' => $panel['school_slug']])
            : route('pushNotifications.settings');
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
            <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h3 class="mb-1">{{ $pageTitle }}</h3>
                    <p class="text-muted mb-0">{{ $pageDescription }}</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge rounded-pill bg-light text-dark px-3 py-2">Recent {{ $recentNotifications->count() }}</span>
                    <span class="badge rounded-pill {{ $panel['is_school_panel'] ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success' }} px-3 py-2">
                        {{ $panel['is_school_panel'] ? 'School Scope' : 'Admin Scope' }}
                    </span>
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

        <div class="row">
            <div class="col-lg-5 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="mb-3">Send Manual Push</h5>
                        <form method="POST" action="{{ $sendRoute }}" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label fw-semibold">Audience</label>
                                <select class="form-select" name="audience" required>
                                    <option value="parents">Parents</option>
                                    <option value="all_mobile_users">All Mobile Users</option>
                                </select>
                            </div>
                            @if (! $panel['is_school_panel'])
                                <div class="col-12">
                                    <label class="form-label fw-semibold">School</label>
                                    <select class="form-select" name="school_id">
                                        <option value="">All Schools</option>
                                        @foreach ($schools as $school)
                                            <option value="{{ $school->id }}">{{ $school->school_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-12">
                                <label class="form-label fw-semibold">Title</label>
                                <input type="text" class="form-control" name="title" maxlength="150" required placeholder="Write a short push title">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Message</label>
                                <textarea class="form-control" name="message" rows="4" maxlength="1000" required placeholder="Write the push message parents should receive"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Send Push Notification</button>
                                <a href="{{ $indexRoute }}" class="btn btn-outline-secondary">Reset</a>
                            </div>
                            <div class="col-12">
                                <div class="small text-muted">
                                    Push delivery uses Firebase HTTP v1 with the configured service account. Even if live push is unavailable on a device, notifications are still stored in the mobile notification inbox.
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Automation Templates</h5>
                            <span class="text-muted small">Edit titles, messages, and enable or disable events</span>
                        </div>
                        <form method="POST" action="{{ $settingsRoute }}">
                            @csrf
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Event</th>
                                            <th>Enabled</th>
                                            <th>Title Template</th>
                                            <th>Message Template</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($settings as $eventKey => $setting)
                                            <tr>
                                                <td class="fw-semibold">{{ str_replace('_', ' ', ucfirst($eventKey)) }}</td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="settings[{{ $eventKey }}][enabled]" value="1" @checked($setting['enabled'])>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="settings[{{ $eventKey }}][title_template]" value="{{ $setting['title_template'] }}" maxlength="150" required>
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="settings[{{ $eventKey }}][message_template]" rows="2" maxlength="500" required>{{ $setting['message_template'] }}</textarea>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-success">Save Automation Settings</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Recent Notification History</h5>
                    <span class="text-muted small">Latest entries from the mobile notification log</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Recipient</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Type</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentNotifications as $notification)
                                <tr>
                                    <td>{{ $notification->id }}</td>
                                    <td>{{ trim(($notification->first_name ?? '') . ' ' . ($notification->last_name ?? '')) ?: ($notification->email ?: '-') }}</td>
                                    <td>{{ $notification->title }}</td>
                                    <td style="max-width: 360px; white-space: normal;">{{ $notification->message }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $notification->type ?: 'general' }}</span></td>
                                    <td>{{ $notification->created_at_value ? \Illuminate\Support\Carbon::parse($notification->created_at_value)->format('d M Y, h:i A') : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No notifications found yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

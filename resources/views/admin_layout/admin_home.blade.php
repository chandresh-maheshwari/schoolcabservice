@extends('admin_layout.index')

@section('content')
    <div class="container-fluid">
        <div class="section-breadcrumb">
            <div class="breadcrumb-wrapper pb-0">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Dashboard</li>
                    </ol>
                </nav>
            </div>
        </div>

        @php
            $authUser = Auth::user();
            $dashboardTitle = $isAdminUser ? 'Admin Dashboard' : 'School Dashboard';
            $schoolName = $school?->school_name ?: (Auth::user()->first_name ?? null);
        @endphp

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-1">{{ $dashboardTitle }}</h3>
                <p class="text-muted mb-0">
                    @if (! $isAdminUser && $schoolName)
                        Welcome, {{ $schoolName }}
                    @else
                        Welcome, {{ Auth::user()->first_name ?? 'User' }}
                    @endif
                </p>
            </div>
            @if (count($cards) > 1)
                <div class="text-muted small mt-2 mt-md-0">
                    <i class="fa fa-arrows mr-1"></i> Drag dashboard cards to change sequence.
                </div>
            @endif
        </div>

        @php
            $schoolSlug = $currentSchoolSlug ?? request()->route('schoolSlug');
        @endphp

        <div class="row dashboard-card-grid" id="dashboardCardGrid"
            data-save-url="{{ $isAdminUser ? route('admin.dashboard.cards.order') : route('school.dashboard.cards.order', ['schoolSlug' => $schoolSlug]) }}">
            @foreach ($cards as $card)
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-4 dashboard-card-item"
                    data-card-key="{{ $card['key'] }}" draggable="true">
                    <a href="{{ str_starts_with($card['route'], 'school.') ? route($card['route'], ['schoolSlug' => $schoolSlug]) : route($card['route']) }}" class="text-decoration-none">
                        <div class="card shadow-sm h-100 dashboard-stat-card">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small mb-1">{{ $card['label'] }}</div>
                                    <div class="h3 mb-0">{{ (int) ($stats[$card['key']] ?? 0) }}</div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="dashboard-card-handle text-muted mr-3" title="Drag to reorder">
                                        <i class="fa fa-arrows"></i>
                                    </span>
                                    <div class="{{ $card['bg'] }} rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 46px; height: 46px;">
                                        <i class="{{ $card['icon'] }} text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="row">
            <div class="col-12 col-xl-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <h6 class="mb-0">Recent Bookings</h6>
                        @if ($authUser && $authUser->canAccessAdminRoute('booking.index'))
                            <a href="{{ $isAdminUser ? route('booking.index') : route('school.booking.index', ['schoolSlug' => $schoolSlug]) }}" class="btn btn-sm btn-outline-primary">View all</a>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>School</th>
                                        <th>Route</th>
                                        <th>Payment</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentBookings as $booking)
                                        <tr>
                                            <td>{{ $booking->id }}</td>
                                            <td>{{ $bookingSchoolNameMap[$booking->school_id] ?? '-' }}</td>
                                            <td>{{ $bookingRouteNameMap[$booking->route_id] ?? ($booking->route_id ?? '-') }}</td>
                                            <td>{{ $booking->payment_status ?? '-' }}</td>
                                            <td>{{ optional($booking->created_at)->format('d M Y') ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">No bookings found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <h6 class="mb-0">Recent Emergencies</h6>
                        @if ($authUser && $authUser->canAccessAdminRoute('emergency.index'))
                            <a href="{{ $isAdminUser ? route('emergency.index') : route('school.emergency.index', ['schoolSlug' => $schoolSlug]) }}" class="btn btn-sm btn-outline-primary">View all</a>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Reported By</th>
                                        <th>Driver</th>
                                        <th>Vehicle</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentEmergencies as $incident)
                                        <tr>
                                            <td>{{ $incident->emergency_type ?? '-' }}</td>
                                            <td>{{ $incident->reported_by ?? '-' }}</td>
                                            <td>{{ optional($incident->driver)->driver_name ?? '-' }}</td>
                                            <td>{{ optional($incident->vehicle)->vehicle_number ?? '-' }}</td>
                                            <td>{{ optional($incident->created_at)->format('d M Y') ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">No emergencies found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .dashboard-card-item {
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .dashboard-card-item.dragging {
            opacity: 0.45;
        }

        .dashboard-stat-card {
            cursor: move;
        }

        .dashboard-card-handle {
            font-size: 18px;
            line-height: 1;
            cursor: move;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const grid = document.getElementById('dashboardCardGrid');
            if (!grid) {
                return;
            }

            const items = Array.from(grid.querySelectorAll('.dashboard-card-item'));
            if (items.length < 2) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const saveUrl = grid.dataset.saveUrl;
            let draggingItem = null;
            let saveTimer = null;

            const getOrder = () => Array.from(grid.querySelectorAll('.dashboard-card-item'))
                .map((item) => item.dataset.cardKey)
                .filter(Boolean);

            const saveOrder = () => {
                if (!saveUrl || !csrfToken) {
                    return;
                }

                fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ order: getOrder() }),
                }).catch(() => {
                    // Keep the UI usable even if preference saving fails.
                });
            };

            const queueSave = () => {
                window.clearTimeout(saveTimer);
                saveTimer = window.setTimeout(saveOrder, 200);
            };

            items.forEach((item) => {
                item.addEventListener('dragstart', function (event) {
                    draggingItem = item;
                    item.classList.add('dragging');
                    if (event.dataTransfer) {
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', item.dataset.cardKey || '');
                    }
                });

                item.addEventListener('dragend', function () {
                    item.classList.remove('dragging');
                    draggingItem = null;
                });

                item.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    if (!draggingItem || draggingItem === item) {
                        return;
                    }

                    const rect = item.getBoundingClientRect();
                    const shouldInsertBefore = event.clientY < rect.top + (rect.height / 2);
                    grid.insertBefore(draggingItem, shouldInsertBefore ? item : item.nextSibling);
                });

                item.addEventListener('drop', function (event) {
                    event.preventDefault();
                    queueSave();
                });
            });
        });
    </script>
@endsection

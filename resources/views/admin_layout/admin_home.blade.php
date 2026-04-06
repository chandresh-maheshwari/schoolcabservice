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
            $statusBadgeClass = static function ($status) {
                return match (strtolower((string) $status)) {
                    'open', 'reported', 'requested' => 'status-badge status-open',
                    'in_progress', 'active' => 'status-badge status-progress',
                    'approved', 'closed', 'completed' => 'status-badge status-success',
                    'rejected', 'cancelled' => 'status-badge status-danger',
                    default => 'status-badge status-neutral',
                };
            };
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
            data-save-url="{{ $isAdminUser ? route('admin.dashboard.cards.order') : route('school.dashboard.cards.order', ['schoolSlug' => $schoolSlug]) }}"
            data-live-summary-url="{{ $liveSummaryUrl }}">
            @foreach ($cards as $card)
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-4 dashboard-card-item"
                    data-card-key="{{ $card['key'] }}" draggable="true">
                    <a href="{{ str_starts_with($card['route'], 'school.') ? route($card['route'], ['schoolSlug' => $schoolSlug]) : route($card['route']) }}" class="text-decoration-none">
                        <div class="card shadow-sm h-100 dashboard-stat-card">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small mb-1">{{ $card['label'] }}</div>
                                    <div class="h3 mb-0" data-stat-key="{{ $card['key'] }}">{{ (int) ($stats[$card['key']] ?? 0) }}</div>
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

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <h6 class="mb-0">Action Center</h6>
                <span class="text-muted small">Recent mobile activity and urgent items</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-6 col-xl-3 mb-3">
                        <div class="dashboard-highlight-card border-danger">
                            <div class="text-muted small">Active Emergencies</div>
                            <div class="h2 mb-1" data-action-key="active_emergencies">{{ (int) ($actionStats['active_emergencies'] ?? 0) }}</div>
                            <a href="{{ $isAdminUser ? route('emergency.index') : route('school.emergency.index', ['schoolSlug' => $schoolSlug]) }}" class="small text-danger font-weight-bold">Open SOS board</a>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3 mb-3">
                        <div class="dashboard-highlight-card border-warning">
                            <div class="text-muted small">Open Support Requests</div>
                            <div class="h2 mb-1" data-action-key="open_support_requests">{{ (int) ($actionStats['open_support_requests'] ?? 0) }}</div>
                            <a href="{{ $isAdminUser ? route('supportRequests.index') : route('school.supportRequests.index', ['schoolSlug' => $schoolSlug]) }}" class="small text-warning font-weight-bold">Review support queue</a>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3 mb-3">
                        <div class="dashboard-highlight-card border-info">
                            <div class="text-muted small">Pending Leave Requests</div>
                            <div class="h2 mb-1" data-action-key="pending_leave_requests">{{ (int) ($actionStats['pending_leave_requests'] ?? 0) }}</div>
                            <a href="{{ $isAdminUser ? route('leaveRequests.index') : route('school.leaveRequests.index', ['schoolSlug' => $schoolSlug]) }}" class="small text-info font-weight-bold">Open leave requests</a>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3 mb-3">
                        <div class="dashboard-highlight-card border-secondary">
                            <div class="text-muted small">Feedback In Last 7 Days</div>
                            <div class="h2 mb-1" data-action-key="recent_feedback">{{ (int) ($actionStats['recent_feedback'] ?? 0) }}</div>
                            <a href="{{ $isAdminUser ? route('rating.index') : route('school.rating.index', ['schoolSlug' => $schoolSlug]) }}" class="small text-secondary font-weight-bold">See latest feedback</a>
                        </div>
                    </div>
                </div>
            </div>
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
                                <tbody id="recentBookingsBody">
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
                                <tbody id="recentEmergenciesBody">
                                    @forelse ($recentEmergencies as $incident)
                                        @php
                                            $isActiveEmergency = (int) ($incident->status ?? 0) === 1;
                                        @endphp
                                        <tr class="{{ $isActiveEmergency ? 'row-attention' : '' }}">
                                            <td>
                                                <div>{{ $incident->emergency_type ?? '-' }}</div>
                                                @if ($isActiveEmergency)
                                                    <span class="{{ $statusBadgeClass('active') }}">Active</span>
                                                @else
                                                    <span class="{{ $statusBadgeClass('closed') }}">Resolved / Closed</span>
                                                @endif
                                            </td>
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

        <div class="row">
            <div class="col-12 col-xl-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <h6 class="mb-0">Recent Feedback</h6>
                        <a href="{{ $isAdminUser ? route('rating.index') : route('school.rating.index', ['schoolSlug' => $schoolSlug]) }}" class="btn btn-sm btn-outline-primary">View all</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Rating</th>
                                        <th>Driver</th>
                                        <th>Vehicle</th>
                                        <th>Comment</th>
                                    </tr>
                                </thead>
                                <tbody id="recentRatingsBody">
                                    @forelse ($recentRatings as $rating)
                                        @php
                                            $isNewFeedback = optional($rating->created_at)?->gte(now()->subDays(2)) ?? false;
                                        @endphp
                                        <tr class="{{ $isNewFeedback ? 'row-fresh' : '' }}">
                                            <td>
                                                <div>{{ $rating->rating }}/5</div>
                                                @if ($isNewFeedback)
                                                    <span class="{{ $statusBadgeClass('open') }}">New</span>
                                                @endif
                                            </td>
                                            <td>{{ optional($rating->driver)->driver_name ?? '-' }}</td>
                                            <td>{{ optional($rating->vehicle)->vehicle_number ?? '-' }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit((string) ($rating->comments ?? '-'), 60) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">No feedback found.</td>
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
                        <h6 class="mb-0">Recent Support Requests</h6>
                        <a href="{{ $isAdminUser ? route('supportRequests.index') : route('school.supportRequests.index', ['schoolSlug' => $schoolSlug]) }}" class="btn btn-sm btn-outline-primary">View all</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Requester</th>
                                    </tr>
                                </thead>
                                <tbody id="recentSupportRequestsBody">
                                    @forelse ($recentSupportRequests as $supportRequest)
                                        @php
                                            $supportStatus = strtolower((string) ($supportRequest->status ?? ''));
                                            $supportNeedsReview = in_array($supportStatus, ['open', 'in_progress'], true)
                                                && empty($supportRequest->reviewed_at);
                                        @endphp
                                        <tr class="{{ $supportNeedsReview ? 'row-attention' : '' }}">
                                            <td>{{ \Illuminate\Support\Str::limit((string) ($supportRequest->subject ?? '-'), 40) }}</td>
                                            <td>{{ $supportRequest->category ?? '-' }}</td>
                                            <td>
                                                <span class="{{ $statusBadgeClass($supportRequest->status) }}">{{ $supportRequest->status ?? '-' }}</span>
                                                @if ($supportNeedsReview)
                                                    <span class="mini-pill mini-pill-warning">Needs review</span>
                                                @endif
                                            </td>
                                            <td>{{ $supportRequest->email ?? optional($supportRequest->user)->email ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">No support requests found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <h6 class="mb-0">Recent Leave Requests</h6>
                        <a href="{{ $isAdminUser ? route('leaveRequests.index') : route('school.leaveRequests.index', ['schoolSlug' => $schoolSlug]) }}" class="btn btn-sm btn-outline-primary">View all</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Child</th>
                                        <th>Dates</th>
                                        <th>Status</th>
                                        <th>Reason</th>
                                        <th>Requester</th>
                                    </tr>
                                </thead>
                                <tbody id="recentLeaveRequestsBody">
                                    @forelse ($recentLeaveRequests as $leaveRequest)
                                        @php
                                            $leaveStatus = strtolower((string) ($leaveRequest->status ?? ''));
                                            $leaveNeedsReview = $leaveStatus === 'requested' && empty($leaveRequest->reviewed_at);
                                        @endphp
                                        <tr class="{{ $leaveNeedsReview ? 'row-attention' : '' }}">
                                            <td>{{ $leaveRequest->child_name ?? optional($leaveRequest->child)->child_name ?? '-' }}</td>
                                            <td>
                                                {{ optional($leaveRequest->from_date)->format('d M Y') ?? ($leaveRequest->from_date ?? '-') }}
                                                -
                                                {{ optional($leaveRequest->to_date)->format('d M Y') ?? ($leaveRequest->to_date ?? '-') }}
                                            </td>
                                            <td>
                                                <span class="{{ $statusBadgeClass($leaveRequest->status) }}">{{ $leaveRequest->status ?? '-' }}</span>
                                                @if ($leaveNeedsReview)
                                                    <span class="mini-pill mini-pill-warning">New</span>
                                                @endif
                                            </td>
                                            <td>{{ \Illuminate\Support\Str::limit((string) ($leaveRequest->reason ?? '-'), 70) }}</td>
                                            <td>{{ $leaveRequest->email ?? optional($leaveRequest->user)->email ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">No leave requests found.</td>
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

        .dashboard-highlight-card {
            border: 1px solid #e9ecef;
            border-left-width: 4px;
            border-radius: 14px;
            padding: 16px 18px;
            background: #fff;
            height: 100%;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 4px;
        }

        .status-open {
            background: #fff4db;
            color: #9a6700;
        }

        .status-progress {
            background: #e0f2fe;
            color: #075985;
        }

        .status-success {
            background: #dcfce7;
            color: #166534;
        }

        .status-danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-neutral {
            background: #eef2f7;
            color: #475569;
        }

        .mini-pill {
            display: inline-block;
            margin-left: 6px;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .mini-pill-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .row-attention {
            background: linear-gradient(90deg, rgba(254, 242, 242, 0.95), rgba(255, 255, 255, 1));
        }

        .row-fresh {
            background: linear-gradient(90deg, rgba(239, 246, 255, 0.95), rgba(255, 255, 255, 1));
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const grid = document.getElementById('dashboardCardGrid');
            if (!grid) {
                return;
            }

            const items = Array.from(grid.querySelectorAll('.dashboard-card-item'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const saveUrl = grid.dataset.saveUrl;
            const liveSummaryUrl = grid.dataset.liveSummaryUrl;
            let draggingItem = null;
            let saveTimer = null;
            let pollingTimer = null;
            let isRefreshing = false;
            const refreshIntervalMs = 60000;

            const escapeHtml = (value) => String(value ?? '-')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const statusBadgeClass = (status) => {
                const normalized = String(status || '').toLowerCase();
                if (['open', 'reported', 'requested'].includes(normalized)) {
                    return 'status-badge status-open';
                }
                if (['in_progress', 'active'].includes(normalized)) {
                    return 'status-badge status-progress';
                }
                if (['approved', 'closed', 'completed'].includes(normalized)) {
                    return 'status-badge status-success';
                }
                if (['rejected', 'cancelled'].includes(normalized)) {
                    return 'status-badge status-danger';
                }

                return 'status-badge status-neutral';
            };

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

            if (items.length > 1) {
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
            }

            const updateNavbarCounts = (counts) => {
                if (!counts) {
                    return;
                }

                const notificationRoot = document.querySelector('.nav-notifications[data-live-summary-url]');
                if (!notificationRoot) {
                    return;
                }

                const total = Number(counts.total || 0);
                const totalNode = notificationRoot.querySelector('[data-alert-total]');
                if (totalNode) {
                    totalNode.textContent = total > 0 ? String(total) : '0';
                    totalNode.style.display = total > 0 ? 'inline-flex' : 'none';
                }

                notificationRoot.querySelectorAll('[data-alert-key]').forEach((node) => {
                    const key = node.getAttribute('data-alert-key');
                    node.textContent = String(Number(counts[key] || 0));
                });
            };

            const renderEmptyRow = (colspan, message) =>
                `<tr><td colspan="${colspan}" class="text-center text-muted py-3">${escapeHtml(message)}</td></tr>`;

            const renderRows = (tbodyId, rows, emptyMarkup, rowRenderer) => {
                const tbody = document.getElementById(tbodyId);
                if (!tbody) {
                    return;
                }

                if (!Array.isArray(rows) || rows.length === 0) {
                    tbody.innerHTML = emptyMarkup;
                    return;
                }

                tbody.innerHTML = rows.map(rowRenderer).join('');
            };

            const updateDashboardFromSummary = (summary) => {
                if (!summary) {
                    return;
                }

                Object.entries(summary.stats || {}).forEach(([key, value]) => {
                    const node = grid.querySelector(`[data-stat-key="${key}"]`);
                    if (node) {
                        node.textContent = String(Number(value || 0));
                    }
                });

                Object.entries(summary.actionStats || {}).forEach(([key, value]) => {
                    const node = document.querySelector(`[data-action-key="${key}"]`);
                    if (node) {
                        node.textContent = String(Number(value || 0));
                    }
                });

                renderRows(
                    'recentBookingsBody',
                    summary.recentBookings,
                    renderEmptyRow(5, 'No bookings found.'),
                    (booking) => `
                        <tr>
                            <td>${escapeHtml(booking.id)}</td>
                            <td>${escapeHtml(booking.school)}</td>
                            <td>${escapeHtml(booking.route)}</td>
                            <td>${escapeHtml(booking.payment)}</td>
                            <td>${escapeHtml(booking.createdAt)}</td>
                        </tr>
                    `
                );

                renderRows(
                    'recentEmergenciesBody',
                    summary.recentEmergencies,
                    renderEmptyRow(5, 'No emergencies found.'),
                    (incident) => `
                        <tr class="${incident.isActive ? 'row-attention' : ''}">
                            <td>
                                <div>${escapeHtml(incident.type)}</div>
                                <span class="${statusBadgeClass(incident.isActive ? 'active' : 'closed')}">${escapeHtml(incident.statusLabel)}</span>
                            </td>
                            <td>${escapeHtml(incident.reportedBy)}</td>
                            <td>${escapeHtml(incident.driver)}</td>
                            <td>${escapeHtml(incident.vehicle)}</td>
                            <td>${escapeHtml(incident.createdAt)}</td>
                        </tr>
                    `
                );

                renderRows(
                    'recentRatingsBody',
                    summary.recentRatings,
                    renderEmptyRow(4, 'No feedback found.'),
                    (rating) => `
                        <tr class="${rating.isNew ? 'row-fresh' : ''}">
                            <td>
                                <div>${escapeHtml(rating.rating)}</div>
                                ${rating.isNew ? `<span class="${statusBadgeClass('open')}">New</span>` : ''}
                            </td>
                            <td>${escapeHtml(rating.driver)}</td>
                            <td>${escapeHtml(rating.vehicle)}</td>
                            <td>${escapeHtml(rating.comment)}</td>
                        </tr>
                    `
                );

                renderRows(
                    'recentSupportRequestsBody',
                    summary.recentSupportRequests,
                    renderEmptyRow(4, 'No support requests found.'),
                    (requestRow) => `
                        <tr class="${requestRow.needsReview ? 'row-attention' : ''}">
                            <td>${escapeHtml(requestRow.subject)}</td>
                            <td>${escapeHtml(requestRow.category)}</td>
                            <td>
                                <span class="${statusBadgeClass(requestRow.status)}">${escapeHtml(requestRow.status)}</span>
                                ${requestRow.needsReview ? '<span class="mini-pill mini-pill-warning">Needs review</span>' : ''}
                            </td>
                            <td>${escapeHtml(requestRow.requester)}</td>
                        </tr>
                    `
                );

                renderRows(
                    'recentLeaveRequestsBody',
                    summary.recentLeaveRequests,
                    renderEmptyRow(5, 'No leave requests found.'),
                    (requestRow) => `
                        <tr class="${requestRow.needsReview ? 'row-attention' : ''}">
                            <td>${escapeHtml(requestRow.child)}</td>
                            <td>${escapeHtml(requestRow.dates)}</td>
                            <td>
                                <span class="${statusBadgeClass(requestRow.status)}">${escapeHtml(requestRow.status)}</span>
                                ${requestRow.needsReview ? '<span class="mini-pill mini-pill-warning">New</span>' : ''}
                            </td>
                            <td>${escapeHtml(requestRow.reason)}</td>
                            <td>${escapeHtml(requestRow.requester)}</td>
                        </tr>
                    `
                );

                updateNavbarCounts(summary.navbarAlertCounts || null);
            };

            const scheduleRefresh = () => {
                window.clearTimeout(pollingTimer);
                pollingTimer = window.setTimeout(refreshSummary, refreshIntervalMs);
            };

            const refreshSummary = async () => {
                scheduleRefresh();
                if (!liveSummaryUrl || document.hidden || isRefreshing) {
                    return;
                }

                isRefreshing = true;
                try {
                    const response = await fetch(liveSummaryUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    if (payload && payload.success && payload.data) {
                        updateDashboardFromSummary(payload.data);
                    }
                } catch (error) {
                    // Leave the current dashboard state in place if polling fails.
                } finally {
                    isRefreshing = false;
                }
            };

            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) {
                    refreshSummary();
                }
            });

            updateNavbarCounts(@json($navbarAlertCounts ?? ['total' => 0, 'sos' => 0, 'support' => 0, 'leave' => 0]));
            refreshSummary();
        });
    </script>
@endsection

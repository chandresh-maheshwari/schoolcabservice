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
            $initialNavbarAlertCounts = $navbarAlertCounts ?? ['total' => 0, 'sos' => 0, 'support' => 0, 'leave' => 0];
            $dashboardWidgetOrderPositions = array_flip($dashboardWidgetOrder ?? []);
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
                    <i class="fa fa-arrows mr-1"></i> Drag dashboard cards and recent panels to change sequence.
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
                @php
                    $cardUrl = str_starts_with($card['route'], 'school.')
                        ? route($card['route'], ['schoolSlug' => $schoolSlug])
                        : route($card['route']);
                @endphp
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-4 dashboard-card-item"
                    data-card-key="{{ $card['key'] }}">
                    <a href="{{ $cardUrl }}" class="text-decoration-none d-block h-100">
                        <div class="card shadow-sm h-100 dashboard-stat-card">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small mb-1">{{ $card['label'] }}</div>
                                    <div class="h3 mb-0 dashboard-card-pointer-target" data-stat-key="{{ $card['key'] }}">{{ (int) ($stats[$card['key']] ?? 0) }}</div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="dashboard-card-handle text-muted mr-3" title="Drag to reorder">
                                        <i class="fa fa-arrows"></i>
                                    </span>
                                    <div class="dashboard-card-pointer-target {{ $card['bg'] }} rounded-circle d-flex align-items-center justify-content-center"
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

        <div class="row dashboard-widget-grid" id="dashboardWidgetGrid"
            data-save-url="{{ $isAdminUser ? route('admin.dashboard.cards.order') : route('school.dashboard.cards.order', ['schoolSlug' => $schoolSlug]) }}">
            <div class="col-12 col-xl-6 mb-4 dashboard-widget-item"
                data-widget-key="recent_bookings"
                style="order: {{ $dashboardWidgetOrderPositions['recent_bookings'] ?? 0 }};">
                <div class="card shadow-sm h-100 dashboard-widget-card">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="dashboard-widget-handle text-muted mr-2" title="Drag to reorder">
                                <i class="fa fa-arrows"></i>
                            </span>
                            <h6 class="mb-0">Recent Bookings</h6>
                        </div>
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

            <div class="col-12 col-xl-6 mb-4 dashboard-widget-item"
                data-widget-key="recent_emergencies"
                style="order: {{ $dashboardWidgetOrderPositions['recent_emergencies'] ?? 1 }};">
                <div class="card shadow-sm h-100 dashboard-widget-card">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="dashboard-widget-handle text-muted mr-2" title="Drag to reorder">
                                <i class="fa fa-arrows"></i>
                            </span>
                            <h6 class="mb-0">Recent Emergencies</h6>
                        </div>
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

            <div class="col-12 col-xl-6 mb-4 dashboard-widget-item"
                data-widget-key="recent_feedback"
                style="order: {{ $dashboardWidgetOrderPositions['recent_feedback'] ?? 2 }};">
                <div class="card shadow-sm h-100 dashboard-widget-card">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="dashboard-widget-handle text-muted mr-2" title="Drag to reorder">
                                <i class="fa fa-arrows"></i>
                            </span>
                            <h6 class="mb-0">Recent Feedback</h6>
                        </div>
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

            <div class="col-12 col-xl-6 mb-4 dashboard-widget-item"
                data-widget-key="recent_support_requests"
                style="order: {{ $dashboardWidgetOrderPositions['recent_support_requests'] ?? 3 }};">
                <div class="card shadow-sm h-100 dashboard-widget-card">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="dashboard-widget-handle text-muted mr-2" title="Drag to reorder">
                                <i class="fa fa-arrows"></i>
                            </span>
                            <h6 class="mb-0">Recent Support Requests</h6>
                        </div>
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

        .dashboard-widget-item {
            float: none;
            width: auto;
            max-width: none;
            margin-bottom: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .dashboard-widget-item.dragging {
            opacity: 0.45;
        }

        .dashboard-widget-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr);
            gap: 1.5rem;
            margin-left: 0;
            margin-right: 0;
            align-items: start;
        }

        .dashboard-widget-grid::after {
            display: none;
        }

        .dashboard-widget-card {
            cursor: move;
        }

        .dashboard-card-grid.ui-sortable,
        .dashboard-widget-grid.ui-sortable {
            min-height: 40px;
        }

        .dashboard-sortable-placeholder {
            visibility: visible !important;
            border: 2px dashed #9bb1ff;
            border-radius: 12px;
            background: rgba(45, 51, 107, 0.06);
            min-height: 120px;
        }

        .dashboard-sortable-placeholder.dashboard-widget-item {
            float: none;
            width: auto;
            max-width: none;
            margin-bottom: 0 !important;
            min-height: 150px;
        }

        @media (min-width: 1200px) {
            .dashboard-widget-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-sortable-placeholder.dashboard-widget-item {
                width: auto;
            }
        }

        .dashboard-card-item.ui-sortable-helper,
        .dashboard-widget-item.ui-sortable-helper {
            z-index: 1055;
        }

        .dashboard-widget-swap-hover .dashboard-widget-card {
            outline: 2px dashed #9bb1ff;
            outline-offset: 2px;
            background: rgba(45, 51, 107, 0.03);
        }

        .dashboard-card-item .dashboard-card-handle,
        .dashboard-card-item .dashboard-card-handle *,
        .dashboard-widget-item .dashboard-widget-handle,
        .dashboard-widget-item .dashboard-widget-handle * {
            font-size: 18px;
            line-height: 1;
            cursor: move;
        }

        .dashboard-card-item .dashboard-card-pointer-target,
        .dashboard-card-item .dashboard-card-pointer-target * {
            cursor: pointer !important;
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

    <script src="{{ asset('assets/vendors/jquery-ui/jquery-ui.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const grid = document.getElementById('dashboardCardGrid');
            if (!grid) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const liveSummaryUrl = grid.dataset.liveSummaryUrl;
            let suppressClickUntil = 0;
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

            const setupSortableContainer = (container, options) => {
                if (!container) {
                    return;
                }

                const itemSelector = options.itemSelector;
                const handleSelector = options.handleSelector;
                const keyAttribute = options.keyAttribute;
                const section = options.section;
                const saveUrl = container.dataset.saveUrl;
                const initialItems = Array.from(container.querySelectorAll(itemSelector));
                let draggingItem = null;
                let saveTimer = null;

                const disableNativeDragging = () => {
                    container.querySelectorAll(`${itemSelector}, ${itemSelector} a, ${itemSelector} img`).forEach((element) => {
                        element.setAttribute('draggable', 'false');
                    });

                    container.querySelectorAll(`${itemSelector} a, ${itemSelector} img`).forEach((element) => {
                        if (element.dataset.dashboardDragBound === '1') {
                            return;
                        }

                        element.dataset.dashboardDragBound = '1';
                        element.addEventListener('dragstart', function (event) {
                            event.preventDefault();
                        });
                    });
                };

                if (options.normalizeDomOrder !== false) {
                    initialItems
                        .slice()
                        .sort((leftItem, rightItem) => {
                            const leftOrder = Number(leftItem.style.order || 0);
                            const rightOrder = Number(rightItem.style.order || 0);
                            return leftOrder - rightOrder;
                        })
                        .forEach((item) => {
                            container.appendChild(item);
                            item.style.order = '';
                        });
                }

                const items = Array.from(container.querySelectorAll(itemSelector));
                const dragAnywhere = options.dragAnywhere === true || !handleSelector;
                const cancelSelector = options.cancelSelector || 'a, button, input, select, textarea';

                const resetItemArming = (item) => {
                    item.dataset.dragArmed = '0';
                    item.setAttribute('draggable', dragAnywhere ? 'true' : 'false');
                };

                const getDropReferenceItem = (event) => {
                    const candidate = event.target.closest(itemSelector);
                    if (candidate && candidate !== draggingItem && container.contains(candidate)) {
                        return candidate;
                    }

                    const siblings = Array.from(container.querySelectorAll(itemSelector))
                        .filter((item) => item !== draggingItem);

                    if (siblings.length === 0) {
                        return null;
                    }

                    const pointerX = event.clientX;
                    const pointerY = event.clientY;

                    let closest = null;
                    let closestDistance = Number.POSITIVE_INFINITY;

                    siblings.forEach((item) => {
                        const rect = item.getBoundingClientRect();
                        const centerX = rect.left + (rect.width / 2);
                        const centerY = rect.top + (rect.height / 2);
                        const distance = Math.hypot(pointerX - centerX, pointerY - centerY);

                        if (distance < closestDistance) {
                            closestDistance = distance;
                            closest = item;
                        }
                    });

                    return closest;
                };

                const moveDraggingItem = (event) => {
                    if (!draggingItem) {
                        return;
                    }

                    const referenceItem = getDropReferenceItem(event);
                    if (!referenceItem) {
                        container.appendChild(draggingItem);
                        return;
                    }

                    const rect = referenceItem.getBoundingClientRect();
                    const sameRow = event.clientY >= rect.top && event.clientY <= rect.bottom;
                    const shouldInsertBefore = sameRow
                        ? event.clientX < rect.left + (rect.width / 2)
                        : event.clientY < rect.top + (rect.height / 2);

                    container.insertBefore(draggingItem, shouldInsertBefore ? referenceItem : referenceItem.nextSibling);
                };

                const getOrder = () => Array.from(container.querySelectorAll(itemSelector))
                    .map((item) => item.dataset[keyAttribute])
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
                        body: JSON.stringify({
                            section,
                            order: getOrder(),
                        }),
                    }).catch(() => {
                        // Keep the UI usable even if preference saving fails.
                    });
                };

                const queueSave = () => {
                    window.clearTimeout(saveTimer);
                    saveTimer = window.setTimeout(saveOrder, 200);
                };

                if (!options.preferNative && window.jQuery && typeof window.jQuery.fn.sortable === 'function') {
                    const $container = window.jQuery(container);
                    const sortableOptions = {
                        items: itemSelector,
                        cancel: cancelSelector,
                        placeholder: options.placeholderClass || 'dashboard-sortable-placeholder',
                        forcePlaceholderSize: true,
                        forceHelperSize: true,
                        tolerance: 'pointer',
                        distance: 6,
                        helper: 'clone',
                        start: function (_event, ui) {
                            suppressClickUntil = Date.now() + 500;
                            ui.item.addClass('dragging');
                            ui.placeholder.height(ui.item.outerHeight());
                            ui.placeholder.width(ui.item.outerWidth());
                            ui.helper.width(ui.item.outerWidth());
                        },
                        stop: function (_event, ui) {
                            ui.item.removeClass('dragging');
                            suppressClickUntil = Date.now() + 500;
                            queueSave();
                        },
                    };

                    if (!options.dragAnywhere && handleSelector) {
                        sortableOptions.handle = handleSelector;
                    }

                    disableNativeDragging();
                    $container.sortable(sortableOptions);

                    return;
                }

                disableNativeDragging();
                items.forEach((item) => resetItemArming(item));

                items.forEach((item) => {
                    const handle = handleSelector ? item.querySelector(handleSelector) : null;
                    const links = item.querySelectorAll('a');

                    if (handle) {
                        handle.addEventListener('mousedown', function (event) {
                            if (event.button !== 0) {
                                return;
                            }

                            item.dataset.dragArmed = '1';
                            item.setAttribute('draggable', 'true');
                        });

                        handle.addEventListener('click', function (event) {
                            event.preventDefault();
                            event.stopPropagation();
                        });
                    }

                    links.forEach((link) => {
                        link.addEventListener('dragstart', function (event) {
                            event.preventDefault();
                        });
                    });
                });

                if (items.length <= 1) {
                    return;
                }

                items.forEach((item) => {
                    item.addEventListener('dragstart', function (event) {
                        const startedFromHandle = handleSelector ? event.target.closest(handleSelector) : null;

                        if (!dragAnywhere && item.dataset.dragArmed !== '1') {
                            event.preventDefault();
                            return;
                        }

                        if (cancelSelector && event.target.closest(cancelSelector) && !startedFromHandle) {
                            event.preventDefault();
                            resetItemArming(item);
                            return;
                        }

                        draggingItem = item;
                        suppressClickUntil = Date.now() + 500;
                        item.classList.add('dragging');
                        if (event.dataTransfer) {
                            event.dataTransfer.effectAllowed = 'move';
                            event.dataTransfer.setData('text/plain', item.dataset[keyAttribute] || '');
                        }
                    });

                    item.addEventListener('dragend', function () {
                        item.classList.remove('dragging');
                        draggingItem = null;
                        suppressClickUntil = Date.now() + 500;
                        resetItemArming(item);
                    });
                });

                document.addEventListener('mouseup', function () {
                    if (draggingItem) {
                        return;
                    }

                    items.forEach((item) => resetItemArming(item));
                });

                container.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    moveDraggingItem(event);
                });

                container.addEventListener('drop', function (event) {
                    event.preventDefault();
                    moveDraggingItem(event);
                    queueSave();
                });
            };

            const setupSwapContainer = (container, options) => {
                if (!container || !(window.jQuery && typeof window.jQuery.fn.draggable === 'function' && typeof window.jQuery.fn.droppable === 'function')) {
                    return false;
                }

                const itemSelector = options.itemSelector;
                const handleSelector = options.handleSelector;
                const keyAttribute = options.keyAttribute;
                const section = options.section;
                const saveUrl = container.dataset.saveUrl;
                const cancelSelector = options.cancelSelector || 'a, button, input, select, textarea';
                let saveTimer = null;

                const disableNativeDragging = () => {
                    container.querySelectorAll(`${itemSelector}, ${itemSelector} a, ${itemSelector} img`).forEach((element) => {
                        element.setAttribute('draggable', 'false');
                    });

                    container.querySelectorAll(`${itemSelector} a, ${itemSelector} img`).forEach((element) => {
                        if (element.dataset.dashboardDragBound === '1') {
                            return;
                        }

                        element.dataset.dashboardDragBound = '1';
                        element.addEventListener('dragstart', function (event) {
                            event.preventDefault();
                        });
                    });
                };

                const items = Array.from(container.querySelectorAll(itemSelector));
                if (items.length <= 1) {
                    return true;
                }

                if (options.normalizeDomOrder !== false) {
                    items
                        .slice()
                        .sort((leftItem, rightItem) => {
                            const leftOrder = Number(leftItem.style.order || 0);
                            const rightOrder = Number(rightItem.style.order || 0);
                            return leftOrder - rightOrder;
                        })
                        .forEach((item) => {
                            container.appendChild(item);
                            item.style.order = '';
                        });
                }

                const getOrder = () => Array.from(container.querySelectorAll(itemSelector))
                    .map((item) => item.dataset[keyAttribute])
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
                        body: JSON.stringify({
                            section,
                            order: getOrder(),
                        }),
                    }).catch(() => {
                        // Keep the UI usable even if preference saving fails.
                    });
                };

                const queueSave = () => {
                    window.clearTimeout(saveTimer);
                    saveTimer = window.setTimeout(saveOrder, 200);
                };

                const swapElements = (firstElement, secondElement) => {
                    if (!firstElement || !secondElement || firstElement === secondElement) {
                        return;
                    }

                    const parent = firstElement.parentNode;
                    if (!parent || parent !== secondElement.parentNode) {
                        return;
                    }

                    const firstPlaceholder = document.createElement('div');
                    const secondPlaceholder = document.createElement('div');

                    parent.replaceChild(firstPlaceholder, firstElement);
                    parent.replaceChild(secondPlaceholder, secondElement);
                    parent.replaceChild(firstElement, secondPlaceholder);
                    parent.replaceChild(secondElement, firstPlaceholder);
                };

                const $items = window.jQuery(container).find(itemSelector);
                disableNativeDragging();

                $items.draggable({
                    helper: 'clone',
                    revert: 'invalid',
                    distance: 6,
                    containment: 'document',
                    scroll: true,
                    cancel: cancelSelector,
                    handle: handleSelector || false,
                    start: function (_event, ui) {
                        suppressClickUntil = Date.now() + 500;
                        window.jQuery(this).addClass('dragging');
                        ui.helper.width(window.jQuery(this).outerWidth());
                    },
                    stop: function () {
                        window.jQuery(this).removeClass('dragging');
                        suppressClickUntil = Date.now() + 500;
                    },
                });

                $items.droppable({
                    accept: itemSelector,
                    tolerance: 'pointer',
                    hoverClass: 'dashboard-widget-swap-hover',
                    drop: function (_event, ui) {
                        const draggedItem = ui.draggable.get(0);
                        const targetItem = this;

                        if (!draggedItem || !targetItem || draggedItem === targetItem) {
                            return;
                        }

                        swapElements(draggedItem, targetItem);
                        queueSave();
                    },
                });

                return true;
            };

            setupSortableContainer(grid, {
                itemSelector: '.dashboard-card-item',
                handleSelector: '.dashboard-card-handle',
                keyAttribute: 'cardKey',
                section: 'cards',
                preferNative: true,
                dragAnywhere: true,
                cancelSelector: 'button, input, select, textarea, .dashboard-card-pointer-target, .dashboard-card-pointer-target *',
                placeholderClass: 'dashboard-sortable-placeholder dashboard-card-item',
            });

            setupSortableContainer(document.getElementById('dashboardWidgetGrid'), {
                itemSelector: '.dashboard-widget-item',
                handleSelector: '.dashboard-widget-handle',
                keyAttribute: 'widgetKey',
                section: 'widgets',
                preferNative: true,
                dragAnywhere: true,
                normalizeDomOrder: true,
                cancelSelector: 'a, button, input, select, textarea',
                placeholderClass: 'dashboard-sortable-placeholder dashboard-widget-item',
            });

            document.addEventListener('click', function (event) {
                const interactiveLink = event.target.closest('.dashboard-card-item a, .dashboard-widget-item a');
                if (interactiveLink && Date.now() < suppressClickUntil) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            }, true);

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

            updateNavbarCounts(@json($initialNavbarAlertCounts));
            refreshSummary();
        });
    </script>
@endsection

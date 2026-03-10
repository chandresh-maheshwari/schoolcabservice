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

            $cards = [
                [
                    'key' => 'vehicles',
                    'label' => 'Vehicles',
                    'route' => $isAdminUser ? 'vehicle.index' : 'school.vehicle.index',
                    'icon' => 'fa fa-bus',
                    'bg' => 'bg-primary',
                ],
                [
                    'key' => 'drivers',
                    'label' => 'Drivers',
                    'route' => $isAdminUser ? 'driver.index' : 'school.driver.index',
                    'icon' => 'fa fa-id-card',
                    'bg' => 'bg-success',
                ],
                [
                    'key' => 'routes',
                    'label' => 'Routes',
                    'route' => $isAdminUser ? 'routes.index' : 'school.routes.index',
                    'icon' => 'fa fa-map',
                    'bg' => 'bg-info',
                ],
                [
                    'key' => 'bookings',
                    'label' => 'Bookings',
                    'route' => $isAdminUser ? 'booking.index' : 'school.booking.index',
                    'icon' => 'fa fa-calendar-check-o',
                    'bg' => 'bg-warning',
                ],
                [
                    'key' => 'stop_pickups',
                    'label' => 'Stop / Pickup',
                    'route' => $isAdminUser ? 'stopPickup.index' : 'school.stopPickup.index',
                    'icon' => 'fa fa-map-marker',
                    'bg' => 'bg-danger',
                ],
                [
                    'key' => 'emergencies',
                    'label' => 'Emergencies',
                    'route' => $isAdminUser ? 'emergency.index' : 'school.emergency.index',
                    'icon' => 'fa fa-exclamation-triangle',
                    'bg' => 'bg-dark',
                ],
                [
                    'key' => 'ratings',
                    'label' => 'Feedback / Ratings',
                    'route' => $isAdminUser ? 'rating.index' : 'school.rating.index',
                    'icon' => 'fa fa-star',
                    'bg' => 'bg-secondary',
                ],
                [
                    'key' => 'parents',
                    'label' => 'Parents',
                    'route' => $isAdminUser ? 'parent.index' : 'school.parent.index',
                    'icon' => 'fa fa-home',
                    'bg' => 'bg-primary',
                ],
                [
                    'key' => 'children',
                    'label' => 'Children',
                    'route' => $isAdminUser ? 'child.index' : 'school.child.index',
                    'icon' => 'fa fa-child',
                    'bg' => 'bg-success',
                ],
            ];

            if ($isAdminUser) {
                array_unshift($cards, [
                    'key' => 'vehicle_types',
                    'label' => 'Vehicle Types',
                    'route' => 'vehicleType.index',
                    'icon' => 'fa fa-car',
                    'bg' => 'bg-info',
                ]);
            }

            $cards = array_values(array_filter($cards, function ($card) use ($authUser) {
                if (! $authUser) {
                    return false;
                }

                return $authUser->canAccessAdminRoute($card['route']);
            }));
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
        </div>

        @php
            $schoolSlug = $currentSchoolSlug ?? request()->route('schoolSlug');
        @endphp

        <div class="row">
            @foreach ($cards as $card)
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-4">
                    <a href="{{ str_starts_with($card['route'], 'school.') ? route($card['route'], ['schoolSlug' => $schoolSlug]) : route($card['route']) }}" class="text-decoration-none">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small mb-1">{{ $card['label'] }}</div>
                                    <div class="h3 mb-0">{{ (int) ($stats[$card['key']] ?? 0) }}</div>
                                </div>
                                <div class="{{ $card['bg'] }} rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 46px; height: 46px;">
                                    <i class="{{ $card['icon'] }} text-white"></i>
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
@endsection

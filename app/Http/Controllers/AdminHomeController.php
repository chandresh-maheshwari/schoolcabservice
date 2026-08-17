<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Role;
use App\Models\Booking;
use App\Models\Child;
use App\Models\Driver;
use App\Models\Emergency;
use App\Models\Parents;
use App\Models\Rating;
use App\Models\Route;
use App\Models\School;
use App\Models\StopPickup;
use App\Models\SupportRequest;
use App\Models\LeaveRequest;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class AdminHomeController extends Controller
{
    private function sortDashboardItemsByPreference(array $items, $authUser): array
    {
        $savedOrder = array_values(array_filter((array) ($authUser?->dashboard_card_order ?? []), 'is_string'));
        if ($savedOrder === []) {
            return $items;
        }

        $itemMap = [];
        foreach ($items as $item) {
            if (! isset($item['key']) || ! is_string($item['key'])) {
                continue;
            }

            $itemMap[$item['key']] = $item;
        }

        $sortedItems = [];
        foreach ($savedOrder as $key) {
            if (isset($itemMap[$key])) {
                $sortedItems[] = $itemMap[$key];
                unset($itemMap[$key]);
            }
        }

        foreach ($itemMap as $item) {
            $sortedItems[] = $item;
        }

        return $sortedItems;
    }

    private function dashboardCards(bool $isAdminUser, $authUser): array
    {
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
                'key' => 'support_requests',
                'label' => 'Support Requests',
                'route' => $isAdminUser ? 'supportRequests.index' : 'school.supportRequests.index',
                'icon' => 'fa fa-life-ring',
                'bg' => 'bg-warning',
            ],
            [
                'key' => 'leave_requests',
                'label' => 'Leave Requests',
                'route' => $isAdminUser ? 'leaveRequests.index' : 'school.leaveRequests.index',
                'icon' => 'fa fa-calendar-times-o',
                'bg' => 'bg-info',
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
                'key' => 'emergency_types',
                'label' => 'Emergency Types',
                'route' => 'emergencyType.index',
                'icon' => 'fa fa-exclamation',
                'bg' => 'bg-dark',
            ], [
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

        return $this->sortDashboardItemsByPreference($cards, $authUser);
    }

    private function dashboardWidgets($authUser): array
    {
        return $this->sortDashboardItemsByPreference([
            ['key' => 'recent_emergencies'],
            ['key' => 'recent_feedback'],
            ['key' => 'recent_support_requests'],
        ], $authUser);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $payload = $this->buildDashboardPayload(request());

        return view('admin_layout.admin_home', [
            'stats' => $payload['stats'],
            'school' => $payload['school'],
            'isAdminUser' => $payload['isAdminUser'],
            'cards' => $payload['cards'],
            'recentBookings' => $payload['recentBookings'],
            'bookingSchoolNameMap' => $payload['bookingSchoolNameMap'],
            'bookingRouteNameMap' => $payload['bookingRouteNameMap'],
            'recentEmergencies' => $payload['recentEmergencies'],
            'recentRatings' => $payload['recentRatings'],
            'recentSupportRequests' => $payload['recentSupportRequests'],
            'recentLeaveRequests' => $payload['recentLeaveRequests'],
            'actionStats' => $payload['actionStats'],
            'liveSummaryUrl' => $payload['liveSummaryUrl'],
            'navbarAlertCounts' => $payload['navbarAlertCounts'],
        ]);
    }

    public function liveSummary(Request $request)
    {
        $payload = $this->buildDashboardPayload($request);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $payload['stats'],
                'actionStats' => $payload['actionStats'],
                'recentBookings' => $payload['recentBookings']->map(function ($booking) use ($payload) {
                    return [
                        'id' => (int) $booking->id,
                        'school' => $payload['bookingSchoolNameMap'][$booking->school_id] ?? '-',
                        'route' => $payload['bookingRouteNameMap'][$booking->route_id] ?? ($booking->route_id ?? '-'),
                        'payment' => (string) ($booking->payment_status ?? '-'),
                        'createdAt' => optional($booking->created_at)->format('d M Y') ?? '-',
                    ];
                })->values(),
                'recentEmergencies' => $payload['recentEmergencies']->map(function ($incident) {
                    $isActive = (int) ($incident->status ?? 0) === 1;
                    return [
                        'type' => (string) ($incident->emergency_type ?? '-'),
                        'reportedBy' => (string) ($incident->reported_by ?? '-'),
                        'driver' => (string) (optional($incident->driver)->driver_name ?? '-'),
                        'vehicle' => (string) (optional($incident->vehicle)->vehicle_number ?? '-'),
                        'createdAt' => optional($incident->created_at)->format('d M Y') ?? '-',
                        'isActive' => $isActive,
                        'statusLabel' => $isActive ? 'Open' : 'Resolved / Closed',
                    ];
                })->values(),
                'recentRatings' => $payload['recentRatings']->map(function ($rating) {
                    return [
                        'rating' => (int) ($rating->rating ?? 0),
                        'driver' => (string) (optional($rating->driver)->driver_name ?? '-'),
                        'vehicle' => (string) (optional($rating->vehicle)->vehicle_number ?? '-'),
                        'comment' => (string) \Illuminate\Support\Str::limit((string) ($rating->comments ?? '-'), 60),
                        'isNew' => optional($rating->created_at)?->gte(now()->subDays(2)) ?? false,
                    ];
                })->values(),
                'recentSupportRequests' => $payload['recentSupportRequests']->map(function ($supportRequest) {
                    $status = strtolower((string) ($supportRequest->status ?? ''));
                    $needsReview = in_array($status, ['open', 'in_progress'], true) && empty($supportRequest->reviewed_at);
                    return [
                        'subject' => (string) \Illuminate\Support\Str::limit((string) ($supportRequest->subject ?? '-'), 40),
                        'category' => (string) ($supportRequest->category ?? '-'),
                        'status' => (string) ($supportRequest->status ?? '-'),
                        'requester' => (string) ($supportRequest->email ?? optional($supportRequest->user)->email ?? '-'),
                        'needsReview' => $needsReview,
                    ];
                })->values(),
                'recentLeaveRequests' => $payload['recentLeaveRequests']->map(function ($leaveRequest) {
                    $status = strtolower((string) ($leaveRequest->status ?? ''));
                    $needsReview = $status === 'requested' && empty($leaveRequest->reviewed_at);
                    return [
                        'child' => (string) ($leaveRequest->child_name ?? optional($leaveRequest->child)->child_name ?? '-'),
                        'dates' => (optional($leaveRequest->from_date)->format('d M Y') ?? ($leaveRequest->from_date ?? '-'))
                            . ' - ' .
                            (optional($leaveRequest->to_date)->format('d M Y') ?? ($leaveRequest->to_date ?? '-')),
                        'status' => (string) ($leaveRequest->status ?? '-'),
                        'reason' => (string) \Illuminate\Support\Str::limit((string) ($leaveRequest->reason ?? '-'), 70),
                        'requester' => (string) ($leaveRequest->email ?? optional($leaveRequest->user)->email ?? '-'),
                        'needsReview' => $needsReview,
                    ];
                })->values(),
                'navbarAlertCounts' => $payload['navbarAlertCounts'],
            ],
        ]);
    }

    private function buildDashboardPayload(Request $request): array
    {
        $user = Auth::user();
        $userId = $user?->id;
        $isAdminUser = (bool) ($user && $user->isAdmin());

        $school = null;
        $schoolId = null;

        if (! $isAdminUser && $userId) {
            $school = School::query()
                ->where('user_id', $userId)
                ->where(function ($q) {
                    $q->where('deleted', 0)->orWhereNull('deleted');
                })
                ->first();
            $schoolId = $school?->id;
        }

        $scopeByUserId = function ($query) use ($isAdminUser, $userId) {
            if ($isAdminUser || ! $userId) {
                return $query;
            }

            return $query->where('user_id', $userId);
        };

        $scopeByUserOrSchool = function ($query, ?string $schoolColumn = null) use ($isAdminUser, $userId, $schoolId) {
            if ($isAdminUser) {
                return $query;
            }

            if (! $userId) {
                return $query->whereRaw('1 = 0');
            }

            if ($schoolColumn && $schoolId) {
                return $query->where(function ($scopedQuery) use ($userId, $schoolId, $schoolColumn) {
                    $scopedQuery->where('user_id', $userId)
                        ->orWhere($schoolColumn, $schoolId);
                });
            }

            return $query->where('user_id', $userId);
        };

        $countNotDeleted = function ($query) {
            return $query->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })->count();
        };

        $schoolScopedChildrenCount = Child::query()
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })
            ->when($isAdminUser, fn ($q) => $q)
            ->when(! $isAdminUser && $schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $isAdminUser && ! $schoolId, fn ($q) => $q->whereRaw('1 = 0'))
            ->count();

        $schoolScopedParentsCount = Parents::query()
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })
            ->when($isAdminUser, fn ($q) => $q)
            ->when(! $isAdminUser && $schoolId, function ($q) use ($schoolId) {
                $q->whereExists(function ($childQuery) use ($schoolId) {
                    $childQuery->select(DB::raw(1))
                        ->from('children')
                        ->whereColumn('children.parent_id', 'parents.id')
                        ->where('children.school_id', $schoolId)
                        ->where(function ($deletedQuery) {
                            $deletedQuery->where('children.deleted', 0)->orWhereNull('children.deleted');
                        });
                });
            })
            ->when(! $isAdminUser && ! $schoolId, fn ($q) => $q->whereRaw('1 = 0'))
            ->count();

        $stats = [
            'vehicle_types' => $countNotDeleted($scopeByUserOrSchool(
                VehicleType::query(),
                Schema::hasColumn('vehicle_types', 'school_id') ? 'school_id' : null
            )),
            'vehicles' => $countNotDeleted($scopeByUserOrSchool(
                Vehicle::query(),
                Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null
            )),
            'drivers' => $countNotDeleted($scopeByUserOrSchool(
                Driver::query(),
                Schema::hasColumn('drivers', 'school_id') ? 'school_id' : null
            )),
            'routes' => $countNotDeleted($scopeByUserOrSchool(
                Route::query(),
                Schema::hasColumn('routes', 'school_id') ? 'school_id' : null
            )),
            'bookings' => Booking::query()
                ->where(function ($q) {
                    $q->where('deleted', 0)->orWhereNull('deleted');
                })
                ->when($isAdminUser, fn ($q) => $q)
                ->when(! $isAdminUser && $schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when(! $isAdminUser && ! $schoolId, fn ($q) => $q->whereRaw('1 = 0'))
                ->count(),
            'emergencies' => (clone $this->scopeEmergencyRecords(
                Emergency::query(),
                $isAdminUser,
                $userId,
                $schoolId
            ))->count(),
            'ratings' => (clone $this->scopeRatingRecords(
                Rating::query(),
                $isAdminUser,
                $userId,
                $schoolId
            ))->count(),
            'support_requests' => $this->scopeSupportRequests(SupportRequest::query(), $isAdminUser, $schoolId)->count(),
            'leave_requests' => $this->scopeLeaveRequests(LeaveRequest::query(), $isAdminUser, $schoolId)->count(),
            'stop_pickups' => $countNotDeleted(
                $this->scopeStopPickupRecords(
                    StopPickup::query(),
                    $isAdminUser,
                    $userId,
                    $schoolId
                )
            ),
            'parents' => $schoolScopedParentsCount,
            'children' => $schoolScopedChildrenCount,
        ];

        $recentBookingsQuery = Booking::query()
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            });

        if (! $isAdminUser) {
            if ($schoolId) {
                $recentBookingsQuery->where('school_id', $schoolId);
            } else {
                $recentBookingsQuery->whereRaw('1 = 0');
            }
        }

        $recentBookings = $recentBookingsQuery
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $bookingSchoolNameMap = DB::table('schools')
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })
            ->whereIn('id', $recentBookings->pluck('school_id')->filter()->all())
            ->pluck('school_name', 'id')
            ->toArray();

        $bookingRouteNameMap = DB::table('routes')
            ->whereIn('id', $recentBookings->pluck('route_id')->filter()->all())
            ->pluck('name', 'id')
            ->toArray();

        $recentEmergencies = $this->scopeEmergencyRecords(
            Emergency::query()->with(['driver', 'vehicle']),
            $isAdminUser,
            $userId,
            $schoolId
        )
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentRatings = $this->scopeRatingRecords(
            Rating::query()->with(['driver', 'vehicle']),
            $isAdminUser,
            $userId,
            $schoolId
        )
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentSupportRequests = $this->scopeSupportRequests(
            SupportRequest::query()->with(['user', 'parent.children.school']),
            $isAdminUser,
            $schoolId
        )->latest('id')->limit(5)->get();

        $recentLeaveRequests = $this->scopeLeaveRequests(
            LeaveRequest::query()->with(['user', 'child.parent', 'child.school', 'parent.children.school']),
            $isAdminUser,
            $schoolId
        )->orderByDesc('id')->limit(5)->get();

        $actionStats = [
            'active_emergencies' => (clone $this->scopeEmergencyRecords(
                Emergency::query(),
                $isAdminUser,
                $userId,
                $schoolId
            ))
                ->where('status', 1)
                ->count(),
            'open_support_requests' => (clone $this->scopeSupportRequests(SupportRequest::query(), $isAdminUser, $schoolId))
                ->whereIn('status', ['open', 'in_progress'])
                ->count(),
            'pending_leave_requests' => (clone $this->scopeLeaveRequests(LeaveRequest::query(), $isAdminUser, $schoolId))
                ->where('status', 'requested')
                ->count(),
            'recent_feedback' => (clone $this->scopeRatingRecords(
                Rating::query(),
                $isAdminUser,
                $userId,
                $schoolId
            ))
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ];

        $cards = $this->dashboardCards($isAdminUser, $user);
        $dashboardWidgetOrder = collect($this->dashboardWidgets($user))
            ->pluck('key')
            ->values()
            ->all();
        $schoolSlug = $request->route('schoolSlug');
        $liveSummaryUrl = $isAdminUser
            ? route('admin.dashboard.live-summary')
            : route('school.dashboard.live-summary', ['schoolSlug' => $schoolSlug]);

        $navbarAlertCounts = [
            'sos' => $actionStats['active_emergencies'],
            'support' => $actionStats['open_support_requests'],
            'leave' => $actionStats['pending_leave_requests'],
        ];
        $navbarAlertCounts['total'] = array_sum($navbarAlertCounts);

        return compact(
            'stats',
            'school',
            'isAdminUser',
            'cards',
            'recentBookings',
            'bookingSchoolNameMap',
            'bookingRouteNameMap',
            'recentEmergencies',
            'recentRatings',
            'recentSupportRequests',
            'recentLeaveRequests',
            'actionStats',
            'dashboardWidgetOrder',
            'liveSummaryUrl',
            'navbarAlertCounts',
        );
    }

    private function scopeEmergencyRecords($query, bool $isAdminUser, ?int $userId, ?int $schoolId = null)
    {
        $query->where(function ($q) {
            $q->where('deleted', 0)->orWhereNull('deleted');
        });

        if (! $isAdminUser && $userId) {
            $query->where(function ($visibilityQuery) use ($userId, $schoolId) {
                $visibilityQuery->where('user_id', $userId);

                if ($schoolId && Schema::hasColumn('drivers', 'school_id')) {
                    $visibilityQuery->orWhereExists(function ($driverQuery) use ($schoolId) {
                        $driverQuery->select(DB::raw(1))
                            ->from('drivers')
                            ->whereColumn('drivers.id', 'emergency_incidents.driver_id')
                            ->where('drivers.school_id', $schoolId)
                            ->where(function ($deletedQuery) {
                                $deletedQuery->where('drivers.deleted', 0)->orWhereNull('drivers.deleted');
                            });
                    });
                }

                if ($schoolId && Schema::hasColumn('vehicles', 'school_id')) {
                    $visibilityQuery->orWhereExists(function ($vehicleQuery) use ($schoolId) {
                        $vehicleQuery->select(DB::raw(1))
                            ->from('vehicles')
                            ->whereColumn('vehicles.id', 'emergency_incidents.vehicle_id')
                            ->where('vehicles.school_id', $schoolId)
                            ->where(function ($deletedQuery) {
                                $deletedQuery->where('vehicles.deleted', 0)->orWhereNull('vehicles.deleted');
                            });
                    });
                }
            });
        } elseif (! $isAdminUser) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    private function scopeRatingRecords($query, bool $isAdminUser, ?int $userId, ?int $schoolId = null)
    {
        $query->where(function ($q) {
            $q->where('deleted', 0)->orWhereNull('deleted');
        });

        if (! $isAdminUser && $userId) {
            $query->where(function ($visibilityQuery) use ($userId, $schoolId) {
                $visibilityQuery->where('user_id', $userId);

                if ($schoolId && Schema::hasColumn('drivers', 'school_id')) {
                    $visibilityQuery->orWhereExists(function ($driverQuery) use ($schoolId) {
                        $driverQuery->select(DB::raw(1))
                            ->from('drivers')
                            ->whereColumn('drivers.id', 'ratings.driver_id')
                            ->where('drivers.school_id', $schoolId)
                            ->where(function ($deletedQuery) {
                                $deletedQuery->where('drivers.deleted', 0)->orWhereNull('drivers.deleted');
                            });
                    });
                }

                if ($schoolId && Schema::hasColumn('vehicles', 'school_id')) {
                    $visibilityQuery->orWhereExists(function ($vehicleQuery) use ($schoolId) {
                        $vehicleQuery->select(DB::raw(1))
                            ->from('vehicles')
                            ->whereColumn('vehicles.id', 'ratings.vehicle_id')
                            ->where('vehicles.school_id', $schoolId)
                            ->where(function ($deletedQuery) {
                                $deletedQuery->where('vehicles.deleted', 0)->orWhereNull('vehicles.deleted');
                            });
                    });
                }
            });
        } elseif (! $isAdminUser) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    private function scopeSupportRequests($query, bool $isAdminUser, ?int $schoolId)
    {
        if ($isAdminUser || ! $schoolId) {
            return $query;
        }

        $supportRequestsHasParentId = Schema::hasColumn('support_requests', 'parent_id');
        $parentsHasUserId = Schema::hasColumn('parents', 'user_id');

        return $query->whereExists(function ($parentQuery) use ($schoolId, $supportRequestsHasParentId, $parentsHasUserId) {
            $parentQuery->select(DB::raw(1))
                ->from('parents as p')
                ->join('children as c', 'c.parent_id', '=', 'p.id')
                ->where(function ($visibilityQuery) use ($supportRequestsHasParentId, $parentsHasUserId) {
                    if ($supportRequestsHasParentId) {
                        $visibilityQuery->whereColumn('p.id', 'support_requests.parent_id')
                            ->orWhereColumn('p.login_user_id', 'support_requests.user_id');
                    } else {
                        $visibilityQuery->whereColumn('p.login_user_id', 'support_requests.user_id');
                    }

                    if ($parentsHasUserId) {
                        $visibilityQuery->orWhereColumn('p.user_id', 'support_requests.user_id');
                    }
                })
                ->where('c.school_id', $schoolId)
                ->where(function ($deletedQuery) {
                    $deletedQuery->where('p.deleted', 0)->orWhereNull('p.deleted');
                })
                ->where(function ($deletedQuery) {
                    $deletedQuery->where('c.deleted', 0)->orWhereNull('c.deleted');
                });
        });
    }

    private function scopeLeaveRequests($query, bool $isAdminUser, ?int $schoolId)
    {
        if ($isAdminUser || ! $schoolId) {
            return $query;
        }

        $leaveRequestsHasParentId = Schema::hasColumn('leave_requests', 'parent_id');

        return $query->where(function ($leaveQuery) use ($schoolId, $leaveRequestsHasParentId) {
            $leaveQuery->whereHas('child', function ($childQuery) use ($schoolId) {
                $childQuery->where('school_id', $schoolId);
            });

            if ($leaveRequestsHasParentId) {
                $leaveQuery->orWhereHas('parent.children', function ($childQuery) use ($schoolId) {
                    $childQuery->where('school_id', $schoolId)
                        ->where(function ($deletedQuery) {
                            $deletedQuery->where('deleted', 0)->orWhereNull('deleted');
                        });
                });
            }
        });
    }

    private function scopeStopPickupRecords($query, bool $isAdminUser, ?int $userId, ?int $schoolId)
    {
        if ($isAdminUser) {
            return $query;
        }

        if (! $userId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($stopPickupQuery) use ($userId, $schoolId) {
            $stopPickupQuery->where('user_id', $userId);

            if ($schoolId && Schema::hasColumn('stops_pickup', 'school_id')) {
                $stopPickupQuery->orWhere('school_id', $schoolId);
            }

            $stopPickupQuery->orWhereExists(function ($routeQuery) use ($userId, $schoolId) {
                $routeQuery->select(DB::raw(1))
                    ->from('routes')
                    ->whereColumn('routes.id', 'stops_pickup.route_id')
                    ->where(function ($visibleRouteQuery) use ($userId, $schoolId) {
                        $visibleRouteQuery->where('routes.user_id', $userId);

                        if ($schoolId && Schema::hasColumn('routes', 'school_id')) {
                            $visibleRouteQuery->orWhere('routes.school_id', $schoolId);
                        }
                    })
                    ->where(function ($deletedQuery) {
                        $deletedQuery->where('routes.deleted', 0)->orWhereNull('routes.deleted');
                    });
            });
        });
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $user = User::findOrFail($id);
        $roles = Role::query()->notDeleted()->get();
        return view('admin_profile.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $user = User::findOrFail($id);

        $validator = \Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'mobile' => 'sometimes|required|digits:10',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role_id' => 'sometimes|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }

            // Store new photo
            $photoName = time() . '_' . $request->file('photo')->getClientOriginalName();
            $photoPath = $request->file('photo')->storeAs('profile_pictures', $photoName, 'public');
            $user->photo = $photoPath;
        }

        $user->first_name = $request->input('first_name', $user->first_name);
        $user->last_name = $request->input('last_name', $user->last_name);
        $user->mobile = $request->input('mobile', $user->mobile);
        $user->email = $request->input('email', $user->email);
        $user->role_id = $request->input('role_id', $user->role_id);

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json(['success' => true, 'message' => 'User updated Successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Display the user profile page.
     *
     * @return \Illuminate\Http\Response
     */
    public function profile(Request $request, ?string $schoolSlug = null)
    {
        return view('admin_profile.index');
    }

    /**
     * Update the user's profile photo.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePhoto(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = \Validator::make($request->all(), [
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                    Storage::disk('public')->delete($user->photo);
                }

                // Store new photo
                $photoName = time() . '_' . $request->file('photo')->getClientOriginalName();
                $photoPath = $request->file('photo')->storeAs('profile_pictures', $photoName, 'public');
                
                $user->photo = $photoPath;
                $user->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Profile photo updated Successfully.'
                ]);
            }

            // If no new photo is uploaded, return success with existing photo
            return response()->json([
                'success' => true,
                'message' => 'Profile photo remains unchanged.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile photo. Please try again.'
            ], 500);
        }
    }

    public function updateDashboardCardOrder(Request $request)
    {
        $user = Auth::user();
        abort_if(! $user, 401);

        $section = (string) $request->input('section', 'cards');
        $allowedKeys = match ($section) {
            'widgets' => collect($this->dashboardWidgets($user))->pluck('key')->values()->all(),
            default => collect($this->dashboardCards($user->isAdmin(), $user))->pluck('key')->values()->all(),
        };

        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'string', 'distinct'],
        ]);

        $requestedOrder = array_values(array_filter(
            $validated['order'],
            fn ($key) => in_array($key, $allowedKeys, true)
        ));

        if ($requestedOrder === []) {
            return response()->json([
                'success' => false,
                'message' => 'No valid dashboard cards were provided.',
            ], 422);
        }

        $existingOrder = array_values(array_filter((array) ($user->dashboard_card_order ?? []), 'is_string'));
        $preservedKeys = array_values(array_filter(
            $existingOrder,
            fn ($key) => ! in_array($key, $allowedKeys, true)
        ));
        $remainingKeys = array_values(array_diff($allowedKeys, $requestedOrder));
        $user->dashboard_card_order = array_values(array_merge($preservedKeys, $requestedOrder, $remainingKeys));
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard order updated successfully.',
        ]);
    }
}

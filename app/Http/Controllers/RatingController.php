<?php
namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Driver;
use App\Models\Parents;
use App\Models\Rating;
use App\Models\School;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RatingController extends Controller
{
    public function __construct(private readonly PushNotificationService $pushNotifications)
    {
    }
    /**
     * Display rating & feedback listing page.
     * created by ns
     */
    public function index()
    {
        return view('rating_feedback.index');
    }

    /**
     * Display rating & feedback create form.
     * created by ns
     */
    public function create()
    {
        $drivers = $this->feedbackDriverOptionsQuery()
            ->get();

        $vehicles = $this->feedbackVehicleOptionsQuery()
            ->get();

        return view('rating_feedback.create', compact('drivers', 'vehicles'));
    }

    /**
     * Store rating & feedback data.
     * created by ns
     */
    public function store(Request $request)
    {
        $request->validate([
            'driver_id' => 'nullable|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'rating'   => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
        ]);

        $driverId = $this->extractDriverId($request);
        $vehicleId = $this->extractVehicleId($request);
        $this->ensureAccessibleFeedbackEntities($request, $driverId, $vehicleId);

        Rating::create([
            'user_id'    => $this->resolveRatingOwnerUserId($request, $driverId, $vehicleId),
            'driver_id'  => $driverId,
            'vehicle_id' => $vehicleId,
            'rating'     => $request->rating,
            'comments'   => $request->comments,
            'deleted'    => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rating submitted successfully',
        ]);
    }

    public function storeParentFeedback(Request $request)
    {
        $validated = $request->validate([
            'child_id' => 'nullable|exists:children,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'rating' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
        ]);

        $parent = Parents::query()
            ->where(function ($query) {
                $query->where('login_user_id', (int) Auth::id());
                if (\Illuminate\Support\Facades\Schema::hasColumn('parents', 'user_id')) {
                    $query->orWhere('user_id', (int) Auth::id());
                }
            })
            ->with(['children.route.driver', 'children.route.vehicle', 'children.school'])
            ->firstOrFail();

        $child = $this->resolveParentFeedbackChild($parent, $validated['child_id'] ?? null);
        if (! $child && empty($validated['driver_id']) && empty($validated['vehicle_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'No linked child, driver, or vehicle was found for this parent.',
            ], 422);
        }

        $driverId = isset($validated['driver_id']) ? (int) $validated['driver_id'] : (int) optional($child?->route)->driver_id;
        $vehicleId = isset($validated['vehicle_id']) ? (int) $validated['vehicle_id'] : (int) optional($child?->route)->bus_id;
        $ownerUserId = (int) optional($child?->school)->user_id;
        if ($ownerUserId <= 0) {
            $ownerUserId = (int) ($this->resolveRatingOwnerUserId($request, $driverId, $vehicleId) ?? 0);
        }
        if ($ownerUserId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to map feedback to a school or admin account.',
            ], 422);
        }

        $rating = Rating::create([
            'user_id' => $ownerUserId > 0 ? $ownerUserId : null,
            'driver_id' => $driverId > 0 ? $driverId : null,
            'vehicle_id' => $vehicleId > 0 ? $vehicleId : null,
            'rating' => $validated['rating'],
            'comments' => $validated['comments'] ?? null,
            'deleted' => 0,
        ]);

        $this->notifyFeedbackRecipients(
            $rating,
            $validated['child_id'] ?? null,
            $ownerUserId,
            trim((string) ($validated['comments'] ?? '')),
            (int) $validated['rating']
        );

        return response()->json([
            'success' => true,
            'message' => 'Feedback submitted successfully.',
            'data' => $rating,
        ], 201);
    }

    public function storeParentFeedbackFromEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'childId' => 'nullable',
            'rating' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
        ]);

        $userId = (int) User::query()
            ->where('email', trim((string) $validated['email']))
            ->where(function ($query) {
                $query->where('deleted', 0)->orWhereNull('deleted');
            })
            ->value('id');

        if ($userId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Parent user not found.',
            ], 404);
        }

        $parent = Parents::query()
            ->where(function ($query) use ($userId) {
                $query->where('login_user_id', $userId);
                if (\Illuminate\Support\Facades\Schema::hasColumn('parents', 'user_id')) {
                    $query->orWhere('user_id', $userId);
                }
            })
            ->with(['children.route.driver', 'children.route.vehicle', 'children.school'])
            ->first();

        if (! $parent) {
            return response()->json([
                'success' => false,
                'message' => 'Parent profile not found.',
            ], 404);
        }

        $child = $this->resolveParentFeedbackChild($parent, $validated['childId'] ?? null);
        if (! $child) {
            return response()->json([
                'success' => false,
                'message' => 'No child found for feedback submission.',
            ], 422);
        }

        $driverId = (int) optional($child->route)->driver_id;
        $vehicleId = (int) optional($child->route)->bus_id;
        $ownerUserId = $this->resolveParentFeedbackOwnerUserId($child, $driverId, $vehicleId, $request);
        if ($ownerUserId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to map feedback to a school or admin account.',
            ], 422);
        }

        $rating = Rating::create([
            'user_id' => $ownerUserId > 0 ? $ownerUserId : null,
            'driver_id' => $driverId > 0 ? $driverId : null,
            'vehicle_id' => $vehicleId > 0 ? $vehicleId : null,
            'rating' => (int) $validated['rating'],
            'comments' => trim((string) ($validated['comments'] ?? '')),
            'deleted' => 0,
        ]);

        $this->notifyFeedbackRecipients(
            $rating,
            $validated['childId'] ?? null,
            $ownerUserId,
            trim((string) $validated['comments']),
            (int) $validated['rating']
        );

        return response()->json([
            'success' => true,
            'message' => 'Feedback submitted successfully.',
            'data' => [
                'id' => (int) $rating->id,
                'rating' => (int) $rating->rating,
                'comments' => (string) ($rating->comments ?? ''),
            ],
        ], 201);
    }

    /**
     * Display rating & feedback edit form.
     * created by ns
     */
    public function edit($maybeSlugOrId, $maybeId = null)
    {
        $id = $this->normalizeRouteId($maybeSlugOrId, $maybeId);

        $query = Rating::query();
        $this->applyRatingVisibilityScope($query, request(), 'user_id');
        $rating = $query->findOrFail($id);

        $drivers = $this->feedbackDriverOptionsQuery()
            ->get();

        $vehicles = $this->feedbackVehicleOptionsQuery()
            ->get();

        return view('rating_feedback.edit', compact('rating', 'drivers', 'vehicles'));
    }

    /**
     * Update rating & feedback data.
     * created by ns
     */
    public function update(Request $request, $maybeSlugOrId, $maybeId = null)
    {
        $id = $this->normalizeRouteId($maybeSlugOrId, $maybeId);
        $request->validate([
            'driver_id' => 'nullable|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'rating'   => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
        ]);

        $query = Rating::query();
        $this->applyRatingVisibilityScope($query, $request, 'user_id');
        $rating = $query->findOrFail($id);

        $driverId = $this->extractDriverId($request);
        $vehicleId = $this->extractVehicleId($request);
        $this->ensureAccessibleFeedbackEntities($request, $driverId, $vehicleId);

        $rating->update([
            'driver_id'  => $driverId,
            'vehicle_id' => $vehicleId,
            'rating'     => $request->rating,
            'comments'   => $request->comments,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rating updated successfully',
        ]);
    }

    /**
     * Soft delete rating & feedback record.
     * created by ns
     */
    public function destroy($maybeSlugOrId, $maybeId = null)
    {
        $id = $this->normalizeRouteId($maybeSlugOrId, $maybeId);

        $query = Rating::query();
        $this->applyRatingVisibilityScope($query, request(), 'user_id');
        $rating = $query->findOrFail($id);

        $rating->deleted = 1;
        $rating->save();

        return response()->json([
            'success' => true,
            'message' => 'Rating deleted successfully',
        ]);
    }

    /**
     * Fetch rating & feedback list for DataTable.
     * created by ns
     */
    public function ratingList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = (int) $request->input('iSortCol_0', 0);
        $columnKey   = $request->input('mDataProp_' . $indexColumn, 'id');

        // DataTables sends column keys like "driver_name" even though the DB stores driver_id/vehicle_id.
        // Map the keys to actual sortable columns and join only when needed.
        $sortableKeys = [
            'id',
            'school_name',
            'driver_name',
            'vehicle_number',
            'rating',
            'comments',
        ];

        $columnKey = in_array($columnKey, $sortableKeys, true) ? $columnKey : 'id';

        $columnSortOrder = in_array(
            $request->input('sSortDir_0'),
            ['asc', 'desc']
        ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        $query = Rating::query()
            ->with(['driver', 'vehicle'])
            ->where('ratings.deleted', 0);

        if ($columnKey === 'driver_name') {
            $query->leftJoin('drivers', 'ratings.driver_id', '=', 'drivers.id');
        } elseif ($columnKey === 'vehicle_number') {
            $query->leftJoin('vehicles', 'ratings.vehicle_id', '=', 'vehicles.id');
        } elseif ($columnKey === 'school_name') {
            $query->leftJoin('schools', function ($join) {
                $join->on('ratings.user_id', '=', 'schools.user_id')
                    ->where('schools.deleted', 0);
            });
        }

        $query->select('ratings.*');
        $this->applyRatingVisibilityScope($query, $request, 'ratings.user_id');
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $matchingSchoolReferences = $this->resolveSchoolSearchIds($searchValue);
            $matchingSchoolIds = $matchingSchoolReferences['school_ids'];
            $matchingSchoolUserIds = $matchingSchoolReferences['user_ids'];

            $query->where(function ($q) use ($searchValue, $matchingSchoolIds, $matchingSchoolUserIds) {
                $q->where('rating', 'like', "%$searchValue%")
                    ->orWhere('comments', 'like', "%$searchValue%");

                // Keep relation-search grouped to avoid bypassing actor scope via top-level ORs.
                $q->orWhereHas('driver', function ($driverQuery) use ($searchValue) {
                    $driverQuery->where('driver_name', 'like', "%$searchValue%");
                })->orWhereHas('vehicle', function ($vehicleQuery) use ($searchValue) {
                    $vehicleQuery->where('vehicle_number', 'like', "%$searchValue%");
                });

                if (! empty($matchingSchoolUserIds)) {
                    $q->orWhereIn('ratings.user_id', $matchingSchoolUserIds);
                }

                if (! empty($matchingSchoolUserIds) || ! empty($matchingSchoolIds)) {
                    $q->orWhereHas('driver', function ($driverQuery) use ($matchingSchoolIds, $matchingSchoolUserIds) {
                        $driverQuery->where(function ($schoolScopedDriverQuery) use ($matchingSchoolIds, $matchingSchoolUserIds) {
                            if (! empty($matchingSchoolUserIds)) {
                                $schoolScopedDriverQuery->whereIn('drivers.user_id', $matchingSchoolUserIds);
                            }

                            if (! empty($matchingSchoolIds) && \Illuminate\Support\Facades\Schema::hasColumn('drivers', 'school_id')) {
                                $method = ! empty($matchingSchoolUserIds) ? 'orWhereIn' : 'whereIn';
                                $schoolScopedDriverQuery->{$method}('drivers.school_id', $matchingSchoolIds);
                            }
                        });
                    });

                    $q->orWhereHas('vehicle', function ($vehicleQuery) use ($matchingSchoolIds, $matchingSchoolUserIds) {
                        $vehicleQuery->where(function ($schoolScopedVehicleQuery) use ($matchingSchoolIds, $matchingSchoolUserIds) {
                            if (! empty($matchingSchoolUserIds)) {
                                $schoolScopedVehicleQuery->whereIn('vehicles.user_id', $matchingSchoolUserIds);
                            }

                            if (! empty($matchingSchoolIds) && \Illuminate\Support\Facades\Schema::hasColumn('vehicles', 'school_id')) {
                                $method = ! empty($matchingSchoolUserIds) ? 'orWhereIn' : 'whereIn';
                                $schoolScopedVehicleQuery->{$method}('vehicles.school_id', $matchingSchoolIds);
                            }
                        });
                    });

                    $q->orWhereExists(function ($routeQuery) use ($matchingSchoolIds, $matchingSchoolUserIds) {
                        $routeQuery->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('routes')
                            ->where(function ($ratingLinkQuery) {
                                $ratingLinkQuery->whereColumn('routes.driver_id', 'ratings.driver_id')
                                    ->orWhereColumn('routes.bus_id', 'ratings.vehicle_id');
                            })
                            ->where(function ($routeScopeQuery) use ($matchingSchoolIds, $matchingSchoolUserIds) {
                                if (! empty($matchingSchoolUserIds)) {
                                    $routeScopeQuery->whereIn('routes.user_id', $matchingSchoolUserIds);
                                }

                                if (! empty($matchingSchoolIds) && \Illuminate\Support\Facades\Schema::hasColumn('routes', 'school_id')) {
                                    $method = ! empty($matchingSchoolUserIds) ? 'orWhereIn' : 'whereIn';
                                    $routeScopeQuery->{$method}('routes.school_id', $matchingSchoolIds);
                                }
                            })
                            ->where(function ($deletedQuery) {
                                $deletedQuery->where('routes.deleted', 0)->orWhereNull('routes.deleted');
                            });
                    });
                }
            });
        }

        $totalRecordwithFilter = (clone $query)->count();

        $sortColumnMap = [
            'id' => 'ratings.id',
            'rating' => 'ratings.rating',
            'comments' => 'ratings.comments',
            'driver_name' => 'drivers.driver_name',
            'vehicle_number' => 'vehicles.vehicle_number',
            'school_name' => 'schools.school_name',
        ];

        $sortColumn = $sortColumnMap[$columnKey] ?? 'ratings.id';

        $ratingDetails = $query
            ->orderBy($sortColumn, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        $data = [];
        $schoolNameMap = $this->getSchoolNameMapForUserIds($ratingDetails->pluck('user_id')->all());
        $schoolNamesByDriverId = $this->getSchoolNameMapForDriverIds($ratingDetails->pluck('driver_id')->all());
        $schoolNamesByVehicleId = $this->getSchoolNameMapForVehicleIds($ratingDetails->pluck('vehicle_id')->all());

        foreach ($ratingDetails as $rating) {
            $data[] = [
                'id'             => $rating->id,
                'school_name'    => $schoolNameMap[$rating->user_id]
                    ?? $schoolNamesByDriverId[(int) ($rating->driver_id ?? 0)]
                    ?? $schoolNamesByVehicleId[(int) ($rating->vehicle_id ?? 0)]
                    ?? '-',
                'driver_name'    => optional($rating->driver)->driver_name,
                'vehicle_number' => optional($rating->vehicle)->vehicle_number,
                'rating'         => $rating->rating,
                'comments'       => $rating->comments,
            ];
        }

        return response()->json([
            'draw'                 => intval($draw),
            'sEcho'                => intval($draw),
            'recordsTotal'         => $totalRecords,
            'recordsFiltered'      => $totalRecordwithFilter,
            'iTotalRecords'        => $totalRecords,
            'iTotalDisplayRecords' => $totalRecordwithFilter,
            'data'                 => $data,
            'aaData'               => $data,
        ]);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided',
            ]);
        }

        $query = Rating::whereIn('id', $ids);
        $this->applyRatingVisibilityScope($query, $request, 'user_id');
        $query->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected routes deleted successfully',
        ]);
    }

    private function extractDriverId(Request $request): ?int
    {
        foreach (['driver_id', 'driver_name'] as $key) {
            $value = $request->input($key);
            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    private function extractVehicleId(Request $request): ?int
    {
        foreach (['vehicle_id', 'vehicle_number'] as $key) {
            $value = $request->input($key);
            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    private function resolveRatingOwnerUserId(Request $request, ?int $driverId, ?int $vehicleId): ?int
    {
        if ($this->isPrivilegedActor($request)) {
            return $this->resolveActorUserId($request);
        }

        if ($this->isSchoolActor($request)) {
            return $this->resolveActorUserId($request);
        }

        if ($vehicleId) {
            $vehicleUserId = (int) Vehicle::query()->whereKey($vehicleId)->value('user_id');
            if ($vehicleUserId > 0) {
                return $vehicleUserId;
            }
        }

        if ($driverId) {
            $driverUserId = (int) Driver::query()->whereKey($driverId)->value('user_id');
            if ($driverUserId > 0) {
                return $driverUserId;
            }
        }

        $childId = $request->input('child_id', $request->input('childId'));
        if (is_numeric($childId) && (int) $childId > 0) {
            $child = Child::query()->with('school')->find((int) $childId);
            $schoolUserId = (int) optional($child?->school)->user_id;
            if ($schoolUserId > 0) {
                return $schoolUserId;
            }

            $schoolId = (int) ($child?->school_id ?? 0);
            if ($schoolId > 0) {
                $fallbackSchoolUserId = (int) School::query()->whereKey($schoolId)->value('user_id');
                if ($fallbackSchoolUserId > 0) {
                    return $fallbackSchoolUserId;
                }
            }
        }

        return $this->resolveActorUserId($request);
    }

    private function feedbackDriverOptionsQuery(?Request $request = null)
    {
        $query = Driver::query()
            ->where('deleted', 0)
            ->select('id', 'driver_name', 'vehicle_id')
            ->orderBy('driver_name');

        $this->applySchoolAwareScope($query, $request, 'user_id', \Illuminate\Support\Facades\Schema::hasColumn('drivers', 'school_id') ? 'school_id' : null);

        return $query;
    }

    private function feedbackVehicleOptionsQuery(?Request $request = null)
    {
        $query = Vehicle::query()
            ->where('deleted', 0)
            ->select('id', 'vehicle_number', 'driver_id')
            ->orderBy('vehicle_number');

        $this->applySchoolAwareScope($query, $request, 'user_id', \Illuminate\Support\Facades\Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);

        return $query;
    }

    private function ensureAccessibleFeedbackEntities(Request $request, ?int $driverId, ?int $vehicleId): void
    {
        $driver = null;
        $vehicle = null;

        if ($driverId !== null) {
            $driverQuery = Driver::query()
                ->where('deleted', 0)
                ->whereKey($driverId);

            $this->applySchoolAwareScope($driverQuery, $request, 'user_id', \Illuminate\Support\Facades\Schema::hasColumn('drivers', 'school_id') ? 'school_id' : null);

            $driver = $driverQuery->first(['id', 'vehicle_id']);

            if (! $driver) {
                throw ValidationException::withMessages([
                    'driver_id' => 'Selected driver is not accessible for current user.',
                ]);
            }
        }

        if ($vehicleId !== null) {
            $vehicleQuery = Vehicle::query()
                ->where('deleted', 0)
                ->whereKey($vehicleId);

            $this->applySchoolAwareScope($vehicleQuery, $request, 'user_id', \Illuminate\Support\Facades\Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);

            $vehicle = $vehicleQuery->first(['id', 'driver_id']);

            if (! $vehicle) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Selected vehicle is not accessible for current user.',
                ]);
            }
        }

        if ($driver && $vehicle) {
            $driverVehicleId = (int) ($driver->vehicle_id ?? 0);
            $vehicleDriverId = (int) ($vehicle->driver_id ?? 0);

            $isMatchedPair = ($driverVehicleId > 0 && $driverVehicleId === (int) $vehicle->id)
                || ($vehicleDriverId > 0 && $vehicleDriverId === (int) $driver->id);

            if (! $isMatchedPair) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Selected vehicle is not assigned to the selected driver.',
                ]);
            }
        }
    }

    private function resolveParentFeedbackOwnerUserId(?Child $child, ?int $driverId, ?int $vehicleId, ?Request $request = null): int
    {
        $schoolUserId = (int) optional($child?->school)->user_id;
        if ($schoolUserId > 0) {
            return $schoolUserId;
        }

        $schoolId = (int) ($child?->school_id ?? optional($child?->route)->school_id ?? 0);
        if ($schoolId > 0) {
            $resolvedSchoolUserId = (int) School::query()->whereKey($schoolId)->value('user_id');
            if ($resolvedSchoolUserId > 0) {
                return $resolvedSchoolUserId;
            }
        }

        $resolvedOwnerUserId = (int) ($this->resolveRatingOwnerUserId(
            $request ?: request(),
            $driverId,
            $vehicleId
        ) ?? 0);

        return $resolvedOwnerUserId > 0 ? $resolvedOwnerUserId : 0;
    }

    private function resolveParentFeedbackChild(Parents $parent, $childId): ?Child
    {
        $children = $parent->children()
            ->with(['route.driver', 'route.vehicle', 'school'])
            ->get();
        if ($children->isEmpty()) {
            $children = Child::query()
                ->where(function ($query) use ($parent) {
                    $query->where('parent_id', (int) $parent->id);

                    $linkedUserId = (int) ($parent->login_user_id ?? $parent->user_id ?? 0);
                    if ($linkedUserId > 0 && \Illuminate\Support\Facades\Schema::hasColumn('children', 'user_id')) {
                        $query->orWhere('user_id', $linkedUserId);
                    }
                })
                ->where(function ($query) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                })
                ->with(['route.driver', 'route.vehicle', 'school'])
                ->get();
        }

        if ($children->isEmpty()) {
            return null;
        }

        if (is_numeric($childId) && (int) $childId > 0) {
            $matchedChild = $children->firstWhere('id', (int) $childId);
            if ($matchedChild) {
                return $matchedChild;
            }
        }

        return $children->first();
    }

    private function notifyFeedbackRecipients(Rating $rating, $childId, int $schoolUserId, string $comments, int $score): void
    {
        try {
            $recipientUserIds = User::query()
                ->where(function ($query) use ($schoolUserId) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                })
                ->get()
                ->filter(function (User $user) use ($schoolUserId) {
                    if ($schoolUserId > 0 && (int) $user->id === $schoolUserId) {
                        return true;
                    }

                    return $user->isAdmin();
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if (empty($recipientUserIds)) {
                return;
            }

            $childName = '-';
            if (is_numeric($childId) && (int) $childId > 0) {
                $childName = (string) (Child::query()->whereKey((int) $childId)->value('child_name') ?? '-');
            }

            $message = 'Parent feedback submitted';
            if ($childName !== '-') {
                $message .= ' for ' . $childName;
            }
            $message .= ' with rating ' . $score . '/5';
            if ($comments !== '') {
                $message .= ': ' . $comments;
            }

            $this->pushNotifications->sendToUsers(
                $recipientUserIds,
                'New parent feedback',
                $message,
                'feedback',
                [
                    'ratingId' => (int) $rating->id,
                    'childId' => is_numeric($childId) ? (int) $childId : null,
                    'schoolUserId' => $schoolUserId > 0 ? $schoolUserId : null,
                    'rating' => $score,
                ]
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function applyRatingVisibilityScope($query, Request $request, string $userColumn = 'user_id')
    {
        if (! $this->shouldRestrictToActorData($request)) {
            return $query;
        }

        $schoolId = $this->resolveSchoolIdFromContext($request);
        if ($schoolId) {
            return $query->where(function ($visibilityQuery) use ($request, $userColumn, $schoolId) {
                $this->applySchoolAwareScope($visibilityQuery, $request, $userColumn);

                if (\Illuminate\Support\Facades\Schema::hasColumn('drivers', 'school_id')) {
                    $visibilityQuery->orWhereExists(function ($driverQuery) use ($schoolId) {
                        $driverQuery->selectRaw('1')
                            ->from('drivers')
                            ->whereColumn('drivers.id', 'ratings.driver_id')
                            ->where('drivers.school_id', $schoolId)
                            ->where(function ($deletedQuery) {
                                $deletedQuery->where('drivers.deleted', 0)->orWhereNull('drivers.deleted');
                            });
                    });
                }

                if (\Illuminate\Support\Facades\Schema::hasColumn('vehicles', 'school_id')) {
                    $visibilityQuery->orWhereExists(function ($vehicleQuery) use ($schoolId) {
                        $vehicleQuery->selectRaw('1')
                            ->from('vehicles')
                            ->whereColumn('vehicles.id', 'ratings.vehicle_id')
                            ->where('vehicles.school_id', $schoolId)
                            ->where(function ($deletedQuery) {
                                $deletedQuery->where('vehicles.deleted', 0)->orWhereNull('vehicles.deleted');
                            });
                    });
                }
            });
        }

        return $this->applyActorScope($query, $request, $userColumn);
    }
}

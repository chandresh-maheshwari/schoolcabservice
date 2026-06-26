<?php
namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Route;
use App\Models\StopPickup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class StopPickupController extends Controller
{
    /**
     * Display stop & pickup listing page.
     * created by ns
     */
    public function index()
    {
        return view('stop_pickup.index');
    }

    /**
     * Display stop & pickup create form.
     * created by ns
     */
    public function create()
    {
        $routeData = Route::select('id', 'name', 'route_json')
            ->where('deleted', 0);
        $this->applySchoolAwareScope($routeData, request(), 'user_id', Schema::hasColumn('routes', 'school_id') ? 'school_id' : null);
        $routeData = $routeData
            ->orderBy('name')
            ->get();

        return view('stop_pickup.create', compact('routeData'));
    }

    public function routePoints(Request $request, $routeId)
    {
        $routeQuery = Route::where('id', (int) $routeId)
            ->where('deleted', 0);
        $this->applySchoolAwareScope($routeQuery, $request, 'user_id', Schema::hasColumn('routes', 'school_id') ? 'school_id' : null);
        $route = $routeQuery->first();

        if (! $route) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid route selected',
                'points' => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'points' => $this->extractRoutePoints($route),
        ]);
    }

    /**
     * Store stop & pickup data.
     * created by ns
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'route_id'       => 'required|exists:routes,id',
            'pickup_name'    => 'nullable|string|max:5000',
            'stop_name'      => 'required|string|max:255',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'sequence_order' => 'required|integer',
        ], [
            'latitude.between'  => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $routeQuery = Route::where('id', $request->route_id)
            ->where('deleted', 0);
        $this->applySchoolAwareScope($routeQuery, $request, 'user_id', Schema::hasColumn('routes', 'school_id') ? 'school_id' : null);
        $routeColumns = ['id', 'user_id'];
        if (Schema::hasColumn('routes', 'school_id')) {
            $routeColumns[] = 'school_id';
        }
        $routeData = $routeQuery->first($routeColumns);

        if (! $routeData) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid route selected',
            ], 422);
        }

        $payload = [
            'user_id'        => $this->resolveModuleOwnerUserId($request, (int) ($routeData->user_id ?? 0), [
                (int) ($routeData->user_id ?? 0),
            ]),
            'route_id'       => $routeData->id,
            'pickup_name'    => $this->normalizeStopPickupText($request->pickup_name),
            'stop_name'      => $this->normalizeStopPickupText($request->stop_name),
            'latitude'       => $request->latitude,
            'longitude'      => $request->longitude,
            'sequence_order' => $request->sequence_order,
            'status'         => 0,
        ];
        if (Schema::hasColumn('stops_pickup', 'school_id')) {
            $payload['school_id'] = $this->resolveModuleSchoolId($request, null, [
                $routeData->school_id ?? null,
            ], (int) ($routeData->user_id ?? 0));
        }
        StopPickup::create($payload);

        return response()->json([
            'success' => true,
            'message' => 'Stop And Pickup Point created successfully',
        ]);
    }

    /**
     * Display stop & pickup edit form.
     * created by ns
     */
    public function edit($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);

        $query = StopPickup::where('stops_pickup.deleted', 0);
        $this->applyStopPickupAccessScope($query, request(), 'stops_pickup.user_id', Schema::hasColumn('stops_pickup', 'school_id') ? 'stops_pickup.school_id' : null);
        $stopPickup = $query->where('stops_pickup.id', $id)->firstOrFail();

        $routeData = Route::where('deleted', 0)
            ->select('id', 'name', 'route_json');
        $this->applySchoolAwareScope($routeData, request(), 'user_id', Schema::hasColumn('routes', 'school_id') ? 'school_id' : null);
        $routeData = $routeData->orderBy('name')->get();


        return view('stop_pickup.edit', compact('stopPickup', 'routeData'));
    }

    /**
     * Update stop & pickup data.
     * created by ns
     */
    public function update(Request $request, $schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $validator = Validator::make($request->all(), [
            'route_id'       => 'required',
            'pickup_name'    => 'nullable|string|max:5000',
            'stop_name'      => 'required|string|max:255',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'sequence_order' => 'required|integer',
        ], [
            'latitude.between'  => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $routeData = Route::where('id', $request->route_id)
            ->where('deleted', 0);
        $this->applySchoolAwareScope($routeData, $request, 'user_id', Schema::hasColumn('routes', 'school_id') ? 'school_id' : null);
        $routeColumns = ['id', 'user_id'];
        if (Schema::hasColumn('routes', 'school_id')) {
            $routeColumns[] = 'school_id';
        }
        $routeData = $routeData->first($routeColumns);

        if (! $routeData) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid route selected',
            ], 422);
        }

        $query = StopPickup::where('stops_pickup.deleted', 0);
        $this->applyStopPickupAccessScope($query, $request, 'stops_pickup.user_id', Schema::hasColumn('stops_pickup', 'school_id') ? 'stops_pickup.school_id' : null);
        $stopPickup = $query->where('stops_pickup.id', $id)->firstOrFail();

        $payload = [
            'user_id'        => $this->resolveModuleOwnerUserId($request, (int) ($stopPickup->user_id ?? 0), [
                (int) ($routeData->user_id ?? 0),
            ]),
            'route_id'       => $routeData->id,
            'pickup_name'    => $this->normalizeStopPickupText($request->pickup_name),
            'stop_name'      => $this->normalizeStopPickupText($request->stop_name),
            'latitude'       => $request->latitude,
            'longitude'      => $request->longitude,
            'sequence_order' => $request->sequence_order,
        ];
        if (Schema::hasColumn('stops_pickup', 'school_id')) {
            $payload['school_id'] = $this->resolveModuleSchoolId($request, (int) ($stopPickup->school_id ?? 0), [
                $routeData->school_id ?? null,
            ], (int) ($routeData->user_id ?? 0));
        }
        $stopPickup->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Stop And Pickup Point updated successfully',
        ]);
    }

    /**
     * Soft delete stop and pickup record.
     * created by ns
     */
    public function destroy($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);

        $query = StopPickup::query();
        $this->applyStopPickupAccessScope($query, request(), 'user_id', Schema::hasColumn('stops_pickup', 'school_id') ? 'school_id' : null);
        $stopPickup = $query->findOrFail($id);

        $usageMap = $this->getStopPickupDeletionUsageMap([(int) $stopPickup->id]);
        $currentUsage = $usageMap[(int) $stopPickup->id] ?? [];
        if (($currentUsage['total'] ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => $this->buildStopPickupDeletionBlockedMessage($currentUsage),
            ], 422);
        }

        $stopPickup->deleted = 1;
        $stopPickup->save();

        return response()->json([
            'success' => true,
            'message' => 'Stop And Pickup Point deleted Successfully.',
        ]);
    }

    /**
     * Toggle Stop And Pickup  active/inactive status.
     * created by ns
     */
    public function toggleStatus($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);

        $query = StopPickup::query();
        $this->applyStopPickupAccessScope($query, request(), 'user_id', Schema::hasColumn('stops_pickup', 'school_id') ? 'school_id' : null);
        $stopPickup = $query->findOrFail($id);

        $stopPickup->status = $stopPickup->status == 1 ? 0 : 1;
        $stopPickup->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    private function extractRoutePoints(Route $route): array
    {
        $routeJson = is_array($route->route_json ?? null) ? $route->route_json : [];
        $points = [];

        $appendPoint = function ($point, string $pointType) use (&$points) {
            if (! is_array($point)) {
                return;
            }

            $name = trim((string) ($point['name'] ?? $point['address'] ?? ''));
            $latitude = $point['lat'] ?? $point['latitude'] ?? null;
            $longitude = $point['lng'] ?? $point['lon'] ?? $point['longitude'] ?? null;

            if ($name === '' || ! is_numeric($latitude) || ! is_numeric($longitude)) {
                return;
            }

            $normalizedType = trim(strtolower($pointType)) !== '' ? strtolower($pointType) : 'pickup';
            $labelType = ucfirst($normalizedType);
            $points[] = [
                'name' => $name,
                'type' => $normalizedType,
                'label' => $labelType.' - '.$name,
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
                'sequence' => is_numeric($point['sequence'] ?? null) ? (int) $point['sequence'] : null,
            ];
        };

        $appendPoint($routeJson['start_point'] ?? null, 'start');

        foreach ((array) ($routeJson['pickup_points'] ?? []) as $point) {
            $appendPoint($point, 'pickup');
        }

        $appendPoint($routeJson['end_point'] ?? null, 'end');

        return array_values($points);
    }

    /**
     * Get active Stop And Pickup count.
     * created by ns
     */
    public function getActiveCount()
    {
        $query = StopPickup::where('deleted', 0)
            ->where('status', true);
        $this->applyStopPickupAccessScope($query, request(), 'user_id', Schema::hasColumn('stops_pickup', 'school_id') ? 'school_id' : null);

        $activeCount = $query->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Fetch stop & pickup list for DataTable.
     * created by ns
     */
    public function stopPickupList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = (int) $request->input('iSortCol_0', 0);
        $columnKey   = $request->input('mDataProp_' . $indexColumn, 'id');

        $sortableKeys = [
            'id',
            'school_name',
            'name', // route name
            'pickup_name',
            'stop_name',
            'sequence_order',
        ];

        $columnKey = in_array($columnKey, $sortableKeys, true) ? $columnKey : 'id';

        $columnSortOrder = in_array(
            $request->input('sSortDir_0'),
            ['asc', 'desc']
        ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        $query = StopPickup::query()
            ->with('route')
            ->where('stops_pickup.deleted', 0);

        if ($columnKey === 'name') {
            $query->leftJoin('routes', 'routes.id', '=', 'stops_pickup.route_id');
        } elseif ($columnKey === 'school_name') {
            $query->leftJoin('schools', function ($join) {
                $join->on('stops_pickup.user_id', '=', 'schools.user_id')
                    ->where('schools.deleted', 0);
            });
        }

        $query->select('stops_pickup.*');
        $this->applyStopPickupAccessScope($query, $request, 'stops_pickup.user_id', Schema::hasColumn('stops_pickup', 'school_id') ? 'stops_pickup.school_id' : null);
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $matchingSchoolReferences = $this->resolveSchoolSearchIds($searchValue);
            $matchingSchoolIds = $matchingSchoolReferences['school_ids'];
            $matchingUserIds = $matchingSchoolReferences['user_ids'];

            $query->where(function ($q) use ($searchValue, $matchingSchoolIds, $matchingUserIds) {
                $q->where('pickup_name', 'like', "%$searchValue%")
                    ->orWhere('stop_name', 'like', "%$searchValue%")
                    ->orWhere('latitude', 'like', "%$searchValue%")
                    ->orWhere('longitude', 'like', "%$searchValue%")
                    ->orWhere('sequence_order', 'like', "%$searchValue%");

                // Keep relation-search grouped to avoid bypassing actor scope via top-level ORs.
                $q->orWhereHas('route', function ($routeQuery) use ($searchValue) {
                    $routeQuery->where('name', 'like', "%$searchValue%");
                });

                if (! empty($matchingSchoolIds) && Schema::hasColumn('stops_pickup', 'school_id')) {
                    $q->orWhereIn('stops_pickup.school_id', $matchingSchoolIds);
                }

                if (! empty($matchingUserIds)) {
                    $q->orWhereIn('stops_pickup.user_id', $matchingUserIds);
                }
            });
        }

        $totalRecordwithFilter = (clone $query)->count();

        $sortColumnMap = [
            'id' => 'stops_pickup.id',
            'pickup_name' => 'stops_pickup.pickup_name',
            'stop_name' => 'stops_pickup.stop_name',
            'sequence_order' => 'stops_pickup.sequence_order',
            'name' => 'routes.name',
            'school_name' => 'schools.school_name',
        ];

        $sortColumn = $sortColumnMap[$columnKey] ?? 'stops_pickup.id';

        $stopPickupDetails = $query
            ->orderBy($sortColumn, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        $data = [];
        $schoolNameMap = $this->getSchoolNameMapForRouteIds($stopPickupDetails->pluck('route_id')->all());
        $usageMap = $this->getStopPickupDeletionUsageMap(
            $stopPickupDetails->pluck('id')->map(fn ($id) => (int) $id)->all()
        );

        foreach ($stopPickupDetails as $stopPickup) {
            $stopPickupUsage = $usageMap[(int) $stopPickup->id] ?? [];
            $canDelete = (($stopPickupUsage['total'] ?? 0) === 0);

            $data[] = [
                'id'             => (string) $stopPickup->id,
                'school_name'    => $schoolNameMap[$stopPickup->route_id] ?? '-',
                'route_name'     => optional($stopPickup->route)->name ?? '-',
                'pickup_name'    => $stopPickup->pickup_name,
                'stop_name'      => $stopPickup->stop_name,
                'latitude'       => $stopPickup->latitude,
                'longitude'      => $stopPickup->longitude,
                'sequence_order' => $stopPickup->sequence_order,
                'status'         => $stopPickup->status,
                'can_delete'     => $canDelete,
                'is_assigned'    => ! $canDelete,
                'delete_block_reason' => $canDelete
                    ? null
                    : $this->buildStopPickupDeletionBlockedMessage($stopPickupUsage),
            ];
        }

        return response()->json([
            'draw'                 => intval($draw),
            'iTotalRecords'        => $totalRecords,
            'iTotalDisplayRecords' => $totalRecordwithFilter,
            'aaData'               => $data,
        ]);
    }

    /**
     * Multi delete stop & pickup records.
     * created by ns
     */
    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided for deletion',
            ]);
        }

        $query = StopPickup::whereIn('id', $ids);
        $this->applyStopPickupAccessScope($query, $request, 'user_id', Schema::hasColumn('stops_pickup', 'school_id') ? 'school_id' : null);
        $stopPickups = $query->get(['id']);

        if ($stopPickups->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid stop and pickup points found for delete.',
            ]);
        }

        $usageMap = $this->getStopPickupDeletionUsageMap(
            $stopPickups->pluck('id')->map(fn ($id) => (int) $id)->all()
        );
        $totalUsage = $this->sumStopPickupDeletionUsage($usageMap);
        if (($totalUsage['total'] ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => $this->buildStopPickupDeletionBlockedMessage($totalUsage, true),
            ], 422);
        }

        StopPickup::whereIn('id', $stopPickups->pluck('id'))->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected stop and pickup points deleted successfully',
        ]);
    }

    private function getStopPickupDeletionUsageMap(array $stopPickupIds): array
    {
        $stopPickupIds = array_values(array_filter(array_map('intval', $stopPickupIds)));
        if (empty($stopPickupIds)) {
            return [];
        }

        $usageMap = [];
        foreach ($stopPickupIds as $stopPickupId) {
            $usageMap[$stopPickupId] = [
                'pickup_children' => 0,
                'stop_children' => 0,
                'total' => 0,
            ];
        }

        if (! Schema::hasTable('children')) {
            return $usageMap;
        }

        if (Schema::hasColumn('children', 'pickup_name')) {
            $pickupUsage = Child::query()
                ->select('pickup_name', DB::raw('COUNT(*) as aggregate'))
                ->whereIn('pickup_name', $stopPickupIds)
                ->where(function ($query) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                })
                ->groupBy('pickup_name')
                ->pluck('aggregate', 'pickup_name')
                ->all();

            foreach ($pickupUsage as $stopPickupId => $count) {
                if (! isset($usageMap[(int) $stopPickupId])) {
                    continue;
                }

                $usageMap[(int) $stopPickupId]['pickup_children'] = (int) $count;
            }
        }

        if (Schema::hasColumn('children', 'stop_name')) {
            $stopUsage = Child::query()
                ->select('stop_name', DB::raw('COUNT(*) as aggregate'))
                ->whereIn('stop_name', $stopPickupIds)
                ->where(function ($query) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                })
                ->groupBy('stop_name')
                ->pluck('aggregate', 'stop_name')
                ->all();

            foreach ($stopUsage as $stopPickupId => $count) {
                if (! isset($usageMap[(int) $stopPickupId])) {
                    continue;
                }

                $usageMap[(int) $stopPickupId]['stop_children'] = (int) $count;
            }
        }

        foreach ($usageMap as $stopPickupId => $usage) {
            $usageMap[$stopPickupId]['total'] = (int) $usage['pickup_children'] + (int) $usage['stop_children'];
        }

        return $usageMap;
    }

    private function sumStopPickupDeletionUsage(array $usageMap): array
    {
        $totals = [
            'pickup_children' => 0,
            'stop_children' => 0,
            'total' => 0,
        ];

        foreach ($usageMap as $usage) {
            $totals['pickup_children'] += (int) ($usage['pickup_children'] ?? 0);
            $totals['stop_children'] += (int) ($usage['stop_children'] ?? 0);
        }

        $totals['total'] = $totals['pickup_children'] + $totals['stop_children'];

        return $totals;
    }

    private function buildStopPickupDeletionBlockedMessage(array $usage, bool $plural = false): string
    {
        $parts = [];

        $pickupChildrenCount = (int) ($usage['pickup_children'] ?? 0);
        $stopChildrenCount = (int) ($usage['stop_children'] ?? 0);

        if ($pickupChildrenCount > 0) {
            $parts[] = $pickupChildrenCount.' '.($pickupChildrenCount === 1 ? 'child pickup assignment' : 'child pickup assignments');
        }

        if ($stopChildrenCount > 0) {
            $parts[] = $stopChildrenCount.' '.($stopChildrenCount === 1 ? 'child stop assignment' : 'child stop assignments');
        }

        if (empty($parts)) {
            return $plural
                ? 'One or more selected stop and pickup points are assigned and cannot be deleted.'
                : 'This stop or pickup point is assigned and cannot be deleted.';
        }

        $usageText = $this->joinStopPickupUsageParts($parts);

        return $plural
            ? 'One or more selected stop and pickup points are linked to '.$usageText.'. Remove those assignments before deleting them.'
            : 'This stop or pickup point is linked to '.$usageText.'. Remove those assignments before deleting it.';
    }

    private function joinStopPickupUsageParts(array $parts): string
    {
        $parts = array_values(array_filter(array_map('trim', $parts)));
        $partCount = count($parts);

        if ($partCount === 0) {
            return '';
        }

        if ($partCount === 1) {
            return $parts[0];
        }

        if ($partCount === 2) {
            return $parts[0].' and '.$parts[1];
        }

        $lastPart = array_pop($parts);

        return implode(', ', $parts).', and '.$lastPart;
    }

    private function normalizeStopPickupText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    private function applyStopPickupAccessScope($query, ?Request $request = null, string $userColumn = 'user_id', ?string $schoolColumn = null)
    {
        $request = $request ?: request();

        if (! $this->shouldRestrictToActorData($request)) {
            return $query;
        }

        $actorUserId = $this->resolveActorUserId($request);
        if (! $actorUserId) {
            return $query->whereRaw('1 = 0');
        }

        if (! $this->isSchoolActor($request)) {
            return $query->where($userColumn, $actorUserId);
        }

        $schoolId = $this->resolveSchoolIdFromContext($request, $actorUserId);

        return $query->where(function ($scopeQuery) use ($userColumn, $actorUserId, $schoolColumn, $schoolId) {
            $scopeQuery->where($userColumn, $actorUserId);

            if ($schoolColumn && $schoolId) {
                $scopeQuery->orWhere($schoolColumn, $schoolId);
            }

            $scopeQuery->orWhereExists(function ($routeQuery) use ($schoolId, $actorUserId) {
                $routeQuery->select(DB::raw(1))
                    ->from('routes')
                    ->whereColumn('routes.id', 'stops_pickup.route_id')
                    ->where(function ($visibleRouteQuery) use ($schoolId, $actorUserId) {
                        $visibleRouteQuery->where('routes.user_id', $actorUserId);

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
}

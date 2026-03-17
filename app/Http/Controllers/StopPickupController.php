<?php
namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\StopPickup;
use Illuminate\Http\Request;
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
        $routeData = Route::select('id', 'name')
            ->get();

        return view('stop_pickup.create', compact('routeData'));
    }

    /**
     * Store stop & pickup data.
     * created by ns
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'route_id'       => 'required|exists:routes,id',
            'pickup_name'    => 'required|string|max:255',
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

        $routeData = Route::find($request->route_id);

        StopPickup::create([
            'user_id'        => $this->resolveActorUserId($request),
            'route_id'       => $routeData->id,
            'pickup_name'    => $request->pickup_name,
            'stop_name'      => $request->stop_name,
            'latitude'       => $request->latitude,
            'longitude'      => $request->longitude,
            'sequence_order' => $request->sequence_order,
            'status'         => 0,
        ]);

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
        $this->applyActorScope($query, request(), 'stops_pickup.user_id');
        $stopPickup = $query->where('stops_pickup.id', $id)->firstOrFail();

        $routeData = Route::where('deleted', 0)
            ->select('id', 'name')
            ->get();


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
            'pickup_name'    => 'required|string|max:255',
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
            ->where('deleted', 0)
            ->first();

        if (! $routeData) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid route selected',
            ], 422);
        }

        $query = StopPickup::where('stops_pickup.deleted', 0);
        $this->applyActorScope($query, $request, 'stops_pickup.user_id');
        $stopPickup = $query->where('stops_pickup.id', $id)->firstOrFail();

        $stopPickup->update([
            'route_id'       => $routeData->id,
            'pickup_name'    => $request->pickup_name,
            'stop_name'      => $request->stop_name,
            'latitude'       => $request->latitude,
            'longitude'      => $request->longitude,
            'sequence_order' => $request->sequence_order,
        ]);

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
        $this->applyActorScope($query, request(), 'user_id');
        $stopPickup = $query->findOrFail($id);

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
        $this->applyActorScope($query, request(), 'user_id');
        $stopPickup = $query->findOrFail($id);

        $stopPickup->status = $stopPickup->status == 1 ? 0 : 1;
        $stopPickup->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active Stop And Pickup count.
     * created by ns
     */
    public function getActiveCount()
    {
        $query = StopPickup::where('deleted', 0)
            ->where('status', true);
        $this->applyActorScope($query, request(), 'user_id');

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
        $this->applyActorScope($query, $request, 'stops_pickup.user_id');
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('pickup_name', 'like', "%$searchValue%")
                    ->orWhere('stop_name', 'like', "%$searchValue%")
                    ->orWhere('latitude', 'like', "%$searchValue%")
                    ->orWhere('longitude', 'like', "%$searchValue%")
                    ->orWhere('sequence_order', 'like', "%$searchValue%");

                // Keep relation-search grouped to avoid bypassing actor scope via top-level ORs.
                $q->orWhereHas('route', function ($routeQuery) use ($searchValue) {
                    $routeQuery->where('name', 'like', "%$searchValue%");
                });
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
        $schoolNameMap = $this->getSchoolNameMapForUserIds($stopPickupDetails->pluck('user_id')->all());

        foreach ($stopPickupDetails as $stopPickup) {
            $data[] = [
                'id'             => (string) $stopPickup->id,
                'school_name'    => $schoolNameMap[$stopPickup->user_id] ?? '-',
                'route_name'     => optional($stopPickup->route)->name ?? '-',
                'pickup_name'    => $stopPickup->pickup_name,
                'stop_name'      => $stopPickup->stop_name,
                'latitude'       => $stopPickup->latitude,
                'longitude'      => $stopPickup->longitude,
                'sequence_order' => $stopPickup->sequence_order,
                'status'         => $stopPickup->status,
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
        $this->applyActorScope($query, $request, 'user_id');
        $query->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected stop and pickup points deleted successfully',
        ]);
    }
}

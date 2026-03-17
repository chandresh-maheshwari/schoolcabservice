<?php
namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Rating;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class RatingController extends Controller
{
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
        $drivers = Driver::where('deleted', 0)
            ->select('id', 'driver_name')
            ->get();

        $vehicles = Vehicle::where('deleted', 0)
            ->select('id', 'vehicle_number')
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
            // 'driver_name'    => 'required|exists:drivers,driver_name',
            // 'vehicle_number' => 'required|exists:vehicles,vehicle_number',
            'rating'   => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
        ]);

        Rating::create([
            'user_id'    => $this->resolveActorUserId($request),
            'driver_id'  => $request->driver_name,
            'vehicle_id' => $request->vehicle_number,
            'rating'     => $request->rating,
            'comments'   => $request->comments,
            'deleted'    => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rating submitted successfully',
        ]);
    }

    /**
     * Display rating & feedback edit form.
     * created by ns
     */
    public function edit($maybeSlugOrId, $maybeId = null)
    {
        $id = $this->normalizeRouteId($maybeSlugOrId, $maybeId);

        $query = Rating::query();
        $this->applyActorScope($query);
        $rating = $query->findOrFail($id);

        $drivers = Driver::where('deleted', 0)
            ->select('id', 'driver_name')
            ->get();

        $vehicles = Vehicle::where('deleted', 0)
            ->select('id', 'vehicle_number')
            ->get();

        return view('rating_feedback.edit', compact('rating', 'drivers', 'vehicles'));
    }

    /**
     * Update rating & feedback data.
     * created by ns
     */
    public function update(Request $request, $maybeSlugOrId, $maybeId = null)
    {
        $request->validate([
            // 'driver_name'    => 'required|exists:drivers,driver_name',
            // 'vehicle_number' => 'required|exists:vehicles,vehicle_number',
            'rating'   => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
        ]);

        $id = $this->normalizeRouteId($maybeSlugOrId, $maybeId);

        $query = Rating::query();
        $this->applyActorScope($query, $request);
        $rating = $query->findOrFail($id);

        $rating->update([
            'driver_id'  => $request->driver_name,
            'vehicle_id' => $request->vehicle_number,
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
        $this->applyActorScope($query);
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
        $this->applyActorScope($query, $request, 'ratings.user_id');
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('rating', 'like', "%$searchValue%")
                    ->orWhere('comments', 'like', "%$searchValue%");

                // Keep relation-search grouped to avoid bypassing actor scope via top-level ORs.
                $q->orWhereHas('driver', function ($driverQuery) use ($searchValue) {
                    $driverQuery->where('driver_name', 'like', "%$searchValue%");
                })->orWhereHas('vehicle', function ($vehicleQuery) use ($searchValue) {
                    $vehicleQuery->where('vehicle_number', 'like', "%$searchValue%");
                });
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

        foreach ($ratingDetails as $rating) {
            $data[] = [
                'id'             => $rating->id,
                'school_name'    => $schoolNameMap[$rating->user_id] ?? '-',
                'driver_name'    => optional($rating->driver)->driver_name,
                'vehicle_number' => optional($rating->vehicle)->vehicle_number,
                'rating'         => $rating->rating,
                'comments'       => $rating->comments,
            ];
        }

        return response()->json([
            'draw'                 => intval($draw),
            'iTotalRecords'        => $totalRecords,
            'iTotalDisplayRecords' => $totalRecordwithFilter,
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
        $this->applyActorScope($query, $request);
        $query->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected routes deleted successfully',
        ]);
    }
}

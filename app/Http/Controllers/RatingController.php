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
    public function edit($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $rating = Rating::findOrFail($id);

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
    public function update(Request $request, $schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $request->validate([
            // 'driver_name'    => 'required|exists:drivers,driver_name',
            // 'vehicle_number' => 'required|exists:vehicles,vehicle_number',
            'rating'   => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
        ]);

        $rating = Rating::findOrFail($id);

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
    public function destroy($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $rating          = Rating::findOrFail($id);
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
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, 'id');

        $allowedColumns = [
            'id',
            'driver_name',
            'vehicle_number',
            'rating',
            'comments',
            'deleted',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';

        $columnSortOrder = in_array(
            $request->input('sSortDir_0'),
            ['asc', 'desc']
        ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        $query = Rating::with(['driver', 'vehicle'])->where('deleted', 0);
        $this->applyActorScope($query, $request);
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('rating', 'like', "%$searchValue%")
                    ->orWhere('comments', 'like', "%$searchValue%");
            })->orWhereHas('driver', function ($driverQuery) use ($searchValue) {
                $driverQuery->where('driver_name', 'like', "%$searchValue%");
            })->orWhereHas('vehicle', function ($vehicleQuery) use ($searchValue) {
                $vehicleQuery->where('vehicle_number', 'like', "%$searchValue%");
            });
        }

        $totalRecordwithFilter = (clone $query)->count();

        $ratingDetails = $query
            ->orderBy($columnName, $columnSortOrder)
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

        Rating::whereIn('id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected routes deleted successfully',
        ]);
    }
}

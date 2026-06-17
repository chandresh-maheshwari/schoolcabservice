<?php
namespace App\Http\Controllers;

use App\Models\VehicleType;
use Illuminate\Http\Request;

class VehicleTypeController extends Controller
{
    /**
     * Display vehicle type listing page.
     * created by ns
     */
    public function index()
    {
        return view('vehicle_type.index');
    }

    /**
     * Display vehicle type create form.
     * created by ns
     */
    public function create()
    {
        return view('vehicle_type.create');
    }

    /**
     * Store vehicle type data.
     * created by ns
     */
    public function store(Request $request)
{
    $request->validate([
        'vehicle_type' => 'required|string|max:255',
    ]);

    $currentUserId = $this->resolvePersistedUserId($request);
    if (! $currentUserId) {
        return response()->json([
            'success' => false,
            'message' => 'User session not found. Please login again.',
        ], 401);
    }

    $ownerUserId = $this->resolveModuleOwnerUserId($request, $currentUserId);

    $normalizedVehicleType = trim((string) $request->vehicle_type);
    if ($normalizedVehicleType === '') {
        return response()->json([
            'success' => false,
            'message' => 'Vehicle Type is required.',
        ], 422);
    }

    $existingVehicleType = VehicleType::query()
        ->whereRaw('LOWER(TRIM(vehicle_type)) = ?', [mb_strtolower($normalizedVehicleType)])
        ->first();

    if ($existingVehicleType) {
        if ((int) ($existingVehicleType->deleted ?? 0) === 1) {
            $existingVehicleType->vehicle_type = $normalizedVehicleType;
            $existingVehicleType->deleted = 0;
            $existingVehicleType->status = $existingVehicleType->status ?? 0;
            $existingVehicleType->user_id = $ownerUserId;
            $existingVehicleType->save();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle Type restored Successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Vehicle Type already exists.',
        ], 422);
    }

    $vehicleType = new VehicleType();
    $vehicleType->vehicle_type = $normalizedVehicleType;
    $vehicleType->status = 0;
    $vehicleType->deleted = 0;
    $vehicleType->user_id = $ownerUserId;
    $vehicleType->save();

    return response()->json([
        'success' => true,
        'message' => 'Vehicle Type created Successfully.',
    ]);
}


    /**
     * Display vehicle type edit form.
     * created by ns
     */
    public function edit($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $query = VehicleType::query();
        $this->applyActorScope($query);
        $vehicleType = $query->findOrFail($id);

        return view('vehicle_type.edit', compact('vehicleType'));
    }

    /**
     * Update vehicle type data.
     * created by ns
     */
    public function update(Request $request, $schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $query = VehicleType::query();
        $this->applyActorScope($query, $request);
        $vehicleType = $query->findOrFail($id);
        $vehicleType->update([
            'vehicle_type' => trim((string) $request->vehicle_type),
            'user_id' => $this->resolveModuleOwnerUserId($request, (int) $vehicleType->user_id),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle Type updated successfully.',
        ]);
    }

    /**
     * Soft delete vehicle type record.
     * created by ns
     */
    public function destroy($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $query = VehicleType::query();
        $this->applyActorScope($query);
        $vehicleType = $query->findOrFail($id);

        $vehicleType->deleted = 1;
        $vehicleType->save();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle Type deleted Successfully.',
        ]);
    }

    /**
     * Toggle vehicle type active/inactive status.
     * created by ns
     */
    public function toggleStatus($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $query = VehicleType::query();
        $this->applyActorScope($query);
        $vehicleType = $query->findOrFail($id);

        $vehicleType->status = $vehicleType->status == 1 ? 0 : 1;
        $vehicleType->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active vehicle type count.
     * created by ns
     */
    public function getActiveCount()
    {
        $query = VehicleType::where('deleted', 0)
            ->where('status', true);
        $this->applyActorScope($query);

        $activeCount = $query->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Soft delete multiple vehicle types.
     * created by ns
     */
    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided.',
            ]);
        }

        $query = VehicleType::whereIn('id', $ids);
        $this->applyActorScope($query, $request);
        $query->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected id deleted Successfully.',
        ]);
    }

    /**
     * Fetch vehicle type list for DataTable.
     * created by ns
     */
    // public function vehicleTypeList(Request $request)
    // {
    //     $draw        = intval($request->input('sEcho'));
    //     $row         = intval($request->input('iDisplayStart'));
    //     $rowperpage  = intval($request->input('iDisplayLength'));
    //     $indexColumn = $request->input('iSortCol_0');
    //     $columnName  = $request->input('mDataProp_' . $indexColumn);

    //     $allowedColumns = ['_id', 'vehicle_type', 'status'];
    //     if (! in_array($columnName, $allowedColumns)) {
    //         $columnName = 'vehicle_type';
    //     }

    //     $columnSortOrder = $request->input('sSortDir_0', 'asc');
    //     $searchValue     = $request->input('sSearch');

    //     $vehicleTypeDetails = VehicleType::getVehicleTypeData(
    //         $searchValue,
    //         $columnName,
    //         $columnSortOrder,
    //         $draw,
    //         $row,
    //         $rowperpage
    //     );

    //     $totalRecords          = VehicleType::where('deleted', 0)->count();
    //     $totalRecordwithFilter = VehicleType::getVehicleTypeDataTotal($searchValue);

    //     $data = [];
    //     foreach ($vehicleTypeDetails as $vehicleType) {
    //         $data[] = [
    //             'id'           => (string) $vehicleType->_id,
    //             'vehicle_type' => $vehicleType->vehicle_type ?? '-',
    //             'status'       => $vehicleType->status,
    //         ];
    //     }

    //     return response()->json([
    //         "draw"            => $draw,
    //         "recordsTotal"    => $totalRecords,
    //         "recordsFiltered" => $totalRecordwithFilter,
    //         "data"            => $data,
    //     ]);
    // }
     public function vehicleTypeList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        if (! in_array($columnName, ['id', 'vehicle_type', 'status'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue     = $request->input('sSearch');

        $query = VehicleType::where('deleted', 0);
        $this->applyActorScope($query, $request);
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $query->where('vehicle_type', 'like', '%' . $searchValue . '%');
        }

        $totalRecordwithFilter = (clone $query)->count();
        $vehicleDetails        = $query
            ->orderBy($columnName, in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'desc')
            ->skip($row)
            ->take($rowperpage)
            ->get();

        $data = [];
        $schoolNameMap = $this->getSchoolNameMapForVehicleTypeIds($vehicleDetails->pluck('id')->all());
        foreach ($vehicleDetails as $vehicleType) {
            $data[] = [
                'id'           => $vehicleType->id,
                'school_name'  => $schoolNameMap[$vehicleType->id] ?? '-',
                'vehicle_type' => $vehicleType->vehicle_type ?? '-',
                'status'       => $vehicleType->status,
            ];
        }

        return response()->json([
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data" => $data
        ]);
    }
}

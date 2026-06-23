<?php
namespace App\Http\Controllers;

use App\Models\School;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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
        $schools = School::query()
            ->where('deleted', 0)
            ->orderBy('school_name')
            ->get(['id', 'user_id', 'school_name']);

        return view('vehicle_type.create', compact('schools'));
    }

    /**
     * Store vehicle type data.
     * created by ns
     */
    public function store(Request $request)
{
    $request->validate([
        'vehicle_type' => 'required|string|max:255',
        'user_id' => 'nullable|exists:users,id',
    ]);

    $currentUserId = $this->resolvePersistedUserId($request);
    if (! $currentUserId) {
        return response()->json([
            'success' => false,
            'message' => 'User session not found. Please login again.',
        ], 401);
    }

    $ownerUserId = $this->resolveModuleOwnerUserId($request, $currentUserId);
    $schoolId = $this->resolveModuleSchoolId($request, null, [], $ownerUserId);

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
            if (Schema::hasColumn('vehicle_types', 'school_id')) {
                $existingVehicleType->school_id = $schoolId;
            }
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
    if (Schema::hasColumn('vehicle_types', 'school_id')) {
        $vehicleType->school_id = $schoolId;
    }
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
        $this->applySchoolAwareScope($query, request(), 'user_id', Schema::hasColumn('vehicle_types', 'school_id') ? 'school_id' : null);
        $vehicleType = $query->findOrFail($id);
        $schools = School::query()
            ->where('deleted', 0)
            ->orderBy('school_name')
            ->get(['id', 'user_id', 'school_name']);

        return view('vehicle_type.edit', compact('vehicleType', 'schools'));
    }

    /**
     * Update vehicle type data.
     * created by ns
     */
    public function update(Request $request, $schoolSlugOrId, $id = null)
    {
        $request->validate([
            'vehicle_type' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $query = VehicleType::query();
        $this->applySchoolAwareScope($query, $request, 'user_id', Schema::hasColumn('vehicle_types', 'school_id') ? 'school_id' : null);
        $vehicleType = $query->findOrFail($id);
        $ownerUserId = $this->resolveModuleOwnerUserId($request, (int) $vehicleType->user_id);
        $updatePayload = [
            'vehicle_type' => trim((string) $request->vehicle_type),
            'user_id' => $ownerUserId,
        ];
        if (Schema::hasColumn('vehicle_types', 'school_id')) {
            $updatePayload['school_id'] = $this->resolveModuleSchoolId($request, (int) ($vehicleType->school_id ?? 0), [], $ownerUserId);
        }
        $vehicleType->update([
            ...$updatePayload,
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
        $this->applySchoolAwareScope($query, request(), 'user_id', Schema::hasColumn('vehicle_types', 'school_id') ? 'school_id' : null);
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
        $this->applySchoolAwareScope($query, request(), 'user_id', Schema::hasColumn('vehicle_types', 'school_id') ? 'school_id' : null);
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
        $this->applySchoolAwareScope($query, request(), 'user_id', Schema::hasColumn('vehicle_types', 'school_id') ? 'school_id' : null);

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
        $this->applySchoolAwareScope($query, $request, 'user_id', Schema::hasColumn('vehicle_types', 'school_id') ? 'school_id' : null);
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
        $this->applySchoolAwareScope($query, $request, 'user_id', Schema::hasColumn('vehicle_types', 'school_id') ? 'school_id' : null);
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $matchingSchoolsQuery = School::query()
                ->where('deleted', 0)
                ->where('school_name', 'like', '%' . $searchValue . '%');

            $matchingSchoolIds = Schema::hasColumn('vehicle_types', 'school_id')
                ? (clone $matchingSchoolsQuery)->pluck('id')->map(fn ($id) => (int) $id)->all()
                : [];
            $matchingUserIds = (clone $matchingSchoolsQuery)->pluck('user_id')->map(fn ($id) => (int) $id)->all();

            $query->where(function ($filterQuery) use ($searchValue, $matchingSchoolIds, $matchingUserIds) {
                $filterQuery->where('vehicle_type', 'like', '%' . $searchValue . '%');

                if (! empty($matchingSchoolIds) && Schema::hasColumn('vehicle_types', 'school_id')) {
                    $filterQuery->orWhereIn('school_id', $matchingSchoolIds);
                }

                if (! empty($matchingUserIds)) {
                    $filterQuery->orWhereIn('user_id', $matchingUserIds);
                }
            });
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

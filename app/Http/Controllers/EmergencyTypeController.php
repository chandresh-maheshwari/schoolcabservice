<?php

namespace App\Http\Controllers;

use App\Models\EmergencyType;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmergencyTypeController extends Controller
{
    public function index()
    {
        return view('emergency_type.index');
    }

    public function create()
    {
        $schools = School::query()
            ->where('deleted', 0)
            ->orderBy('school_name')
            ->get(['id', 'user_id', 'school_name']);

        return view('emergency_type.create', compact('schools'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'emergency_type' => ['required', 'string', 'max:255', 'regex:/^(?=.*[A-Za-z])[A-Za-z\s]+$/'],
            'user_id' => 'nullable|exists:users,id',
        ], [
            'emergency_type.regex' => 'Emergency Type must contain letters only. Digits are not allowed.',
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
        $normalizedEmergencyType = trim((string) $request->emergency_type);
        if ($normalizedEmergencyType === '') {
            return response()->json([
                'success' => false,
                'message' => 'Emergency Type is required.',
            ], 422);
        }

        $existingEmergencyType = EmergencyType::query()
            ->whereRaw('LOWER(TRIM(emergency_type)) = ?', [mb_strtolower($normalizedEmergencyType)])
            ->first();

        if ($existingEmergencyType) {
            if ((int) ($existingEmergencyType->deleted ?? 0) === 1) {
                $existingEmergencyType->emergency_type = $normalizedEmergencyType;
                $existingEmergencyType->status = $existingEmergencyType->status ?? 0;
                $existingEmergencyType->deleted = 0;
                $existingEmergencyType->deleted_at = null;
                $existingEmergencyType->user_id = $ownerUserId;
                if (Schema::hasColumn('emergency_types', 'school_id')) {
                    $existingEmergencyType->school_id = $schoolId;
                }
                $existingEmergencyType->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Emergency Type restored successfully.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Emergency Type already exists.',
            ], 422);
        }

        $emergencyType = new EmergencyType();
        $emergencyType->emergency_type = $normalizedEmergencyType;
        $emergencyType->status = 0;
        $emergencyType->deleted = 0;
        $emergencyType->deleted_at = null;
        $emergencyType->user_id = $ownerUserId;
        if (Schema::hasColumn('emergency_types', 'school_id')) {
            $emergencyType->school_id = $schoolId;
        }
        $emergencyType->save();

        return response()->json([
            'success' => true,
            'message' => 'Emergency Type created successfully.',
        ]);
    }

    public function edit($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $query = EmergencyType::query();
        $this->applySchoolAwareScope($query, request(), 'user_id', Schema::hasColumn('emergency_types', 'school_id') ? 'school_id' : null);
        $emergencyType = $query->findOrFail($id);
        $schools = School::query()
            ->where('deleted', 0)
            ->orderBy('school_name')
            ->get(['id', 'user_id', 'school_name']);

        return view('emergency_type.edit', compact('emergencyType', 'schools'));
    }

    public function update(Request $request, $schoolSlugOrId, $id = null)
    {
        $request->validate([
            'emergency_type' => ['required', 'string', 'max:255', 'regex:/^(?=.*[A-Za-z])[A-Za-z\s]+$/'],
            'user_id' => 'nullable|exists:users,id',
        ], [
            'emergency_type.regex' => 'Emergency Type must contain letters only. Digits are not allowed.',
        ]);

        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $query = EmergencyType::query();
        $this->applySchoolAwareScope($query, $request, 'user_id', Schema::hasColumn('emergency_types', 'school_id') ? 'school_id' : null);
        $emergencyType = $query->findOrFail($id);
        $ownerUserId = $this->resolveModuleOwnerUserId($request, (int) $emergencyType->user_id);

        $updatePayload = [
            'emergency_type' => trim((string) $request->emergency_type),
            'user_id' => $ownerUserId,
        ];

        if (Schema::hasColumn('emergency_types', 'school_id')) {
            $updatePayload['school_id'] = $this->resolveModuleSchoolId($request, (int) ($emergencyType->school_id ?? 0), [], $ownerUserId);
        }

        $emergencyType->update($updatePayload);

        return response()->json([
            'success' => true,
            'message' => 'Emergency Type updated successfully.',
        ]);
    }

    public function destroy($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $query = EmergencyType::query();
        $this->applySchoolAwareScope($query, request(), 'user_id', Schema::hasColumn('emergency_types', 'school_id') ? 'school_id' : null);
        $emergencyType = $query->findOrFail($id);
        $emergencyType->deleted = 1;
        $emergencyType->deleted_at = now();
        $emergencyType->save();

        return response()->json([
            'success' => true,
            'message' => 'Emergency Type deleted successfully.',
        ]);
    }

    public function toggleStatus($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $query = EmergencyType::query();
        $this->applySchoolAwareScope($query, request(), 'user_id', Schema::hasColumn('emergency_types', 'school_id') ? 'school_id' : null);
        $emergencyType = $query->findOrFail($id);
        $emergencyType->status = (int) $emergencyType->status === 1 ? 0 : 1;
        $emergencyType->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
        ]);
    }

    public function getActiveCount()
    {
        $query = EmergencyType::where('deleted', 0)->where('status', 1);
        $this->applySchoolAwareScope($query, request(), 'user_id', Schema::hasColumn('emergency_types', 'school_id') ? 'school_id' : null);

        return response()->json(['count' => $query->count()]);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided.',
            ]);
        }

        $query = EmergencyType::whereIn('id', $ids);
        $this->applySchoolAwareScope($query, $request, 'user_id', Schema::hasColumn('emergency_types', 'school_id') ? 'school_id' : null);
        $query->update([
            'deleted' => 1,
            'deleted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Selected records deleted successfully.',
        ]);
    }

    public function emergencyTypeList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = (int) $request->input('iDisplayStart', 0);
        $rowperpage = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (! in_array($columnName, ['id', 'emergency_type', 'status'], true)) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = trim((string) $request->input('sSearch'));

        $query = EmergencyType::where('deleted', 0);
        $this->applySchoolAwareScope($query, $request, 'user_id', Schema::hasColumn('emergency_types', 'school_id') ? 'school_id' : null);
        $totalRecords = (clone $query)->count();

        if ($searchValue !== '') {
            $matchingSearchIds = $this->resolveSchoolSearchIds($searchValue);
            $query->where(function ($filterQuery) use ($searchValue, $matchingSearchIds) {
                $filterQuery->where('emergency_type', 'like', '%' . $searchValue . '%');

                if (! empty($matchingSearchIds['school_ids']) && Schema::hasColumn('emergency_types', 'school_id')) {
                    $filterQuery->orWhereIn('school_id', $matchingSearchIds['school_ids']);
                }

                if (! empty($matchingSearchIds['user_ids'])) {
                    $filterQuery->orWhereIn('user_id', $matchingSearchIds['user_ids']);
                }
            });
        }

        $totalRecordwithFilter = (clone $query)->count();
        $emergencyTypes = $query
            ->orderBy($columnName, in_array($columnSortOrder, ['asc', 'desc'], true) ? $columnSortOrder : 'desc')
            ->skip($row)
            ->take($rowperpage)
            ->get();

        $schoolNamesByUserId = $this->getSchoolNameMapForUserIds($emergencyTypes->pluck('user_id')->all());
        $schoolNamesBySchoolId = Schema::hasColumn('emergency_types', 'school_id')
            ? $this->getSchoolNameMapForSchoolIds($emergencyTypes->pluck('school_id')->all())
            : [];
        $fallbackSchoolNames = $this->resolveEmergencyTypeSchoolNamesFromIncidents($emergencyTypes);

        $data = [];
        foreach ($emergencyTypes as $emergencyType) {
            $data[] = [
                'id' => $emergencyType->id,
                'school_name' => $schoolNamesBySchoolId[(int) ($emergencyType->school_id ?? 0)]
                    ?? $schoolNamesByUserId[(int) ($emergencyType->user_id ?? 0)]
                    ?? $fallbackSchoolNames[(int) ($emergencyType->id ?? 0)]
                    ?? '-',
                'emergency_type' => $emergencyType->emergency_type ?? '-',
                'status' => $emergencyType->status,
            ];
        }

        return response()->json([
            'draw' => (int) $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecordwithFilter,
            'data' => $data,
        ]);
    }

    private function resolveEmergencyTypeSchoolNamesFromIncidents($emergencyTypes): array
    {
        $typeNamesById = [];
        foreach ($emergencyTypes as $emergencyType) {
            $typeId = (int) ($emergencyType->id ?? 0);
            $typeName = trim((string) ($emergencyType->emergency_type ?? ''));
            if ($typeId > 0 && $typeName !== '') {
                $typeNamesById[$typeId] = $typeName;
            }
        }

        if ($typeNamesById === []) {
            return [];
        }

        $incidentRows = DB::table('emergency_incidents')
            ->where('deleted', 0)
            ->whereIn('emergency_type', array_values(array_unique(array_values($typeNamesById))))
            ->orderByDesc('id')
            ->get(['id', 'user_id', 'driver_id', 'vehicle_id', 'emergency_type']);

        if ($incidentRows->isEmpty()) {
            return [];
        }

        $schoolNamesByUserId = $this->getSchoolNameMapForUserIds($incidentRows->pluck('user_id')->all());
        $schoolNamesByDriverId = $this->getSchoolNameMapForDriverIds($incidentRows->pluck('driver_id')->all());
        $schoolNamesByVehicleId = $this->getSchoolNameMapForVehicleIds($incidentRows->pluck('vehicle_id')->all());
        $resolved = [];

        foreach ($incidentRows as $incidentRow) {
            $matchingTypeIds = array_keys(array_filter($typeNamesById, function ($typeName) use ($incidentRow) {
                return strcasecmp($typeName, (string) ($incidentRow->emergency_type ?? '')) === 0;
            }));

            if ($matchingTypeIds === []) {
                continue;
            }

            $schoolName = $schoolNamesByUserId[(int) ($incidentRow->user_id ?? 0)]
                ?? $schoolNamesByDriverId[(int) ($incidentRow->driver_id ?? 0)]
                ?? $schoolNamesByVehicleId[(int) ($incidentRow->vehicle_id ?? 0)]
                ?? null;

            if (! $schoolName) {
                continue;
            }

            foreach ($matchingTypeIds as $typeId) {
                $typeId = (int) $typeId;
                if ($typeId > 0 && ! isset($resolved[$typeId])) {
                    $resolved[$typeId] = $schoolName;
                }
            }
        }

        return $resolved;
    }
}

<?php
namespace App\Http\Controllers;

use App\Models\PackageDetail;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PackageDetailController extends Controller
{
    private const ALL_SCHOOLS_OPTION = '__all_schools__';
    private const ALL_SCHOOLS_STORAGE = 'all schools';

    private function applyGlobalSchoolPackageScope($query)
    {
        return $query
            ->orWhereNull('school_id')
            ->orWhere('school_id', '')
            ->orWhere('school_id', '0')
            ->orWhereRaw("LOWER(TRIM(school_id)) IN ('all schools', 'all', 'global')")
            ->orWhereRaw("LOWER(REPLACE(REPLACE(TRIM(school_id), '-', ' '), '_', ' ')) IN ('all schools', 'all', 'global')")
            ->orWhereRaw("LOWER(TRIM(school_id)) LIKE '%all schools%'")
            ->orWhereRaw("LOWER(TRIM(school_id)) LIKE '%all-schools%'")
            ->orWhereRaw("LOWER(TRIM(school_id)) LIKE '%all_schools%'");
    }

    private function selectedSchoolIdsForPackage(PackageDetail $package): array
    {
        $rawSchoolIds = trim((string) ($package->school_id ?? ''));
        if ($rawSchoolIds === '') {
            return [];
        }

        return collect(explode(',', $rawSchoolIds))
            ->map(fn ($id) => is_numeric(trim((string) $id)) ? (int) trim((string) $id) : null)
            ->filter(fn ($id) => ! is_null($id) && $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function isGlobalSchoolStorageValue(?string $value): bool
    {
        $normalized = strtolower(trim((string) $value));

        if ($normalized === '') {
            return false;
        }

        $normalized = str_replace(['-', '_'], ' ', $normalized);

        return in_array($normalized, ['all schools', 'all', 'global'], true);
    }

    private function normalizeSelectedSchoolIds(Request $request): array
    {
        $schoolIds = $request->input('school_ids', []);

        if (! is_array($schoolIds)) {
            $schoolIds = [$schoolIds];
        }

        return collect($schoolIds)
            ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
            ->filter(fn ($id) => ! is_null($id) && $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function requestTargetsAllSchools(Request $request): bool
    {
        $schoolIds = $request->input('school_ids', []);

        if (! is_array($schoolIds)) {
            $schoolIds = [$schoolIds];
        }

        return collect($schoolIds)
            ->contains(fn ($value) => trim((string) $value) === self::ALL_SCHOOLS_OPTION);
    }

    private function validateSchoolSelection(Request $request): void
    {
        $schoolIds = $request->input('school_ids', []);

        if (! is_array($schoolIds)) {
            $schoolIds = [$schoolIds];
        }

        $invalidSchoolId = collect($schoolIds)
            ->first(function ($value) {
                $value = trim((string) $value);

                if ($value === '' || $value === self::ALL_SCHOOLS_OPTION) {
                    return false;
                }

                return ! ctype_digit($value)
                    || ! School::query()->where('id', (int) $value)->where('deleted', 0)->exists();
            });

        if ($invalidSchoolId !== null) {
            abort(response()->json([
                'success' => false,
                'message' => 'One or more selected schools are invalid.',
                'errors' => [
                    'school_ids.0' => ['One or more selected schools are invalid.'],
                ],
            ], 422));
        }
    }

    private function schoolIdsToStorage(array $schoolIds): ?string
    {
        $normalized = collect($schoolIds)
            ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
            ->filter(fn ($id) => ! is_null($id) && $id > 0)
            ->unique()
            ->values()
            ->all();

        return empty($normalized) ? null : implode(',', $normalized);
    }

    private function packageSchoolIdSupportsMultiple(): bool
    {
        if (! Schema::hasTable('package_details') || ! Schema::hasColumn('package_details', 'school_id')) {
            return false;
        }

        try {
            $databaseName = DB::getDatabaseName();
            $column = DB::table('information_schema.COLUMNS')
                ->select('DATA_TYPE')
                ->where('TABLE_SCHEMA', $databaseName)
                ->where('TABLE_NAME', 'package_details')
                ->where('COLUMN_NAME', 'school_id')
                ->first();

            $dataType = strtolower((string) ($column->DATA_TYPE ?? ''));
            return in_array($dataType, ['char', 'varchar', 'text', 'mediumtext', 'longtext'], true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function resolveSchoolIdForSchoolUser(Request $request): ?int
    {
        return $this->isSchoolActor($request)
            ? $this->resolveSchoolIdFromContext($request)
            : null;
    }

    private function packageAccessQuery(?Request $request = null)
    {
        $request = $request ?: request();
        $query = PackageDetail::query();

        if ($this->isPrivilegedActor($request)) {
            return $query;
        }

        $schoolId = $this->resolveSchoolIdForSchoolUser($request);
        if ($schoolId) {
            $query->where(function ($schoolScopedQuery) use ($schoolId) {
                $schoolScopedQuery->whereRaw('FIND_IN_SET(?, school_id)', [(string) $schoolId]);

                if (Schema::hasColumn('package_details', 'school_id')) {
                    $this->applyGlobalSchoolPackageScope($schoolScopedQuery);
                }
            });

            return $query;
        }

        $actorUserId = $this->resolveActorUserId($request);
        if (! $actorUserId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('user_id', $actorUserId);
    }

    /**
     * Display package details listing page.
     * created by ns
     */
    public function index()
    {
        return view('package_details.index');
    }

    /**
     * Display package details create form.
     * created by ns
     */
    public function create()
    {
        $request = request();
        $isSchoolUser = $this->isSchoolActor($request);
        $defaultSchoolId = $this->resolveSchoolIdForSchoolUser($request);
        $schoolDataQuery = School::query()
            ->select('id', 'school_name')
            ->where('deleted', 0)
            ->orderBy('school_name');

        if ($isSchoolUser && $defaultSchoolId) {
            $schoolDataQuery->where('id', $defaultSchoolId);
        }

        $schoolData = $schoolDataQuery->get();
        $defaultSchoolName = $defaultSchoolId
            ? (string) School::query()->where('id', $defaultSchoolId)->value('school_name')
            : null;

        return view('package_details.create', compact('schoolData', 'isSchoolUser', 'defaultSchoolId', 'defaultSchoolName'));
    }

    /**
     * Store package details data.
     * created by ns
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id'         => 'nullable|exists:schools,id',
            'school_ids'        => 'nullable|array|min:1',
            'package_name'      => 'required|string|max:255',
            'package_type'      => 'required|string|max:255',
            'booking_type'      => 'required|string|max:255',
            'price'             => 'required|string|max:50',
            'validity_days'     => 'required|integer|min:1',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
        ]);
        $this->validateSchoolSelection($request);
        $basePayload = collect($validated)
            ->except(['school_id', 'school_ids'])
            ->toArray();
        $basePayload['user_id'] = $this->resolveActorUserId($request);
        $basePayload['status'] = 0;

        if ($this->isSchoolActor($request)) {
            $basePayload['school_id'] = (string) $this->resolveSchoolIdForSchoolUser($request);
            PackageDetail::create($basePayload);

            return response()->json([
                'success' => true,
                'message' => 'Package Details created successfully',
            ]);
        }

        $selectedSchoolIds = $this->normalizeSelectedSchoolIds($request);
        if (empty($selectedSchoolIds)) {
            if (! $this->requestTargetsAllSchools($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one school.',
                ], 422);
            }
        }

        if (count($selectedSchoolIds) > 1 && ! $this->packageSchoolIdSupportsMultiple()) {
            return response()->json([
                'success' => false,
                'message' => 'Multiple schools in a single package require the package_details.school_id column to be text/varchar on this server. Right now live DB still has integer type.',
            ], 422);
        }

        $basePayload['school_id'] = $this->requestTargetsAllSchools($request)
            ? self::ALL_SCHOOLS_STORAGE
            : $this->schoolIdsToStorage($selectedSchoolIds);

        PackageDetail::create($basePayload);

        return response()->json([
            'success' => true,
            'message' => 'Package Details created successfully',
        ]);
    }

    /**
     * Display package details edit form.
     * created by ns
     */
    public function edit($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $package = $this->packageAccessQuery(request())->findOrFail($id);

        $request = request();
        $isSchoolUser = $this->isSchoolActor($request);
        $defaultSchoolId = $this->isGlobalSchoolStorageValue($package->school_id)
            ? null
            : (int) ($package->school_id ?: $this->resolveSchoolIdForSchoolUser($request));
        $schoolDataQuery = School::query()
            ->select('id', 'school_name')
            ->where('deleted', 0)
            ->orderBy('school_name');

        if ($isSchoolUser && $defaultSchoolId) {
            $schoolDataQuery->where('id', $defaultSchoolId);
        }

        $schoolData = $schoolDataQuery->get();
        $defaultSchoolName = $defaultSchoolId
            ? (string) School::query()->where('id', $defaultSchoolId)->value('school_name')
            : null;

        return view('package_details.edit', compact('package', 'schoolData', 'isSchoolUser', 'defaultSchoolId', 'defaultSchoolName'));
    }

    /**
     * Update package details data.
     * created by ns
     */
    public function update(Request $request, $schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $package = $this->packageAccessQuery($request)->findOrFail($id);

        $validated = $request->validate([
            'school_id'          => 'nullable|exists:schools,id',
            'school_ids'         => 'nullable|array|min:1',
            'package_name'      => 'required|string|max:255',
            'package_type'      => 'required|string|max:255',
            'booking_type'      => 'required|string|max:255',
            'price'             => 'required|string|max:50',
            'validity_days'     => 'required|integer|min:1',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
        ]);
        $this->validateSchoolSelection($request);

        $payload = collect($validated)
            ->except(['school_id', 'school_ids'])
            ->toArray();

        if ($this->isSchoolActor($request)) {
            $schoolId = $this->resolveSchoolIdForSchoolUser($request);
            $payload['school_id'] = $schoolId ? (string) $schoolId : null;
        } else {
            $selectedSchoolIds = $this->normalizeSelectedSchoolIds($request);
            if (empty($selectedSchoolIds)) {
                if (! $this->requestTargetsAllSchools($request)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please select at least one school.',
                    ], 422);
                }
            }

            if (count($selectedSchoolIds) > 1 && ! $this->packageSchoolIdSupportsMultiple()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Multiple schools in a single package require the package_details.school_id column to be text/varchar on this server. Right now live DB still has integer type.',
                ], 422);
            }

            $payload['school_id'] = $this->requestTargetsAllSchools($request)
                ? self::ALL_SCHOOLS_STORAGE
                : $this->schoolIdsToStorage($selectedSchoolIds);
        }

        $package->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Package Details updated successfully',
        ]);
    }

    /**
     * Soft delete package detail record.
     * created by ns
     */
    public function destroy($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $packageDetail          = $this->packageAccessQuery(request())->findOrFail($id);
        $packageDetail->deleted = 1;
        $packageDetail->save();

        return response()->json([
            'success' => true,
            'message' => 'Package Detail deleted Successfully.',
        ]);
    }

    /**
     * Toggle package detail active/inactive status.
     * created by ns
     */
    public function toggleStatus($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $packageDetail         = $this->packageAccessQuery(request())->findOrFail($id);
        $packageDetail->status = $packageDetail->status == 1 ? 0 : 1;
        $packageDetail->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active package details count.
     * created by ns
     */
    public function getActiveCount()
    {
        $query = PackageDetail::where('deleted', 0)
            ->where('status', true);
        $query->whereIn('id', $this->packageAccessQuery(request())->where('deleted', 0)->where('status', true)->select('id'));
        $activeCount = $query->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Fetch package details list for DataTable.
     * created by ns
     */
    public function packageDetailsList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, 'id');

        $allowedColumns = [
            'id',
            'school_name',
            'package_name',
            'package_type',
            'booking_type',
            'price',
            'validity_days',
            'short_description',
            'description',
            'status',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';
        if ($columnName === 'school_name') {
            $columnName = Schema::hasColumn('package_details', 'school_id') ? 'school_id' : 'user_id';
        }

        $columnSortOrder = in_array(
            $request->input('sSortDir_0'),
            ['asc', 'desc']
        ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        $query = $this->packageAccessQuery($request)->where('deleted', 0);
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $matchingSchoolReferences = $this->resolveSchoolSearchIds($searchValue);

            $query->where(function ($q) use ($searchValue, $matchingSchoolReferences) {
                $q->where('package_name', 'like', "%$searchValue%")
                    ->orWhere('package_type', 'like', "%$searchValue%")
                    ->orWhere('booking_type', 'like', "%$searchValue%")
                    ->orWhere('price', 'like', "%$searchValue%")
                    ->orWhere('validity_days', 'like', "%$searchValue%")
                    ->orWhere('short_description', 'like', "%$searchValue%")
                    ->orWhere('description', 'like', "%$searchValue%");

                if (! empty($matchingSchoolReferences['school_ids'])) {
                    foreach ($matchingSchoolReferences['school_ids'] as $matchingSchoolId) {
                        if (Schema::hasColumn('package_details', 'school_id')) {
                            $q->orWhereRaw('FIND_IN_SET(?, school_id)', [(string) $matchingSchoolId]);
                        }
                    }
                }

                if (! empty($matchingSchoolReferences['user_ids'])) {
                    $q->orWhereIn('user_id', $matchingSchoolReferences['user_ids']);
                }
            });
        }

        $totalRecordwithFilter = (clone $query)->count();

        $packageDetails = $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        $data = [];
        $packageSchoolIds = [];
        if (Schema::hasColumn('package_details', 'school_id')) {
            foreach ($packageDetails as $package) {
                $packageSchoolIds = array_merge($packageSchoolIds, $this->selectedSchoolIdsForPackage($package));
            }
        }
        $schoolNamesBySchoolId = ! empty($packageSchoolIds)
            ? $this->getSchoolNameMapForSchoolIds($packageSchoolIds)
            : [];
        $schoolNamesByUserId = $this->getSchoolNameMapForUserIds($packageDetails->pluck('user_id')->all());

        foreach ($packageDetails as $package) {
            $packageSchoolIds = $this->selectedSchoolIdsForPackage($package);
            $packageSchoolNames = collect($packageSchoolIds)
                ->map(fn ($schoolId) => $schoolNamesBySchoolId[(int) $schoolId] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $data[] = [
                'id'                => $package->id,
                'school_name'       => ! empty($packageSchoolNames)
                    ? implode(', ', $packageSchoolNames)
                    : ($schoolNamesBySchoolId[(int) ($package->school_id ?? 0)]
                        ?? $schoolNamesByUserId[(int) ($package->user_id ?? 0)]
                        ?? 'All Schools'),
                'package_name'      => $package->package_name,
                'package_type'      => $package->package_type,
                'booking_type'      => $package->booking_type,
                'price'             => $package->price,
                'validity_days'     => $package->validity_days,
                'short_description' => $package->short_description,
                'description'       => $package->description,
                'status'            => $package->status,
            ];
        }

        return response()->json([
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
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

        $query = $this->packageAccessQuery($request)->whereIn('id', $ids);
        $query->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected routes deleted successfully',
        ]);
    }
}

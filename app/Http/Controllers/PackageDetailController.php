<?php
namespace App\Http\Controllers;

use App\Models\PackageDetail;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PackageDetailController extends Controller
{
    private function resolveSchoolIdForSchoolUser(Request $request): ?int
    {
        return $this->isSchoolActor($request)
            ? $this->resolveSchoolIdFromContext($request)
            : null;
    }

    private function packageAccessQuery(?Request $request = null)
    {
        $query = PackageDetail::query();
        $this->applyActorScope($query, $request ?: request());

        return $query;
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
            'school_id'          => 'nullable|exists:schools,id',
            'package_name'      => 'required|string|max:255',
            'package_type'      => 'required|string|max:255',
            'booking_type'      => 'required|string|max:255',
            'price'             => 'required|string|max:50',
            'validity_days'     => 'required|integer|min:1',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
        ]);
        $validated['user_id'] = $this->resolveActorUserId($request);
        $validated['school_id'] = $this->isSchoolActor($request)
            ? $this->resolveSchoolIdForSchoolUser($request)
            : ((int) $request->input('school_id') > 0 ? (int) $request->input('school_id') : null);
        $validated['status'] = 1;

        PackageDetail::create($validated);

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
        $defaultSchoolId = (int) ($package->school_id ?: $this->resolveSchoolIdForSchoolUser($request));
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
            'package_name'      => 'required|string|max:255',
            'package_type'      => 'required|string|max:255',
            'booking_type'      => 'required|string|max:255',
            'price'             => 'required|string|max:50',
            'validity_days'     => 'required|integer|min:1',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
        ]);

        $validated['school_id'] = $this->isSchoolActor($request)
            ? $this->resolveSchoolIdForSchoolUser($request)
            : ((int) $request->input('school_id') > 0 ? (int) $request->input('school_id') : null);
        $package->update($validated);

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
        $this->applyActorScope($query, request());
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

        $query = PackageDetail::query()->where('deleted', 0);
        $this->applyActorScope($query, $request);
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

                if (! empty($matchingSchoolReferences['school_ids']) && Schema::hasColumn('package_details', 'school_id')) {
                    $q->orWhereIn('school_id', $matchingSchoolReferences['school_ids']);
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
        $schoolNamesBySchoolId = Schema::hasColumn('package_details', 'school_id')
            ? $this->getSchoolNameMapForSchoolIds($packageDetails->pluck('school_id')->all())
            : [];
        $schoolNamesByUserId = $this->getSchoolNameMapForUserIds($packageDetails->pluck('user_id')->all());

        foreach ($packageDetails as $package) {
            $data[] = [
                'id'                => $package->id,
                'school_name'       => $schoolNamesBySchoolId[(int) ($package->school_id ?? 0)]
                    ?? $schoolNamesByUserId[(int) ($package->user_id ?? 0)]
                    ?? 'All Schools',
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

        $query = PackageDetail::query()->whereIn('id', $ids);
        $this->applyActorScope($query, $request);
        $query->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected routes deleted successfully',
        ]);
    }
}

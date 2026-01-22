<?php
namespace App\Http\Controllers;

use App\Models\PackageDetail;
use Illuminate\Http\Request;

class PackageDetailController extends Controller
{
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
        return view('package_details.create');
    }

    /**
     * Store package details data.
     * created by ns
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_name'      => 'required|string|max:255',
            'package_type'      => 'required|string|max:255',
            'booking_type'      => 'required|string|max:255',
            'price'             => 'required|string|max:50',
            'validity_days'     => 'required|integer',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
        ]);

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
    public function edit($id)
    {
        $package = PackageDetail::findOrFail($id);
        return view('package_details.edit', compact('package'));
    }

    /**
     * Update package details data.
     * created by ns
     */
    public function update(Request $request, $id)
    {
        $package = PackageDetail::findOrFail($id);

        $validated = $request->validate([
            'package_name'      => 'required|string|max:255',
            'package_type'      => 'required|string|max:255',
            'booking_type'      => 'required|string|max:255',
            'price'             => 'required|string|max:50',
            'validity_days'     => 'required|integer',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
        ]);

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
    public function destroy($id)
    {
        $packageDetail          = PackageDetail::findOrFail($id);
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
    public function toggleStatus($id)
    {
        $packageDetail         = PackageDetail::findOrFail($id);
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
        $activeCount = PackageDetail::where('deleted', 0)
            ->where('status', true)
            ->count();

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

        $columnSortOrder = in_array(
            $request->input('sSortDir_0'),
            ['asc', 'desc']
        ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        $packageDetails = PackageDetail::getPackageData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $draw,
            $row,
            $rowperpage
        );

        $totalRecords          = PackageDetail::where('deleted', 0)->count();
        $totalRecordwithFilter = PackageDetail::getPackageDataTotal($searchValue);

        $data = [];

        foreach ($packageDetails as $package) {
            $data[] = [
                'id'                => $package->id,
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

        if (!is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided',
            ]);
        }

        PackageDetail::whereIn('id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected routes deleted successfully',
        ]);
    }
}

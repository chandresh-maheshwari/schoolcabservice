<?php
namespace App\Http\Controllers;

use App\Models\PackageDetail;
use Illuminate\Http\Request;

class PackageDetailController extends Controller
{
    public function index()
    {
        return view('package_details.index');
    }

    public function create()
    {
        return view('package_details.create');
    }

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

    public function edit($id)
    {
        $package = PackageDetail::findOrFail($id);
        return view('package_details.edit', compact('package'));
    }

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
    public function destroy($id)
    {
        $packageDetail          = PackageDetail::findOrFail($id);
        $packageDetail->deleted = 1;
        $packageDetail->save();

        return response()->json(['success' => true, 'message' => 'Package Detail deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $packageDetail         = PackageDetail::findOrFail($id);
        $packageDetail->status = $packageDetail->status == 1 ? 0 : 1;
        $packageDetail->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function getActiveCount()
    {
        $activeCount = PackageDetail::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    public function packageDetailsList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, '_id');

        $allowedColumns = [
            '_id',
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
            : '_id';

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
                'id'                => (string) $package->_id,
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
}

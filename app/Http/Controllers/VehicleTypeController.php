<?php
namespace App\Http\Controllers;

use App\Models\VehicleType;
use Illuminate\Http\Request;

class VehicleTypeController extends Controller
{
    public function index()
    {
        return view('vehicle_type.index');
    }

    public function create()
    {
        return view('vehicle_type.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_type' => 'required|string|max:255',

        ]);
        $data        = $request->all();
        $vehicleType = VehicleType::create([
            'vehicle_type' => $data,
            'status'       => 0,
            'deleted'      => 0,
        ]);
        $vehicleType->update($data);
        return response()->json(['success' => true, 'message' => 'Vehicle Type created Successfully.']);
    }

    public function edit($id)
    {
        $vehicleType = VehicleType::findOrFail($id);
        return view('vehicle_type.edit', compact('vehicleType'));
    }

    public function update(Request $request, $id)
    {
        $vehicleType = VehicleType::findOrFail($id);

        $data = $request->all();

        $vehicleType->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle Type updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $vehicleType          = VehicleType::findOrFail($id);
        $vehicleType->deleted = 1;
        $vehicleType->save();

        return response()->json(['success' => true, 'message' => 'Vehicle Type deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $vehicleType         = VehicleType::findOrFail($id);
        $vehicleType->status = $vehicleType->status == 1 ? 0 : 1;
        $vehicleType->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function getActiveCount()
    {
        $activeCount = VehicleType::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided.']);
        }
        VehicleType::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected id deleted Successfully.']);
    }

    // public function vehicleTypeList(Request $request)
    // {
    //     $draw        = $request->input('sEcho');
    //     $row         = $request->input('iDisplayStart');
    //     $rowperpage  = $request->input('iDisplayLength');
    //     $indexColumn = $request->input('iSortCol_0');
    //     $columnName  = $request->input('mDataProp_' . $indexColumn);

    //     if (! in_array($columnName, ['id', 'vehicle_type', 'status'])) {
    //         $columnName = 'id';
    //     }

    //     $columnSortOrder = $request->input('sSortDir_0');
    //     $searchValue     = $request->input('sSearch');

    //     $vehicleTypeDetails    = VehicleType::getVehicleTypeData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
    //     $totalRecords          = VehicleType::count();
    //     $totalRecordwithFilter = VehicleType::getVehicleTypeDataTotal($searchValue);

    //     $data = [];
    //     foreach ($vehicleTypeDetails as $vehicleType) {
    //         $data[] = [
    //             'id'           => $vehicleType->id,
    //             'vehicle_type' => $vehicleType->vehicle_type ?? '-',
    //             'status'       => $vehicleType->status,
    //         ];
    //     }

    //     $output = [
    //         "draw"            => intval($draw),
    //         "recordsTotal"    => $totalRecords,
    //         "recordsFiltered" => $totalRecordwithFilter,
    //         "data"            => $data,
    //     ];

    //     return response()->json($output);
    // }

    public function vehicleTypeList(Request $request)
    {
        $draw        = intval($request->input('sEcho'));
        $row         = intval($request->input('iDisplayStart'));
        $rowperpage  = intval($request->input('iDisplayLength'));
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        // ✅ MongoDB column whitelist
        $allowedColumns = ['_id', 'vehicle_type', 'status'];
        if (! in_array($columnName, $allowedColumns)) {
            $columnName = 'vehicle_type';
        }

        $columnSortOrder = $request->input('sSortDir_0', 'asc');
        $searchValue     = $request->input('sSearch');

//     dd(
//     VehicleType::count(),
//     VehicleType::where(function ($q) {
//         $q->where('deleted', 0)->orWhereNull('deleted');
//     })
// );
        // ✅ Data
        $vehicleTypeDetails = VehicleType::getVehicleTypeData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $draw,
            $row,
            $rowperpage
        );

        // ✅ Total records
        $totalRecords = VehicleType::where('deleted', 0)->count();

        // ✅ Filtered count
        $totalRecordwithFilter = VehicleType::getVehicleTypeDataTotal($searchValue);

        // ✅ Format response
        // dd($vehicleTypeDetails);
        $data = [];
        foreach ($vehicleTypeDetails as $vehicleType) {
            $data[] = [
                'id'           => (string) $vehicleType->_id, // MongoDB ObjectId → string
                'vehicle_type' => $vehicleType->vehicle_type ?? '-',
                'status'       => $vehicleType->status,
            ];
        }

        return response()->json([
            "draw"            => $draw,
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ]);
    }
}

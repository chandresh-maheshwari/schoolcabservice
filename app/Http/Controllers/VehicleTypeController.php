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

        $data = $request->all();

        $vehicleType = VehicleType::create([
            'vehicle_type' => $data,
            'status'       => 0,
            'deleted'      => 0,
        ]);

        $vehicleType->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle Type created Successfully.',
        ]);
    }

    /**
     * Display vehicle type edit form.
     * created by ns
     */
    public function edit($id)
    {
        $vehicleType = VehicleType::findOrFail($id);
        return view('vehicle_type.edit', compact('vehicleType'));
    }

    /**
     * Update vehicle type data.
     * created by ns
     */
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

    /**
     * Soft delete vehicle type record.
     * created by ns
     */
    public function destroy($id)
    {
        $vehicleType          = VehicleType::findOrFail($id);
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
    public function toggleStatus($id)
    {
        $vehicleType         = VehicleType::findOrFail($id);
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
        $activeCount = VehicleType::where('deleted', 0)
            ->where('status', true)
            ->count();

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

        VehicleType::whereIn('_id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected id deleted Successfully.',
        ]);
    }

    /**
     * Fetch vehicle type list for DataTable.
     * created by ns
     */
    public function vehicleTypeList(Request $request)
    {
        $draw        = intval($request->input('sEcho'));
        $row         = intval($request->input('iDisplayStart'));
        $rowperpage  = intval($request->input('iDisplayLength'));
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        $allowedColumns = ['_id', 'vehicle_type', 'status'];
        if (! in_array($columnName, $allowedColumns)) {
            $columnName = 'vehicle_type';
        }

        $columnSortOrder = $request->input('sSortDir_0', 'asc');
        $searchValue     = $request->input('sSearch');

        $vehicleTypeDetails = VehicleType::getVehicleTypeData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $draw,
            $row,
            $rowperpage
        );

        $totalRecords          = VehicleType::where('deleted', 0)->count();
        $totalRecordwithFilter = VehicleType::getVehicleTypeDataTotal($searchValue);

        $data = [];
        foreach ($vehicleTypeDetails as $vehicleType) {
            $data[] = [
                'id'           => (string) $vehicleType->_id,
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

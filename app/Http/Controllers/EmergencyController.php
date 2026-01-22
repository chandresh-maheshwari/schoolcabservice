<?php
namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Emergency;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class EmergencyController extends Controller
{
    /**
     * Display emergency listing page.
     * created by ns
     */
    public function index()
    {
        return view('emergency.index');
    }

    /**
     * Fetch emergency list for DataTable.
     * created by ns
     */
    public function emergencyList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, '_id');

        $allowedColumns = [
            'id',
            'driver_id',
            'vehicle_number',
            'reported_by',
            'emergency_type',
            'contact_number',
            'description',
            'status',
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

        $emergencyDetails = Emergency::getEmergencyData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $draw,
            $row,
            $rowperpage
        );

        $totalRecords          = Emergency::where('deleted', 0)->count();
        $totalRecordwithFilter = Emergency::getEmergencyDataTotal($searchValue);

        $data = [];

        foreach ($emergencyDetails as $emergency) {
            $data[] = [
                'id'             => $emergency->id,
               'driver_name'    => optional($emergency->driver)->driver_name,
               'vehicle_number' => optional($emergency->vehicle)->vehicle_number,
                'reported_by'    => $emergency->reported_by,
                'emergency_type' => $emergency->emergency_type,
                'contact_number' => $emergency->contact_number,
                'description'    => $emergency->description,
                'status'         => $emergency->status,
            ];
        }

        return response()->json([
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ]);
    }

    /**
     * Display emergency create form.
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

        return view('emergency.create', compact('drivers', 'vehicles'));
    }

    /**
     * Store emergency data.
     * created by ns
     */
    public function store(Request $request)
    {

        $request->validate([
            'reported_by'    => 'required|in:parent,admin',
            'emergency_type' => 'required|string|max:100',
            'description'    => 'required|string|max:1000',
            'contact_number' => 'required|string|max:20',
        ]);

        Emergency::create([
            'driver_id'    => $request->driver_name,
            'vehicle_id' => $request->vehicle_number,
            'reported_by'    => $request->reported_by,
            'emergency_type' => $request->emergency_type,
            'description'    => $request->description,
            'contact_number' => $request->contact_number,
            'status'         => 0,
            'deleted'        => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Emergency created successfully',
        ]);
    }

    /**
     * Display emergency edit form.
     * created by ns
     */
    public function edit($id)
    {
        $emergency = Emergency::findOrFail($id);

        $drivers = Driver::where('deleted', 0)
            ->select('id','driver_name')
            ->get();

        $vehicles = Vehicle::where('deleted', 0)
            ->select('id','vehicle_number')
            ->get();

        return view('emergency.edit', compact('emergency', 'drivers', 'vehicles'));
    }

    /**
     * Update emergency data.
     * created by ns
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'driver_id'    => 'required|exists:drivers,driver_name',
            'vehicle_id' => 'required|exists:vehicles,vehicle_number',
            'reported_by'    => 'required|in:parent,admin',
            'emergency_type' => 'required|string|max:100',
            'description'    => 'required|string|max:1000',
            'contact_number' => 'required|string|max:20',
        ]);

        $emergency = Emergency::findOrFail($id);

        $emergency->update([
            'driver_id'    => $request->driver_name,
            'vehicle_id' => $request->vehicle_name,
            'reported_by'    => $request->reported_by,
            'emergency_type' => $request->emergency_type,
            'description'    => $request->description,
            'contact_number' => $request->contact_number,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Emergency updated successfully',
        ]);
    }

    /**
     * Soft delete emergency record.
     * created by ns
     */
    public function destroy($id)
    {
        $emergency          = Emergency::findOrFail($id);
        $emergency->deleted = 1;
        $emergency->save();

        return response()->json([
            'success' => true,
            'message' => 'Emergency deleted Successfully.',
        ]);
    }

    /**
     * Toggle emergency active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $emergency         = Emergency::findOrFail($id);
        $emergency->status = $emergency->status == 1 ? 0 : 1;
        $emergency->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active emergency count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = Emergency::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
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

        Emergency::whereIn('id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected routes deleted successfully',
        ]);
    }
}

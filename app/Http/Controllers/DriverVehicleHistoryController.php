<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DriverVehicleHistory;

class DriverVehicleHistoryController extends Controller
{
    public function index()
    {
        return view('driver_history.index');
    }

    /**
     * Fetch driver vehicle history list for DataTable.
     * created by ns
     */
    public function driverHistoryList(Request $request)
    {
        $draw        = intval($request->input('sEcho'));
        $row         = intval($request->input('iDisplayStart'));
        $rowperpage  = intval($request->input('iDisplayLength'));
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        $allowedColumns = ['id', 'driver_name', 'vehicle_number','is_assigned'];
        if (! in_array($columnName, $allowedColumns)) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0', 'asc');
        $searchValue     = $request->input('sSearch');

        $driverHistoryDetails = DriverVehicleHistory::getDriverVehicleHistoryData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $draw,
            $row,
            $rowperpage
        );

        $totalRecords          = DriverVehicleHistory::count();
        $totalRecordwithFilter = DriverVehicleHistory::getDriverVehicleHistoryDataTotal($searchValue);

        $data = [];
        foreach ($driverHistoryDetails as $driverHistory) {
            $data[] = [
                'id'           => $driverHistory->id,
                'driver_name'    => optional($driverHistory->driver)->driver_name,
               'vehicle_number' => optional($driverHistory->vehicle)->vehicle_number,
                'is_assigned' => $driverHistory->is_assigned,
            ];
        }

        return response()->json([
            "draw"            => $draw,
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ]);
    }

    /**
     * Delete driver vehicle history record.
     */
     public function destroy($id)
    {
        $driverHistory  = DriverVehicleHistory::findOrFail($id);
        $driverHistory->deleted = 1;
        $driverHistory->save();

        return response()->json(['success' => true, 'message' => 'Driver History deleted Successfully.']);
    }

    /**
     * Multi delete driver vehicle history records.
     */
    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided for deletion',
            ]);
        }

        DriverVehicleHistory::whereIn('id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected driver history deleted successfully',
        ]);
    }
}

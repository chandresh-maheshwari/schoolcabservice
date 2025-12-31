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

    public function driverHistoryList(Request $request)
    {
        $draw        = intval($request->input('sEcho'));
        $row         = intval($request->input('iDisplayStart'));
        $rowperpage  = intval($request->input('iDisplayLength'));
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        $allowedColumns = ['_id', 'driver_name', 'vehicle_number','is_assigned'];
        if (! in_array($columnName, $allowedColumns)) {
            $columnName = '_id';
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
                'id'           => (string) $driverHistory->_id,
                'driver_name' => $driverHistory->driver_name ?? '-',
                'vehicle_number' => $driverHistory->vehicle_number ?? '-',
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

     public function destroy($id)
    {
        $driverHistory  = DriverVehicleHistory::findOrFail($id);
        $driverHistory->deleted = 1;
        $driverHistory->save();

        return response()->json(['success' => true, 'message' => 'Driver History deleted Successfully.']);
    }
}

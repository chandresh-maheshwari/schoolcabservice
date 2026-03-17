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
        $indexColumn = (int) $request->input('iSortCol_0', 0);
        $columnKey   = $request->input('mDataProp_' . $indexColumn, 'id');

        $sortableKeys = [
            'id',
            'school_name',
            'driver_name',
            'vehicle_number',
            'is_assigned',
        ];

        $columnKey = in_array($columnKey, $sortableKeys, true) ? $columnKey : 'id';

        $columnSortOrder = $request->input('sSortDir_0', 'asc');
        $searchValue     = $request->input('sSearch');

        $query = DriverVehicleHistory::query()
            ->with(['driver', 'vehicle'])
            ->where(function ($q) {
                $q->where('driver_vehicle_histories.deleted', 0)
                    ->orWhereNull('driver_vehicle_histories.deleted');
            });

        if ($columnKey === 'driver_name') {
            $query->leftJoin('drivers', 'driver_vehicle_histories.driver_id', '=', 'drivers.id');
        } elseif ($columnKey === 'vehicle_number') {
            $query->leftJoin('vehicles', 'driver_vehicle_histories.vehicle_id', '=', 'vehicles.id');
        } elseif ($columnKey === 'school_name') {
            $query->leftJoin('schools', function ($join) {
                $join->on('driver_vehicle_histories.user_id', '=', 'schools.user_id')
                    ->where('schools.deleted', 0);
            });
        }

        $query->select('driver_vehicle_histories.*');
        $this->applyActorScope($query, $request, 'driver_vehicle_histories.user_id');
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('is_assigned', 'like', "%$searchValue%");

                // Keep relation-search grouped to avoid bypassing actor scope via top-level ORs.
                $q->orWhereHas('driver', function ($driverQuery) use ($searchValue) {
                    $driverQuery->where('driver_name', 'like', "%$searchValue%");
                })->orWhereHas('vehicle', function ($vehicleQuery) use ($searchValue) {
                    $vehicleQuery->where('vehicle_number', 'like', "%$searchValue%");
                });
            });
        }

        $totalRecordwithFilter = (clone $query)->count();

        $sortColumnMap = [
            'id' => 'driver_vehicle_histories.id',
            'is_assigned' => 'driver_vehicle_histories.is_assigned',
            'driver_name' => 'drivers.driver_name',
            'vehicle_number' => 'vehicles.vehicle_number',
            'school_name' => 'schools.school_name',
        ];

        $sortColumn = $sortColumnMap[$columnKey] ?? 'driver_vehicle_histories.id';

        $driverHistoryDetails = $query
            ->orderBy($sortColumn, in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'desc')
            ->skip($row)
            ->take($rowperpage)
            ->get();

        $data = [];
        $schoolNameMap = $this->getSchoolNameMapForUserIds($driverHistoryDetails->pluck('user_id')->all());
        foreach ($driverHistoryDetails as $driverHistory) {
            $data[] = [
                'id'           => $driverHistory->id,
                'school_name'  => $schoolNameMap[$driverHistory->user_id] ?? '-',
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
     public function destroy($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);

        $query = DriverVehicleHistory::query();
        $this->applyActorScope($query, request(), 'user_id');
        $driverHistory = $query->findOrFail($id);

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

        $query = DriverVehicleHistory::whereIn('id', $ids);
        $this->applyActorScope($query, $request, 'user_id');
        $query->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected driver history deleted successfully',
        ]);
    }
}

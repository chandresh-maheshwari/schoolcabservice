<?php
namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\StopPickup;
use Illuminate\Http\Request;

class StopPickupController extends Controller
{
    /**
     * Display stop & pickup listing page.
     * created by ns
     */
    public function index()
    {
        return view('stop_pickup.index');
    }

    /**
     * Display stop & pickup create form.
     * created by ns
     */
    public function create()
    {
        $routeData = Route::where('deleted', 0)
            ->select('_id', 'name')
            ->get();

        return view('stop_pickup.create', compact('routeData'));
    }

    /**
     * Store stop & pickup data.
     * created by ns
     */
    public function store(Request $request)
    {
        $request->validate([
            'route_id'       => 'required',
            'pickup_name'    => 'required|string|max:255',
            'stop_name'      => 'required|string|max:255',
            'latitude'       => 'required',
            'longitude'      => 'required',
            'sequence_order' => 'required|integer',
        ]);

        $routeData = Route::where('_id', $request->route_id)
            ->where('deleted', 0)
            ->first();

        if (! $routeData) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid route selected',
            ], 422);
        }

        StopPickup::create([
            'name'           => $routeData->name,
            'pickup_name'    => $request->pickup_name,
            'stop_name'      => $request->stop_name,
            'latitude'       => $request->latitude,
            'longitude'      => $request->longitude,
            'sequence_order' => $request->sequence_order,
            'status'         => 0,
            'deleted'        => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stop And Pickup Point created successfully',
        ]);
    }

    /**
     * Display stop & pickup edit form.
     * created by ns
     */
    public function edit($id)
    {
        $stopPickup = StopPickup::where('_id', $id)
            ->where('deleted', 0)
            ->firstOrFail();

        $routeData = Route::where('deleted', 0)
            ->select('_id', 'name')
            ->get();

        return view('stop_pickup.edit', compact('stopPickup', 'routeData'));
    }

    /**
     * Update stop & pickup data.
     * created by ns
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'route_id'       => 'required',
            'pickup_name'    => 'required|string|max:255',
            'stop_name'      => 'required|string|max:255',
            'latitude'       => 'required',
            'longitude'      => 'required',
            'sequence_order' => 'required|integer',
        ]);

        $routeData = Route::where('_id', $request->route_id)
            ->where('deleted', 0)
            ->first();

        if (! $routeData) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid route selected',
            ], 422);
        }

        $stopPickup = StopPickup::where('_id', $id)
            ->where('deleted', 0)
            ->firstOrFail();

        $stopPickup->update([
            'name'           => $routeData->name,
            'pickup_name'    => $request->pickup_name,
            'stop_name'      => $request->stop_name,
            'latitude'       => $request->latitude,
            'longitude'      => $request->longitude,
            'sequence_order' => $request->sequence_order,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stop And Pickup Point updated successfully',
        ]);
    }

    /**
     * Soft delete stop and pickup record.
     * created by ns
     */
    public function destroy($id)
    {
        $stopPickup          = StopPickup::findOrFail($id);
        $stopPickup->deleted = 1;
        $stopPickup->save();

        return response()->json([
            'success' => true,
            'message' => 'Stop And Pickup Point deleted Successfully.',
        ]);
    }

    /**
     * Toggle Stop And Pickup  active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $stopPickup         = StopPickup::findOrFail($id);
        $stopPickup->status = $stopPickup->status == 1 ? 0 : 1;
        $stopPickup->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active Stop And Pickup count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = StopPickup::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Fetch stop & pickup list for DataTable.
     * created by ns
     */
    public function stopPickupList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, '_id');

        $allowedColumns = [
            '_id',
            'name',
            'pickup_name',
            'stop_name',
            'latitude',
            'longitude',
            'sequence_order',
            'status',
            'deleted',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : '_id';

        $columnSortOrder = in_array(
            $request->input('sSortDir_0'),
            ['asc', 'desc']
        ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        $stopPickupDetails = StopPickup::getStopData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $draw,
            $row,
            $rowperpage
        );

        $totalRecords          = StopPickup::where('deleted', 0)->count();
        $totalRecordwithFilter = StopPickup::getStopDataTotal($searchValue);

        $data = [];

        foreach ($stopPickupDetails as $stopPickup) {
            $data[] = [
                'id'             => (string) $stopPickup->_id,
                'name'           => $stopPickup->name,
                'pickup_name'    => $stopPickup->pickup_name,
                'stop_name'      => $stopPickup->stop_name,
                'latitude'       => $stopPickup->latitude,
                'longitude'      => $stopPickup->longitude,
                'sequence_order' => $stopPickup->sequence_order,
                'status'         => $stopPickup->status,
            ];
        }

        return response()->json([
            'draw'                 => intval($draw),
            'iTotalRecords'        => $totalRecords,
            'iTotalDisplayRecords' => $totalRecordwithFilter,
            'aaData'               => $data,
        ]);
    }

    /**
     * Multi delete stop & pickup records.
     * created by ns
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

        StopPickup::whereIn('_id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected stop and pickup points deleted successfully',
        ]);
    }
}

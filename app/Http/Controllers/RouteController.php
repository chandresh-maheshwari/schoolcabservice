<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Vehicle;
use App\Models\Driver;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index()
    {
        return view('routes.index');
    }

    public function create()
    {

        $buses   = Vehicle::where('deleted', 0)->get();
        $drivers = Driver::where('deleted', 0)->get();

        return view('routes.create', compact('buses', 'drivers'));
    }

   public function store(Request $request)
{
    $request->validate([
        'name'      => 'required|string|max:255',
        'bus_id'    => 'required',
        'driver_id' => 'required',
        'geojson'   => 'required|json',
        'stops'     => 'required|json',
    ]);

    try {
        Route::create([
            'name'       => $request->name,
            'bus_id'     => $request->bus_id,
            'driver_id'  => $request->driver_id,
            'geojson'   => json_decode($request->geojson, true),
            'stops'     => json_decode($request->stops, true),
            'status'     => 1,
            'deleted'    => 0,
            'created_at'=> now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Route created successfully',
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong',
            'error'   => $e->getMessage()
        ], 422);
    }
}
public function edit($id)
{
    $route   = Route::where('deleted', 0)->findOrFail($id);
    $buses   = Vehicle::where('deleted', 0)->get();
    $drivers = Driver::where('deleted', 0)->get();

    return view('routes.edit', compact(
        'route',
        'buses',
        'drivers'
    ));
}
   public function update(Request $request, $id)
{
    $route = Route::where('deleted', 0)->findOrFail($id);

    $request->validate([
        'name'      => 'required|string|max:255',
        'bus_id'    => 'required',
        'driver_id' => 'required',
        // 'geojson'   => 'required|json',
        // 'stops'     => 'required|json',
    ]);

    try {

        $route->update([
            'name'      => $request->name,
            'bus_id'    => $request->bus_id,
            'driver_id' => $request->driver_id,

            // JSON string auto cast → array (MongoDB)
          'geojson' => json_decode($request->geojson, true), // array
'stops'   => json_decode($request->stops, true),
            'deleted'   => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Route updated successfully',
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong while updating route',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


    public function destroy($id)
    {
        $route = Route::findOrFail($id);
        $route->deleted = 1;
        $route->save();

        return response()->json([
            'success' => true,
            'message' => 'Route deleted successfully',
        ]);
    }

    public function toggleStatus($id)
    {
        $route = Route::findOrFail($id);
        $route->status = ! $route->status;
        $route->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
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

        Route::whereIn('_id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected routes deleted successfully',
        ]);
    }

    /**
     * Datatable list
     */
    public function routeList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $searchValue = $request->input('sSearch');

        // $query = Route::where('deleted', 0);
        $query = Route::with(['vehicle', 'driver'])
              ->where('deleted', 0);
            //   ->get();

        if (!empty($searchValue)) {
            $query->where('name', 'like', "%$searchValue%");
        }

        $totalRecords = $query->count();

        $routes = $query
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();

        $data = [];

        foreach ($routes as $route) {
    $data[] = [
        'id'     => (string) $route->_id,
        'name'   => $route->name,

        // Vehicle relation
        'vehicle_number'    => optional($route->vehicle)->vehicle_number ?? '-',

        // Driver relation
        'driver_name' => optional($route->driver)->driver_name ?? '-',

        // Stops count
        'stops'  => is_array($route->stops) ? count($route->stops) : 0,

        'status' => $route->status,
    ];
}
        return response()->json([
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecords,
            "data"            => $data,
        ]);
    }
}

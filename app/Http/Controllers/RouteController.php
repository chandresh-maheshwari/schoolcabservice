<?php
namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Route;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index()
    {
        return view('routes.index');
    }

    public function create()
    {
        $buses   = $this->getAvailableVehicles();
        $drivers = $this->getAvailableDrivers();

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

        if ($this->isVehicleAssignedToActiveRoute((int) $request->bus_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected vehicle is already assigned to another route.',
            ], 422);
        }

        if ($this->isDriverAssignedToActiveRoute((int) $request->driver_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected driver is already assigned to another route.',
            ], 422);
        }

        try {
            $route = Route::create([
                'user_id'    => $this->resolveActorUserId($request),
                'name'       => $request->name,
                'bus_id'     => $request->bus_id,
                'driver_id'  => $request->driver_id,
                'geojson'    => json_decode($request->geojson, true),
                'stops'      => json_decode($request->stops, true),
                'status'     => 0,
                // 'deleted'    => 0,
                'created_at' => now(),
            ]);
            $this->refreshVehicleAssignmentFlag((int) $route->bus_id);
            $this->refreshDriverAssignmentFlag((int) $route->driver_id);

            return response()->json([
                'success' => true,
                'message' => 'Route created successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }
    public function edit($id)
    {
        $route   = Route::findOrFail($id);
        $buses   = $this->getAvailableVehicles($route->id, (int) $route->bus_id);
        $drivers = $this->getAvailableDrivers($route->id, (int) $route->driver_id);

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

        if ($this->isVehicleAssignedToActiveRoute((int) $request->bus_id, $route->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected vehicle is already assigned to another route.',
            ], 422);
        }

        if ($this->isDriverAssignedToActiveRoute((int) $request->driver_id, $route->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected driver is already assigned to another route.',
            ], 422);
        }

        try {
            $oldBusId    = (int) $route->bus_id;
            $oldDriverId = (int) $route->driver_id;

            $route->update([
                'name'      => $request->name,
                'bus_id'    => $request->bus_id,
                'driver_id' => $request->driver_id,

                                                                     // JSON string auto cast → array (MongoDB)
                'geojson'   => json_decode($request->geojson, true), // array
                'stops'     => json_decode($request->stops, true),
                'deleted'   => 0,
            ]);
            $this->refreshVehicleAssignmentFlag($oldBusId);
            $this->refreshVehicleAssignmentFlag((int) $route->bus_id);
            $this->refreshDriverAssignmentFlag($oldDriverId);
            $this->refreshDriverAssignmentFlag((int) $route->driver_id);

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
        $route          = Route::findOrFail($id);
        $oldBusId       = (int) $route->bus_id;
        $oldDriverId    = (int) $route->driver_id;
        $route->deleted = 1;
        $route->save();
        $this->refreshVehicleAssignmentFlag($oldBusId);
        $this->refreshDriverAssignmentFlag($oldDriverId);

        return response()->json([
            'success' => true,
            'message' => 'Route deleted Successfully.',
        ]);
    }

    /**
     * Toggle Child And Parent active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $route         = Route::findOrFail($id);
        $route->status = $route->status == 1 ? 0 : 1;
        $route->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active Child And Parent count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = Route::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Soft delete multiple route records.
     * created by ns
     */
    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided',
            ]);
        }

        Route::whereIn('id', $ids)->update(['deleted' => 1]);

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
        $query = Route::with(['vehicle', 'driver'])
            ->where(function ($q) {
                $q->where('deleted', 0)
                    ->orWhereNull('deleted');
            });

        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {

            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhereHas('vehicle', function ($vehicleQuery) use ($searchValue) {
                        $vehicleQuery->where('vehicle_number', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('driver', function ($driverQuery) use ($searchValue) {
                        $driverQuery->where('driver_name', 'like', "%{$searchValue}%");
                    });

            });
        }

        $totalFiltered = (clone $query)->count();
        $routes       = $query
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();

        $data = [];
        foreach ($routes as $route) {
            $data[] = [
                'id'             => (string) $route->id,
                'name'           => $route->name,

                // Vehicle relation
                'vehicle_number' => optional($route->vehicle)->vehicle_number ?? '-',

                // Driver relation
                'driver_name'    => optional($route->driver)->driver_name ?? '-',

                // Stops count
                'stops'          => is_array($route->stops) ? count($route->stops) : 0,

                'status'         => $route->status,
            ];
        }
        return response()->json([
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalFiltered,
            "data"            => $data,
        ]);
    }

    private function getAvailableVehicles(?int $excludeRouteId = null, ?int $currentVehicleId = null)
    {
        $assignedVehicleIds = Route::where('deleted', 0)
            ->when($excludeRouteId, function ($query, $excludeRouteId) {
                return $query->where('id', '!=', $excludeRouteId);
            })
            ->whereNotNull('bus_id')
            ->pluck('bus_id');

        $query = Vehicle::where('deleted', 0);

        if ($assignedVehicleIds->isNotEmpty()) {
            $query->whereNotIn('id', $assignedVehicleIds);
        }

        if ($currentVehicleId) {
            $query->orWhere(function ($q) use ($currentVehicleId) {
                $q->where('deleted', 0)->where('id', $currentVehicleId);
            });
        }

        return $query->get();
    }

    private function getAvailableDrivers(?int $excludeRouteId = null, ?int $currentDriverId = null)
    {
        $assignedDriverIds = Route::where('deleted', 0)
            ->when($excludeRouteId, function ($query, $excludeRouteId) {
                return $query->where('id', '!=', $excludeRouteId);
            })
            ->whereNotNull('driver_id')
            ->pluck('driver_id');

        $query = Driver::where('deleted', 0);

        if ($assignedDriverIds->isNotEmpty()) {
            $query->whereNotIn('id', $assignedDriverIds);
        }

        if ($currentDriverId) {
            $query->orWhere(function ($q) use ($currentDriverId) {
                $q->where('deleted', 0)->where('id', $currentDriverId);
            });
        }

        return $query->get();
    }

    private function isVehicleAssignedToActiveRoute(int $vehicleId, ?int $exceptRouteId = null): bool
    {
        if (! $vehicleId) {
            return false;
        }

        return Route::where('deleted', 0)
            ->where('bus_id', $vehicleId)
            ->when($exceptRouteId, function ($query, $exceptRouteId) {
                return $query->where('id', '!=', $exceptRouteId);
            })
            ->exists();
    }

    private function isDriverAssignedToActiveRoute(int $driverId, ?int $exceptRouteId = null): bool
    {
        if (! $driverId) {
            return false;
        }

        return Route::where('deleted', 0)
            ->where('driver_id', $driverId)
            ->when($exceptRouteId, function ($query, $exceptRouteId) {
                return $query->where('id', '!=', $exceptRouteId);
            })
            ->exists();
    }

    private function refreshVehicleAssignmentFlag(?int $vehicleId): void
    {
        if (! $vehicleId) {
            return;
        }

        $isAssigned = Route::where('deleted', 0)
            ->where('bus_id', $vehicleId)
            ->exists();

        Vehicle::where('id', $vehicleId)->update([
            'is_assigned' => $isAssigned ? 1 : 0,
        ]);
    }

    private function refreshDriverAssignmentFlag(?int $driverId): void
    {
        if (! $driverId) {
            return;
        }

        $isAssigned = Route::where('deleted', 0)
            ->where('driver_id', $driverId)
            ->exists();

        Driver::where('id', $driverId)->update([
            'is_assigned' => $isAssigned ? 1 : 0,
        ]);
    }
}

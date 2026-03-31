<?php
namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Route;
use App\Models\School;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            'bus_id'    => 'required|integer|min:1',
            'driver_id' => 'required|integer|min:1',
            'geojson'   => 'required|json',
            'stops'     => 'required|json',
        ]);

        $persistedUserId = $this->resolvePersistedUserId($request);
        if (! $persistedUserId) {
            return response()->json([
                'success' => false,
                'message' => 'User session not found. Please login again.',
            ], 401);
        }

        $busId = (int) $request->bus_id;
        $driverId = (int) $request->driver_id;

        $vehicleQuery = Vehicle::where('deleted', 0)->where('id', $busId);
        $driverQuery  = Driver::where('deleted', 0)->where('id', $driverId);
        $this->applyActorScope($vehicleQuery, $request);
        $this->applyActorScope($driverQuery, $request);

        if (! $vehicleQuery->exists() || ! $driverQuery->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Selected vehicle or driver is not accessible for current user.',
            ], 422);
        }

        if ($this->isVehicleAssignedToActiveRoute($busId)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected vehicle is already assigned to another route.',
            ], 422);
        }

        if ($this->isDriverAssignedToActiveRoute($driverId)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected driver is already assigned to another route.',
            ], 422);
        }

        try {
            $route = DB::transaction(function () use ($request, $persistedUserId, $busId, $driverId) {
                $payload = [
                    'user_id'    => $persistedUserId,
                    'name'       => $request->name,
                    'bus_id'     => $busId,
                    'driver_id'  => $driverId,
                    'geojson'    => json_decode($request->geojson, true),
                    'stops'      => json_decode($request->stops, true),
                    'status'     => 0,
                    'created_at' => now(),
                ];

                if (Schema::hasColumn('routes', 'school_id')) {
                    $schoolId = School::where('user_id', $persistedUserId)
                        ->where('deleted', 0)
                        ->value('id');
                    $payload['school_id'] = $schoolId ?: null;
                }

                $route = Route::create($payload);

                $this->refreshVehicleAssignmentFlag((int) $route->bus_id);
                $this->refreshDriverAssignmentFlag((int) $route->driver_id);

                if (Schema::hasColumn('drivers', 'route_id')) {
                    Driver::where('id', (int) $route->driver_id)->update(['route_id' => (int) $route->id]);
                }

                return $route;
            });

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
    // Supports both admin routes and school routes under `{schoolSlug}` prefix.
    public function edit($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $routeQuery = Route::query();
        $this->applyActorScope($routeQuery);
        $route = $routeQuery->findOrFail($id);

        $buses   = $this->getAvailableVehicles($route->id, (int) $route->bus_id);
        $drivers = $this->getAvailableDrivers($route->id, (int) $route->driver_id);

        return view('routes.edit', compact(
            'route',
            'buses',
            'drivers'
        ));
    }

    // Supports both admin routes and school routes under `{schoolSlug}` prefix.
    public function update(Request $request, $schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $routeQuery = Route::where('deleted', 0);
        $this->applyActorScope($routeQuery, $request);
        $route = $routeQuery->findOrFail($id);

        $request->validate([
            'name'      => 'required|string|max:255',
            'bus_id'    => 'required|integer|min:1',
            'driver_id' => 'required|integer|min:1',
            // 'geojson'   => 'required|json',
            // 'stops'     => 'required|json',
        ]);

        $busId = (int) $request->bus_id;
        $driverId = (int) $request->driver_id;

        $vehicleQuery = Vehicle::where('deleted', 0)->where('id', $busId);
        $driverQuery  = Driver::where('deleted', 0)->where('id', $driverId);
        $this->applyActorScope($vehicleQuery, $request);
        $this->applyActorScope($driverQuery, $request);

        if (! $vehicleQuery->exists() || ! $driverQuery->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Selected vehicle or driver is not accessible for current user.',
            ], 422);
        }

        if ($this->isVehicleAssignedToActiveRoute($busId, $route->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected vehicle is already assigned to another route.',
            ], 422);
        }

        if ($this->isDriverAssignedToActiveRoute($driverId, $route->id)) {
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
                'bus_id'    => $busId,
                'driver_id' => $driverId,

                                                                     // JSON string auto cast → array (MongoDB)
                'geojson'   => json_decode($request->geojson, true), // array
                'stops'     => json_decode($request->stops, true),
                'deleted'   => 0,
            ]);
            $this->refreshVehicleAssignmentFlag($oldBusId);
            $this->refreshVehicleAssignmentFlag((int) $route->bus_id);
            $this->refreshDriverAssignmentFlag($oldDriverId);
            $this->refreshDriverAssignmentFlag((int) $route->driver_id);

            if (Schema::hasColumn('drivers', 'route_id')) {
                if ($oldDriverId && $oldDriverId !== (int) $route->driver_id) {
                    Driver::where('id', $oldDriverId)
                        ->where('route_id', (int) $route->id)
                        ->update(['route_id' => null]);
                }
                Driver::where('id', (int) $route->driver_id)->update(['route_id' => (int) $route->id]);
            }

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

    // Supports both admin routes and school routes under `{schoolSlug}` prefix.
    public function destroy($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $query = Route::query();
        $this->applyActorScope($query);
        $route = $query->findOrFail($id);

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
        $query = Route::query();
        $this->applyActorScope($query);
        $route = $query->findOrFail($id);

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
        $query = Route::where('deleted', 0)
            ->where('status', true);
        $this->applyActorScope($query);

        $activeCount = $query->count();

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

        $routeQuery = Route::whereIn('id', $ids);
        $this->applyActorScope($routeQuery, $request);

        $routes = $routeQuery->get(['id', 'bus_id', 'driver_id']);
        if ($routes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid routes found for delete.',
            ]);
        }

        Route::whereIn('id', $routes->pluck('id'))->update(['deleted' => 1]);

        foreach ($routes as $route) {
            $this->refreshVehicleAssignmentFlag((int) $route->bus_id);
            $this->refreshDriverAssignmentFlag((int) $route->driver_id);
        }

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
        $this->applyActorScope($query, $request);

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
            ->orderByDesc('id')
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();

        $data = [];
        $schoolNameMap = $this->getSchoolNameMapForUserIds($routes->pluck('user_id')->all());
        foreach ($routes as $route) {
            $data[] = [
                'id'             => (string) $route->id,
                'school_name'    => $schoolNameMap[$route->user_id] ?? '-',
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
        $assignedVehicleIdsQuery = Route::where('deleted', 0)
            ->when($excludeRouteId, function ($query, $excludeRouteId) {
                return $query->where('id', '!=', $excludeRouteId);
            })
            ->whereNotNull('bus_id');
        $this->applyActorScope($assignedVehicleIdsQuery);

        $assignedVehicleIds = $assignedVehicleIdsQuery->pluck('bus_id');

        $query = Vehicle::where('deleted', 0);
        $this->applyActorScope($query);

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
        $assignedDriverIdsQuery = Route::where('deleted', 0)
            ->when($excludeRouteId, function ($query, $excludeRouteId) {
                return $query->where('id', '!=', $excludeRouteId);
            })
            ->whereNotNull('driver_id');
        $this->applyActorScope($assignedDriverIdsQuery);

        $assignedDriverIds = $assignedDriverIdsQuery->pluck('driver_id');

        $query = Driver::where('deleted', 0);
        $this->applyActorScope($query);

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

        $query = Route::where('deleted', 0)
            ->where('bus_id', $vehicleId)
            ->when($exceptRouteId, function ($query, $exceptRouteId) {
                return $query->where('id', '!=', $exceptRouteId);
            });
        $this->applyActorScope($query);

        return $query->exists();
    }

    private function isDriverAssignedToActiveRoute(int $driverId, ?int $exceptRouteId = null): bool
    {
        if (! $driverId) {
            return false;
        }

        $query = Route::where('deleted', 0)
            ->where('driver_id', $driverId)
            ->when($exceptRouteId, function ($query, $exceptRouteId) {
                return $query->where('id', '!=', $exceptRouteId);
            });
        $this->applyActorScope($query);

        return $query->exists();
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

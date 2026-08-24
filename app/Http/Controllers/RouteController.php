<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\CustomRouteLocation;
use App\Models\Driver;
use App\Models\Route;
use App\Models\School;
use App\Models\State;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class RouteController extends Controller
{
    public function previewGoogleRoute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'points' => 'required|array|min:2|max:27',
            'points.*.lat' => 'required|numeric',
            'points.*.lng' => 'required|numeric',
            'points.*.name' => 'nullable|string|max:255',
            'points.*.address' => 'nullable|string|max:1000',
            'points.*.type' => 'nullable|string|max:30',
            'points.*.sequence' => 'nullable|numeric',
        ]);

        $apiKey = (string) config('services.google_maps.api_key', '');
        if ($apiKey === '') {
            return response()->json([
                'success' => false,
                'message' => 'Google Maps API key is not configured.',
            ], 503);
        }

        $points = array_values(array_filter(array_map(function (array $point) {
            if (! is_numeric($point['lat'] ?? null) || ! is_numeric($point['lng'] ?? null)) {
                return null;
            }

            return [
                'lat' => (float) $point['lat'],
                'lng' => (float) $point['lng'],
            ];
        }, $validated['points'] ?? [])));

        if (count($points) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'At least two valid route points are required.',
            ], 422);
        }

        $origin = array_shift($points);
        $destination = array_pop($points);
        $intermediates = array_map(function (array $point) {
            return $this->buildGoogleWaypoint($point['lat'], $point['lng']);
        }, $points);

        $response = Http::timeout(15)
            ->acceptJson()
            ->withHeaders([
                'X-Goog-Api-Key' => $apiKey,
                'X-Goog-FieldMask' => implode(',', [
                    'routes.distanceMeters',
                    'routes.duration',
                    'routes.staticDuration',
                    'routes.description',
                    'routes.polyline.geoJsonLinestring',
                    'routes.legs.distanceMeters',
                    'routes.legs.duration',
                    'routes.legs.staticDuration',
                ]),
            ])
            ->post('https://routes.googleapis.com/directions/v2:computeRoutes', [
                'origin' => $this->buildGoogleWaypoint($origin['lat'], $origin['lng']),
                'destination' => $this->buildGoogleWaypoint($destination['lat'], $destination['lng']),
                'intermediates' => $intermediates,
                'travelMode' => 'DRIVE',
                'routingPreference' => 'TRAFFIC_AWARE_OPTIMAL',
                'trafficModel' => 'BEST_GUESS',
                'departureTime' => now()->utc()->toIso8601String(),
                'computeAlternativeRoutes' => false,
                'polylineQuality' => 'OVERVIEW',
                'polylineEncoding' => 'GEO_JSON_LINESTRING',
                'languageCode' => 'en-IN',
                'regionCode' => 'IN',
            ]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => data_get($response->json(), 'error.message', 'Google route request failed.'),
            ], $response->status() >= 400 ? $response->status() : 502);
        }

        $routes = collect($response->json('routes', []))
            ->values()
            ->map(function (array $route, int $routeIndex) {
                $geometry = $this->normalizeGooglePolyline(
                    data_get($route, 'polyline.geoJsonLinestring')
                    ?? data_get($route, 'polyline.geoJsonLineString')
                    ?? data_get($route, 'polyline')
                );

                return [
                    'index' => $routeIndex,
                    'geometry' => $geometry,
                    'distance' => (float) data_get($route, 'distanceMeters', 0),
                    'duration' => $this->parseGoogleDurationSeconds(data_get($route, 'duration')),
                    'static_duration' => $this->parseGoogleDurationSeconds(data_get($route, 'staticDuration')),
                    'summary' => trim((string) data_get($route, 'description', '')),
                    'legs' => collect(data_get($route, 'legs', []))
                        ->values()
                        ->map(function (array $leg, int $legIndex) {
                            return [
                                'index' => $legIndex,
                                'distance' => (float) data_get($leg, 'distanceMeters', 0),
                                'duration' => $this->parseGoogleDurationSeconds(data_get($leg, 'duration')),
                                'static_duration' => $this->parseGoogleDurationSeconds(data_get($leg, 'staticDuration')),
                                'summary' => null,
                            ];
                        })
                        ->all(),
                ];
            })
            ->filter(function (array $route) {
                return is_array($route['geometry'] ?? null)
                    && ($route['geometry']['type'] ?? null) === 'LineString'
                    && count($route['geometry']['coordinates'] ?? []) >= 2;
            })
            ->values()
            ->all();

        if (empty($routes)) {
            return response()->json([
                'success' => false,
                'message' => 'Google route geometry was not available.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'provider' => 'google_routes',
            'routes' => $routes,
        ]);
    }

    public function searchCustomLocations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
        ]);

        $query = $this->buildCustomLocationScopeQuery($request);
        $search = trim((string) ($validated['q'] ?? ''));

        if ($search !== '') {
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        $locations = $query
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'results' => $locations->map(function (CustomRouteLocation $location) {
                return $this->transformCustomLocationForMap($location);
            })->all(),
        ]);
    }

    public function storeCustomLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $persistedUserId = $this->resolvePersistedUserId($request);
        if (! $persistedUserId) {
            return response()->json([
                'success' => false,
                'message' => 'User session not found. Please login again.',
            ], 401);
        }

        $name = trim((string) $validated['name']);
        $address = trim((string) ($validated['address'] ?? ''));
        $latitude = (float) $validated['lat'];
        $longitude = (float) $validated['lng'];

        $duplicateQuery = $this->buildCustomLocationScopeQuery($request)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('latitude', $latitude)
            ->where('longitude', $longitude);

        if ($duplicateQuery->exists()) {
            $existingLocation = $duplicateQuery->latest('id')->first();

            return response()->json([
                'success' => true,
                'message' => 'Custom location already exists.',
                'location' => $this->transformCustomLocationForMap($existingLocation),
            ]);
        }

        $locationPayload = [
            'user_id' => $persistedUserId,
            'name' => $name,
            'address' => $address !== '' ? $address : $name,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => 1,
            'deleted' => 0,
        ];

        if (Schema::hasColumn('custom_route_locations', 'school_id')) {
            $schoolId = $this->resolveCustomLocationSchoolId($request, $persistedUserId);
            $locationPayload['school_id'] = $schoolId ?: null;
        }

        $location = CustomRouteLocation::create($locationPayload);

        return response()->json([
            'success' => true,
            'message' => 'Custom location saved successfully.',
            'location' => $this->transformCustomLocationForMap($location),
        ]);
    }

    public function index()
    {
        return view('routes.index');
    }

    public function create()
    {
        $states = State::query()->orderBy('name')->get(['id', 'name']);
        $buses = $this->getAvailableVehicles();
        $drivers = $this->getAvailableDrivers();
        $schools = School::query()
            ->where('deleted', 0)
            ->where('status', 1)
            ->orderBy('school_name')
            ->get(['id', 'user_id', 'school_name']);
        $hasAnySchools = School::query()
            ->where('deleted', 0)
            ->exists();
        $defaultSchoolId = $this->resolveSchoolIdFromContext(request());
        $defaultSchoolName = optional($schools->firstWhere('id', $defaultSchoolId))->school_name;
        $isSchoolUser = $this->isSchoolActor(request());

        return view('routes.create', compact('states', 'buses', 'drivers', 'schools', 'defaultSchoolId', 'defaultSchoolName', 'isSchoolUser', 'hasAnySchools'));
    }

    public function getCities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'state' => ['required', 'string', 'max:255'],
        ]);

        $state = trim((string) $validated['state']);
        $cacheKey = 'route_state_cities_' . md5(strtolower($state));
        $cities = Cache::remember($cacheKey, now()->addDays(7), function () use ($state) {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(6)
                ->timeout(15)
                ->retry(1, 300)
                ->post('https://countriesnow.space/api/v0.1/countries/state/cities', [
                    'country' => 'India',
                    'state' => $state,
                ]);

            $cities = $response->successful() ? data_get($response->json(), 'data', []) : [];
            if (! is_array($cities)) {
                return [];
            }

            $cities = array_values(array_unique(array_filter(array_map(
                fn ($city) => trim((string) $city),
                $cities
            ))));
            sort($cities);

            return $cities;
        });

        return response()->json(['success' => true, 'cities' => $cities]);
    }

    public function vehicleDrivers(Request $request, $schoolSlugOrVehicleId, $vehicleId = null): JsonResponse
    {
        $vehicleId = $vehicleId ?? $schoolSlugOrVehicleId;
        $exceptRouteId = (int) $request->query('route_id', 0);
        if (! is_numeric($vehicleId) || (int) $vehicleId <= 0) {
            abort(404);
        }

        $vehicleQuery = Vehicle::where('deleted', 0)->where('status', 1)->where('id', (int) $vehicleId);
        $this->applyActorScope($vehicleQuery, $request);
        $vehicle = $vehicleQuery->first(['id', 'driver_id']);

        if (! $vehicle) {
            return response()->json([
                'success' => true,
                'drivers' => [],
            ]);
        }

        $driverQuery = Driver::where('deleted', 0)->where('status', 1)
            ->where(function ($query) use ($vehicle) {
                $query->where('vehicle_id', (int) $vehicle->id);

                if ((int) ($vehicle->driver_id ?? 0) > 0) {
                    $query->orWhere('id', (int) $vehicle->driver_id);
                }
            });
        $this->applyActorScope($driverQuery, $request);

        $drivers = $driverQuery
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [(int) ($vehicle->driver_id ?? 0)])
            ->orderBy('driver_name')
            ->get(['id', 'driver_name', 'vehicle_id'])
            ->filter(function (Driver $driver) use ($exceptRouteId) {
                return ! $this->isDriverAssignedToActiveRoute((int) $driver->id, $exceptRouteId ?: null);
            })
            ->unique('id')
            ->values()
            ->map(function (Driver $driver) use ($vehicle) {
                return [
                    'id' => (int) $driver->id,
                    'driver_name' => (string) $driver->driver_name,
                    'vehicle_id' => (int) ($driver->vehicle_id ?: $vehicle->id),
                ];
            })
            ->all();

        return response()->json([
            'success' => true,
            'drivers' => $drivers,
        ]);
    }

    public function driverVehicles(Request $request, $schoolSlugOrDriverId, $driverId = null): JsonResponse
    {
        $driverId = $driverId ?? $schoolSlugOrDriverId;
        $exceptRouteId = (int) $request->query('route_id', 0);
        if (! is_numeric($driverId) || (int) $driverId <= 0) {
            abort(404);
        }

        $driverQuery = Driver::where('deleted', 0)->where('status', 1)->where('id', (int) $driverId);
        $this->applySchoolAwareScope($driverQuery, $request, 'user_id', Schema::hasColumn('drivers', 'school_id') ? 'school_id' : null);
        $driver = $driverQuery->first(['id', 'vehicle_id']);

        if (! $driver) {
            return response()->json([
                'success' => true,
                'vehicles' => [],
            ]);
        }

        $candidateVehicleIds = $this->resolveLinkedVehicleIdsForDriver((int) $driver->id, $driver);
        $isEmergencyReplacementFlow = false;
        $isRouteEditFlow = $exceptRouteId > 0;

        if (! $isRouteEditFlow) {
            $candidateVehicleIds = $candidateVehicleIds->merge(
                $this->getAvailableVehicles()->pluck('id')->map(fn ($value) => (int) $value)
            );
        }

        if ($exceptRouteId > 0) {
            $routeQuery = Route::query()->with('vehicle')->where('id', $exceptRouteId)->where('deleted', 0);
            $this->applyRouteAccessScope($routeQuery, $request);
            $route = $routeQuery->first();

            $candidateVehicleIds = $candidateVehicleIds->merge(
                $this->getAvailableVehicles($exceptRouteId)->pluck('id')->map(fn ($value) => (int) $value)
            );

            if ($route && $route->vehicle && $this->isVehicleEmergencyMarked($route->vehicle)) {
                $isEmergencyReplacementFlow = true;
            }
        }

        if ($candidateVehicleIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'vehicles' => [],
            ]);
        }

        $vehicleQuery = Vehicle::query()
            ->where('deleted', 0)
            ->where('status', 1)
            ->whereIn('id', $candidateVehicleIds->all())
            ->orderBy('vehicle_number')
            ->orderBy('id');
        $this->applySchoolAwareScope($vehicleQuery, $request, 'user_id', Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);

        $vehicleColumns = ['id', 'vehicle_number', 'driver_id'];
        if (Schema::hasColumn('vehicles', 'availability_status')) {
            $vehicleColumns[] = 'availability_status';
        }
        if (Schema::hasColumn('vehicles', 'is_assigned')) {
            $vehicleColumns[] = 'is_assigned';
        }

        $currentRouteBusId = 0;
        if ($exceptRouteId > 0) {
            $currentRouteBusId = (int) Route::where('deleted', 0)->where('id', $exceptRouteId)->value('bus_id');
        }

        $linkedVehicleIds = $candidateVehicleIds
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();

        $vehicles = $vehicleQuery->get($vehicleColumns)
            ->filter(function (Vehicle $vehicle) use ($exceptRouteId, $currentRouteBusId, $linkedVehicleIds) {
                $vehicleId = (int) $vehicle->id;

                if ($currentRouteBusId > 0 && $vehicleId === $currentRouteBusId) {
                    return ! $this->isVehicleEmergencyMarked($vehicle);
                }

                if (in_array($vehicleId, $linkedVehicleIds, true)) {
                    return ! $this->isVehicleAssignedToActiveRoute($vehicleId, $exceptRouteId ?: null)
                        && ! $this->isVehicleEmergencyMarked($vehicle);
                }

                return ! $this->isVehicleAssignedToActiveRoute($vehicleId, $exceptRouteId ?: null)
                    && ! $this->isVehicleMarkedAssigned($vehicle)
                    && ! $this->isVehicleEmergencyMarked($vehicle);
            })
            ->map(function (Vehicle $vehicle) use ($driver, $isEmergencyReplacementFlow, $isRouteEditFlow) {
                return [
                    'id' => (int) $vehicle->id,
                    'vehicle_number' => (string) ($vehicle->vehicle_number ?? ''),
                    'driver_id' => (int) (($vehicle->driver_id ?: (($isEmergencyReplacementFlow || $isRouteEditFlow) ? $driver->id : $driver->id))),
                    'availability_status' => (string) ($vehicle->availability_status ?? 'available'),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'vehicles' => $vehicles,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'name' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'bus_id' => 'required|integer|min:1',
            'driver_id' => 'required|integer|min:1',
            'route_json' => 'required|json',
        ]);

        $routeJson = $this->parseRouteJsonPayload($request);
        if ($routeJson === null || ! $this->hasRequiredRouteEndpoints($routeJson)) {
            return response()->json([
                'success' => false,
                'message' => 'Start point and end point are required.',
            ], 422);
        }

        if (! $this->routePointsBelongToSelectedCity($routeJson, (string) $request->city, (string) $request->state)) {
            return response()->json([
                'success' => false,
                'message' => 'Route points must all be inside the selected city. Please clear and select the route points again for ' . trim((string) $request->city) . '.',
            ], 422);
        }

        $persistedUserId = $this->resolvePersistedUserId($request);
        if (! $persistedUserId) {
            return response()->json([
                'success' => false,
                'message' => 'User session not found. Please login again.',
            ], 401);
        }

        $busId = (int) $request->bus_id;
        $driverId = (int) $request->driver_id;

        $vehicleQuery = Vehicle::where('deleted', 0)->where('status', 1)->where('id', $busId);
        $driverQuery = Driver::where('deleted', 0)->where('status', 1)->where('id', $driverId);
        $this->applySchoolAwareScope($vehicleQuery, $request, 'user_id', Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);
        $this->applySchoolAwareScope($driverQuery, $request, 'user_id', Schema::hasColumn('drivers', 'school_id') ? 'school_id' : null);

        $vehicleColumns = ['id', 'user_id', 'driver_id'];
        if (Schema::hasColumn('vehicles', 'school_id')) {
            $vehicleColumns[] = 'school_id';
        }

        $driverColumns = ['id', 'user_id', 'vehicle_id'];
        if (Schema::hasColumn('drivers', 'school_id')) {
            $driverColumns[] = 'school_id';
        }

        $vehicle = $vehicleQuery->first($vehicleColumns);
        $driver = $driverQuery->first($driverColumns);

        if (! $vehicle || ! $driver) {
            return response()->json([
                'success' => false,
                'message' => 'Selected vehicle or driver is not accessible for current user.',
            ], 422);
        }

        if (! $this->isDriverLinkedToVehicle($driver, $vehicle)
            && ! $this->canAssignAvailableVehicleToDriver($vehicle, null)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected driver is not assigned to the selected vehicle.',
            ], 422);
        }

        if ($this->isVehicleEmergencyMarked($vehicle)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected vehicle is suspended and cannot be assigned to a route.',
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
            DB::transaction(function () use ($request, $persistedUserId, $busId, $driverId, $routeJson, $vehicle, $driver) {
                $routeOwnerUserId = $this->resolveRouteOwnerUserId($request, $vehicle, $driver, $persistedUserId);
                $payload = [
                    'user_id' => $routeOwnerUserId,
                    'name' => $request->name,
                    'state' => trim((string) $request->state),
                    'city' => trim((string) $request->city),
                    'bus_id' => $busId,
                    'driver_id' => $driverId,
                    'route_json' => $routeJson,
                    'status' => 0,
                    'deleted' => 0,
                    'created_at' => now(),
                ];

                if (Schema::hasColumn('routes', 'school_id')) {
                    $schoolId = $this->resolveRouteSchoolId($request, $routeOwnerUserId, [
                        $vehicle->school_id ?? null,
                        $driver->school_id ?? null,
                    ]);
                    $payload['school_id'] = $schoolId ?: null;
                }

                $route = Route::create($payload);
                $this->syncRouteSchoolToLinkedModules($route);

                $this->refreshVehicleAssignmentFlag((int) $route->bus_id);
                $this->refreshDriverAssignmentFlag((int) $route->driver_id);
                $this->syncDriverVehicleLink((int) $route->driver_id, (int) $route->bus_id, $route->school_id ?? null);

                if (Schema::hasColumn('drivers', 'route_id')) {
                    Driver::where('id', (int) $route->driver_id)->update(['route_id' => (int) $route->id]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Route created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function edit($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $routeQuery = Route::query()->with('vehicle');
        $this->applyRouteAccessScope($routeQuery, request());
        $route = $routeQuery->findOrFail($id);

        $buses = $this->getAvailableVehicles($route->id, (int) $route->bus_id);
        $states = State::query()->orderBy('name')->get(['id', 'name']);
        $drivers = $this->getAvailableDrivers($route->id, (int) $route->driver_id);
        $schools = School::query()
            ->where('deleted', 0)
            ->where('status', 1)
            ->orderBy('school_name')
            ->get(['id', 'user_id', 'school_name']);
        $hasAnySchools = School::query()
            ->where('deleted', 0)
            ->exists();
        $defaultSchoolId = (int) ($route->school_id ?: $this->resolveSchoolIdFromContext(request()));
        $defaultSchoolName = optional($schools->firstWhere('id', $defaultSchoolId))->school_name;
        $isSchoolUser = $this->isSchoolActor(request());
        $routeVehicleReplacementWarning = $route->vehicle && $this->isVehicleEmergencyMarked($route->vehicle)
            ? 'Assigned vehicle is in emergency status. Please assign another available extra bus before starting the trip.'
            : null;
        $routeRunningTripWarning = $this->hasRunningTripForVehicle((int) ($route->bus_id ?? 0))
            ? 'This route currently has a running trip. Use the Emergency module for during-trip vehicle replacement. Route edit replacement should be used only before trip start.'
            : null;
        $routeReplacementLabel = $this->buildRouteReplacementLabelHtml($route);

        return view('routes.edit', compact('route', 'states', 'buses', 'drivers', 'schools', 'defaultSchoolId', 'defaultSchoolName', 'isSchoolUser', 'hasAnySchools', 'routeVehicleReplacementWarning', 'routeRunningTripWarning', 'routeReplacementLabel'));
    }

    public function update(Request $request, $schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $routeQuery = Route::where('deleted', 0)->with('vehicle');
        $this->applyRouteAccessScope($routeQuery, $request);
        $route = $routeQuery->findOrFail($id);

        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'name' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'bus_id' => 'required|integer|min:1',
            'driver_id' => 'required|integer|min:1',
            'route_json' => 'required|json',
        ]);

        $routeJson = $this->parseRouteJsonPayload($request);
        if ($routeJson === null || ! $this->hasRequiredRouteEndpoints($routeJson)) {
            return response()->json([
                'success' => false,
                'message' => 'Start point and end point are required.',
            ], 422);
        }

        if (! $this->routePointsBelongToSelectedCity($routeJson, (string) $request->city, (string) $request->state)) {
            return response()->json([
                'success' => false,
                'message' => 'Route points must all be inside the selected city. Please clear and select the route points again for ' . trim((string) $request->city) . '.',
            ], 422);
        }

        $busId = (int) $request->bus_id;
        $driverId = (int) $request->driver_id;
        $isRunningTripActiveForCurrentRouteVehicle = $this->hasRunningTripForVehicle((int) ($route->bus_id ?? 0));

        if (
            $isRunningTripActiveForCurrentRouteVehicle &&
            (
                $busId !== (int) ($route->bus_id ?? 0)
                || $driverId !== (int) ($route->driver_id ?? 0)
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'A trip is already running for this route. Please use the Emergency module for during-trip vehicle or driver replacement.',
            ], 422);
        }

        $vehicleQuery = Vehicle::where('deleted', 0)->where('status', 1)->where('id', $busId);
        $driverQuery = Driver::where('deleted', 0)->where('status', 1)->where('id', $driverId);
        $this->applySchoolAwareScope($vehicleQuery, $request, 'user_id', Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);
        $this->applySchoolAwareScope($driverQuery, $request, 'user_id', Schema::hasColumn('drivers', 'school_id') ? 'school_id' : null);

        $vehicleColumns = ['id', 'user_id', 'driver_id'];
        if (Schema::hasColumn('vehicles', 'school_id')) {
            $vehicleColumns[] = 'school_id';
        }

        $driverColumns = ['id', 'user_id', 'vehicle_id'];
        if (Schema::hasColumn('drivers', 'school_id')) {
            $driverColumns[] = 'school_id';
        }

        $vehicle = $vehicleQuery->first($vehicleColumns);
        $driver = $driverQuery->first($driverColumns);

        if (! $vehicle || ! $driver) {
            return response()->json([
                'success' => false,
                'message' => 'Selected vehicle or driver is not accessible for current user.',
            ], 422);
        }

        if (! $this->isDriverLinkedToVehicle($driver, $vehicle, $route->id)
            && ! $this->canUseEmergencyReplacementVehicle($route, $driver, $vehicle)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected driver is not assigned to the selected vehicle.',
            ], 422);
        }

        if ($this->isVehicleEmergencyMarked($vehicle)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected vehicle is suspended and cannot be assigned to a route.',
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
            $oldBusId = (int) $route->bus_id;
            $oldDriverId = (int) $route->driver_id;
            $routeOwnerUserId = $this->resolveRouteOwnerUserId($request, $vehicle, $driver, (int) $route->user_id);
            $shouldLogEmergencyReplacement = $this->shouldLogEmergencyReplacement($route, $busId);

            $routePayload = [
                'user_id' => $routeOwnerUserId,
                'name' => $request->name,
                'state' => trim((string) $request->state),
                'city' => trim((string) $request->city),
                'bus_id' => $busId,
                'driver_id' => $driverId,
                'route_json' => $routeJson,
                'deleted' => 0,
            ];

            if (Schema::hasColumn('routes', 'school_id')) {
                $routePayload['school_id'] = $this->resolveRouteSchoolId($request, $routeOwnerUserId, [
                    $vehicle->school_id ?? null,
                    $driver->school_id ?? null,
                    $route->school_id ?? null,
                ]);
            }

            $route->update($routePayload);
            $this->syncEmergencyReplacementHistory($route, $oldBusId, $busId, $shouldLogEmergencyReplacement);
            $this->syncRouteSchoolToLinkedModules($route);

            $this->refreshVehicleAssignmentFlag($oldBusId);
            $this->refreshVehicleAssignmentFlag((int) $route->bus_id);
            $this->refreshDriverAssignmentFlag($oldDriverId);
            $this->refreshDriverAssignmentFlag((int) $route->driver_id);
            $this->syncDriverVehicleLink((int) $route->driver_id, (int) $route->bus_id, $route->school_id ?? null);

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
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $query = Route::query();
        $this->applyRouteAccessScope($query, request());
        $route = $query->findOrFail($id);

        $routeUsage = $this->getRouteDeletionUsageMap([(int) $route->id]);
        $currentRouteUsage = $routeUsage[(int) $route->id] ?? [];
        if (($currentRouteUsage['total'] ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => $this->buildRouteDeletionBlockedMessage($currentRouteUsage),
            ], 422);
        }

        $oldBusId = (int) $route->bus_id;
        $oldDriverId = (int) $route->driver_id;
        $route->deleted = 1;
        $route->save();
        if (Schema::hasColumn('drivers', 'route_id') && $oldDriverId > 0) {
            Driver::where('id', $oldDriverId)
                ->where('route_id', $route->id)
                ->update(['route_id' => null]);
        }
        $this->cleanupDeletedRouteState([(int) $route->id], [$oldDriverId]);
        $this->refreshVehicleAssignmentFlag($oldBusId);
        $this->refreshDriverAssignmentFlag($oldDriverId);

        return response()->json([
            'success' => true,
            'message' => 'Route deleted Successfully.',
        ]);
    }

    public function toggleStatus($id)
    {
        $query = Route::query();
        $this->applyRouteAccessScope($query, request());
        $route = $query->findOrFail($id);

        $route->status = $route->status == 1 ? 0 : 1;
        $route->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    public function getActiveCount()
    {
        $query = Route::where('deleted', 0)
            ->where('status', true);
        $this->applyRouteAccessScope($query, request());

        $activeCount = $query->count();

        return response()->json(['count' => $activeCount]);
    }

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
        $this->applyRouteAccessScope($routeQuery, $request);

        $routes = $routeQuery->get(['id', 'bus_id', 'driver_id']);
        if ($routes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid routes found for delete.',
            ]);
        }

        $routeUsage = $this->getRouteDeletionUsageMap(
            $routes->pluck('id')->map(fn ($id) => (int) $id)->all()
        );
        $totalUsage = $this->sumRouteDeletionUsage($routeUsage);
        if (($totalUsage['total'] ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => $this->buildRouteDeletionBlockedMessage($totalUsage, true),
            ], 422);
        }

        Route::whereIn('id', $routes->pluck('id'))->update(['deleted' => 1]);
        if (Schema::hasColumn('drivers', 'route_id')) {
            foreach ($routes as $route) {
                $driverId = (int) $route->driver_id;
                if ($driverId <= 0) {
                    continue;
                }

                Driver::where('id', $driverId)
                    ->where('route_id', (int) $route->id)
                    ->update(['route_id' => null]);
            }
        }
        $this->cleanupDeletedRouteState(
            $routes->pluck('id')->map(fn ($value) => (int) $value)->all(),
            $routes->pluck('driver_id')->map(fn ($value) => (int) $value)->all()
        );

        foreach ($routes as $route) {
            $this->refreshVehicleAssignmentFlag((int) $route->bus_id);
            $this->refreshDriverAssignmentFlag((int) $route->driver_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Selected routes deleted successfully',
        ]);
    }

    public function routeList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = (int) $request->input('iDisplayStart', 0);
        $rowperpage = (int) $request->input('iDisplayLength', 10);
        $searchValue = $request->input('sSearch');
        $query = Route::with(['vehicle', 'driver'])
            ->where(function ($q) {
                $q->where('deleted', 0)
                    ->orWhereNull('deleted');
            });
        $this->applyRouteAccessScope($query, $request);

        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $matchingSchoolReferences = $this->resolveSchoolSearchIds($searchValue);
            $matchingSchoolIds = $matchingSchoolReferences['school_ids'];
            $matchingUserIds = $matchingSchoolReferences['user_ids'];

            $query->where(function ($q) use ($searchValue, $matchingSchoolIds, $matchingUserIds) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhereHas('vehicle', function ($vehicleQuery) use ($searchValue) {
                        $vehicleQuery->where('vehicle_number', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('driver', function ($driverQuery) use ($searchValue) {
                        $driverQuery->where('driver_name', 'like', "%{$searchValue}%");
                    });

                if (! empty($matchingSchoolIds) && Schema::hasColumn('routes', 'school_id')) {
                    $q->orWhereIn('school_id', $matchingSchoolIds);
                }

                if (! empty($matchingUserIds)) {
                    $q->orWhereIn('user_id', $matchingUserIds);
                }
            });
        }

        $totalFiltered = (clone $query)->count();
        $routes = $query
            ->orderByDesc('id')
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();

        $data = [];
        $schoolNameMap = $this->getSchoolNameMapForUserIds(
            $routes->pluck('user_id')
                ->merge($routes->pluck('vehicle.user_id'))
                ->merge($routes->pluck('driver.user_id'))
                ->all()
        );
        $schoolNamesBySchoolId = $this->getSchoolNameMapForSchoolIds($routes->pluck('school_id')->all());
        $schoolNamesByVehicleId = $this->getSchoolNameMapForVehicleIds($routes->pluck('bus_id')->all());
        $schoolNamesByDriverId = $this->getSchoolNameMapForDriverIds($routes->pluck('driver_id')->all());
        $replacementVehicleNumbersById = $this->getVehicleNumberMapByIds(
            $this->getRouteReplacementVehicleIds(
                $routes->pluck('id')->map(fn ($id) => (int) $id)->all(),
                $routes->pluck('bus_id')->all()
            )
        );
        $routeReplacementHistoryLabels = $this->getRouteReplacementHistoryLabels(
            $routes->pluck('id')->map(fn ($id) => (int) $id)->all(),
            $replacementVehicleNumbersById,
            $routes->keyBy('id')
        );
        $routeUsageMap = $this->getRouteDeletionUsageMap(
            $routes->pluck('id')->map(fn ($id) => (int) $id)->all()
        );
        foreach ($routes as $route) {
            $routeStops = data_get($route->route_json, 'pickup_points', data_get($route->route_json, 'stops', $route->stops));
            $routeUsage = $routeUsageMap[(int) $route->id] ?? [];
            $canDelete = (($routeUsage['total'] ?? 0) === 0);

            $data[] = [
                'id' => (string) $route->id,
                'school_name' => $schoolNamesBySchoolId[$route->school_id]
                    ?? $schoolNamesByVehicleId[(int) ($route->bus_id ?? 0)]
                    ?? $schoolNamesByDriverId[(int) ($route->driver_id ?? 0)]
                    ?? $schoolNameMap[$route->user_id]
                    ?? $schoolNameMap[optional($route->vehicle)->user_id]
                    ?? $schoolNameMap[optional($route->driver)->user_id]
                    ?? '-',
                'name' => $route->name,
                'vehicle_number' => optional($route->vehicle)->vehicle_number ?? '-',
                'vehicle_availability_status' => (string) (optional($route->vehicle)->availability_status ?? 'available'),
                'driver_name' => optional($route->driver)->driver_name ?? '-',
                'stops' => is_array($routeStops) ? count($routeStops) : 0,
                'status' => $route->status,
                'can_delete' => $canDelete,
                'is_assigned' => ! $canDelete,
                'delete_block_reason' => $canDelete
                    ? null
                    : $this->buildRouteDeletionBlockedMessage($routeUsage),
                'vehicle_status_warning' => optional($route->vehicle)->availability_status === 'emergency'
                    ? 'Assigned vehicle is suspended. Reassign another vehicle before trip start.'
                    : null,
                'replacement_label' => $routeReplacementHistoryLabels[(int) $route->id]
                    ?? $this->buildRouteReplacementLabelHtml($route, $replacementVehicleNumbersById),
            ];
        }

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalFiltered,
            'data' => $data,
        ]);
    }

    private function applyRouteAccessScope($query, ?Request $request = null)
    {
        $request = $request ?: request();

        if (! $this->shouldRestrictToActorData($request)) {
            return $query;
        }

        $actorUserId = $this->resolveActorUserId($request);
        if (! $actorUserId) {
            return $query->whereRaw('1 = 0');
        }

        if (! $this->isSchoolActor($request)) {
            return $query->where('user_id', $actorUserId);
        }

        $schoolId = $this->resolveRouteSchoolId($request, $actorUserId);

        return $query->where(function ($routeQuery) use ($actorUserId, $schoolId) {
            $routeQuery->where('user_id', $actorUserId)
                ->orWhereHas('vehicle', function ($vehicleQuery) use ($actorUserId) {
                    $vehicleQuery->where('user_id', $actorUserId);
                })
                ->orWhereHas('driver', function ($driverQuery) use ($actorUserId) {
                    $driverQuery->where('user_id', $actorUserId);
                });

            if ($schoolId && Schema::hasColumn('routes', 'school_id')) {
                $routeQuery->orWhere('school_id', $schoolId);
            }
        });
    }

    private function resolveRouteOwnerUserId(Request $request, Vehicle $vehicle, Driver $driver, ?int $fallbackUserId = null): int
    {
        $ownerUserId = $fallbackUserId ?: $this->resolvePersistedUserId($request) ?: $this->resolveActorUserId($request);

        if ($this->isPrivilegedActor($request)) {
            foreach ([(int) ($vehicle->user_id ?? 0), (int) ($driver->user_id ?? 0)] as $candidateUserId) {
                if ($candidateUserId > 0) {
                    return $candidateUserId;
                }
            }
        }

        return (int) $ownerUserId;
    }

    private function resolveRouteSchoolId(?Request $request = null, ?int $ownerUserId = null, array $candidateSchoolIds = []): ?int
    {
        return $this->resolveModuleSchoolId($request, null, $candidateSchoolIds, $ownerUserId);
    }

    private function syncRouteSchoolToLinkedModules(Route $route): void
    {
        if (! Schema::hasColumn('routes', 'school_id') || ! $route->school_id) {
            return;
        }

        $schoolId = (int) $route->school_id;

        if (Schema::hasColumn('vehicles', 'school_id') && (int) ($route->bus_id ?? 0) > 0) {
            Vehicle::where('id', (int) $route->bus_id)->update(['school_id' => $schoolId]);

            $vehicleTypeId = Vehicle::where('id', (int) $route->bus_id)->value('vehicle_type_id');
            if (Schema::hasColumn('vehicle_types', 'school_id') && is_numeric($vehicleTypeId) && (int) $vehicleTypeId > 0) {
                DB::table('vehicle_types')
                    ->where('id', (int) $vehicleTypeId)
                    ->update(['school_id' => $schoolId]);
            }
        }

        if (Schema::hasColumn('drivers', 'school_id') && (int) ($route->driver_id ?? 0) > 0) {
            Driver::where('id', (int) $route->driver_id)->update(['school_id' => $schoolId]);
        }

        if (Schema::hasColumn('stops_pickup', 'school_id')) {
            DB::table('stops_pickup')
                ->where('route_id', (int) $route->id)
                ->update(['school_id' => $schoolId]);
        }

        if (Schema::hasColumn('driver_vehicle_histories', 'school_id')) {
            DB::table('driver_vehicle_histories')
                ->where(function ($query) use ($route) {
                    $query->where('vehicle_id', (int) $route->bus_id)
                        ->orWhere('driver_id', (int) $route->driver_id);
                })
                ->update(['school_id' => $schoolId]);
        }
    }

    protected function getSchoolNameMapForSchoolIds(array $schoolIds): array
    {
        $schoolIds = array_values(array_unique(array_filter(array_map(function ($value) {
            return is_numeric($value) ? (int) $value : null;
        }, $schoolIds), fn ($value) => $value && $value > 0)));

        if (empty($schoolIds)) {
            return [];
        }

        return School::query()
            ->where('deleted', 0)
            ->where('status', 1)
            ->whereIn('id', $schoolIds)
            ->pluck('school_name', 'id')
            ->toArray();
    }

    private function getAvailableVehicles(?int $excludeRouteId = null, ?int $currentVehicleId = null)
    {
        $query = Vehicle::where('deleted', 0)->where('status', 1);
        if (Schema::hasColumn('vehicles', 'availability_status') || Schema::hasColumn('vehicles', 'is_assigned')) {
            $query->where(function ($vehicleQuery) {
                if (Schema::hasColumn('vehicles', 'availability_status')) {
                    $vehicleQuery->whereNull('availability_status')
                        ->orWhere('availability_status', 'available');
                }

                if (Schema::hasColumn('vehicles', 'is_assigned')) {
                    $vehicleQuery->where(function ($assignedQuery) {
                        $assignedQuery->whereNull('is_assigned')
                            ->orWhere('is_assigned', 0);
                    });
                }
            });
        }
        $this->applySchoolAwareScope($query, request(), 'user_id', Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);
        $query->orderBy('vehicle_number')->orderBy('id');

        $assignedVehicleIds = $this->getAssignedVehicleIds($excludeRouteId);
        $vehicles = $query->get()->filter(function (Vehicle $vehicle) use ($assignedVehicleIds, $currentVehicleId) {
            $vehicleId = (int) $vehicle->id;

            if ($currentVehicleId && $vehicleId === $currentVehicleId) {
                return ! $this->isVehicleEmergencyMarked($vehicle);
            }

            return ! in_array($vehicleId, $assignedVehicleIds, true)
                && ! $this->isVehicleMarkedAssigned($vehicle)
                && ! $this->isVehicleEmergencyMarked($vehicle);
        })->values();

        if ($currentVehicleId && ! $vehicles->contains(fn ($vehicle) => (int) $vehicle->id === $currentVehicleId)) {
            $currentVehicleQuery = Vehicle::where('deleted', 0)->where('status', 1)->where('id', $currentVehicleId);
            $this->applySchoolAwareScope($currentVehicleQuery, request(), 'user_id', Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);

            $currentVehicle = $currentVehicleQuery->first();
            if ($currentVehicle && ! $this->isVehicleEmergencyMarked($currentVehicle)) {
                $vehicles->push($currentVehicle);
            }
        }

        $schoolIdByUserId = School::query()
            ->where('deleted', 0)
            ->where('status', 1)
            ->pluck('id', 'user_id');

        return $vehicles
            ->map(function (Vehicle $vehicle) use ($schoolIdByUserId) {
                $vehicle->effective_school_id = (int) ($vehicle->school_id
                    ?? $schoolIdByUserId->get((int) ($vehicle->user_id ?? 0), 0));

                return $vehicle;
            })
            ->unique('id')
            ->sortBy(fn ($vehicle) => mb_strtolower((string) ($vehicle->vehicle_number ?? '')).'|'.str_pad((string) $vehicle->id, 10, '0', STR_PAD_LEFT))
            ->values();
    }

    private function isVehicleEmergencyMarked(Vehicle $vehicle): bool
    {
        if (! Schema::hasColumn('vehicles', 'availability_status')) {
            return false;
        }

        return Str::lower((string) ($vehicle->availability_status ?? 'available')) === 'emergency';
    }

    private function isVehicleMarkedAssigned(Vehicle $vehicle): bool
    {
        if (! Schema::hasColumn('vehicles', 'is_assigned')) {
            return false;
        }

        return (int) ($vehicle->is_assigned ?? 0) === 1;
    }

    private function getAvailableDrivers(?int $excludeRouteId = null, ?int $currentDriverId = null)
    {
        $query = Driver::where('deleted', 0)->where('status', 1);
        $this->applySchoolAwareScope($query, request(), 'user_id', Schema::hasColumn('drivers', 'school_id') ? 'school_id' : null);
        $query->orderBy('driver_name')->orderBy('id');
        $assignedDriverIds = $this->getAssignedDriverIds($excludeRouteId);

        $drivers = $query->get()->filter(function (Driver $driver) use ($currentDriverId, $assignedDriverIds) {
            if (! $currentDriverId && in_array((int) $driver->id, $assignedDriverIds, true)) {
                return false;
            }

            if ($currentDriverId && (int) $driver->id === $currentDriverId) {
                return true;
            }

            if (in_array((int) $driver->id, $assignedDriverIds, true)) {
                return false;
            }

            return $this->resolveLinkedVehicleIdsForDriver((int) $driver->id, $driver)->isNotEmpty();
        })->values();

        if ($currentDriverId && ! $drivers->contains(fn ($driver) => (int) $driver->id === $currentDriverId)) {
            $currentDriverQuery = Driver::where('deleted', 0)->where('status', 1)->where('id', $currentDriverId);
            $this->applySchoolAwareScope($currentDriverQuery, request(), 'user_id', Schema::hasColumn('drivers', 'school_id') ? 'school_id' : null);

            $currentDriver = $currentDriverQuery->first();
            if ($currentDriver) {
                $drivers->push($currentDriver);
            }
        }

        $schoolIdByUserId = School::query()
            ->where('deleted', 0)
            ->where('status', 1)
            ->pluck('id', 'user_id');

        return $drivers
            ->map(function (Driver $driver) use ($schoolIdByUserId) {
                $driver->effective_school_id = (int) ($driver->school_id
                    ?? $schoolIdByUserId->get((int) ($driver->user_id ?? 0), 0));

                return $driver;
            })
            ->unique('id')
            ->sortBy(fn ($driver) => mb_strtolower((string) ($driver->driver_name ?? '')).'|'.str_pad((string) $driver->id, 10, '0', STR_PAD_LEFT))
            ->values();
    }

    private function resolveLinkedVehicleIdsForDriver(int $driverId, ?Driver $driver = null)
    {
        if ($driverId <= 0) {
            return collect();
        }

        $candidateVehicleIds = collect([
            (int) (($driver?->vehicle_id) ?? 0),
        ])->filter(fn ($value) => $value > 0);

        $directVehicleQuery = Vehicle::query()
            ->where('deleted', 0)
            ->where('status', 1)
            ->where('driver_id', $driverId);
        $this->applySchoolAwareScope($directVehicleQuery, request(), 'user_id', Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);
        $candidateVehicleIds = $candidateVehicleIds->merge(
            $directVehicleQuery->pluck('id')->map(fn ($value) => (int) $value)
        );

        if (Schema::hasTable('driver_vehicle_histories')) {
            $historyVehicleIds = DB::table('driver_vehicle_histories')
                ->where('driver_id', $driverId)
                ->when(Schema::hasColumn('driver_vehicle_histories', 'is_assigned'), function ($query) {
                    $query->where('is_assigned', 1);
                })
                ->orderByDesc('id')
                ->pluck('vehicle_id')
                ->map(fn ($value) => (int) $value);

            $candidateVehicleIds = $candidateVehicleIds->merge($historyVehicleIds);
        }

        return $candidateVehicleIds
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values();
    }

    private function isDriverLinkedToVehicle(Driver $driver, Vehicle $vehicle, ?int $currentRouteId = null): bool
    {
        $driverVehicleId = (int) ($driver->vehicle_id ?? 0);
        $vehicleDriverId = (int) ($vehicle->driver_id ?? 0);

        if ($driverVehicleId === (int) $vehicle->id || $vehicleDriverId === (int) $driver->id) {
            return true;
        }

        if ($currentRouteId) {
            $routeQuery = Route::where('id', $currentRouteId)
                ->where('deleted', 0)
                ->where('bus_id', (int) $vehicle->id)
                ->where('driver_id', (int) $driver->id);

            $this->applyActorScope($routeQuery);

            return $routeQuery->exists();
        }

        return false;
    }

    private function canUseEmergencyReplacementVehicle(Route $route, Driver $driver, Vehicle $vehicle): bool
    {
        if ((int) $route->driver_id !== (int) $driver->id) {
            return false;
        }

        $currentVehicle = $route->relationLoaded('vehicle')
            ? $route->vehicle
            : Vehicle::where('deleted', 0)->where('id', (int) $route->bus_id)->first();

        if (! $currentVehicle || ! $this->isVehicleEmergencyMarked($currentVehicle)) {
            return false;
        }

        return ! $this->isVehicleEmergencyMarked($vehicle)
            && ! $this->isVehicleMarkedAssigned($vehicle)
            && ! $this->isVehicleAssignedToActiveRoute((int) $vehicle->id, (int) $route->id);
    }

    private function canAssignAvailableVehicleToDriver(Vehicle $vehicle, ?int $exceptRouteId = null): bool
    {
        return ! $this->isVehicleEmergencyMarked($vehicle)
            && ! $this->isVehicleMarkedAssigned($vehicle)
            && ! $this->isVehicleAssignedToActiveRoute((int) $vehicle->id, $exceptRouteId);
    }

    private function shouldLogEmergencyReplacement(Route $route, int $newBusId): bool
    {
        $currentBusId = (int) ($route->bus_id ?? 0);
        if ($currentBusId <= 0 || $currentBusId === $newBusId) {
            return false;
        }

        $currentVehicle = $route->relationLoaded('vehicle')
            ? $route->vehicle
            : Vehicle::where('deleted', 0)->where('id', $currentBusId)->first();

        return $currentVehicle ? $this->isVehicleEmergencyMarked($currentVehicle) : false;
    }

    private function syncEmergencyReplacementHistory(Route $route, int $oldBusId, int $newBusId, bool $shouldLogReplacement): void
    {
        if (! Schema::hasTable('route_vehicle_replacements') || $oldBusId <= 0 || $newBusId <= 0 || $oldBusId === $newBusId) {
            return;
        }

        if (! $shouldLogReplacement) {
            return;
        }

        $now = now();
        $activeRow = DB::table('route_vehicle_replacements')
            ->where('route_id', (int) $route->id)
            ->where('vehicle_id', $oldBusId)
            ->where('is_suspended', 0)
            ->orderByDesc('id')
            ->first(['id']);

        if ($activeRow) {
            DB::table('route_vehicle_replacements')
                ->where('id', (int) $activeRow->id)
                ->update([
                    'replacement_vehicle_id' => $newBusId,
                    'is_suspended' => 1,
                    'replaced_at' => $now,
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('route_vehicle_replacements')->insert([
                'route_id' => (int) $route->id,
                'vehicle_id' => $oldBusId,
                'replacement_vehicle_id' => $newBusId,
                'is_suspended' => 1,
                'replaced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('route_vehicle_replacements')
            ->where('route_id', (int) $route->id)
            ->where('vehicle_id', $newBusId)
            ->update([
                'replacement_vehicle_id' => null,
                'is_suspended' => 0,
                'updated_at' => $now,
            ]);

        $hasCurrentRow = DB::table('route_vehicle_replacements')
            ->where('route_id', (int) $route->id)
            ->where('vehicle_id', $newBusId)
            ->exists();

        if (! $hasCurrentRow) {
            DB::table('route_vehicle_replacements')->insert([
                'route_id' => (int) $route->id,
                'vehicle_id' => $newBusId,
                'replacement_vehicle_id' => null,
                'is_suspended' => 0,
                'replaced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function buildRouteReplacementLabel(Route $route, array $vehicleNumbersById = []): ?string
    {
        return $this->buildRouteReplacementLabelHtml($route, $vehicleNumbersById);
    }

    private function buildRouteReplacementLabelHtml(Route $route, array $vehicleNumbersById = []): ?string
    {
        $historyLabels = $this->getRouteReplacementHistoryLabels([(int) $route->id], $vehicleNumbersById, collect([(int) $route->id => $route]));

        return $historyLabels[(int) $route->id] ?? null;
    }

    private function getRouteReplacementHistoryLabels(array $routeIds, array $vehicleNumbersById = [], $routesById = null): array
    {
        $routeIds = array_values(array_unique(array_filter(array_map(function ($value) {
            return is_numeric($value) ? (int) $value : null;
        }, $routeIds), fn ($value) => $value && $value > 0)));

        if (empty($routeIds) || ! Schema::hasTable('route_vehicle_replacements')) {
            return [];
        }

        $historyRows = DB::table('route_vehicle_replacements')
            ->whereIn('route_id', $routeIds)
            ->orderBy('id')
            ->get(['route_id', 'vehicle_id', 'replacement_vehicle_id', 'is_suspended']);

        if ($historyRows->isEmpty()) {
            return [];
        }

        $allVehicleIds = collect($historyRows)
            ->flatMap(function ($row) {
                return [
                    (int) ($row->vehicle_id ?? 0),
                    (int) ($row->replacement_vehicle_id ?? 0),
                ];
            })
            ->merge(
                collect($routeIds)->map(function ($routeId) use ($routesById) {
                    return (int) (data_get($routesById, $routeId.'.bus_id') ?? 0);
                })
            )
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();

        $vehicleNumbersById = $vehicleNumbersById + $this->getVehicleNumberMapByIds($allVehicleIds);

        $labels = [];
        foreach ($historyRows->groupBy('route_id') as $routeId => $rows) {
            $chain = [];

            foreach ($rows as $row) {
                foreach ([
                    (int) ($row->vehicle_id ?? 0),
                    (int) ($row->replacement_vehicle_id ?? 0),
                ] as $vehicleId) {
                    if ($vehicleId > 0 && (empty($chain) || end($chain) !== $vehicleId)) {
                        $chain[] = $vehicleId;
                    }
                }
            }

            $currentBusId = (int) (data_get($routesById, $routeId.'.bus_id') ?? 0);
            if ($currentBusId > 0 && (empty($chain) || end($chain) !== $currentBusId)) {
                $chain[] = $currentBusId;
            }

            $numbers = array_values(array_filter(array_map(function ($vehicleId) use ($vehicleNumbersById) {
                return $vehicleNumbersById[$vehicleId] ?? null;
            }, $chain)));

            if (count($numbers) >= 2) {
                $labels[(int) $routeId] = $this->buildReplacementHistoryHtml($numbers);
            }
        }

        return $labels;
    }

    private function getRouteReplacementVehicleIds(array $routeIds, array $currentBusIds = []): array
    {
        $vehicleIds = collect($currentBusIds)
            ->map(fn ($value) => is_numeric($value) ? (int) $value : null)
            ->filter(fn ($value) => $value && $value > 0);

        if (! empty($routeIds) && Schema::hasTable('route_vehicle_replacements')) {
            $historyVehicleIds = DB::table('route_vehicle_replacements')
                ->whereIn('route_id', $routeIds)
                ->get(['vehicle_id', 'replacement_vehicle_id'])
                ->flatMap(function ($row) {
                    return [
                        (int) ($row->vehicle_id ?? 0),
                        (int) ($row->replacement_vehicle_id ?? 0),
                    ];
                });

            $vehicleIds = $vehicleIds->merge($historyVehicleIds);
        }

        return $vehicleIds
            ->filter(fn ($value) => $value && $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function buildReplacementHistoryHtml(array $numbers): ?string
    {
        $numbers = array_values(array_filter($numbers, fn ($value) => is_string($value) && trim($value) !== ''));
        if (count($numbers) < 2) {
            return null;
        }

        $parts = ['<div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">'];
        $seenVehicles = [];

        foreach ($numbers as $index => $vehicleNumber) {
            $isFirst = $index === 0;
            $isLast = $index === count($numbers) - 1;
            $normalizedVehicleNumber = mb_strtolower(trim($vehicleNumber));
            $isReassignedVehicle = $normalizedVehicleNumber !== '' && in_array($normalizedVehicleNumber, $seenVehicles, true);

            if ($isFirst) {
                $label = 'Original';
                $style = 'background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;';
            } elseif ($isLast) {
                $label = 'Current';
                $style = 'background:#dcfce7;color:#166534;border:1px solid #bbf7d0;';
            } elseif ($isReassignedVehicle) {
                $label = 'Reassign';
                $style = 'background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;';
            } else {
                $label = 'Replacement';
                $style = 'background:#fef3c7;color:#b45309;border:1px solid #fde68a;';
            }

            $parts[] = '<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;'.$style.'">'
                .'<span>'.$label.'</span>'
                .'<span>'.e($vehicleNumber).'</span>'
                .'</span>';

            if (! $isLast) {
                $parts[] = '<span style="color:#64748b;font-weight:700;">&rarr;</span>';
            }

            if ($normalizedVehicleNumber !== '' && ! in_array($normalizedVehicleNumber, $seenVehicles, true)) {
                $seenVehicles[] = $normalizedVehicleNumber;
            }
        }

        $parts[] = '</div>';

        return implode('', $parts);
    }

    private function syncDriverVehicleLink(int $driverId, int $vehicleId, $schoolId = null): void
    {
        if ($driverId <= 0 || $vehicleId <= 0) {
            return;
        }

        if (Schema::hasColumn('vehicles', 'driver_id')) {
            Vehicle::where('driver_id', $driverId)
                ->where('id', '!=', $vehicleId)
                ->update(['driver_id' => null]);
        }

        if (Schema::hasColumn('drivers', 'vehicle_id')) {
            Driver::where('id', $driverId)->update(['vehicle_id' => $vehicleId]);
        }

        if (Schema::hasColumn('vehicles', 'driver_id')) {
            Vehicle::where('id', $vehicleId)->update(['driver_id' => $driverId]);
        }

        if (Schema::hasTable('driver_vehicle_histories')) {
            $historyResetQuery = DB::table('driver_vehicle_histories')
                ->where(function ($query) use ($driverId, $vehicleId) {
                    $query->where('driver_id', $driverId)
                        ->orWhere('vehicle_id', $vehicleId);
                });

            if (Schema::hasColumn('driver_vehicle_histories', 'is_assigned')) {
                $historyResetQuery->update(['is_assigned' => 0]);
            }

            $historyPayload = [
                'driver_id' => $driverId,
                'vehicle_id' => $vehicleId,
            ];

            if (Schema::hasColumn('driver_vehicle_histories', 'is_assigned')) {
                $historyPayload['is_assigned'] = 1;
            }

            if (Schema::hasColumn('driver_vehicle_histories', 'school_id')) {
                $historyPayload['school_id'] = $schoolId ?: null;
            }

            if (Schema::hasColumn('driver_vehicle_histories', 'created_at')) {
                $historyPayload['created_at'] = now();
            }

            if (Schema::hasColumn('driver_vehicle_histories', 'updated_at')) {
                $historyPayload['updated_at'] = now();
            }

            DB::table('driver_vehicle_histories')->insert($historyPayload);
        }
    }

    private function getVehicleNumberMapByIds(array $vehicleIds): array
    {
        $vehicleIds = array_values(array_unique(array_filter(array_map(function ($value) {
            return is_numeric($value) ? (int) $value : null;
        }, $vehicleIds), fn ($value) => $value && $value > 0)));

        if (empty($vehicleIds)) {
            return [];
        }

        return Vehicle::query()
            ->whereIn('id', $vehicleIds)
            ->pluck('vehicle_number', 'id')
            ->toArray();
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

    private function getAssignedVehicleIds(?int $exceptRouteId = null): array
    {
        $query = Route::where('deleted', 0)
            ->whereNotNull('bus_id')
            ->when($exceptRouteId, function ($query, $exceptRouteId) {
                return $query->where('id', '!=', $exceptRouteId);
            });
        $this->applyRouteAccessScope($query, request());

        return $query->pluck('bus_id')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();
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

    private function hasRunningTripForVehicle(int $vehicleId): bool
    {
        if (! $vehicleId || ! Schema::hasTable('trips')) {
            return false;
        }

        $runningTrip = DB::table('trips')
            ->where('status', 'running')
            ->orderByDesc('id')
            ->first();

        if (! $runningTrip) {
            return false;
        }

        if (Schema::hasTable('trip_vehicle_segments')) {
            $activeSegmentExists = DB::table('trip_vehicle_segments')
                ->where('trip_id', (int) ($runningTrip->id ?? 0))
                ->where('status', 'active')
                ->where('vehicle_id', $vehicleId)
                ->exists();

            if ($activeSegmentExists) {
                return true;
            }
        }

        $driverUserId = (int) ($runningTrip->driverUserId ?? $runningTrip->driver_user_id ?? 0);
        if ($driverUserId <= 0) {
            return false;
        }

        return Driver::query()
            ->where('deleted', 0)
            ->where('vehicle_id', $vehicleId)
            ->where(function ($query) use ($driverUserId) {
                $applied = false;
                if (Schema::hasColumn('drivers', 'login_user_id')) {
                    $query->where('login_user_id', $driverUserId);
                    $applied = true;
                }

                if (Schema::hasColumn('drivers', 'user_id')) {
                    if ($applied) {
                        $query->orWhere('user_id', $driverUserId);
                    } else {
                        $query->where('user_id', $driverUserId);
                    }
                }
            })
            ->exists();
    }

    private function getAssignedDriverIds(?int $exceptRouteId = null): array
    {
        $query = Route::where('deleted', 0)
            ->whereNotNull('driver_id')
            ->when($exceptRouteId, function ($query, $exceptRouteId) {
                return $query->where('id', '!=', $exceptRouteId);
            });
        $this->applyRouteAccessScope($query, request());

        return $query->pluck('driver_id')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();
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

    private function cleanupDeletedRouteState(array $routeIds, array $driverIds = []): void
    {
        $routeIds = array_values(array_filter(array_map('intval', $routeIds)));
        if (empty($routeIds)) {
            return;
        }

        if (Schema::hasTable('trips')) {
            $routeColumn = Schema::hasColumn('trips', 'routeId')
                ? 'routeId'
                : (Schema::hasColumn('trips', 'route_id') ? 'route_id' : null);

            if ($routeColumn) {
                $tripUpdates = [
                    'status' => 'completed',
                ];

                if (Schema::hasColumn('trips', 'nextStop')) {
                    $tripUpdates['nextStop'] = null;
                } elseif (Schema::hasColumn('trips', 'next_stop')) {
                    $tripUpdates['next_stop'] = null;
                }

                if (Schema::hasColumn('trips', 'currentRoute')) {
                    $tripUpdates['currentRoute'] = null;
                } elseif (Schema::hasColumn('trips', 'current_route')) {
                    $tripUpdates['current_route'] = null;
                }

                if (Schema::hasColumn('trips', 'stops')) {
                    $tripUpdates['stops'] = json_encode([]);
                }

                if (Schema::hasColumn('trips', 'driverLat')) {
                    $tripUpdates['driverLat'] = null;
                }

                if (Schema::hasColumn('trips', 'driverLng')) {
                    $tripUpdates['driverLng'] = null;
                }

                if (Schema::hasColumn('trips', 'updated_at')) {
                    $tripUpdates['updated_at'] = now();
                }

                DB::table('trips')
                    ->whereIn($routeColumn, $routeIds)
                    ->where('status', 'running')
                    ->update($tripUpdates);
            }
        }

        $driverIds = array_values(array_filter(array_map('intval', $driverIds)));
        if (empty($driverIds) || ! Schema::hasTable('drivers')) {
            return;
        }

        $driverUpdates = [];
        if (Schema::hasColumn('drivers', 'current_route_json')) {
            $driverUpdates['current_route_json'] = null;
        }
        if (Schema::hasColumn('drivers', 'stops_json')) {
            $driverUpdates['stops_json'] = json_encode([]);
        }
        if (Schema::hasColumn('drivers', 'last_completed_stop_index')) {
            $driverUpdates['last_completed_stop_index'] = -1;
        }

        if (! empty($driverUpdates)) {
            DB::table('drivers')
                ->whereIn('id', $driverIds)
                ->update($driverUpdates);
        }
    }
    private function buildCustomLocationScopeQuery(Request $request)
    {
        return CustomRouteLocation::query()
            ->where(function ($innerQuery) {
                $innerQuery->where('deleted', 0)
                    ->orWhereNull('deleted');
            })
            ->where(function ($innerQuery) {
                $innerQuery->where('status', 1)
                    ->orWhereNull('status');
            });
    }

    private function resolveCustomLocationSchoolId(Request $request, int $persistedUserId): ?int
    {
        $currentSchool = $request->attributes->get('current_school');
        if (is_object($currentSchool) && isset($currentSchool->id) && is_numeric($currentSchool->id)) {
            return (int) $currentSchool->id;
        }

        if (is_array($currentSchool) && is_numeric($currentSchool['id'] ?? null)) {
            return (int) $currentSchool['id'];
        }

        $schoolSlug = trim((string) $request->route('schoolSlug'));
        if ($schoolSlug !== '') {
            $schoolId = School::where('deleted', 0)
                ->whereRaw('LOWER(slug) = ?', [strtolower($schoolSlug)])
                ->value('id');

            if (is_numeric($schoolId) && (int) $schoolId > 0) {
                return (int) $schoolId;
            }
        }

        $schoolId = School::where('user_id', $persistedUserId)
            ->where('deleted', 0)
            ->value('id');

        return is_numeric($schoolId) && (int) $schoolId > 0
            ? (int) $schoolId
            : null;
    }

    private function transformCustomLocationForMap(CustomRouteLocation $location): array
    {
        $name = trim((string) $location->name);
        $address = trim((string) ($location->address ?: $location->name));

        return [
            'id' => (int) $location->id,
            'name' => $name !== '' ? $name : 'Custom Point',
            'address' => $address !== '' ? $address : ($name !== '' ? $name : 'Custom Point'),
            'lat' => (float) $location->latitude,
            'lng' => (float) $location->longitude,
            'is_custom' => true,
        ];
    }

    private function parseRouteJsonPayload(Request $request): ?array
    {
        $incomingRouteJson = $request->input('route_json');

        if (is_string($incomingRouteJson) && trim($incomingRouteJson) !== '') {
            $decodedRouteJson = json_decode($incomingRouteJson, true);
            if (! is_array($decodedRouteJson)) {
                return null;
            }

            return $this->normalizeRouteJson($decodedRouteJson);
        }

        $geojson = $request->input('geojson');
        $stops = $request->input('stops');

        if (! $geojson && ! $stops) {
            return null;
        }

        $decodedGeoJson = is_string($geojson) ? json_decode($geojson, true) : $geojson;
        $decodedStops = is_string($stops) ? json_decode($stops, true) : $stops;

        return $this->normalizeRouteJson([
            'geojson' => $decodedGeoJson,
            'stops' => $decodedStops,
        ]);
    }

    private function hasRequiredRouteEndpoints(array $routeJson): bool
    {
        return is_array($routeJson['start_point'] ?? null)
            && is_array($routeJson['end_point'] ?? null)
            && is_array($routeJson['geojson'] ?? null);
    }

    private function routePointsBelongToSelectedCity(array $routeJson, string $city, string $state): bool
    {
        $city = trim($city);
        $state = trim($state);
        if ($city === '' || $state === '') {
            return false;
        }

        $cacheKey = 'route_city_bounds_' . md5(strtolower($city . '|' . $state));
        $bounds = Cache::remember($cacheKey, now()->addDays(30), function () use ($city, $state) {
            $response = Http::acceptJson()
                ->withHeaders(['User-Agent' => config('app.name', 'SchoolCabService') . ' route planner'])
                ->connectTimeout(6)
                ->timeout(15)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => 'in',
                    'q' => $city . ', ' . $state . ', India',
                ]);

            $box = $response->successful() ? data_get($response->json(), '0.boundingbox') : null;
            if (! is_array($box) || count($box) !== 4 || ! collect($box)->every('is_numeric')) {
                return null;
            }

            return [
                'south' => (float) $box[0],
                'north' => (float) $box[1],
                'west' => (float) $box[2],
                'east' => (float) $box[3],
            ];
        });

        // Browser-side selection already enforces the city. Do not reject a
        // valid route when the public geocoder is temporarily unreachable.
        if (! is_array($bounds)) {
            return true;
        }

        $points = array_filter(array_merge(
            [$routeJson['start_point'] ?? null],
            is_array($routeJson['pickup_points'] ?? null) ? $routeJson['pickup_points'] : [],
            [$routeJson['end_point'] ?? null],
        ), 'is_array');

        foreach ($points as $point) {
            $lat = $point['lat'] ?? $point['latitude'] ?? null;
            $lng = $point['lng'] ?? $point['lon'] ?? $point['longitude'] ?? null;
            if (! is_numeric($lat) || ! is_numeric($lng)) {
                return false;
            }

            if ((float) $lat < $bounds['south'] || (float) $lat > $bounds['north']
                || (float) $lng < $bounds['west'] || (float) $lng > $bounds['east']) {
                return false;
            }
        }

        return true;
    }

    private function buildGoogleWaypoint(float $lat, float $lng): array
    {
        return [
            'location' => [
                'latLng' => [
                    'latitude' => $lat,
                    'longitude' => $lng,
                ],
            ],
        ];
    }

    private function normalizeGooglePolyline($polyline): ?array
    {
        if (! is_array($polyline) || ($polyline['type'] ?? null) !== 'LineString' || ! is_array($polyline['coordinates'] ?? null)) {
            return null;
        }

        $coordinates = [];
        foreach ($polyline['coordinates'] as $coordinate) {
            if (! is_array($coordinate) || count($coordinate) < 2) {
                continue;
            }

            if (! is_numeric($coordinate[0]) || ! is_numeric($coordinate[1])) {
                continue;
            }

            $coordinates[] = [
                (float) $coordinate[0],
                (float) $coordinate[1],
            ];
        }

        if (count($coordinates) < 2) {
            return null;
        }

        return [
            'type' => 'LineString',
            'coordinates' => $coordinates,
        ];
    }

    private function parseGoogleDurationSeconds($duration): float
    {
        if (! is_string($duration) || trim($duration) === '') {
            return 0.0;
        }

        if (preg_match('/^-?\d+(?:\.\d+)?s$/', trim($duration)) === 1) {
            return (float) substr(trim($duration), 0, -1);
        }

        return 0.0;
    }

    private function getRouteDeletionUsageMap(array $routeIds): array
    {
        $routeIds = array_values(array_filter(array_map('intval', $routeIds)));
        if (empty($routeIds)) {
            return [];
        }

        $usageMap = [];
        foreach ($routeIds as $routeId) {
            $usageMap[$routeId] = [
                'children' => 0,
                'bookings' => 0,
                'stops' => 0,
                'total' => 0,
            ];
        }

        if (Schema::hasTable('children') && Schema::hasColumn('children', 'route_id')) {
            $childCounts = Child::query()
                ->select('route_id', DB::raw('COUNT(*) as aggregate'))
                ->whereIn('route_id', $routeIds)
                ->where(function ($query) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                })
                ->groupBy('route_id')
                ->pluck('aggregate', 'route_id')
                ->all();

            foreach ($childCounts as $routeId => $count) {
                if (! isset($usageMap[(int) $routeId])) {
                    continue;
                }

                $usageMap[(int) $routeId]['children'] = (int) $count;
            }
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'route_id')) {
            $bookingCounts = DB::table('bookings')
                ->select('route_id', DB::raw('COUNT(*) as aggregate'))
                ->whereIn('route_id', $routeIds)
                ->where(function ($query) {
                    if (Schema::hasColumn('bookings', 'deleted')) {
                        $query->where('deleted', 0)->orWhereNull('deleted');
                        return;
                    }

                    $query->whereRaw('1 = 1');
                })
                ->groupBy('route_id')
                ->pluck('aggregate', 'route_id')
                ->all();

            foreach ($bookingCounts as $routeId => $count) {
                if (! isset($usageMap[(int) $routeId])) {
                    continue;
                }

                $usageMap[(int) $routeId]['bookings'] = (int) $count;
            }
        }

        if (Schema::hasTable('stops_pickup') && Schema::hasColumn('stops_pickup', 'route_id')) {
            $stopCounts = DB::table('stops_pickup')
                ->select('route_id', DB::raw('COUNT(*) as aggregate'))
                ->whereIn('route_id', $routeIds)
                ->where(function ($query) {
                    if (Schema::hasColumn('stops_pickup', 'deleted')) {
                        $query->where('deleted', 0)->orWhereNull('deleted');
                        return;
                    }

                    $query->whereRaw('1 = 1');
                })
                ->groupBy('route_id')
                ->pluck('aggregate', 'route_id')
                ->all();

            foreach ($stopCounts as $routeId => $count) {
                if (! isset($usageMap[(int) $routeId])) {
                    continue;
                }

                $usageMap[(int) $routeId]['stops'] = (int) $count;
            }
        }

        foreach ($usageMap as $routeId => $usage) {
            $usageMap[$routeId]['total'] = (int) $usage['children'] + (int) $usage['bookings'] + (int) $usage['stops'];
        }

        return $usageMap;
    }

    private function sumRouteDeletionUsage(array $usageMap): array
    {
        $totals = [
            'children' => 0,
            'bookings' => 0,
            'stops' => 0,
            'total' => 0,
        ];

        foreach ($usageMap as $usage) {
            $totals['children'] += (int) ($usage['children'] ?? 0);
            $totals['bookings'] += (int) ($usage['bookings'] ?? 0);
            $totals['stops'] += (int) ($usage['stops'] ?? 0);
        }

        $totals['total'] = $totals['children'] + $totals['bookings'] + $totals['stops'];

        return $totals;
    }

    private function buildRouteDeletionBlockedMessage(array $usage, bool $plural = false): string
    {
        $parts = [];

        $childrenCount = (int) ($usage['children'] ?? 0);
        $bookingCount = (int) ($usage['bookings'] ?? 0);
        $stopCount = (int) ($usage['stops'] ?? 0);

        if ($childrenCount > 0) {
            $parts[] = $childrenCount.' active '.($childrenCount === 1 ? 'child' : 'children');
        }

        if ($bookingCount > 0) {
            $parts[] = $bookingCount.' '.($bookingCount === 1 ? 'booking' : 'bookings');
        }

        if ($stopCount > 0) {
            $parts[] = $stopCount.' '.($stopCount === 1 ? 'pickup stop' : 'pickup stops');
        }

        if (empty($parts)) {
            return $plural
                ? 'One or more selected routes are assigned and cannot be deleted.'
                : 'This route is assigned and cannot be deleted.';
        }

        $usageText = $this->joinDeletionUsageParts($parts);

        return $plural
            ? 'One or more selected routes are linked to '.$usageText.'. Remove those assignments before deleting routes.'
            : 'This route is linked to '.$usageText.'. Remove those assignments before deleting the route.';
    }

    private function joinDeletionUsageParts(array $parts): string
    {
        $parts = array_values(array_filter(array_map('trim', $parts)));
        $partCount = count($parts);

        if ($partCount === 0) {
            return '';
        }

        if ($partCount === 1) {
            return $parts[0];
        }

        if ($partCount === 2) {
            return $parts[0].' and '.$parts[1];
        }

        $lastPart = array_pop($parts);

        return implode(', ', $parts).', and '.$lastPart;
    }

    private function normalizeRouteJson(array $payload): array
    {
        $startPoint = $this->normalizeLocationPoint($payload['start_point'] ?? null, 'start', 1);
        $endPoint = $this->normalizeLocationPoint($payload['end_point'] ?? null, 'end');

        $pickupPoints = $this->normalizePointList($payload['pickup_points'] ?? [], 'pickup');
        $legacyStops = $this->normalizePointList($payload['stops'] ?? [], 'pickup');

        if (! $startPoint && ! $endPoint && empty($pickupPoints) && ! empty($legacyStops)) {
            $legacyOrderedPoints = array_values($legacyStops);
            $startPoint = $this->normalizeLocationPoint(array_shift($legacyOrderedPoints), 'start', 1);

            if (! empty($legacyOrderedPoints)) {
                $endPoint = $this->normalizeLocationPoint(
                    array_pop($legacyOrderedPoints),
                    'end',
                    count($legacyOrderedPoints) + 2
                );
                $pickupPoints = $this->normalizePointList($legacyOrderedPoints, 'pickup');
            }
        }

        $pickupPoints = array_values(array_filter(array_map(function ($point, $index) {
            return $this->normalizeLocationPoint($point, 'pickup', $index + 2);
        }, $pickupPoints, array_keys($pickupPoints))));

        if ($endPoint) {
            $endPoint['sequence'] = count($pickupPoints) + 2;
        }

        $orderedPoints = [];
        if ($startPoint) {
            $orderedPoints[] = $startPoint;
        }
        foreach ($pickupPoints as $pickupPoint) {
            $orderedPoints[] = $pickupPoint;
        }
        if ($endPoint) {
            $orderedPoints[] = $endPoint;
        }

        $geojson = $payload['geojson'] ?? null;
        if (! is_array($geojson) && isset($payload['type'], $payload['coordinates'])) {
            $geojson = [
                'type' => $payload['type'],
                'coordinates' => $payload['coordinates'],
            ];
        }

        return [
            'start_point' => $startPoint,
            'pickup_points' => $pickupPoints,
            'end_point' => $endPoint,
            'geojson' => $this->normalizeGeojson($geojson, $orderedPoints),
            'route_summary' => $this->normalizeRouteSummary($payload['route_summary'] ?? null),
            'route_alternatives' => $this->normalizeRouteAlternatives($payload['route_alternatives'] ?? []),
            'route_legs' => $this->normalizeRouteLegs($payload['route_legs'] ?? []),
            'stops' => array_values(array_filter($orderedPoints, 'is_array')),
        ];
    }

    private function normalizeRouteSummary($summary): ?array
    {
        if (! is_array($summary)) {
            return null;
        }

        return [
            'distance_meters' => is_numeric($summary['distance_meters'] ?? null) ? (float) $summary['distance_meters'] : null,
            'distance_text' => isset($summary['distance_text']) ? trim((string) $summary['distance_text']) : null,
            'duration_seconds' => is_numeric($summary['duration_seconds'] ?? null) ? (float) $summary['duration_seconds'] : null,
            'duration_text' => isset($summary['duration_text']) ? trim((string) $summary['duration_text']) : null,
            'summary' => isset($summary['summary']) ? trim((string) $summary['summary']) : null,
            'selected_route_index' => is_numeric($summary['selected_route_index'] ?? null)
                ? (int) $summary['selected_route_index']
                : null,
        ];
    }

    private function normalizeRouteAlternatives($alternatives): array
    {
        if (! is_array($alternatives)) {
            return [];
        }

        $normalized = [];
        foreach (array_values($alternatives) as $alternative) {
            if (! is_array($alternative)) {
                continue;
            }

            $normalized[] = [
                'index' => is_numeric($alternative['index'] ?? null) ? (int) $alternative['index'] : null,
                'distance_meters' => is_numeric($alternative['distance_meters'] ?? null) ? (float) $alternative['distance_meters'] : null,
                'distance_text' => isset($alternative['distance_text']) ? trim((string) $alternative['distance_text']) : null,
                'duration_seconds' => is_numeric($alternative['duration_seconds'] ?? null) ? (float) $alternative['duration_seconds'] : null,
                'duration_text' => isset($alternative['duration_text']) ? trim((string) $alternative['duration_text']) : null,
                'summary' => isset($alternative['summary']) ? trim((string) $alternative['summary']) : null,
            ];
        }

        return $normalized;
    }

    private function normalizeRouteLegs($legs): array
    {
        if (! is_array($legs)) {
            return [];
        }

        $normalized = [];
        foreach (array_values($legs) as $leg) {
            if (! is_array($leg)) {
                continue;
            }

            $normalized[] = [
                'index' => is_numeric($leg['index'] ?? null) ? (int) $leg['index'] : null,
                'from_sequence' => is_numeric($leg['from_sequence'] ?? null) ? (int) $leg['from_sequence'] : null,
                'to_sequence' => is_numeric($leg['to_sequence'] ?? null) ? (int) $leg['to_sequence'] : null,
                'distance_meters' => is_numeric($leg['distance_meters'] ?? null) ? (float) $leg['distance_meters'] : null,
                'distance_text' => isset($leg['distance_text']) ? trim((string) $leg['distance_text']) : null,
                'duration_seconds' => is_numeric($leg['duration_seconds'] ?? null) ? (float) $leg['duration_seconds'] : null,
                'duration_text' => isset($leg['duration_text']) ? trim((string) $leg['duration_text']) : null,
                'summary' => isset($leg['summary']) ? trim((string) $leg['summary']) : null,
            ];
        }

        return $normalized;
    }

    private function normalizePointList($points, string $defaultType): array
    {
        if (! is_array($points)) {
            return [];
        }

        $normalized = [];
        foreach (array_values($points) as $index => $point) {
            $normalizedPoint = $this->normalizeLocationPoint($point, $defaultType, $index + 1);
            if ($normalizedPoint) {
                $normalized[] = $normalizedPoint;
            }
        }

        return $normalized;
    }

    private function normalizeLocationPoint($point, string $defaultType, ?int $sequence = null): ?array
    {
        if (! is_array($point)) {
            return null;
        }

        $lat = $point['lat'] ?? $point['latitude'] ?? null;
        $lng = $point['lng'] ?? $point['lon'] ?? $point['longitude'] ?? null;

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $defaultName = ucfirst($defaultType) . ' Point';
        $name = trim((string) ($point['name'] ?? $point['title'] ?? $point['address'] ?? $point['display_name'] ?? $defaultName));
        $address = trim((string) ($point['address'] ?? $point['display_name'] ?? $name));
        $resolvedSequence = is_numeric($point['sequence'] ?? null)
            ? (int) $point['sequence']
            : $sequence;

        return [
            'name' => $name !== '' ? $name : $defaultName,
            'address' => $address !== '' ? $address : ($name !== '' ? $name : $defaultName),
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'type' => trim((string) ($point['type'] ?? $defaultType)) ?: $defaultType,
            'sequence' => $resolvedSequence,
        ];
    }

    private function normalizeGeojson($geojson, array $orderedPoints): ?array
    {
        if (is_array($geojson) && ($geojson['type'] ?? null) === 'LineString' && is_array($geojson['coordinates'] ?? null)) {
            $coordinates = [];
            foreach ($geojson['coordinates'] as $coordinate) {
                if (
                    is_array($coordinate)
                    && count($coordinate) >= 2
                    && is_numeric($coordinate[0] ?? null)
                    && is_numeric($coordinate[1] ?? null)
                ) {
                    $coordinates[] = [(float) $coordinate[0], (float) $coordinate[1]];
                }
            }

            if (count($coordinates) >= 2) {
                return [
                    'type' => 'LineString',
                    'coordinates' => $coordinates,
                ];
            }
        }

        $coordinates = [];
        foreach ($orderedPoints as $point) {
            if (is_numeric($point['lng'] ?? null) && is_numeric($point['lat'] ?? null)) {
                $coordinates[] = [(float) $point['lng'], (float) $point['lat']];
            }
        }

        if (count($coordinates) < 2) {
            return null;
        }

        return [
            'type' => 'LineString',
            'coordinates' => $coordinates,
        ];
    }
}

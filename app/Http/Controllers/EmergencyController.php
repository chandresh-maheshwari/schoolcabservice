<?php
namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Driver;
use App\Models\Emergency;
use App\Models\EmergencyType;
use App\Models\Parents;
use App\Models\School;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class EmergencyController extends Controller
{
    public function __construct(private readonly PushNotificationService $pushNotifications)
    {
    }

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
        $indexColumn = (int) $request->input('iSortCol_0', 0);
        $columnKey   = $request->input('mDataProp_' . $indexColumn, 'id');

        // DataTables sends presentation keys like "driver_name" even though the DB stores driver_id/vehicle_id.
        // Map the keys to actual sortable columns and join only when needed.
        $sortableKeys = [
            'id',
            'school_name',
            'driver_name',
            'vehicle_number',
            'reported_by',
            'emergency_type',
            'contact_number',
            'description',
            'status',
        ];

        $columnKey = in_array($columnKey, $sortableKeys, true) ? $columnKey : 'id';

        $columnSortOrder = in_array(
            $request->input('sSortDir_0'),
            ['asc', 'desc']
        ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        $query = Emergency::query()
            ->with(['driver', 'vehicle'])
            ->where('emergency_incidents.deleted', 0);

        if ($columnKey === 'driver_name') {
            $query->leftJoin('drivers', 'emergency_incidents.driver_id', '=', 'drivers.id');
        } elseif ($columnKey === 'vehicle_number') {
            $query->leftJoin('vehicles', 'emergency_incidents.vehicle_id', '=', 'vehicles.id');
        } elseif ($columnKey === 'school_name') {
            $query->leftJoin('schools', function ($join) {
                $join->on('emergency_incidents.user_id', '=', 'schools.user_id')
                    ->where('schools.deleted', 0);
            });
        }

        $query->select('emergency_incidents.*');
        $this->applyEmergencyVisibilityScope($query, $request, 'emergency_incidents.user_id');
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('reported_by', 'like', "%$searchValue%")
                    ->orWhere('emergency_type', 'like', "%$searchValue%")
                    ->orWhere('contact_number', 'like', "%$searchValue%")
                    ->orWhere('description', 'like', "%$searchValue%");

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
            'id' => 'emergency_incidents.id',
            'reported_by' => 'emergency_incidents.reported_by',
            'emergency_type' => 'emergency_incidents.emergency_type',
            'contact_number' => 'emergency_incidents.contact_number',
            'description' => 'emergency_incidents.description',
            'status' => 'emergency_incidents.status',
            'driver_name' => 'drivers.driver_name',
            'vehicle_number' => 'vehicles.vehicle_number',
            'school_name' => 'schools.school_name',
        ];

        $sortColumn = $sortColumnMap[$columnKey] ?? 'emergency_incidents.id';

        $emergencyDetails = $query
            ->orderBy($sortColumn, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        $data = [];
        $schoolNameMap = $this->getSchoolNameMapForUserIds($emergencyDetails->pluck('user_id')->all());
        $schoolNamesByDriverId = $this->getSchoolNameMapForDriverIds($emergencyDetails->pluck('driver_id')->all());
        $schoolNamesByVehicleId = $this->getSchoolNameMapForVehicleIds($emergencyDetails->pluck('vehicle_id')->all());

        foreach ($emergencyDetails as $emergency) {
            $data[] = [
                'id'             => $emergency->id,
                'school_name'    => $schoolNameMap[$emergency->user_id]
                    ?? $schoolNamesByDriverId[(int) ($emergency->driver_id ?? 0)]
                    ?? $schoolNamesByVehicleId[(int) ($emergency->vehicle_id ?? 0)]
                    ?? '-',
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
            'draw'                 => intval($draw),
            'sEcho'                => intval($draw),
            'recordsTotal'         => $totalRecords,
            'recordsFiltered'      => $totalRecordwithFilter,
            'iTotalRecords'        => $totalRecords,
            'iTotalDisplayRecords' => $totalRecordwithFilter,
            'data'                 => $data,
            'aaData'               => $data,
        ]);
    }

    /**
     * Display emergency create form.
     * created by ns
     */
    public function create()
    {
        $drivers = Driver::query()
            ->where('deleted', 0)
            ->where('status', 1)
            ->select('id', 'driver_name', 'vehicle_id')
            ->orderBy('driver_name');
        $this->applySchoolAwareScope($drivers, request(), 'user_id', Schema::hasColumn('drivers', 'school_id') ? 'school_id' : null);
        $drivers = $drivers->get();

        $vehicles = Vehicle::query()
            ->where('deleted', 0)
            ->where('status', 1)
            ->select('id', 'vehicle_number', 'driver_id')
            ->orderBy('vehicle_number');
        $this->applySchoolAwareScope($vehicles, request(), 'user_id', Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);
        $vehicles = $vehicles->get();

        $emergencyTypes = EmergencyType::query()
            ->where('deleted', 0)
            ->where('status', 1)
            ->orderBy('emergency_type');
        $this->applySchoolAwareScope($emergencyTypes, request(), 'user_id', Schema::hasColumn('emergency_types', 'school_id') ? 'school_id' : null);
        $emergencyTypes = $emergencyTypes->get(['id', 'emergency_type']);

        return view('emergency.create', compact('drivers', 'vehicles', 'emergencyTypes'));
    }

    /**
     * Store emergency data.
     * created by ns
     */
    public function store(Request $request)
    {

        $request->validate([
            'reported_by'    => 'required|in:parent,admin,driver',
            'emergency_type' => 'required|string|max:100',
            'description'    => 'required|string|max:1000',
            'contact_number' => 'required|digits_between:10,11',

        ]);

        $driverId = $this->extractDriverId($request);
        $vehicleId = $this->extractVehicleId($request);
        $this->ensureScopedEmergencyRelations($request, $driverId, $vehicleId);
        $ownerUserId = $this->resolveEmergencyOwnerUserId($request, $driverId, $vehicleId);

        Emergency::create([
            'user_id'        => $ownerUserId,
            'driver_id'      => $driverId,
            'vehicle_id'     => $vehicleId,
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

    public function storeDriverEmergency(Request $request)
    {
        $validated = $request->validate([
            'emergency_type' => 'required|string|max:100',
            'description' => 'required|string|max:1000',
            'contact_number' => 'nullable|digits_between:10,11',
        ]);

        $driver = Driver::query()
            ->where(function ($query) {
                $query->where('deleted', 0)->orWhereNull('deleted');
            })
            ->where(function ($query) {
                $query->where('login_user_id', (int) Auth::id());
                if (\Illuminate\Support\Facades\Schema::hasColumn('drivers', 'user_id')) {
                    $query->orWhere('user_id', (int) Auth::id());
                }
            })
            ->with('vehicle')
            ->firstOrFail();

        $ownerUserId = $this->resolveEmergencyOwnerUserIdFromDriver($driver);

        $emergency = Emergency::create([
            'user_id' => $ownerUserId > 0 ? $ownerUserId : null,
            'driver_id' => (int) $driver->id,
            'vehicle_id' => $driver->vehicle_id ? (int) $driver->vehicle_id : optional($driver->vehicle)->id,
            'reported_by' => 'driver',
            'emergency_type' => $validated['emergency_type'],
            'description' => $validated['description'],
            'contact_number' => $validated['contact_number'] ?? $driver->emergency_phone ?? $driver->driver_phone,
            'status' => 0,
            'deleted' => 0,
        ]);

        $recipientUserIds = $this->driverEmergencyRecipientUserIds($driver, $ownerUserId);
        if ($recipientUserIds !== []) {
            $driverName = trim((string) ($driver->driver_name ?? ''));
            if ($driverName === '') {
                $driverName = 'Driver';
            }

            $description = trim((string) ($validated['description'] ?? ''));
            $this->pushNotifications->sendEventToUsers(
                'driver_emergency_alert',
                $recipientUserIds,
                [
                    'driverName' => $driverName,
                    'emergencyType' => (string) $validated['emergency_type'],
                    'routeLabel' => $this->driverEmergencyRouteLabel($driver),
                    'detailSuffix' => $description !== '' ? ': ' . $description : '',
                ],
                [
                    'emergencyId' => (int) $emergency->id,
                    'driverId' => (int) $driver->id,
                    'vehicleId' => (int) ($emergency->vehicle_id ?? 0),
                    'reportedBy' => 'driver',
                    'contactNumber' => (string) ($emergency->contact_number ?? ''),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Emergency alert sent successfully.',
            'data' => $emergency,
        ], 201);
    }

    public function getDriverSchoolEmergencyContact(Request $request)
    {
        $driver = $this->resolveDriverFromRequest($request);

        if (! $driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not found.',
            ], 404);
        }

        $ownerUserId = (int) ($driver->user_id ?? optional($driver->vehicle)->user_id ?? 0);
        $school = $ownerUserId > 0
            ? School::query()
                ->where(function ($query) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                })
                ->where('user_id', $ownerUserId)
                ->first()
            : null;

        $phone = trim((string) ($school->phone ?? ''));

        return response()->json([
            'success' => $phone !== '',
            'message' => $phone !== '' ? 'School emergency contact fetched successfully.' : 'School emergency contact not found.',
            'data' => [
                'schoolName' => (string) ($school->school_name ?? ''),
                'schoolContact' => $phone,
                'driverName' => (string) ($driver->driver_name ?? ''),
                'vehicleNumber' => (string) (optional($driver->vehicle)->vehicle_number ?? ''),
            ],
        ], $phone !== '' ? 200 : 404);
    }

    public function getDriverEmergencyHistory(Request $request)
    {
        $driver = $this->resolveDriverFromRequest($request);
        $identifiers = $this->resolveDriverEmergencyHistoryIdentifiers($request, $driver);
        if (
            $identifiers['ownerUserId'] <= 0 &&
            $identifiers['driverId'] <= 0 &&
            $identifiers['vehicleId'] <= 0
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not found.',
                'data' => [],
            ], 404);
        }

        $query = Emergency::query()
            ->where(function ($deletedQuery) {
                $deletedQuery->where('deleted', 0)->orWhereNull('deleted');
            })
            ->where(function ($incidentQuery) use ($identifiers) {
                if ($identifiers['driverId'] > 0) {
                    $incidentQuery->orWhere('driver_id', $identifiers['driverId']);
                }

                if ($identifiers['vehicleId'] > 0) {
                    $incidentQuery->orWhere('vehicle_id', $identifiers['vehicleId']);
                }

                if (
                    $identifiers['driverId'] <= 0 &&
                    $identifiers['vehicleId'] <= 0 &&
                    $identifiers['ownerUserId'] > 0
                ) {
                    $incidentQuery->orWhere('user_id', $identifiers['ownerUserId']);
                }
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10);

        $items = $query->get()->map(function (Emergency $emergency) {
            return [
                'id' => (int) $emergency->id,
                'emergencyType' => (string) ($emergency->emergency_type ?? 'Emergency'),
                'description' => (string) ($emergency->description ?? ''),
                'contactNumber' => (string) ($emergency->contact_number ?? ''),
                'status' => (int) ($emergency->status ?? 0) === 1 ? 'reported' : (string) ($emergency->status ?? 'reported'),
                'createdAt' => optional($emergency->created_at)->toIso8601String(),
                'updatedAt' => optional($emergency->updated_at)->toIso8601String(),
                'reportedBy' => (string) ($emergency->reported_by ?? ''),
                'source' => 'shared',
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    private function resolveDriverEmergencyHistoryIdentifiers(
        Request $request,
        ?Driver $driver = null
    ): array {
        $ownerUserId = 0;
        $driverId = 0;
        $vehicleId = 0;

        if ($driver) {
            $ownerUserId = $this->resolveEmergencyOwnerUserIdFromDriver($driver);
            $driverId = (int) ($driver->id ?? 0);
            $vehicleId = (int) ($driver->vehicle_id ?? optional($driver->vehicle)->id ?? 0);
        }

        $requestedUserId = (int) $request->query('user_id', $request->input('user_id', 0));
        $requestedDriverId = (int) $request->query('driver_id', $request->input('driver_id', 0));
        $requestedVehicleId = (int) $request->query('vehicle_id', $request->input('vehicle_id', 0));

        return [
            'ownerUserId' => $ownerUserId > 0 ? $ownerUserId : $requestedUserId,
            'driverId' => $driverId > 0 ? $driverId : $requestedDriverId,
            'vehicleId' => $vehicleId > 0 ? $vehicleId : $requestedVehicleId,
        ];
    }

    public function storeDriverEmergencyFromEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'emergencyType' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'contactNumber' => 'nullable|digits_between:10,11',
        ]);

        $driver = $this->resolveDriverFromRequest($request);
        if (! $driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found.',
            ], 404);
        }

        $ownerUserId = $this->resolveEmergencyOwnerUserIdFromDriver($driver);

        $emergency = Emergency::create([
            'user_id' => $ownerUserId > 0 ? $ownerUserId : null,
            'driver_id' => (int) $driver->id,
            'vehicle_id' => $driver->vehicle_id ? (int) $driver->vehicle_id : optional($driver->vehicle)->id,
            'reported_by' => 'driver',
            'emergency_type' => $validated['emergencyType'],
            'description' => trim((string) ($validated['description'] ?? '')),
            'contact_number' => $validated['contactNumber'] ?? $driver->emergency_phone ?? $driver->driver_phone,
            'status' => 0,
            'deleted' => 0,
        ]);

        $recipientUserIds = $this->driverEmergencyRecipientUserIds($driver, $ownerUserId);
        if ($recipientUserIds !== []) {
            $driverName = trim((string) ($driver->driver_name ?? ''));
            if ($driverName === '') {
                $driverName = 'Driver';
            }

            $routeLabel = $this->driverEmergencyRouteLabel($driver);
            $description = trim((string) ($validated['description'] ?? ''));

            $this->pushNotifications->sendEventToUsers(
                'driver_emergency_alert',
                $recipientUserIds,
                [
                    'driverName' => $driverName,
                    'emergencyType' => (string) $validated['emergencyType'],
                    'routeLabel' => $routeLabel,
                    'detailSuffix' => $description !== '' ? ': ' . $description : '',
                ],
                [
                    'emergencyId' => (int) $emergency->id,
                    'driverId' => (int) $driver->id,
                    'vehicleId' => (int) ($emergency->vehicle_id ?? 0),
                    'reportedBy' => 'driver',
                    'contactNumber' => (string) ($emergency->contact_number ?? ''),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Emergency alert sent successfully.',
            'data' => $emergency,
        ], 201);
    }

    /**
     * Display emergency edit form.
     * created by ns
     */
    public function edit($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);

        $query = Emergency::query();
        $this->applyEmergencyVisibilityScope($query, request(), 'user_id');
        $emergency = $query->findOrFail($id);
        $drivers = Driver::query()
            ->where('deleted', 0)
            ->where('status', 1)
            ->select('id', 'driver_name', 'vehicle_id')
            ->orderBy('driver_name');
        $this->applySchoolAwareScope($drivers, request(), 'user_id', Schema::hasColumn('drivers', 'school_id') ? 'school_id' : null);
        $drivers = $drivers->get();

        $vehicles = Vehicle::query()
            ->where('deleted', 0)
            ->where('status', 1)
            ->select('id', 'vehicle_number', 'driver_id')
            ->orderBy('vehicle_number');
        $this->applySchoolAwareScope($vehicles, request(), 'user_id', Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);
        $vehicles = $vehicles->get();

        $emergencyTypes = EmergencyType::query()
            ->where('deleted', 0)
            ->where('status', 1)
            ->orderBy('emergency_type');
        $this->applySchoolAwareScope($emergencyTypes, request(), 'user_id', Schema::hasColumn('emergency_types', 'school_id') ? 'school_id' : null);
        $emergencyTypes = $emergencyTypes->get(['id', 'emergency_type']);

        $replacementVehicles = $this->getEmergencyReplacementVehicles(request(), (int) ($emergency->vehicle_id ?? 0));
        $replacementDrivers = $this->getEmergencyReplacementDrivers(request(), (int) ($emergency->driver_id ?? 0));
        $runningTripState = $this->getRunningTripReplacementState((int) ($emergency->vehicle_id ?? 0));

        return view('emergency.edit', compact('emergency', 'drivers', 'vehicles', 'emergencyTypes', 'replacementVehicles', 'replacementDrivers', 'runningTripState'));
    }

    /**
     * Update emergency data.
     * created by ns
     */
    public function update(Request $request, $schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $request->validate([
            'status' => 'required|in:0,1',
            'additional_comment' => 'nullable|string|max:2000',
            'replacement_vehicle_id' => 'nullable|integer|min:1',
            'replacement_driver_id' => 'nullable|integer|min:1',
            'handover_action' => 'nullable|in:assign_replacement,mark_arrived,continue_trip',
        ]);

        $query = Emergency::query();
        $this->applyEmergencyVisibilityScope($query, $request, 'user_id');
        $emergency = $query->findOrFail($id);
        $handoverResponse = null;

        $replacementVehicleId = (int) $request->input('replacement_vehicle_id', 0);
        $replacementDriverId = (int) $request->input('replacement_driver_id', 0);
        $handoverAction = trim((string) $request->input('handover_action', ''));
        $requiresRunningTripReplacement = (int) $request->status === 1
            && $this->hasRunningTripForVehicle((int) ($emergency->vehicle_id ?? 0));

        if ($requiresRunningTripReplacement && $handoverAction !== '') {
            if ($handoverAction === 'assign_replacement' && ($replacementVehicleId <= 0 || $replacementDriverId <= 0)) {
                throw ValidationException::withMessages([
                    'replacement_vehicle_id' => 'Replacement vehicle is required for running trip emergency handover.',
                    'replacement_driver_id' => 'Replacement driver is required for running trip emergency handover.',
                ]);
            }

            $replacementVehicle = $handoverAction === 'assign_replacement'
                ? $this->resolveEmergencyReplacementVehicle($request, $replacementVehicleId)
                : null;
            $replacementDriver = $handoverAction === 'assign_replacement'
                ? $this->resolveEmergencyReplacementDriver($request, $replacementDriverId)
                : null;

            if ($handoverAction === 'assign_replacement' && ! $replacementVehicle) {
                throw ValidationException::withMessages([
                    'replacement_vehicle_id' => 'Selected replacement vehicle is not available for handover.',
                ]);
            }

            if ($handoverAction === 'assign_replacement' && ! $replacementDriver) {
                throw ValidationException::withMessages([
                    'replacement_driver_id' => 'Selected replacement driver is not available for handover.',
                ]);
            }

            if ($handoverAction === 'assign_replacement' && (int) ($replacementDriver->vehicle_id ?? 0) !== (int) $replacementVehicle->id) {
                throw ValidationException::withMessages([
                    'replacement_driver_id' => 'Selected replacement driver is not linked to the selected replacement vehicle.',
                ]);
            }

            $handoverResponse = Http::acceptJson()
                ->timeout(20)
                ->post(rtrim((string) env('SCB_BACKEND_URL', 'http://127.0.0.1:3000'), '/') . '/trip/handover', [
                    'action' => $handoverAction,
                    'emergencyIncidentId' => (int) $emergency->id,
                    'vehicle_id' => (int) ($emergency->vehicle_id ?? 0),
                    'replacement_vehicle_id' => (int) ($replacementVehicle->id ?? 0),
                    'replacement_driver_id' => (int) ($replacementDriver->id ?? 0),
                ]);

            if ($handoverResponse->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => data_get($handoverResponse->json(), 'message', 'Running trip handover failed.'),
                ], $handoverResponse->status() >= 400 ? $handoverResponse->status() : 422);
            }
        }

        $emergency->update([
            'status' => (int) $request->status,
            'additional_comment' => $request->additional_comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => $handoverAction !== ''
                ? data_get($handoverResponse?->json(), 'message', 'Emergency handover updated successfully.')
                : 'Emergency status updated successfully.',
        ]);
    }

    /**
     * Soft delete emergency record.
     * created by ns
     */
    public function destroy($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);

        $query = Emergency::query();
        $this->applyEmergencyVisibilityScope($query, request(), 'user_id');
        $emergency = $query->findOrFail($id);

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
    public function toggleStatus($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);

        $query = Emergency::query();
        $this->applyEmergencyVisibilityScope($query, request(), 'user_id');
        $emergency = $query->findOrFail($id);

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
        $query = Emergency::where('deleted', 0)
            ->where('status', true);
        $this->applyEmergencyVisibilityScope($query, request(), 'user_id');

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

        $query = Emergency::whereIn('id', $ids);
        $this->applyEmergencyVisibilityScope($query, $request, 'user_id');
        $query->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected routes deleted successfully',
        ]);
    }

    private function getEmergencyReplacementVehicles(Request $request, ?int $currentVehicleId = null)
    {
        $query = Vehicle::query()
            ->where('deleted', 0)
            ->where('status', 1)
            ->whereNotNull('driver_id');

        if (Schema::hasColumn('vehicles', 'availability_status')) {
            $query->where(function ($vehicleQuery) {
                $vehicleQuery->whereNull('availability_status')
                    ->orWhere('availability_status', 'available');
            });
        }

        if (Schema::hasColumn('vehicles', 'is_assigned')) {
            $query->where(function ($vehicleQuery) {
                $vehicleQuery->whereNull('is_assigned')
                    ->orWhere('is_assigned', 0);
            });
        }

        $this->applySchoolAwareScope($query, $request, 'user_id', Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);

        return $query
            ->orderBy('vehicle_number')
            ->get(['id', 'vehicle_number', 'driver_id', 'availability_status', 'is_assigned'])
            ->filter(function (Vehicle $vehicle) use ($currentVehicleId) {
                if ($currentVehicleId > 0 && (int) $vehicle->id === $currentVehicleId) {
                    return false;
                }

                if (Schema::hasColumn('vehicles', 'availability_status') && strtolower((string) ($vehicle->availability_status ?? 'available')) === 'emergency') {
                    return false;
                }

                if ($this->isVehicleAssignedToActiveRoute((int) $vehicle->id)) {
                    return false;
                }

                $linkedDriverId = (int) ($vehicle->driver_id ?? 0);
                if ($linkedDriverId <= 0) {
                    return false;
                }

                $linkedDriver = Driver::query()
                    ->where('deleted', 0)
                    ->where('status', 1)
                    ->where('id', $linkedDriverId)
                    ->first(['id', 'vehicle_id']);

                if (! $linkedDriver || (int) ($linkedDriver->vehicle_id ?? 0) !== (int) $vehicle->id) {
                    return false;
                }

                if ($this->isDriverAssignedToActiveRoute($linkedDriverId)) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    private function getEmergencyReplacementDrivers(Request $request, ?int $currentDriverId = null)
    {
        $availableVehicleIds = $this->getEmergencyReplacementVehicles($request)
            ->pluck('id')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->values()
            ->all();

        $query = Driver::query()
            ->where('deleted', 0)
            ->where('status', 1);

        $this->applySchoolAwareScope($query, $request, 'user_id', Schema::hasColumn('drivers', 'school_id') ? 'school_id' : null);

        return $query
            ->orderBy('driver_name')
            ->get(['id', 'driver_name', 'vehicle_id'])
            ->filter(function (Driver $driver) use ($currentDriverId, $availableVehicleIds) {
                if ($currentDriverId > 0 && (int) $driver->id === $currentDriverId) {
                    return false;
                }

                if ((int) ($driver->vehicle_id ?? 0) <= 0) {
                    return false;
                }

                if ($this->isDriverAssignedToActiveRoute((int) $driver->id)) {
                    return false;
                }

                if (! in_array((int) ($driver->vehicle_id ?? 0), $availableVehicleIds, true)) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    private function resolveEmergencyReplacementVehicle(Request $request, int $vehicleId): ?Vehicle
    {
        return $this->getEmergencyReplacementVehicles($request)
            ->firstWhere('id', $vehicleId);
    }

    private function resolveEmergencyReplacementDriver(Request $request, int $driverId): ?Driver
    {
        return $this->getEmergencyReplacementDrivers($request)
            ->firstWhere('id', $driverId);
    }

    private function isVehicleAssignedToActiveRoute(int $vehicleId): bool
    {
        if ($vehicleId <= 0 || ! Schema::hasTable('routes')) {
            return false;
        }

        $query = \App\Models\Route::query()
            ->where('deleted', 0)
            ->where('bus_id', $vehicleId);

        if (Schema::hasColumn('routes', 'status')) {
            $query->where('status', 1);
        }

        return $query->exists();
    }

    private function isDriverAssignedToActiveRoute(int $driverId): bool
    {
        if ($driverId <= 0 || ! Schema::hasTable('routes')) {
            return false;
        }

        $query = \App\Models\Route::query()
            ->where('deleted', 0)
            ->where('driver_id', $driverId);

        if (Schema::hasColumn('routes', 'status')) {
            $query->where('status', 1);
        }

        return $query->exists();
    }

    private function hasRunningTripForVehicle(int $vehicleId): bool
    {
        return ($this->getRunningTripReplacementState($vehicleId)['has_running_trip'] ?? false) === true;
    }

    private function getRunningTripReplacementState(int $vehicleId): array
    {
        if ($vehicleId <= 0 || ! Schema::hasTable('trips')) {
            return [
                'has_running_trip' => false,
                'current_trip_id' => null,
                'current_segment' => null,
                'pending_segment' => null,
                'stage' => 'none',
            ];
        }

        $runningTrip = \Illuminate\Support\Facades\DB::table('trips')
            ->where('status', 'running')
            ->orderByDesc('id')
            ->first();

        if (! $runningTrip) {
            return [
                'has_running_trip' => false,
                'current_trip_id' => null,
                'current_segment' => null,
                'pending_segment' => null,
                'stage' => 'none',
            ];
        }

        $activeSegment = null;
        $pendingSegment = null;
        if (Schema::hasTable('trip_vehicle_segments')) {
            $segments = \Illuminate\Support\Facades\DB::table('trip_vehicle_segments')
                ->where('trip_id', (int) ($runningTrip->id ?? 0))
                ->orderBy('segment_order')
                ->orderBy('id')
                ->get();

            foreach ($segments as $segment) {
                $status = strtolower((string) ($segment->status ?? ''));
                if ($status === 'active') {
                    $activeSegment = $segment;
                }
                if (in_array($status, ['assigned', 'arrived'], true)) {
                    $pendingSegment = $segment;
                }
            }
        }

        $driverUserId = (int) ($runningTrip->driverUserId ?? $runningTrip->driver_user_id ?? 0);
        $matchesActiveDriverVehicle = false;
        if ($driverUserId > 0) {
            $driverQuery = Driver::query()->where('deleted', 0)->where('vehicle_id', $vehicleId);
            $driverQuery->where(function ($query) use ($driverUserId) {
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
            });
            $matchesActiveDriverVehicle = $driverQuery->exists();
        }

        $hasRunningTrip = ((int) ($activeSegment->vehicle_id ?? 0) === $vehicleId)
            || $matchesActiveDriverVehicle;

        $stage = 'none';
        if ($hasRunningTrip) {
            if ($pendingSegment && strtolower((string) ($pendingSegment->status ?? '')) === 'assigned') {
                $stage = 'assigned';
            } elseif ($pendingSegment && strtolower((string) ($pendingSegment->status ?? '')) === 'arrived') {
                $stage = 'arrived';
            } else {
                $stage = 'active';
            }
        }

        return [
            'has_running_trip' => $hasRunningTrip,
            'current_trip_id' => (int) ($runningTrip->id ?? 0) ?: null,
            'current_segment' => $activeSegment,
            'pending_segment' => $pendingSegment,
            'stage' => $stage,
        ];
    }

    private function extractDriverId(Request $request): ?int
    {
        foreach (['driver_id', 'driver_name'] as $key) {
            $value = $request->input($key);
            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    private function extractVehicleId(Request $request): ?int
    {
        foreach (['vehicle_id', 'vehicle_number'] as $key) {
            $value = $request->input($key);
            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    private function resolveEmergencyOwnerUserId(Request $request, ?int $driverId, ?int $vehicleId): ?int
    {
        if ($driverId) {
            $driver = Driver::query()
                ->with('vehicle')
                ->find($driverId);

            if ($driver) {
                $driverOwnerUserId = $this->resolveEmergencyOwnerUserIdFromDriver($driver);
                if ($driverOwnerUserId > 0) {
                    return $driverOwnerUserId;
                }
            }
        }

        if ($vehicleId) {
            $vehicle = Vehicle::query()->find($vehicleId);
            if ($vehicle) {
                $vehicleOwnerUserId = $this->resolveEmergencyOwnerUserIdFromVehicle($vehicle);
                if ($vehicleOwnerUserId > 0) {
                    return $vehicleOwnerUserId;
                }
            }
        }

        $contextSchoolOwnerUserId = $this->resolveSchoolOwnerUserId($request);
        if ($contextSchoolOwnerUserId) {
            return $contextSchoolOwnerUserId;
        }

        if ($this->isSchoolActor($request) || $this->isPrivilegedActor($request)) {
            $actorUserId = $this->resolveActorUserId($request);
            if ($actorUserId) {
                return $actorUserId;
            }
        }

        $childId = $request->input('child_id');
        if (is_numeric($childId) && (int) $childId > 0) {
            $child = Child::query()->with('school')->find((int) $childId);
            $schoolUserId = (int) optional($child?->school)->user_id;
            if ($schoolUserId > 0) {
                return $schoolUserId;
            }
        }

        $parent = Parents::query()
            ->where(function ($query) {
                $query->where('login_user_id', (int) Auth::id());
                if (\Illuminate\Support\Facades\Schema::hasColumn('parents', 'user_id')) {
                    $query->orWhere('user_id', (int) Auth::id());
                }
            })
            ->with('children.school')
            ->first();

        $schoolUserId = (int) optional(optional($parent?->children)->first()?->school)->user_id;

        return $schoolUserId > 0 ? $schoolUserId : $this->resolveActorUserId($request);
    }

    private function ensureScopedEmergencyRelations(Request $request, ?int $driverId, ?int $vehicleId): void
    {
        $driver = null;
        $vehicle = null;

        if ($driverId) {
            $driverQuery = Driver::query()
                ->where('deleted', 0)
                ->where('status', 1)
                ->whereKey($driverId);
            $this->applySchoolAwareScope($driverQuery, $request, 'user_id', Schema::hasColumn('drivers', 'school_id') ? 'school_id' : null);

            $driver = $driverQuery->first(['id', 'vehicle_id']);

            if (! $driver) {
                throw ValidationException::withMessages([
                    'driver_id' => 'Selected driver is not accessible for current user.',
                ]);
            }
        }

        if ($vehicleId) {
            $vehicleQuery = Vehicle::query()
                ->where('deleted', 0)
                ->where('status', 1)
                ->whereKey($vehicleId);
            $this->applySchoolAwareScope($vehicleQuery, $request, 'user_id', Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);

            $vehicle = $vehicleQuery->first(['id', 'driver_id']);

            if (! $vehicle) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Selected vehicle is not accessible for current user.',
                ]);
            }
        }

        if ($driver && $vehicle) {
            $driverVehicleId = (int) ($driver->vehicle_id ?? 0);
            $vehicleDriverId = (int) ($vehicle->driver_id ?? 0);

            $isMatchedPair = ($driverVehicleId > 0 && $driverVehicleId === (int) $vehicle->id)
                || ($vehicleDriverId > 0 && $vehicleDriverId === (int) $driver->id);

            if (! $isMatchedPair) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Selected vehicle is not assigned to the selected driver.',
                ]);
            }
        }
    }

    private function resolveDriverFromRequest(Request $request): ?Driver
    {
        $resolvedUserId = $this->resolveActorUserId($request);
        $email = trim((string) ($request->input('email', $request->query('email', ''))));

        if (! $resolvedUserId && $email !== '') {
            $mobileUser = $this->resolveMobileUserByLogin($email);
            $resolvedUserId = (int) ($mobileUser->id ?? 0);
        }

        if (! $resolvedUserId) {
            return null;
        }

        return Driver::query()
            ->where(function ($query) {
                $query->where('deleted', 0)->orWhereNull('deleted');
            })
            ->where(function ($query) use ($resolvedUserId) {
                $query->where('login_user_id', $resolvedUserId);
                if (\Illuminate\Support\Facades\Schema::hasColumn('drivers', 'user_id')) {
                    $query->orWhere('user_id', $resolvedUserId);
                }
            })
            ->with('vehicle')
            ->first();
    }

    private function resolveMobileUserByLogin(?string $login): ?User
    {
        $login = trim((string) $login);
        if ($login === '') {
            return null;
        }

        $normalizedLogin = mb_strtolower($login);
        $normalizedDigits = preg_replace('/\D+/', '', $login);

        $user = User::query()
            ->where(function ($query) use ($normalizedLogin, $normalizedDigits) {
                $query->whereRaw('LOWER(email) = ?', [$normalizedLogin]);

                if (Schema::hasColumn('users', 'username')) {
                    $query->orWhereRaw('LOWER(username) = ?', [$normalizedLogin]);
                }

                if ($normalizedDigits !== '' && Schema::hasColumn('users', 'mobile')) {
                    $query->orWhere('mobile', $normalizedDigits);
                }
            })
            ->where(function ($query) {
                if (Schema::hasColumn('users', 'deleted')) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                    return;
                }

                $query->whereRaw('1 = 1');
            })
            ->first();

        if ($user) {
            return $user;
        }

        if (! Schema::hasTable('parents')) {
            return null;
        }

        $parent = Parents::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedLogin])
            ->where(function ($query) {
                if (Schema::hasColumn('parents', 'deleted')) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                    return;
                }

                $query->whereRaw('1 = 1');
            })
            ->latest('id')
            ->first();

        if (! $parent) {
            return null;
        }

        $linkedUserId = (int) ($parent->login_user_id ?? $parent->user_id ?? 0);
        if ($linkedUserId <= 0) {
            return null;
        }

        return User::query()
            ->where('id', $linkedUserId)
            ->where(function ($query) {
                if (Schema::hasColumn('users', 'deleted')) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                    return;
                }

                $query->whereRaw('1 = 1');
            })
            ->first();
    }

    private function driverEmergencyRecipientUserIds(Driver $driver, int $ownerUserId = 0): array
    {
        $adminIds = User::query()
            ->get()
            ->filter(fn (User $user) => $user->isAdmin())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $parentIds = Child::query()
            ->where(function ($query) {
                $query->where('deleted', 0)->orWhereNull('deleted');
            })
            ->whereIn('route_id', $this->driverRouteIds($driver))
            ->with('parent')
            ->get()
            ->flatMap(function (Child $child) {
                $parent = $child->parent;

                return [
                    (int) ($parent->login_user_id ?? 0),
                    (int) ($parent->user_id ?? 0),
                ];
            })
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return collect([
            ...$adminIds,
            ...$parentIds,
            $ownerUserId,
        ])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function driverRouteIds(Driver $driver): array
    {
        $routeIds = $driver->routes()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $directRouteId = (int) ($driver->route_id ?? $driver->routeId ?? 0);
        if ($directRouteId > 0) {
            $routeIds[] = $directRouteId;
        }

        return collect($routeIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function driverEmergencyRouteLabel(Driver $driver): string
    {
        $routeName = trim((string) $driver->routes()->value('name'));
        if ($routeName !== '') {
            return 'route ' . $routeName;
        }

        $vehicleNumber = trim((string) optional($driver->vehicle)->vehicle_number);
        if ($vehicleNumber !== '') {
            return 'vehicle ' . $vehicleNumber;
        }

        return 'the assigned vehicle';
    }

    private function applyEmergencyVisibilityScope($query, Request $request, string $userColumn = 'user_id')
    {
        if (! $this->shouldRestrictToActorData($request)) {
            return $query;
        }

        $schoolId = $this->resolveSchoolIdFromContext($request);
        if ($schoolId) {
            return $query->where(function ($visibilityQuery) use ($request, $userColumn, $schoolId) {
                $this->applySchoolAwareScope($visibilityQuery, $request, $userColumn);

                if (Schema::hasColumn('drivers', 'school_id')) {
                    $visibilityQuery->orWhereExists(function ($driverQuery) use ($schoolId) {
                        $driverQuery->selectRaw('1')
                            ->from('drivers')
                            ->whereColumn('drivers.id', 'emergency_incidents.driver_id')
                            ->where('drivers.school_id', $schoolId)
                            ->where(function ($deletedQuery) {
                                $deletedQuery->where('drivers.deleted', 0)->orWhereNull('drivers.deleted');
                            });
                    });
                }

                if (Schema::hasColumn('vehicles', 'school_id')) {
                    $visibilityQuery->orWhereExists(function ($vehicleQuery) use ($schoolId) {
                        $vehicleQuery->selectRaw('1')
                            ->from('vehicles')
                            ->whereColumn('vehicles.id', 'emergency_incidents.vehicle_id')
                            ->where('vehicles.school_id', $schoolId)
                            ->where(function ($deletedQuery) {
                                $deletedQuery->where('vehicles.deleted', 0)->orWhereNull('vehicles.deleted');
                            });
                    });
                }
            });
        }

        return $this->applyActorScope($query, $request, $userColumn);
    }

    private function resolveEmergencyOwnerUserIdFromDriver(Driver $driver): int
    {
        $driverOwnerUserId = (int) ($driver->user_id ?? 0);
        if ($driverOwnerUserId > 0) {
            return $driverOwnerUserId;
        }

        $driverSchoolId = Schema::hasColumn('drivers', 'school_id')
            ? (int) ($driver->school_id ?? 0)
            : 0;
        if ($driverSchoolId > 0) {
            $schoolOwnerUserId = (int) School::query()
                ->where('deleted', 0)
                ->where('id', $driverSchoolId)
                ->value('user_id');
            if ($schoolOwnerUserId > 0) {
                return $schoolOwnerUserId;
            }
        }

        $vehicleOwnerUserId = $this->resolveEmergencyOwnerUserIdFromVehicle($driver->vehicle);
        if ($vehicleOwnerUserId > 0) {
            return $vehicleOwnerUserId;
        }

        $routeSchoolId = (int) ($driver->routes()->orderByDesc('id')->value('school_id') ?? 0);
        if ($routeSchoolId > 0) {
            $schoolOwnerUserId = (int) School::query()
                ->where('deleted', 0)
                ->where('id', $routeSchoolId)
                ->value('user_id');
            if ($schoolOwnerUserId > 0) {
                return $schoolOwnerUserId;
            }
        }

        return 0;
    }

    private function resolveEmergencyOwnerUserIdFromVehicle(?Vehicle $vehicle): int
    {
        if (! $vehicle) {
            return 0;
        }

        $vehicleOwnerUserId = (int) ($vehicle->user_id ?? 0);
        if ($vehicleOwnerUserId > 0) {
            return $vehicleOwnerUserId;
        }

        $vehicleSchoolId = Schema::hasColumn('vehicles', 'school_id')
            ? (int) ($vehicle->school_id ?? 0)
            : 0;
        if ($vehicleSchoolId > 0) {
            $schoolOwnerUserId = (int) School::query()
                ->where('deleted', 0)
                ->where('id', $vehicleSchoolId)
                ->value('user_id');
            if ($schoolOwnerUserId > 0) {
                return $schoolOwnerUserId;
            }
        }

        return 0;
    }
}

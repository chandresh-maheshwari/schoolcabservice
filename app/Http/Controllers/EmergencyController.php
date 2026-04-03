<?php
namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Driver;
use App\Models\Emergency;
use App\Models\Parents;
use App\Models\School;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $this->applyActorScope($query, $request, 'emergency_incidents.user_id');
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

        foreach ($emergencyDetails as $emergency) {
            $data[] = [
                'id'             => $emergency->id,
                'school_name'    => $schoolNameMap[$emergency->user_id] ?? '-',
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
            'reported_by'    => 'required|in:parent,admin,driver',
            'emergency_type' => 'required|string|max:100',
            'description'    => 'required|string|max:1000',
            'contact_number' => 'required|digits_between:10,11',

        ]);

        $driverId = $this->extractDriverId($request);
        $vehicleId = $this->extractVehicleId($request);
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

        $ownerUserId = (int) ($driver->user_id ?? optional($driver->vehicle)->user_id ?? 0);

        $emergency = Emergency::create([
            'user_id' => $ownerUserId > 0 ? $ownerUserId : null,
            'driver_id' => (int) $driver->id,
            'vehicle_id' => $driver->vehicle_id ? (int) $driver->vehicle_id : optional($driver->vehicle)->id,
            'reported_by' => 'driver',
            'emergency_type' => $validated['emergency_type'],
            'description' => $validated['description'],
            'contact_number' => $validated['contact_number'] ?? $driver->emergency_phone ?? $driver->driver_phone,
            'status' => 1,
            'deleted' => 0,
        ]);

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

        $ownerUserId = (int) ($driver->user_id ?? optional($driver->vehicle)->user_id ?? 0);

        $emergency = Emergency::create([
            'user_id' => $ownerUserId > 0 ? $ownerUserId : null,
            'driver_id' => (int) $driver->id,
            'vehicle_id' => $driver->vehicle_id ? (int) $driver->vehicle_id : optional($driver->vehicle)->id,
            'reported_by' => 'driver',
            'emergency_type' => $validated['emergencyType'],
            'description' => trim((string) ($validated['description'] ?? '')),
            'contact_number' => $validated['contactNumber'] ?? $driver->emergency_phone ?? $driver->driver_phone,
            'status' => 1,
            'deleted' => 0,
        ]);

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
        $this->applyActorScope($query, request(), 'user_id');
        $emergency = $query->findOrFail($id);
        $drivers   = Driver::where('deleted', 0)
            ->select('id', 'driver_name')
            ->get();

        $vehicles = Vehicle::where('deleted', 0)
            ->select('id', 'vehicle_number')
            ->get();

        return view('emergency.edit', compact('emergency', 'drivers', 'vehicles'));
    }

    /**
     * Update emergency data.
     * created by ns
     */
    public function update(Request $request, $schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $request->validate([
            'driver_id'      => 'required|exists:drivers,id',
            'vehicle_id'     => 'required|exists:vehicles,id',
            'reported_by'    => 'required|in:parent,admin,driver',
            'emergency_type' => 'required|string|max:100',
            'description'    => 'required|string|max:1000',
            'contact_number' => 'required|digits_between:10,11',
        ]);

        $query = Emergency::query();
        $this->applyActorScope($query, $request, 'user_id');
        $emergency = $query->findOrFail($id);

       $emergency->update([
    'driver_id'      => $request->driver_id,
    'vehicle_id'     => $request->vehicle_id,
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
    public function destroy($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);

        $query = Emergency::query();
        $this->applyActorScope($query, request(), 'user_id');
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
        $this->applyActorScope($query, request(), 'user_id');
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
        $this->applyActorScope($query, request(), 'user_id');

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
        $this->applyActorScope($query, $request, 'user_id');
        $query->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected routes deleted successfully',
        ]);
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
        if ($this->isPrivilegedActor($request)) {
            return $this->resolveActorUserId($request);
        }

        if ($driverId) {
            $driverUserId = (int) Driver::query()->whereKey($driverId)->value('user_id');
            if ($driverUserId > 0) {
                return $driverUserId;
            }
        }

        if ($vehicleId) {
            $vehicleUserId = (int) Vehicle::query()->whereKey($vehicleId)->value('user_id');
            if ($vehicleUserId > 0) {
                return $vehicleUserId;
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

    private function resolveDriverFromRequest(Request $request): ?Driver
    {
        $resolvedUserId = $this->resolveActorUserId($request);
        $email = trim((string) ($request->input('email', $request->query('email', ''))));

        if (! $resolvedUserId && $email !== '') {
            $resolvedUserId = (int) User::query()
                ->where('email', $email)
                ->where(function ($query) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                })
                ->value('id');
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
}

<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\Driver;
use App\Models\DriverVehicleHistory;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DriverController extends Controller
{

    /**
     * Display driver listing page.
     * created by ns
     */
    public function index()
    {
        return view('driver.index');
    }

    /**
     * Display driver create form.
     * created by ns
     */
    public function create()
    {
        $vehicle = Vehicle::with('vehicleType')
            ->where('deleted', 0)
            ->where('is_assigned', 0);
        $this->applyActorScope($vehicle);
        $vehicle = $vehicle->get();

        // dd($vehicle);
        return view('driver.create', compact('vehicle'));
    }

    /**
     * Store driver data.
     * created by ns
     */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'user_id'             => 'nullable|exists:users,id',
    //         'vehicle_id'          => 'nullable|exists:vehicles,id',
    //         'driver_name'         => 'required|string|max:255',
    //         'driver_phone'        => 'required|string|max:20',
    //         'emergency_phone'     => 'nullable|string|max:20',
    //         'driver_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp',
    //         'license_no'          => 'required|string|max:255|unique:drivers,license_no',
    //         'license_expiry_date' => 'required|date|after_or_equal:today',
    //         'license_image'       => 'nullable|image|mimes:jpg,jpeg,png,webp',
    //         'adher_no'            => 'nullable|string|max:20',
    //         'adher_card_iamge'    => 'nullable|image|mimes:jpg,jpeg,png,webp',
    //         'experience_years'    => 'required|integer|min:0',
    //         'joining_date'        => 'nullable|date',
    //     ]);
    //     try {

    //         $driver = Driver::create([
    //             'user_id'             => $request->user_id,
    //             'vehicle_id'          => $request->vehicle_id,
    //             'driver_name'         => $request->driver_name,
    //             'driver_phone'        => $request->driver_phone,
    //             'emergency_phone'     => $request->emergency_phone,
    //             'license_no'          => $request->license_no,
    //             'license_expiry_date' => $request->license_expiry_date,
    //             'adher_no'            => $request->adher_no,
    //             'experience_years'    => $request->experience_years,
    //             'joining_date'        => $request->joining_date,
    //             'status'              => 0,
    //             'is_assigned'         => $request->vehicle_id ? 1 : 0,
    //             'deleted'             => 0,
    //         ]);
    //         $driverImage = $request->hasFile('driver_image')
    //             ? ImageHelper::upload($request, 'driver_image', 'drivers', $driver->id, [636, 424])
    //             : null;

    //         $licenseImage = $request->hasFile('license_image')
    //             ? ImageHelper::upload($request, 'license_image', 'drivers', $driver->id, [800, 600])
    //             : null;

    //         $adherImage = $request->hasFile('adher_card_iamge')
    //             ? ImageHelper::upload($request, 'adher_card_iamge', 'drivers', $driver->id, [800, 600])
    //             : null;

    //         $driver->update([
    //             'driver_image'     => $driverImage,
    //             'license_image'    => $licenseImage,
    //             'adher_card_iamge' => $adherImage,
    //         ]);

    //         if ($request->vehicle_id) {

    //             $vehicle = Vehicle::where('id', $request->vehicle_id)->first();

    //             if ($vehicle) {

    //                 // Mark vehicle as assigned
    //                 $vehicle->update(['is_assigned' => 1]);

    //                 // 🔥 DRIVER VEHICLE HISTORY ENTRY
    //                 DriverVehicleHistory::create([
    //                     'driver_name'    => $driver->driver_name,
    //                     'vehicle_number' => $vehicle->vehicle_number,
    //                     'is_assigned'    => 1,
    //                 ]);
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Driver created successfully',
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {


            $request->validate(
                [
                    'user_id'             => 'nullable|exists:users,id',
                    'vehicle_id'          => 'nullable|exists:vehicles,id',
                    'driver_name'         => 'required|string|max:255',
                    'driver_phone'        => 'required|digits_between:10,11',
                    'emergency_phone'     => 'nullable|digits_between:10,11',

                    'driver_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=636,min_height=424',
                    'license_image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=800,min_height=600',
                    'adher_card_iamge'    => 'nullable|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=800,min_height=600',

                    'license_no'          => [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('drivers', 'license_no')->where(fn($q) => $q->where('deleted', 0)),
                    ],

                    'license_expiry_date' => 'required|date|after_or_equal:today',
                    'adher_no'            => 'nullable|string|max:20',
                    'experience_years'    => 'required|integer|min:0',
                    'joining_date'        => 'nullable|date',
                ],
                [

                    'driver_image.dimensions'     => 'Driver image must be at least 636 × 424 pixels.',
                    'license_image.dimensions'    => 'License image must be at least 800 × 600 pixels.',
                    'adher_card_iamge.dimensions' => 'Aadhaar image must be at least 800 × 600 pixels.',
                ]
            );

            $persistedUserId = $this->resolvePersistedUserId($request);
            if (! $persistedUserId) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'User session not found. Please login again.',
                ], 401);
            }

            if ($request->vehicle_id) {
                $vehicleQuery = Vehicle::where('id', (int) $request->vehicle_id)->where('deleted', 0);
                $this->applyActorScope($vehicleQuery, $request);
                if (! $vehicleQuery->exists()) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected vehicle is not accessible for current user.',
                    ], 422);
                }
            }

            $driver = Driver::create([
                'user_id'             => $persistedUserId,
                'vehicle_id'          => $request->vehicle_id,
                'driver_name'         => $request->driver_name,
                'driver_phone'        => $request->driver_phone,
                'emergency_phone'     => $request->emergency_phone,
                'license_no'          => $request->license_no,
                'license_expiry_date' => $request->license_expiry_date,
                'adher_no'            => $request->adher_no,
                'experience_years'    => $request->experience_years,
                'joining_date'        => $request->joining_date,
                'status'              => 0,
                'is_assigned'         => $request->vehicle_id ? 1 : 0,
                'deleted'             => 0,
            ]);

            if ($request->hasFile('driver_image')) {
                $driver->driver_image = ImageHelper::upload(
                    $request,
                    'driver_image',
                    'drivers',
                    $driver->id,
                    [636, 424],
                    null,
                    false
                );
            }

            if ($request->hasFile('license_image')) {
                $driver->license_image = ImageHelper::upload(
                    $request,
                    'license_image',
                    'drivers',
                    $driver->id,
                    [800, 600],
                    null,
                    false
                );
            }

            if ($request->hasFile('adher_card_iamge')) {
                $driver->adher_card_iamge = ImageHelper::upload(
                    $request,
                    'adher_card_iamge',
                    'drivers',
                    $driver->id,
                    [800, 600],
                    null,
                    false
                );
            }

            $driver->save();

            if ($request->vehicle_id) {
                $vehicleQuery = Vehicle::where('id', (int) $request->vehicle_id);
                $this->applyActorScope($vehicleQuery, $request);
                $vehicle = $vehicleQuery->first();

                if ($vehicle) {
                    $vehicle->update(['is_assigned' => 1]);

                    DriverVehicleHistory::create([
                        'driver_id'   => $driver->id,
                        'vehicle_id'  => $vehicle->id,
                        'is_assigned' => 1,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Driver created successfully',
            ], 200);

        } catch (ValidationException $e) {

            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->first()[0],
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Driver Store Error', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Display driver edit form.
     * created by ns
     */

    public function edit($id)
    {
        $driverQuery = Driver::query();
        $this->applyActorScope($driverQuery);
        $driver = $driverQuery->findOrFail($id);

        $vehicles = Vehicle::where('deleted', 0)
            ->where(function ($q) use ($driver) {
                $q->where('is_assigned', 0);
                if (! empty($driver->vehicle_id)) {
                    $q->orWhere('id', $driver->vehicle_id);
                }
            })
            ->with('vehicleType');
        $this->applyActorScope($vehicles);
        $vehicles = $vehicles->get();

        return view('driver.edit', compact('driver', 'vehicles'));
    }

    /**
     * Update driver data.
     * created by ns
     */
    public function update(Request $request, $id)
{
    DB::beginTransaction();

    try {

        $driverQuery = Driver::query();
        $this->applyActorScope($driverQuery, $request);
        $driver = $driverQuery->findOrFail($id);
        $oldVehicleId  = $driver->vehicle_id;

        $request->validate(
            [
                'user_id'             => 'nullable|exists:users,id',
                'vehicle_id'          => 'nullable|exists:vehicles,id',
                'vehicle_number'      => 'nullable|string|max:50',
                'driver_name'         => 'required|string|max:255',
                'driver_phone'        => 'required|string|max:20',
                'emergency_phone'     => 'nullable|string|max:20',
                'driver_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=636,min_height=424',
                'license_no'          => 'required|string|max:255|unique:drivers,license_no,' . $driver->id,
                'license_expiry_date' => 'nullable|date|after_or_equal:today',
                'license_image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=636,min_height=424',
                'adher_no'            => 'nullable|string|max:20',
                'adher_card_iamge'    => 'nullable|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=636,min_height=424',
                'experience_years'    => 'required|integer|min:0',
                'joining_date'        => 'nullable|date',
            ],
            [

                'driver_image.dimensions'     => 'Driver image must be at least 636 × 424 pixels.',
                'license_image.dimensions'    => 'License image must be at least 636 × 424 pixels.',
                'adher_card_iamge.dimensions' => 'Aadhaar image must be at least 636 × 424 pixels.',
            ]
        );

        $oldDriverImage  = $driver->driver_image;
        $oldLicenseImage = $driver->license_image;
        $oldAdherImage   = $driver->adher_card_iamge;

        $persistedUserId = $this->resolvePersistedUserId($request);
        if (! $persistedUserId) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'User session not found. Please login again.',
            ], 401);
        }

        if ($request->vehicle_id) {
            $vehicleScope = Vehicle::where('id', (int) $request->vehicle_id)->where('deleted', 0);
            $this->applyActorScope($vehicleScope, $request);
            if (! $vehicleScope->exists()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Selected vehicle is not accessible for current user.',
                ], 422);
            }
        }

        $driver->update([
            'user_id'             => $persistedUserId,
            'vehicle_id'          => $request->vehicle_id,
            'driver_name'         => $request->driver_name,
            'driver_phone'        => $request->driver_phone,
            'emergency_phone'     => $request->emergency_phone,
            'license_no'          => $request->license_no,
            'license_expiry_date' => $request->license_expiry_date,
            'adher_no'            => $request->adher_no,
            'experience_years'    => $request->experience_years,
            'joining_date'        => $request->joining_date,
            'is_assigned'         => $request->vehicle_id ? 1 : 0,
        ]);

        // ================= IMAGE UPDATES =================

        if ($request->hasFile('driver_image')) {
            $newDriverImage = ImageHelper::upload(
                $request,
                'driver_image',
                'drivers',
                $driver->id,
                [636, 424],
                null,
                false
            );
            $driver->driver_image = $newDriverImage;
        }

        if ($request->hasFile('license_image')) {
            $newLicenseImage = ImageHelper::upload(
                $request,
                'license_image',
                'drivers',
                $driver->id,
                [636, 424],
                null,
                false
            );
            $driver->license_image = $newLicenseImage;
        }

        if ($request->hasFile('adher_card_iamge')) {
            $newAdherImage = ImageHelper::upload(
                $request,
                'adher_card_iamge',
                'drivers',
                $driver->id,
                [636, 424],-
                null,
                false
            );
            $driver->adher_card_iamge = $newAdherImage;
        }

        $driver->save();


        if ($oldVehicleId && $oldVehicleId != $request->vehicle_id) {
            Vehicle::where('id', $oldVehicleId)->update(['is_assigned' => 0]);
        }

        if ($request->vehicle_id) {
            $newVehicleQuery = Vehicle::where('id', $request->vehicle_id);
            $this->applyActorScope($newVehicleQuery, $request);
            $newVehicleQuery->update(['is_assigned' => 1]);
        }

        if ($oldVehicleId != $request->vehicle_id) {
            if ($oldVehicleId) {
                DriverVehicleHistory::where('driver_id', $driver->id)
                    ->where('vehicle_id', $oldVehicleId)
                    ->where('deleted', 0)
                    ->where('is_assigned', 1)
                    ->update(['is_assigned' => 0]);
            }

            if ($request->vehicle_id) {
                DriverVehicleHistory::create([
                    'driver_id'   => $driver->id,
                    'vehicle_id'  => $request->vehicle_id,
                    'is_assigned' => 1,
                ]);
            }
        }

        DB::commit();

        if (isset($newDriverImage) && $oldDriverImage && file_exists(public_path('storage/' . $oldDriverImage))) {
            unlink(public_path('storage/' . $oldDriverImage));
        }
        if (isset($newLicenseImage) && $oldLicenseImage && file_exists(public_path('storage/' . $oldLicenseImage))) {
            unlink(public_path('storage/' . $oldLicenseImage));
        }
        if (isset($newAdherImage) && $oldAdherImage && file_exists(public_path('storage/' . $oldAdherImage))) {
            unlink(public_path('storage/' . $oldAdherImage));
        }

        return response()->json([
            'success' => true,
            'message' => 'Driver updated successfully',
        ], 200);

    } catch (ValidationException $e) {

        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => collect($e->errors())->first()[0],
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 200);
    }
}

    /**
     * Soft delete driver record.
     * created by ns
     */

    public function destroy($id)
    {
        $query = Driver::query();
        $this->applyActorScope($query);
        $driver = $query->findOrFail($id);

        $driver->deleted = 1;
        $driver->save();

        return response()->json(['success' => true, 'message' => 'Driver deleted Successfully.']);
    }

    /**
     * Toggle driver active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $query = Driver::query();
        $this->applyActorScope($query);
        $driver = $query->findOrFail($id);

        $driver->status = $driver->status == 1 ? 0 : 1;
        $driver->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    /**
     * Get active driver count.
     * created by ns
     */
    public function getActiveCount()
    {
        $query = Driver::where('deleted', 0)->where('status', true);
        $this->applyActorScope($query);
        $activeCount = $query->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Delete driver profile image.
     * created by ns
     */
   public function driverImage($id)
{
    $query = Driver::query();
    $this->applyActorScope($query);
    $driver = $query->findOrFail($id);

    if (!empty($driver->driver_image)) {

        $imagePath = public_path($driver->driver_image);

        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        $driver->driver_image = null;
        $driver->save();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully.'
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'No image to delete.'
    ]);
}

    /**
     * Delete driver license image.
     * created by ns
     */
    public function licenseImage($id)
{
    $query = Driver::query();
    $this->applyActorScope($query);
    $driver = $query->findOrFail($id);

    if (!empty($driver->license_image)) {

        $imagePath = public_path($driver->license_image);

        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        $driver->license_image = null;
        $driver->save();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully.'
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'No image to delete.'
    ]);
}

    /**
     * Delete driver Aadhar card image.
     * created by ns
     */
    public function adharCardImage($id)
{
    $query = Driver::query();
    $this->applyActorScope($query);
    $driver = $query->findOrFail($id);

    if (!empty($driver->adher_card_iamge)) {

        $imagePath = public_path($driver->adher_card_iamge);

        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        $driver->adher_card_iamge = null;
        $driver->save();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully.'
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'No image to delete.'
    ]);
}
    /**
     * Fetch driver list for DataTable.
     * created by ns
     */
    // public function driverList(Request $request)
    // {
    //     $draw        = $request->input('sEcho');
    //     $row         = (int) $request->input('iDisplayStart', 0);
    //     $rowperpage  = (int) $request->input('iDisplayLength', 10);
    //     $indexColumn = $request->input('iSortCol_0');
    //     $columnName  = $request->input('mDataProp_' . $indexColumn, '_id');

    //     if (! in_array($columnName, ['_id', 'driver_name', 'driver_phone', 'driver_image', 'emergency_phone', 'license_no ', 'license_expiry_date', 'license_image', 'adher_no', 'adher_card_iamge', 'experience_years', 'status', 'is_assigned', 'joining_date'])) {
    //         $columnName = '_id';
    //     }

    //     $columnSortOrder = $request->input('sSortDir_0');
    //     $searchValue     = $request->input('sSearch');

    //     $driverDetails         = Driver::getDriverData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
    //     $totalRecords          = Driver::count();
    //     $totalRecordwithFilter = Driver::getDriverDataTotal($searchValue);

    //     $data = [];
    //     foreach ($driverDetails as $driver) {
    //         $data[] = [
    //             'id'                  => (string) $driver->_id,
    //             // 'user_id'             => $driver->user_id,
    //             'driver_name'         => $driver->driver_name,
    //             'driver_phone'        => $driver->driver_phone,
    //             'driver_image'        => $driver->driver_image,
    //             'emergency_phone'     => $driver->emergency_phone,
    //             'license_no'          => $driver->license_no,
    //             'license_expiry_date' => $driver->license_expiry_date,
    //             'license_image'       => $driver->license_image,
    //             'adher_no'            => $driver->adher_no,
    //             'adher_card_iamge'    => $driver->adher_card_iamge,
    //             'vehicle_number'      => $driver->vehicle_number ?? null,
    //             'experience_years'    => $driver->experience_years,
    //             'is_assigned'         => $driver->is_assigned,
    //             'status'              => $driver->status,
    //             'joining_date'        => $driver->joining_date,
    //         ];
    //     }

    //     $output = [
    //         "draw"            => intval($draw),
    //         "recordsTotal"    => $totalRecords,
    //         "recordsFiltered" => $totalRecordwithFilter,
    //         "data"            => $data,
    //     ];

    //     return response()->json($output);
    // }

    public function driverList(Request $request)
    {
        // $draw        = $request->input('sEcho');
        // $row         = (int) $request->input('iDisplayStart', 0);
        // $rowperpage  = (int) $request->input('iDisplayLength', 10);
        // $indexColumn = $request->input('iSortCol_0', 0);
        // $columnName  = $request->input('mDataProp_' . $indexColumn, '_id');
        $draw        = $request->input('sEcho');
        $row         = $request->input('iDisplayStart');
        $rowperpage  = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        if (! in_array($columnName, ['id', 'driver_name', 'driver_phone', 'driver_image', 'emergency_phone', 'license_no', 'license_expiry_date', 'license_image', 'adher_no', 'adher_card_iamge', 'experience_years', 'status', 'is_assigned', 'joining_date'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue     = $request->input('sSearch');
        $sortColumnMap   = [
            'id'                  => 'id',
            'driver_name'         => 'driver_name',
            'driver_phone'        => 'driver_phone',
            'driver_image'        => 'driver_image',
            'emergency_phone'     => 'emergency_phone',
            'license_no'          => 'license_no',
            'license_expiry_date' => 'license_expiry_date',
            'license_image'       => 'license_image',
            'adher_no'            => 'adher_no',
            'adher_card_iamge'    => 'adher_card_iamge',
            'experience_years'    => 'experience_years',
            'status'              => 'status',
            'is_assigned'         => 'is_assigned',
            'joining_date'        => 'joining_date',
        ];

        $query = Driver::with('vehicle')->where('deleted', 0);
        $this->applyActorScope($query, $request);
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('driver_name', 'like', "%$searchValue%")
                    ->orWhere('driver_phone', 'like', "%$searchValue%")
                    ->orWhere('license_no', 'like', "%$searchValue%")
                    ->orWhere('adher_no', 'like', "%$searchValue%");
            });
        }

        $totalRecordwithFilter = (clone $query)->count();
        $driverDetails         = $query
            ->orderBy($sortColumnMap[$columnName] ?? 'id', in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'desc')
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();

        $data = [];
        foreach ($driverDetails as $driver) {
            $data[] = [
                'id'                  => $driver->id,
                // 'user_id'             => $driver->user_id,
                'driver_name'         => $driver->driver_name,
                'driver_phone'        => $driver->driver_phone,
                'driver_image'        => $driver->driver_image,
                'emergency_phone'     => $driver->emergency_phone,
                'license_no'          => $driver->license_no,
                'license_expiry_date' => $driver->license_expiry_date,
                'license_image'       => $driver->license_image,
                'adher_no'            => $driver->adher_no,
                'adher_card_iamge'    => $driver->adher_card_iamge,
                'vehicle_number'      => optional($driver->vehicle)->vehicle_number,
                'experience_years'    => $driver->experience_years,
                'is_assigned'         => $driver->is_assigned,
                'status'              => $driver->status,
                'joining_date'        => $driver->joining_date,
            ];
        }

        $searchValue = $request->input('sSearch');
        return response()->json([
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ]);
    }

    /**
     * Soft delete multiple driver.
     * created by ns
     */
    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided.',
            ]);
        }

        $query = Driver::whereIn('id', $ids);
        $this->applyActorScope($query, $request);
        $query->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected id deleted Successfully.',
        ]);
    }
}

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
            ->where('is_assigned', 0)
            ->get();

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

            $driver = Driver::create([
                'user_id'             => $request->user_id,
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
                    [636, 424]
                );
            }

            if ($request->hasFile('license_image')) {
                $driver->license_image = ImageHelper::upload(
                    $request,
                    'license_image',
                    'drivers',
                    $driver->id,
                    [800, 600]
                );
            }

            if ($request->hasFile('adher_card_iamge')) {
                $driver->adher_card_iamge = ImageHelper::upload(
                    $request,
                    'adher_card_iamge',
                    'drivers',
                    $driver->id,
                    [800, 600]
                );
            }

            $driver->save();

            if ($request->vehicle_id) {
                $vehicle = Vehicle::find($request->vehicle_id);

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
        $driver = Driver::findOrFail($id);

        $vehicles = Vehicle::where('deleted', 0)
            ->where(function ($q) use ($driver) {
                // unassigned vehicles
                $q->where('is_assigned', 0);

                // driver ka already assigned vehicle (EDIT CASE)
                if (! empty($driver->vehicle_id)) {
                    $q->orWhere('id', $driver->vehicle_id);
                }
            })
            ->with('vehicleType')
            ->get();

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

        $driver = Driver::findOrFail($id);

        $oldDriverName = $driver->getOriginal('driver_name');
        $oldVehicleId  = $driver->vehicle_id;

        if ($oldVehicleId) {
            $oldVehicle       = Vehicle::where('id', $oldVehicleId)->first();
            $oldVehicleNumber = $oldVehicle?->vehicle_number;
        }

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

        $driver->update([
            'user_id'             => $request->user_id,
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
                [636, 424]
            );
            $driver->driver_image = $newDriverImage;
        }

        if ($request->hasFile('license_image')) {
            $newLicenseImage = ImageHelper::upload(
                $request,
                'license_image',
                'drivers',
                $driver->id,
                [636, 424]
            );
            $driver->license_image = $newLicenseImage;
        }

        if ($request->hasFile('adher_card_iamge')) {
            $newAdherImage = ImageHelper::upload(
                $request,
                'adher_card_iamge',
                'drivers',
                $driver->id,
                [636, 424]
            );
            $driver->adher_card_iamge = $newAdherImage;
        }

        $driver->save();


        if ($oldVehicleId && $oldVehicleId != $request->vehicle_id) {
            Vehicle::where('id', $oldVehicleId)->update(['is_assigned' => 0]);
        }

        if ($request->vehicle_id) {
            Vehicle::where('id', $request->vehicle_id)->update(['is_assigned' => 1]);
        }

        if ($oldVehicleId && $oldVehicleId != $request->vehicle_id) {

            $oldVehicle       = Vehicle::where('id', $oldVehicleId)->first();
            $oldVehicleNumber = $oldVehicle?->vehicle_number;

            $vehicleNumber = null;
            if ($request->vehicle_id) {
                $vehicle       = Vehicle::where('id', $request->vehicle_id)->first();
                $vehicleNumber = $vehicle?->vehicle_number;
            }

            $history = DriverVehicleHistory::where('driver_name', $oldDriverName)->first();

            if ($history) {
                $history->update([
                    'driver_name'    => $request->driver_name,
                    'vehicle_number' => $vehicleNumber,
                    'is_assigned'    => 1,
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
        $driver          = Driver::findOrFail($id);
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
        $driver         = Driver::findOrFail($id);
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
        $activeCount = Driver::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Delete driver profile image.
     * created by ns
     */
   public function driverImage($id)
{
    $driver = Driver::findOrFail($id);

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
    $driver = Driver::findOrFail($id);

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
    $driver = Driver::findOrFail($id);

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

        if (! in_array($columnName, ['id', 'driver_name', 'driver_phone', 'driver_image', 'emergency_phone', 'license_no ', 'license_expiry_date', 'license_image', 'adher_no', 'adher_card_iamge', 'experience_years', 'status', 'is_assigned', 'joining_date'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue     = $request->input('sSearch');

        $driverDetails         = Driver::getDriverData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords          = Driver::count();
        $totalRecordwithFilter = Driver::getDriverDataTotal($searchValue);

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
                'vehicle_number'      => $driver->vehicle_number ?? null,
                'experience_years'    => $driver->experience_years,
                'is_assigned'         => $driver->is_assigned,
                'status'              => $driver->status,
                'joining_date'        => $driver->joining_date,
            ];
        }

        // $columnSortOrder = in_array(
        //     $request->input('sSortDir_0'),
        //     ['asc', 'desc']
        // ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        // $vehicleDetails = Vehicle::getVehicleData(
        //     $searchValue,
        //     $columnName,
        //     $columnSortOrder,
        //     $draw,
        //     $row,
        //     $rowperpage
        // );

        // $totalRecords          = Vehicle::where('deleted', 0)->count();
        // $totalRecordwithFilter = Vehicle::getVehicleDataTotal($searchValue);

        // $data = [];

        // foreach ($vehicleDetails as $vehicle) {
        //     $data[] = [
        //         'id'                    => $vehicle->id,
        //         'vehicle_number'        => $vehicle->vehicle_number,
        //         'vehicle_image'         => $vehicle->vehicle_image,
        //         'vehicle_type'          => $vehicle->vehicle_type ?? '-',
        //         'seating_capacity'      => $vehicle->seating_capacity,
        //         'rc_number'             => $vehicle->rc_number,
        //         'rc_expiry_date'        => $vehicle->rc_expiry_date,
        //         'insurance_number'      => $vehicle->insurance_number,
        //         'insurance_expiry_date' => $vehicle->insurance_expiry_date,
        //         'is_assigned'           => $vehicle->is_assigned,
        //         'status'                => $vehicle->status,
        //     ];
        // }

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

        Driver::whereIn('id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected id deleted Successfully.',
        ]);
    }
}

<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    public function index()
    {
        return view('driver.index');
    }

    public function create()
    {
        // $vehicle = Vehicle::leftJoin('vehicle_types', 'vehicle_types._id', '=', 'vehicles.vehicle_type_id')
        //     ->where('vehicles.deleted', 0)
        //     ->where('vehicles.is_assigned', 0)
        //     ->select(
        //         'vehicles._id',
        //         'vehicles.vehicle_number',
        //         'vehicle_types.vehicle_type as vehicle_type_name'
        //     )
        //     ->get();
        $vehicle = Vehicle::with('vehicleType')  // eager load vehicleType relation
        ->where('deleted', 0)
        ->where('is_assigned', 0)
        ->get();
        // dd($vehicle);

        return view('driver.create', compact('vehicle'));
    }

   public function store(Request $request)
{
     $request->validate([
        'user_id'             => 'nullable|exists:users,_id',
        'vehicle_id'          => 'nullable|exists:vehicles,_id',
        'driver_name'         => 'required|string|max:255',
        'driver_phone'        => 'required|string|max:20',
        'emergency_phone'     => 'nullable|string|max:20',
        'driver_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp',
        'license_no'          => 'required|string|max:255|unique:drivers,license_no',
        'license_expiry_date' => 'required|date|after_or_equal:today',
        'license_image'       => 'nullable|image|mimes:jpg,jpeg,png,webp',
        'adher_no'            => 'nullable|string|max:20',
        'adher_card_iamge'    => 'nullable|image|mimes:jpg,jpeg,png,webp',
        'experience_years'    => 'required|integer|min:0',
        'joining_date'        => 'nullable|date',
    ]);
    try {

        // ✅ Create driver
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

        // ✅ Upload images
        $driverImage  = $request->hasFile('driver_image')
            ? ImageHelper::upload($request, 'driver_image', 'drivers', $driver->_id, [636, 424])
            : null;

        $licenseImage = $request->hasFile('license_image')
            ? ImageHelper::upload($request, 'license_image', 'drivers', $driver->_id, [800, 600])
            : null;

        $adherImage   = $request->hasFile('adher_card_iamge')
            ? ImageHelper::upload($request, 'adher_card_iamge', 'drivers', $driver->_id, [800, 600])
            : null;

        $driver->update([
            'driver_image'     => $driverImage,
            'license_image'    => $licenseImage,
            'adher_card_iamge' => $adherImage,
        ]);

        // ✅ Assign vehicle
        if ($request->vehicle_id) {
            Vehicle::where('_id', $request->vehicle_id)
                ->update(['is_assigned' => 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Driver created successfully'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}


    public function edit($id)
    {
        $driver = Driver::findOrFail($id);

        $vehicle = Vehicle::leftJoin('vehicle_types', 'vehicle_types.id', '=', 'vehicles.vehicle_type_id')
            ->where('vehicles.deleted', 0)
            ->where(function ($q) use ($driver) {
                $q->where('vehicles.is_assigned', 0)
                    ->orWhere('vehicles.id', $driver->vehicle_id);
            })
            ->select(
                'vehicles.id',
                'vehicles.vehicle_number',
                'vehicle_types.vehicle_type as vehicle_type_name'
            )
            ->get();

        return view('driver.edit', compact('driver', 'vehicle'));
    }

    public function update(Request $request, $id)
    {
        $driver       = Driver::findOrFail($id);
        $oldVehicleId = $driver->vehicle_id;

        $request->validate([
            'user_id'             => 'nullable|exists:users,_id',
            'vehicle_id'          => 'nullable|exists:vehicles,_id',
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
        ]);

        // DB::beginTransaction();

        // for cleanup
        // $newDriverImage  = null;
        // $newLicenseImage = null;
        // $newAdherImage   = null;

        try {

            // 🔹 Update basic fields
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

            // 🔹 Replace images if uploaded
            if ($request->hasFile('driver_image')) {
                if ($driver->driver_image && file_exists(public_path('storage/' . $driver->driver_image))) {
                    unlink(public_path('storage/' . $driver->driver_image));
                }

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
                if ($driver->license_image && file_exists(public_path('storage/' . $driver->license_image))) {
                    unlink(public_path('storage/' . $driver->license_image));
                }

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
                if ($driver->adher_card_iamge && file_exists(public_path('storage/' . $driver->adher_card_iamge))) {
                    unlink(public_path('storage/' . $driver->adher_card_iamge));
                }

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

            // 🔥 VEHICLE ASSIGN / UNASSIGN LOGIC
            if ($oldVehicleId && $oldVehicleId != $request->vehicle_id) {
                Vehicle::where('_id', $oldVehicleId)->update(['is_assigned' => 0]);
            }

            if ($request->vehicle_id) {
                Vehicle::where('_id', $request->vehicle_id)->update(['is_assigned' => 1]);
            }

            // DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Driver updated successfully',
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            // cleanup newly uploaded images
            foreach ([$newDriverImage, $newLicenseImage, $newAdherImage] as $img) {
                if ($img && file_exists(public_path('storage/' . $img))) {
                    unlink(public_path('storage/' . $img));
                }
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    public function destroy($id)
    {
        $driver          = Driver::findOrFail($id);
        $driver->deleted = 1;
        $driver->save();

        return response()->json(['success' => true, 'message' => 'Driver deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $driver         = Driver::findOrFail($id);
      $driver->status = $driver->status == 1 ? 0 : 1;
        $driver->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

   public function getActiveCount()
{
    $activeCount = Driver::where('deleted', 0)
         ->where('status', true)
        ->count();

    return response()->json(['count' => $activeCount]);
}

    public function driverImage($id)
    {
        $driver = Driver::findOrFail($id);
        // dd($vehicle);
        if ($driver->driver_image) {
            $imagePath = public_path($driver->driver_image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $driver->driver_image = null;
            $driver->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }
    public function licenseImage($id)
    {
        $driver = Vehicle::findOrFail($id);
        // dd($vehicle);
        if ($driver->license_image) {
            $imagePath = public_path($driver->license_image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $driver->license_image = null;
            $driver->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }
    public function adharCardImage($id)
    {
        // dd((string)$id);
        $driver = Vehicle::findOrFail($id);
        // dd($driver);
        if ($driver->adher_card_iamge) {
            $imagePath = public_path($driver->adher_card_iamge);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $driver->adher_card_iamge = null;
            $driver->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }


    public function driverList(Request $request)
    {
        $draw        = $request->input('sEcho');
         $row         = (int) $request->input('iDisplayStart', 0);
    $rowperpage       = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0');
    $columnName  = $request->input('mDataProp_' . $indexColumn, '_id');

        if (! in_array($columnName, ['_id', 'driver_name', 'driver_phone', 'driver_image', 'emergency_phone', 'license_no ', 'license_expiry_date', 'license_image', 'adher_no', 'adher_card_iamge', 'experience_years', 'status', 'is_assigned', 'joining_date'])) {
            $columnName = '_id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue     = $request->input('sSearch');

        $driverDetails         = Driver::getDriverData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords          = Driver::count();
        $totalRecordwithFilter = Driver::getDriverDataTotal($searchValue);

        $data = [];
        foreach ($driverDetails as $driver) {
            $data[] = [
                'id'                  =>  (string) $driver->_id,
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
                // 'vehicle_id'          => $driver->vehicle_id,
                'vehicle_number'      => $driver->vehicle_number ?? null,
                'experience_years'    => $driver->experience_years,
                'is_assigned'         => $driver->is_assigned,
                'status'              => $driver->status,
                'joining_date'        => $driver->joining_date,
            ];
        }

        $output = [
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ];

        return response()->json($output);
    }
}

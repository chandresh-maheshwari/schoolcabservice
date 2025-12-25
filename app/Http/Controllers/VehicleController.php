<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    public function index()
    {
        return view('vehicle.index');
    }

    public function create()
    {
        $vehicleTypes = VehicleType::select('vehicle_type', '_id')->get();
        // dd($vehicleTypes);
        return view('vehicle.create', compact('vehicleTypes'));
    }

    // public function store(Request $request)
    // {
    //     // dd($request->all());
    //     $request->validate([
    //         'vehicle_number'        => 'required|string|max:255|unique:vehicles,vehicle_number',
    //         'vehicle_type_id'       => 'required|exists:vehicle_types,_id',
    //         'seating_capacity'      => 'required|integer|min:1',
    //         'vehicle_image'         => 'required|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=636,min_height=424',
    //         'rc_number'             => 'required|string|max:255',
    //         'rc_expiry_date'        => 'required|date|after_or_equal:today',
    //         'rc_image'              => 'required|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=800,min_height=600',
    //         'insurance_number'      => 'required|string|max:50',
    //         'insurance_expiry_date' => 'required|date|after_or_equal:today',
    //         'insurance_image'       => 'required|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=800,min_height=600',
    //     ]);

    //     DB::beginTransaction();
    //     $vehicleImage   = null;
    //     $rcImage        = null;
    //     $insuranceImage = null;

    //     try {

    //         $vehicle = Vehicle::create([
    //             'vehicle_number'        => $request->vehicle_number,
    //             'vehicle_type_id'       => $request->vehicle_type_id,
    //             'seating_capacity'      => $request->seating_capacity,
    //             'rc_number'             => $request->rc_number,
    //             'rc_expiry_date'        => $request->rc_expiry_date,
    //             'insurance_number'      => $request->insurance_number,
    //             'insurance_expiry_date' => $request->insurance_expiry_date,
    //             'status'                => 0,
    //             'is_assigned'           => 0,
    //             'deleted'               => 0,
    //         ]);
    //         dd( $vehicle);

    //         $vehicleImage = ImageHelper::upload(
    //             $request,
    //             'vehicle_image',
    //             'vehicle',
    //             $vehicle->id,
    //             [636, 424]
    //         );

    //         $rcImage = ImageHelper::upload(
    //             $request,
    //             'rc_image',
    //             'vehicle',
    //             $vehicle->id,
    //             [800, 600]
    //         );

    //         $insuranceImage = ImageHelper::upload(
    //             $request,
    //             'insurance_image',
    //             'vehicle',
    //             $vehicle->id,
    //             [800, 600]
    //         );

    //         $vehicle->update([
    //             'vehicle_image'   => $vehicleImage,
    //             'rc_image'        => $rcImage,
    //             'insurance_image' => $insuranceImage,
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Vehicle created successfully',
    //         ]);

    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         if ($vehicleImage && file_exists(public_path('storage/' . $vehicleImage))) {
    //             unlink(public_path('storage/' . $vehicleImage));
    //         }

    //         if ($rcImage && file_exists(public_path('storage/' . $rcImage))) {
    //             unlink(public_path('storage/' . $rcImage));
    //         }

    //         if ($insuranceImage && file_exists(public_path('storage/' . $insuranceImage))) {
    //             unlink(public_path('storage/' . $insuranceImage));
    //         }

    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //         ], 422);
    //     }
    // }



    public function store(Request $request)
{
    $request->validate([
        'vehicle_number'        => 'required|string|max:255|unique:vehicles,vehicle_number',
        'vehicle_type_id'       => 'required|exists:vehicle_types,_id',
        'seating_capacity'      => 'required|integer|min:1',
        'vehicle_image'         => 'required|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=636,min_height=424',
        'rc_number'             => 'required|string|max:255',
        'rc_expiry_date'        => 'required|date|after_or_equal:today',
        'rc_image'              => 'required|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=800,min_height=600',
        'insurance_number'      => 'required|string|max:50',
        'insurance_expiry_date' => 'required|date|after_or_equal:today',
        'insurance_image'       => 'required|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=800,min_height=600',
    ]);

    $vehicleImage = $rcImage = $insuranceImage = null;

    try {
        $vehicle = Vehicle::create([
            'vehicle_number'        => $request->vehicle_number,
            'vehicle_type_id'       => $request->vehicle_type_id,
            'seating_capacity'      => $request->seating_capacity,
            'rc_number'             => $request->rc_number,
            'rc_expiry_date'        => $request->rc_expiry_date,
            'insurance_number'      => $request->insurance_number,
            'insurance_expiry_date' => $request->insurance_expiry_date,
            'status'                => 0,
            'is_assigned'           => 0,
            'deleted'               => 0,
        ]);

        $vehicleImage = ImageHelper::upload($request, 'vehicle_image', 'vehicle', $vehicle->_id, [636, 424]);
        $rcImage = ImageHelper::upload($request, 'rc_image', 'vehicle', $vehicle->_id, [800, 600]);
        $insuranceImage = ImageHelper::upload($request, 'insurance_image', 'vehicle', $vehicle->_id, [800, 600]);

        $vehicle->update([
            'vehicle_image'   => $vehicleImage,
            'rc_image'        => $rcImage,
            'insurance_image' => $insuranceImage,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle created successfully',
        ]);

    } catch (\Exception $e) {

        // cleanup images
        foreach ([$vehicleImage, $rcImage, $insuranceImage] as $img) {
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


    public function edit($id)
    {
        $vehicle      = Vehicle::where('deleted', 0)->findOrFail($id);
        $vehicleTypes = VehicleType::where('deleted', 0)->get();
        // dd($vehicleTypes);

        return view('vehicle.edit', compact('vehicle', 'vehicleTypes'));
    }

    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $request->validate([
            // 'vehicle_number'        => 'required|string|max:255|unique:vehicles,vehicle_number,' . $vehicle->_id,
            'vehicle_type_id'       => 'required|exists:vehicle_types,_id',
            'seating_capacity'      => 'required|integer|min:1',
            'vehicle_image'         => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'rc_number'             => 'required|string|max:255',
            'rc_expiry_date'        => 'required|date|after_or_equal:today',
            'rc_image'              => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'insurance_number'      => 'required|string|max:50',
            'insurance_expiry_date' => 'nullable|date|after_or_equal:today',
            'insurance_image'       => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        try {

            // STEP 1: Update basic fields
            $vehicle->update([
                'vehicle_number'        => $request->vehicle_number,
                'vehicle_type_id'       => $request->vehicle_type_id,
                'seating_capacity'      => $request->seating_capacity,
                'rc_number'             => $request->rc_number,
                'rc_expiry_date'        => $request->rc_expiry_date,
                'insurance_number'      => $request->insurance_number,
                'insurance_expiry_date' => $request->insurance_expiry_date,
            ]);

            // STEP 2: Upload / Replace images (ONLY if new image uploaded)

            $vehicleImage = ImageHelper::upload(
                $request,
                'vehicle_image',
                'vehicle',
                $vehicle->id,
                [636, 424],
                $vehicle->vehicle_image
            );

            $rcImage = ImageHelper::upload(
                $request,
                'rc_image',
                'vehicle',
                $vehicle->id,
                [800, 600],
                $vehicle->rc_image
            );

            $insuranceImage = ImageHelper::upload(
                $request,
                'insurance_image',
                'vehicle',
                $vehicle->id,
                [800, 600],
                $vehicle->insurance_image
            );

            // STEP 3: Update image fields
            $vehicle->update([
                'vehicle_image'   => $vehicleImage,
                'rc_image'        => $rcImage,
                'insurance_image' => $insuranceImage,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle updated successfully',
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $vehicle          = Vehicle::findOrFail($id);
        $vehicle->deleted = 1;
        $vehicle->save();

        return response()->json(['success' => true, 'message' => 'Vehicle deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $vehicle         = Vehicle::findOrFail($id);
        $vehicle->status = ! $vehicle->status;
        $vehicle->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function getActiveCount()
    {
        $activeCount = Vehicle::where('deleted', 0)->where('status')->count();
        return response()->json(['count' => $activeCount]);
    }

    public function vehicleImage($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        // dd($vehicle);
        if ($vehicle->vehicle_image) {
            $imagePath = public_path($vehicle->vehicle_image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $vehicle->vehicle_image = null;
            $vehicle->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }
    public function rcImage($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        // dd($vehicle);
        if ($vehicle->rc_image) {
            $imagePath = public_path($vehicle->rc_image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $vehicle->rc_image = null;
            $vehicle->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }
    public function insuranceImage($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        // dd($vehicle);
        if ($vehicle->insurance_image) {
            $imagePath = public_path($vehicle->insurance_image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $vehicle->insurance_image = null;
            $vehicle->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided.']);
        }
        Vehicle::whereIn('_id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected id deleted Successfully.']);
    }

//     public function vehicleList(Request $request)
//     {
//         $draw        = $request->input('sEcho');
//         $row         = $request->input('iDisplayStart');
//         $rowperpage  = $request->input('iDisplayLength');
//         $indexColumn = $request->input('iSortCol_0');
//         $columnName  = $request->input('mDataProp_' . $indexColumn);

//             // if (! in_array($columnName, ['_id', 'vehicle_number ', 'vehicle_image', 'vehicle_type_id ', 'seating_capacity', 'rc_number', 'rc_expiry_date', 'insurance_number', 'insurance_expiry_date', 'is_assigned', 'status'])) {
//             //     $columnName = '_id';
//             // }

//           $allowedColumns = ['_id', 'vehicle_number', 'vehicle_image', 'vehicle_type_id', 'seating_capacity', 'rc_number', 'rc_expiry_date', 'insurance_number', 'insurance_expiry_date', 'is_assigned', 'status'];
//     if (!in_array($columnName, $allowedColumns)) {
//         $columnName = '_id';
//     }

//         $columnSortOrder = $request->input('sSortDir_0', 'asc');
//         $searchValue     = $request->input('sSearch');

//         $vehicleDetails        = Vehicle::getVehicleData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
// $totalRecords = Vehicle::where('deleted', 0)->count();
//         $totalRecordwithFilter = Vehicle::getVehicleDataTotal($searchValue);

//         $data = [];
//         foreach ($vehicleDetails as $vehicle) {
//             $data[] = [
//                 // 'id'                    => $vehicle->_id,
//                  'id'                    => (string) $vehicle->_id,
//                 'vehicle_number'        => $vehicle->vehicle_number,
//                 'vehicle_image'         => $vehicle->vehicle_image,
//                 'vehicle_type_id' => $vehicle->vehicle_type_id,
//                 'seating_capacity'      => $vehicle->seating_capacity,
//                 'rc_number'             => $vehicle->rc_number,
//                 'rc_expiry_date'        => $vehicle->rc_expiry_date,
//                 'insurance_number'      => $vehicle->insurance_number,
//                 'insurance_expiry_date' => $vehicle->insurance_expiry_date,
//                 'is_assigned'           => $vehicle->is_assigned,
//                 'status'                => $vehicle->status,
//             ];
//         }

//         $output = [
//             "draw"            => intval($draw),
//             "recordsTotal"    => $totalRecords,
//             "recordsFiltered" => $totalRecordwithFilter,
//             "data"            => $data,
//         ];

//         return response()->json($output);
//     }


public function vehicleList(Request $request)
{
    $draw        = $request->input('sEcho');
    $row         = (int) $request->input('iDisplayStart', 0);
    $rowperpage  = (int) $request->input('iDisplayLength', 10);
    $indexColumn = $request->input('iSortCol_0', 0);
    $columnName  = $request->input('mDataProp_' . $indexColumn, '_id');

    $allowedColumns = [
        '_id',
        'vehicle_number',
        'vehicle_image',
        'vehicle_type_id',
        'seating_capacity',
        'rc_number',
        'rc_expiry_date',
        'insurance_number',
        'insurance_expiry_date',
        'is_assigned',
        'status',
    ];

    $columnName = in_array($columnName, $allowedColumns)
        ? $columnName
        : '_id';

    $columnSortOrder = in_array(
        $request->input('sSortDir_0'),
        ['asc', 'desc']
    ) ? $request->input('sSortDir_0') : 'asc';

    $searchValue = $request->input('sSearch');

    $vehicleDetails = Vehicle::getVehicleData(
        $searchValue,
        $columnName,
        $columnSortOrder,
        $draw,
        $row,
        $rowperpage
    );

    $totalRecords = Vehicle::where('deleted', 0)->count();
    $totalRecordwithFilter = Vehicle::getVehicleDataTotal($searchValue);

    $data = [];

    foreach ($vehicleDetails as $vehicle) {
        $data[] = [
            'id'                    => (string) $vehicle->_id,
            'vehicle_number'        => $vehicle->vehicle_number,
            'vehicle_image'         => $vehicle->vehicle_image,
            'vehicle_type'          => optional($vehicle->vehicleType)->vehicle_type ?? '-',
            'seating_capacity'      => $vehicle->seating_capacity,
            'rc_number'             => $vehicle->rc_number,
            'rc_expiry_date'        => $vehicle->rc_expiry_date,
            'insurance_number'      => $vehicle->insurance_number,
            'insurance_expiry_date' => $vehicle->insurance_expiry_date,
            'is_assigned'           => $vehicle->is_assigned,
            'status'                => $vehicle->status,
        ];
    }

    return response()->json([
        "draw"            => intval($draw),
        "recordsTotal"    => $totalRecords,
        "recordsFiltered" => $totalRecordwithFilter,
        "data"            => $data,
    ]);
}

}

<?php

namespace App\Http\Controllers;



use App\Helpers\ImageHelper;

use App\Models\Driver;
use App\Models\School;

use App\Models\Vehicle;

use App\Models\VehicleType;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;



class VehicleController extends Controller

{



    /**

     * Display vehicle listing page.

     * created by ns

     */



    public function index()

    {

        return view('vehicle.index');

    }



    /**

     * Display vehicle create form.

     * created by ns

     */



    public function create()

    {

        $vehicleTypesQuery = VehicleType::select('vehicle_type', 'id')

            ->where('deleted', 0);

        $this->applySchoolAwareScope($vehicleTypesQuery, request(), 'user_id', Schema::hasColumn('vehicle_types', 'school_id') ? 'school_id' : null);

        $vehicleTypes = $vehicleTypesQuery->get();
        $schools = School::query()
            ->where('deleted', 0)
            ->orderBy('school_name')
            ->get(['id', 'user_id', 'school_name']);


        return view('vehicle.create', compact('vehicleTypes', 'schools'));

    }

    private function documentFileRules(string $presenceRule, int $minWidth, int $minHeight, string $label): array
    {
        return [
            $presenceRule,
            'file',
            'mimes:jpg,jpeg,png,webp,pdf',
            function ($attribute, $value, $fail) use ($minWidth, $minHeight, $label) {
                if (! $value || ! ImageHelper::isImageFile($value)) {
                    return;
                }

                if (! ImageHelper::meetsMinimumDimensions($value, $minWidth, $minHeight)) {
                    $fail("{$label} must be at least {$minWidth} x {$minHeight} pixels when uploading an image.");
                }
            },
        ];
    }



    public function store(Request $request)

    {

        // dd($request->all());

        $request->validate(

            [

                'vehicle_number'        => 'required|string|max:255|unique:vehicles,vehicle_number',
                'user_id'               => 'nullable|exists:users,id',

                'vehicle_type_id'       => 'required|exists:vehicle_types,id',
                'user_id'               => 'nullable|exists:users,id',

                'seating_capacity'      => 'required|integer|min:1',



                'vehicle_image'         => 'required|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=636,min_height=424',

                'rc_image'              => $this->documentFileRules('required', 800, 600, 'RC image'),

                'insurance_image'       => $this->documentFileRules('required', 800, 600, 'Insurance image'),



                'rc_number'             => 'required|string|max:255|unique:vehicles,rc_number',

                'rc_expiry_date'        => 'required|date|after_or_equal:today',

                'insurance_number'      => 'required|string|max:50|unique:vehicles,insurance_number',

                'insurance_expiry_date' => 'required|date|after_or_equal:today',

            ],

            [



                'vehicle_image.dimensions'   => 'Vehicle image must be at least 636 × 424 pixels.',

                'rc_image.dimensions'        => 'RC image must be at least 800 × 600 pixels.',

                'insurance_image.dimensions' => 'Insurance image must be at least 800 × 600 pixels.',



                'vehicle_image.required'     => 'Vehicle image is required.',

                'rc_image.required'          => 'RC image is required.',

                'insurance_image.required'   => 'Insurance image is required.',
                'rc_number.unique'           => 'RC number already exists.',
                'insurance_number.unique'    => 'Insurance number already exists.',

            ]

        );



        DB::beginTransaction();

        $vehicleImage   = null;

        $rcImage        = null;

        $insuranceImage = null;



        try {

            $persistedUserId = $this->resolvePersistedUserId($request);

            if (! $persistedUserId) {

                DB::rollBack();

                return response()->json([

                    'success' => false,

                    'message' => 'User session not found. Please login again.',

                ], 401);

            }



            $vehicleTypeQuery = VehicleType::where('id', $request->vehicle_type_id)->where('deleted', 0);

            $this->applySchoolAwareScope($vehicleTypeQuery, $request, 'user_id', Schema::hasColumn('vehicle_types', 'school_id') ? 'school_id' : null);
            $vehicleTypeColumns = ['id', 'user_id'];
            if (Schema::hasColumn('vehicle_types', 'school_id')) {
                $vehicleTypeColumns[] = 'school_id';
            }
            $vehicleType = $vehicleTypeQuery->first($vehicleTypeColumns);

            if (! $vehicleType) {

                DB::rollBack();

                return response()->json([

                    'success' => false,

                    'message' => 'Selected vehicle type is not accessible for current user.',

                ], 422);

            }


            $ownerUserId = $this->resolveModuleOwnerUserId($request, $persistedUserId, [
                (int) ($vehicleType->user_id ?? 0),
            ]);
            $schoolId = $this->resolveModuleSchoolId($request, null, [
                $vehicleType->school_id ?? null,
            ], $ownerUserId);

            $vehiclePayload = [

                'user_id'               => $ownerUserId,

                'vehicle_number'        => $request->vehicle_number,

                'vehicle_type_id'       => $request->vehicle_type_id,

                'seating_capacity'      => $request->seating_capacity,

                'rc_number'             => $request->rc_number,

                'rc_expiry_date'        => $request->rc_expiry_date,

                'insurance_number'      => $request->insurance_number,

                'insurance_expiry_date' => $request->insurance_expiry_date,

                'status'                => 0,

                'deleted'               => 0,
            ];

            if (Schema::hasColumn('vehicles', 'school_id')) {
                $vehiclePayload['school_id'] = $schoolId;
            }

            $vehicle = Vehicle::create($vehiclePayload);

            if ($schoolId && Schema::hasColumn('vehicle_types', 'school_id')) {
                VehicleType::where('id', (int) $request->vehicle_type_id)->update(['school_id' => $schoolId]);
            }




            $vehicleImage = ImageHelper::upload(

                $request,

                'vehicle_image',

                'vehicle',

                $vehicle->id,

                [636, 424],

                null,

                false

            );



            $rcImage = ImageHelper::upload(

                $request,

                'rc_image',

                'vehicle',

                $vehicle->id,

                [800, 600],

                null,

                false

            );



            $insuranceImage = ImageHelper::upload(

                $request,

                'insurance_image',

                'vehicle',

                $vehicle->id,

            );



            $vehicle->update([

                'vehicle_image'   => $vehicleImage,

                'rc_image'        => $rcImage,

                'insurance_image' => $insuranceImage,

            ]);

            $this->syncDriverDetailsVehicleRow($vehicle, $request);



            DB::commit();



            return response()->json([

                'success' => true,

                'message' => 'Vehicle created successfully',

            ]);



        } catch (\Exception $e) {



            DB::rollBack();



            if ($vehicleImage && file_exists(public_path('storage/' . $vehicleImage))) {

                unlink(public_path('storage/' . $vehicleImage));

            }



            if ($rcImage && file_exists(public_path('storage/' . $rcImage))) {

                unlink(public_path('storage/' . $rcImage));

            }



            if ($insuranceImage && file_exists(public_path('storage/' . $insuranceImage))) {

                unlink(public_path('storage/' . $insuranceImage));

            }



            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 422);

        }

    }



    /**

     * Store vehicle data.

     * created by ns

     */



    // public function store(Request $request)

    // {

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



    //     $vehicleImage = $rcImage = $insuranceImage = null;



    //     try {

    //         $vehicleType = VehicleType::findOrFail($request->vehicle_type_id);

    //         $vehicle     = Vehicle::create([

    //             'vehicle_number'        => $request->vehicle_number,

    //             'vehicle_type'          => $vehicleType->vehicle_type, // ✅ NAME save hoga

    //             'rc_number'             => $request->rc_number,

    //             'rc_expiry_date'        => $request->rc_expiry_date,

    //             'insurance_number'      => $request->insurance_number,

    //             'insurance_expiry_date' => $request->insurance_expiry_date,

    //             'status'                => 0,

    //             'is_assigned'           => 0,

    //             'deleted'               => 0,

    //         ]);



    //         $vehicleImage   = ImageHelper::upload($request, 'vehicle_image', 'vehicle', $vehicle->_id, [636, 424]);

    //         $rcImage        = ImageHelper::upload($request, 'rc_image', 'vehicle', $vehicle->_id, [800, 600]);

    //         $insuranceImage = ImageHelper::upload($request, 'insurance_image', 'vehicle', $vehicle->_id, [800, 600]);



    //         $vehicle->update([

    //             'vehicle_image'   => $vehicleImage,

    //             'rc_image'        => $rcImage,

    //             'insurance_image' => $insuranceImage,

    //         ]);



    //         return response()->json([

    //             'success' => true,

    //             'message' => 'Vehicle created successfully',

    //         ]);



    //     } catch (\Exception $e) {



    //         // cleanup images

    //         foreach ([$vehicleImage, $rcImage, $insuranceImage] as $img) {

    //             if ($img && file_exists(public_path('storage/' . $img))) {

    //                 unlink(public_path('storage/' . $img));

    //             }

    //         }



    //         return response()->json([

    //             'success' => false,

    //             'message' => $e->getMessage(),

    //         ], 422);

    //     }

    // }



/**

 * Display vehicle edit form.

 * created by ns

 */

    public function edit($schoolSlugOrId, $id = null)

    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);

        $vehicleQuery = Vehicle::where('deleted', 0);

        $this->applySchoolAwareScope($vehicleQuery, request(), 'user_id', Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);

        $vehicle = $vehicleQuery->findOrFail($id);



        $vehicleTypes = VehicleType::where('deleted', 0);

        $this->applySchoolAwareScope($vehicleTypes, request(), 'user_id', Schema::hasColumn('vehicle_types', 'school_id') ? 'school_id' : null);

        $vehicleTypes = $vehicleTypes->get();
        $schools = School::query()
            ->where('deleted', 0)
            ->orderBy('school_name')
            ->get(['id', 'user_id', 'school_name']);

        // dd($vehicleTypes);


        return view('vehicle.edit', compact('vehicle', 'vehicleTypes', 'schools'));

    }



    /**

     * Update vehicle data.

     * created by ns

     */

    // public function update(Request $request, $id)

    // {

    //     $vehicle = Vehicle::findOrFail($id);



    //     $request->validate(

    //         [

    //             // 'vehicle_number'        => 'required|string|max:255|unique:vehicles,vehicle_number',

    //             'vehicle_type_id'       => 'required|exists:vehicle_types,id',

    //             'seating_capacity'      => 'required|integer|min:1',



    //             'vehicle_image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=636,min_height=424',

    //             'rc_image'              => 'nullable|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=800,min_height=600',

    //             'insurance_image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=800,min_height=600',



    //             'rc_number'             => 'required|string|max:255',

    //             'rc_expiry_date'        => 'required|date|after_or_equal:today',

    //             'insurance_number'      => 'required|string|max:50',

    //             'insurance_expiry_date' => 'required|date|after_or_equal:today',

    //         ],

    //         [



    //             'vehicle_image.dimensions'   => 'Vehicle image must be at least 636 × 424 pixels.',

    //             'rc_image.dimensions'        => 'RC image must be at least 800 × 600 pixels.',

    //             'insurance_image.dimensions' => 'Insurance image must be at least 800 × 600 pixels.',



    //             'vehicle_image.required'     => 'Vehicle image is required.',

    //             'rc_image.required'          => 'RC image is required.',

    //             'insurance_image.required'   => 'Insurance image is required.',

    //         ]

    //     );



    //     try {

    //         $vehicleType = VehicleType::findOrFail($request->vehicle_type_id);

    //         // STEP 1: Update basic fields

    //         $vehicle->update([

    //             'vehicle_number'        => $request->vehicle_number,

    //             'vehicle_type_id'       => $request->vehicle_type_id,

    //             'seating_capacity'      => $request->seating_capacity,

    //             'rc_number'             => $request->rc_number,

    //             'rc_expiry_date'        => $request->rc_expiry_date,

    //             'insurance_number'      => $request->insurance_number,

    //             'insurance_expiry_date' => $request->insurance_expiry_date,

    //         ]);



    //         // STEP 2: Upload / Replace images (ONLY if new image uploaded)



    //         $vehicleImage = ImageHelper::upload(

    //             $request,

    //             'vehicle_image',

    //             'vehicle',

    //             $vehicle->id,

    //             [636, 424], null,

    //             false,

    //             $vehicle->vehicle_image

    //         );



    //         $rcImage = ImageHelper::upload(

    //             $request,

    //             'rc_image',

    //             'vehicle',

    //             $vehicle->id,

    //             [800, 600], null,

    //             false,

    //             $vehicle->rc_image

    //         );



    //         $insuranceImage = ImageHelper::upload(

    //             $request,

    //             'insurance_image',

    //             'vehicle',

    //             $vehicle->id,

    //             [800, 600],null,

    //             false,

    //             $vehicle->insurance_image

    //         );



    //         // STEP 3: Update image fields

    //         $vehicle->update([

    //             'vehicle_image'   => $vehicleImage,

    //             'rc_image'        => $rcImage,

    //             'insurance_image' => $insuranceImage,

    //         ]);



    //         return response()->json([

    //             'success' => true,

    //             'message' => 'Vehicle updated successfully',

    //         ]);



    //     } catch (\Exception $e) {



    //         return response()->json([

    //             'success' => false,

    //             'message' => $e->getMessage(),

    //         ], 500);

    //     }

    // }



    public function update(Request $request, $schoolSlugOrId, $id = null)

    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);

        $vehicleQuery = Vehicle::query();

        $this->applySchoolAwareScope($vehicleQuery, $request, 'user_id', Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);

        $vehicle = $vehicleQuery->findOrFail($id);



        $request->validate(

            [

                'vehicle_type_id'       => 'required|exists:vehicle_types,id',

                'seating_capacity'      => 'required|integer|min:1',

                'vehicle_image'         => [

                    $vehicle->vehicle_image ? 'nullable' : 'required',

                    'image',

                    'mimes:jpg,jpeg,png,webp',

                    'dimensions:min_width=636,min_height=424',

                ],



                'rc_image'              => $this->documentFileRules(
                    $vehicle->rc_image ? 'nullable' : 'required',
                    800,
                    600,
                    'RC image'
                ),



                'insurance_image'       => $this->documentFileRules(
                    $vehicle->insurance_image ? 'nullable' : 'required',
                    800,
                    600,
                    'Insurance image'
                ),



                'rc_number'             => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('vehicles', 'rc_number')->ignore($vehicle->id),
                ],

                'rc_expiry_date'        => 'required|date|after_or_equal:today',

                'insurance_number'      => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('vehicles', 'insurance_number')->ignore($vehicle->id),
                ],

                'insurance_expiry_date' => 'required|date|after_or_equal:today',

            ],

            [

                'vehicle_image.required'     => 'Vehicle image is required.',

                'rc_image.required'          => 'RC image is required.',

                'insurance_image.required'   => 'Insurance image is required.',
                'rc_number.unique'           => 'RC number already exists.',
                'insurance_number.unique'    => 'Insurance number already exists.',



                'vehicle_image.dimensions'   => 'Vehicle image must be at least 636 × 424 pixels.',

                'rc_image.dimensions'        => 'RC image must be at least 800 × 600 pixels.',

                'insurance_image.dimensions' => 'Insurance image must be at least 800 × 600 pixels.',

            ]

        );



        try {

            $vehicleTypeQuery = VehicleType::where('id', $request->vehicle_type_id)->where('deleted', 0);

            $this->applySchoolAwareScope($vehicleTypeQuery, $request, 'user_id', Schema::hasColumn('vehicle_types', 'school_id') ? 'school_id' : null);
            $vehicleTypeColumns = ['id', 'user_id'];
            if (Schema::hasColumn('vehicle_types', 'school_id')) {
                $vehicleTypeColumns[] = 'school_id';
            }
            $vehicleType = $vehicleTypeQuery->first($vehicleTypeColumns);

            if (! $vehicleType) {

                return response()->json([

                    'success' => false,

                    'message' => 'Selected vehicle type is not accessible for current user.',

                ], 422);

            }


            $ownerUserId = $this->resolveModuleOwnerUserId($request, (int) $vehicle->user_id, [
                (int) ($vehicleType->user_id ?? 0),
            ]);
            $schoolId = $this->resolveModuleSchoolId($request, (int) ($vehicle->school_id ?? 0), [
                $vehicleType->school_id ?? null,
            ], $ownerUserId);

            $vehiclePayload = [

                'user_id'               => $ownerUserId,

                'vehicle_number'        => $request->vehicle_number,

                'vehicle_type_id'       => $request->vehicle_type_id,

                'seating_capacity'      => $request->seating_capacity,

                'rc_number'             => $request->rc_number,

                'rc_expiry_date'        => $request->rc_expiry_date,

                'insurance_number'      => $request->insurance_number,

                'insurance_expiry_date' => $request->insurance_expiry_date,

            ];

            if (Schema::hasColumn('vehicles', 'school_id')) {
                $vehiclePayload['school_id'] = $schoolId;
            }

            $vehicle->update($vehiclePayload);

            if ($schoolId && Schema::hasColumn('vehicle_types', 'school_id')) {
                VehicleType::where('id', (int) $request->vehicle_type_id)->update(['school_id' => $schoolId]);
            }



            if ($request->hasFile('vehicle_image')) {

                $vehicle->vehicle_image = ImageHelper::upload(

                    $request,

                    'vehicle_image',

                    'vehicle',

                    $vehicle->id,

                    [636, 424],

                    $vehicle->vehicle_image,

                    false

                );

            }



            if ($request->hasFile('rc_image')) {

                $vehicle->rc_image = ImageHelper::upload(

                    $request,

                    'rc_image',

                    'vehicle',

                    $vehicle->id,

                    [800, 600],

                    $vehicle->rc_image,

                    false

                );

            }



            if ($request->hasFile('insurance_image')) {

                $vehicle->insurance_image = ImageHelper::upload(

                    $request,

                    'insurance_image',

                    'vehicle',

                    $vehicle->id,

                    [800, 600],

                    $vehicle->insurance_image,

                    false

                );

            }



            $vehicle->save();

            $this->syncDriverDetailsVehicleRow($vehicle, $request);



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



    /**

     * Soft delete vehicle record.

     * created by ns

     */

    public function destroy($schoolSlugOrId, $id = null)

    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);

        DB::beginTransaction();

        try {

            $vehicleQuery = Vehicle::query();

            $this->applyActorScope($vehicleQuery);

            $vehicle = $vehicleQuery->findOrFail($id);

            $vehicle->deleted = 1;

            $vehicle->save();



            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);

        }



        return response()->json(['success' => true, 'message' => 'Vehicle deleted Successfully.']);

    }



    /**

     * Toggle vehicle active/inactive status.

     * created by ns

     */

    public function toggleStatus($id)

    {

        $query = Vehicle::query();

        $this->applyActorScope($query);

        $vehicle = $query->findOrFail($id);



        $vehicle->status = $vehicle->status == 1 ? 0 : 1;

        $vehicle->save();



        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);

    }

    public function toggleEmergencyStatus(Request $request, $id)
    {
        $query = Vehicle::query();
        $this->applyActorScope($query, $request);
        $vehicle = $query->findOrFail($id);

        if (! Schema::hasColumn('vehicles', 'availability_status')) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle emergency fields are not available yet. Please run migrations first.',
            ], 409);
        }

        $markAsEmergency = filter_var($request->input('mark_emergency', true), FILTER_VALIDATE_BOOLEAN);
        $note = trim((string) $request->input('emergency_note', ''));

        if ($markAsEmergency) {
            if ($note === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Emergency note is required when marking a vehicle as emergency.',
                ], 422);
            }

            $vehicle->availability_status = 'emergency';
            if (Schema::hasColumn('vehicles', 'emergency_note')) {
                $vehicle->emergency_note = Str::limit($note, 1000, '');
            }
            if (Schema::hasColumn('vehicles', 'emergency_marked_at')) {
                $vehicle->emergency_marked_at = now();
            }
            if (Schema::hasColumn('vehicles', 'resolved_at')) {
                $vehicle->resolved_at = null;
            }
            if (Schema::hasColumn('vehicles', 'resolved_by')) {
                $vehicle->resolved_by = null;
            }
        } else {
            $vehicle->availability_status = 'available';
            if (Schema::hasColumn('vehicles', 'emergency_note')) {
                $vehicle->emergency_note = null;
            }
            if (Schema::hasColumn('vehicles', 'emergency_marked_at')) {
                $vehicle->emergency_marked_at = null;
            }
            if (Schema::hasColumn('vehicles', 'resolved_at')) {
                $vehicle->resolved_at = now();
            }
            if (Schema::hasColumn('vehicles', 'resolved_by')) {
                $vehicle->resolved_by = (int) ($this->resolveActorUserId($request) ?: 0) ?: null;
            }
        }

        $vehicle->save();

        return response()->json([
            'success' => true,
            'message' => $markAsEmergency
                ? 'Vehicle marked as emergency successfully.'
                : 'Vehicle marked as available successfully.',
            'availability_status' => $vehicle->availability_status,
        ]);
    }



    /**

     * Get active vehicle count.

     * created by ns

     */

    public function getActiveCount()

    {

        $query = Vehicle::where('deleted', 0)->where('status', true);

        $this->applyActorScope($query);

        $activeCount = $query->count();



        return response()->json(['count' => $activeCount]);

    }



    /**

     * Delete vehicle image .

     * created by ns

     */

    public function vehicleImage($id)

    {

        $query = Vehicle::query();

        $this->applyActorScope($query);

        $vehicle = $query->findOrFail($id);

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



    /**

     * Delete rc image .

     * created by ns

     */

    public function rcImage($id)

    {

        $query = Vehicle::query();

        $this->applyActorScope($query);

        $vehicle = $query->findOrFail($id);

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



    /**

     * Delete insurance image .

     * created by ns

     */

    public function insuranceImage($id)

    {

        $query = Vehicle::query();

        $this->applyActorScope($query);

        $vehicle = $query->findOrFail($id);

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



    /**

     * Soft delete multiple vehicle.

     * created by ns

     */

    public function multiDelete(Request $request)

    {

        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {

            return response()->json(['success' => false, 'message' => 'No IDs provided.']);

        }



        DB::beginTransaction();

        try {

            $scopedVehicleQuery = Vehicle::whereIn('id', $ids);

            $this->applyActorScope($scopedVehicleQuery, $request);



            $vehicleIds = $scopedVehicleQuery->pluck('id');

            if ($vehicleIds->isEmpty()) {

                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'No valid IDs provided.']);

            }



            Vehicle::whereIn('id', $vehicleIds)->update(['deleted' => 1]);



            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);

        }



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



/**

 * Fetch vehicle list for DataTable.

 * created by ns

 */

    // public function vehicleList(Request $request)

    // {

    //     $draw        = $request->input('sEcho');

    //     $row         = (int) $request->input('iDisplayStart', 0);

    //     $rowperpage  = (int) $request->input('iDisplayLength', 10);

    //     $indexColumn = $request->input('iSortCol_0', 0);

    //     $columnName  = $request->input('mDataProp_' . $indexColumn, '_id');



    //     $allowedColumns = [

    //         '_id',

    //         'vehicle_number',

    //         'vehicle_image',

    //         'vehicle_type',

    //         'seating_capacity',

    //         'rc_number',

    //         'rc_expiry_date',

    //         'insurance_number',

    //         'insurance_expiry_date',

    //         'is_assigned',

    //         'status',

    //     ];



    //     $columnName = in_array($columnName, $allowedColumns)

    //         ? $columnName

    //         : '_id';



    //     $columnSortOrder = in_array(

    //         $request->input('sSortDir_0'),

    //         ['asc', 'desc']

    //     ) ? $request->input('sSortDir_0') : 'asc';



    //     $searchValue = $request->input('sSearch');



    //     $vehicleDetails = Vehicle::getVehicleData(

    //         $searchValue,

    //         $columnName,

    //         $columnSortOrder,

    //         $draw,

    //         $row,

    //         $rowperpage

    //     );



    //     $totalRecords          = Vehicle::where('deleted', 0)->count();

    //     $totalRecordwithFilter = Vehicle::getVehicleDataTotal($searchValue);



    //     $data = [];



    //     foreach ($vehicleDetails as $vehicle) {

    //         $data[] = [

    //             'id'                    => (string) $vehicle->_id,

    //             'vehicle_number'        => $vehicle->vehicle_number,

    //             'vehicle_image'         => $vehicle->vehicle_image,

    //             'vehicle_type'          => $vehicle->vehicle_type ?? '-',

    //             'seating_capacity'      => $vehicle->seating_capacity,

    //             'rc_number'             => $vehicle->rc_number,

    //             'rc_expiry_date'        => $vehicle->rc_expiry_date,

    //             'insurance_number'      => $vehicle->insurance_number,

    //             'insurance_expiry_date' => $vehicle->insurance_expiry_date,

    //             'is_assigned'           => $vehicle->is_assigned,

    //             'status'                => $vehicle->status,

    //         ];

    //     }



    //     return response()->json([

    //         "draw"            => intval($draw),

    //         "recordsTotal"    => $totalRecords,

    //         "recordsFiltered" => $totalRecordwithFilter,

    //         "data"            => $data,

    //     ]);

    // }



    public function vehicleList(Request $request)

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



        $allowedColumns = [

            'id',

            'vehicle_number',

            'vehicle_image',

            'vehicle_type',

            'seating_capacity',

            'rc_number',

            'rc_expiry_date',

            'insurance_number',

            'insurance_expiry_date',

            'is_assigned',

            'status',

        ];



        if (! in_array($columnName, $allowedColumns)) {

            $columnName = 'id';

        }



        $sortColumnMap = [

            'id'                    => 'id',

            'vehicle_number'        => 'vehicle_number',

            'vehicle_image'         => 'vehicle_image',

            'vehicle_type'          => 'vehicle_type_id',

            'seating_capacity'      => 'seating_capacity',

            'rc_number'             => 'rc_number',

            'rc_expiry_date'        => 'rc_expiry_date',

            'insurance_number'      => 'insurance_number',

            'insurance_expiry_date' => 'insurance_expiry_date',

            'is_assigned'           => 'is_assigned',

            'status'                => 'status',

        ];



        $columnSortOrder = $request->input('sSortDir_0');

        $searchValue     = $request->input('sSearch');



        $columnName = in_array($columnName, $allowedColumns)

            ? $columnName

            : 'id';



        // $columnSortOrder = in_array(

        //     $request->input('sSortDir_0'),

        //     ['asc', 'desc']

        // ) ? $request->input('sSortDir_0') : 'asc';



        $searchValue = $request->input('sSearch');



        $query = Vehicle::with('vehicleType')->where('deleted', 0);

        $this->applySchoolAwareScope($query, $request, 'user_id', Schema::hasColumn('vehicles', 'school_id') ? 'school_id' : null);

        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $matchingSchoolReferences = $this->resolveSchoolSearchIds($searchValue);
            $matchingSchoolIds = $matchingSchoolReferences['school_ids'];
            $matchingUserIds = $matchingSchoolReferences['user_ids'];

            $query->where(function ($q) use ($searchValue, $matchingSchoolIds, $matchingUserIds) {

                $q->where('vehicle_number', 'like', '%' . $searchValue . '%')

                    ->orWhere('rc_number', 'like', '%' . $searchValue . '%')

                    ->orWhere('insurance_number', 'like', '%' . $searchValue . '%')

                    ->orWhere('seating_capacity', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('vehicleType', function ($vehicleTypeQuery) use ($searchValue) {
                        $vehicleTypeQuery->where('vehicle_type', 'like', '%' . $searchValue . '%');
                    });

                if (! empty($matchingSchoolIds) && Schema::hasColumn('vehicles', 'school_id')) {
                    $q->orWhereIn('school_id', $matchingSchoolIds);
                }

                if (! empty($matchingUserIds)) {
                    $q->orWhereIn('user_id', $matchingUserIds);
                }

            });

        }



        $totalRecordwithFilter = (clone $query)->count();

        $vehicleDetails        = $query

            ->orderBy($sortColumnMap[$columnName] ?? 'id', in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'desc')

            ->skip((int) $row)

            ->take((int) $rowperpage)

            ->get();



        $data = [];
        $assignedDriversByVehicleId = $this->getAssignedDriversByVehicleId(
            $vehicleDetails->pluck('id')->all(),
            $request
        );
        $trackingDriversByVehicleNumber = $this->getDriverTrackingLookupByVehicleNumber(
            $vehicleDetails->pluck('vehicle_number')->all(),
            $request
        );
        $schoolNameMap = $this->getSchoolNameMapForVehicleIds($vehicleDetails->pluck('id')->all());



        foreach ($vehicleDetails as $vehicle) {
            $assignedDriver = $assignedDriversByVehicleId[$vehicle->id] ?? null;
            $trackingMapping = $this->resolveTrackingMappingForVehicle(
                $vehicle,
                $trackingDriversByVehicleNumber,
                $assignedDriver,
                $request
            );

            $data[] = [

                'id'                    => $vehicle->id,
                'school_name'           => $schoolNameMap[$vehicle->id] ?? '-',
                'vehicle_number'        => $vehicle->vehicle_number,

                'vehicle_image'         => $vehicle->vehicle_image,

                'vehicle_type'          => $vehicle->vehicleType->vehicle_type ?? '-',

                'seating_capacity'      => $vehicle->seating_capacity,

                'rc_number'             => $vehicle->rc_number,

                'rc_expiry_date'        => $vehicle->rc_expiry_date,

                'insurance_number'      => $vehicle->insurance_number,

                'insurance_expiry_date' => $vehicle->insurance_expiry_date,

                'is_assigned'           => $vehicle->is_assigned,

                'status'                => $vehicle->status,
                'availability_status'   => Schema::hasColumn('vehicles', 'availability_status')
                    ? ($vehicle->availability_status ?? 'available')
                    : 'available',
                'emergency_note'        => Schema::hasColumn('vehicles', 'emergency_note')
                    ? ($vehicle->emergency_note ?? null)
                    : null,
                'emergency_marked_at'   => Schema::hasColumn('vehicles', 'emergency_marked_at')
                    ? optional($vehicle->emergency_marked_at)->toDateTimeString()
                    : null,

                'tracking_driver_id'    => $trackingMapping['tracking_driver_id'],
                'tracking_status'       => $trackingMapping['status'],
                'tracking_message'      => $trackingMapping['message'],

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

     * Display live tracking dashboard.

     * created by ns

     */

    public function liveTrackingDashboard()

    {

        return view('vehicle.live_tracking');

    }

    public function tracking(Request $request)
    {
        $focusDriverId = $request->query('focus_driver_id');

        if ($focusDriverId !== null && $focusDriverId !== '') {
            $focusDriverId = $this->resolveTrackingDriverId(
                (int) $focusDriverId,
                $request
            );
        } elseif ($request->filled('focus_vehicle_id')) {
            $focusDriverId = $this->resolveTrackingDriverIdForVehicle(
                (int) $request->query('focus_vehicle_id'),
                $request
            );
        }

        $focusDriverId = $focusDriverId !== null && $focusDriverId !== ''
            ? (int) $focusDriverId
            : null;

        return view('vehicle.tracking', compact('focusDriverId'));
    }



    /**

     * Get live tracking data from the drivers table.

     */

    public function getAllLiveTracking(Request $request)

    {
        $schemaReady = $this->isDriverTrackingSchemaReady();

        if (! $schemaReady) {
            return response()->json([
                'success' => true,
                'schema_ready' => false,
                'updated_at' => now()->toISOString(),
                'vehicles' => [],
                'message' => 'drivers table/current_lat/current_lng columns are missing.',
            ]);
        }

        $trackingQuery = $this->driverTrackingQuery($request);

        $driverId = $request->query('driver_id');
        $selectionResolved = true;

        if ($driverId !== null && $driverId !== '') {
            $driverId = $this->resolveTrackingDriverId((int) $driverId, $request);

            if ($driverId === null) {
                $selectionResolved = false;
            }
        } elseif ($request->filled('vehicle_id')) {
            $driverId = $this->resolveTrackingDriverIdForVehicle(
                (int) $request->query('vehicle_id'),
                $request
            );

            if ($driverId === null) {
                $selectionResolved = false;
            }
        }

        if ($driverId !== null && $driverId !== '') {
            $trackingQuery->where('id', (int) $driverId);
        } elseif ($request->filled('vehicle_id') || $request->filled('driver_id')) {
            return response()->json([
                'success' => true,
                'schema_ready' => true,
                'selection_resolved' => false,
                'updated_at' => now()->toISOString(),
                'vehicles' => [],
                'message' => 'Selected vehicle ke liye drivers table me mapped driver row nahi mili.',
            ]);
        }

        $trackingRows = $trackingQuery->get();

        $trackingData = [];
        foreach ($trackingRows as $trackingRow) {
            $trackingData[] = $this->buildTrackingDataFromDriverRow($trackingRow);
        }

        $updatedAt = collect($trackingData)
            ->pluck('recorded_at')
            ->filter()
            ->max();

        return response()->json([
            'success' => true,
            'schema_ready' => true,
            'selection_resolved' => $selectionResolved,
            'updated_at' => $updatedAt ?: now()->toISOString(),
            'vehicles' => $trackingData,
            'message' => 'Live tracking data retrieved successfully',
        ]);

    }



    /**

     * Get live tracking data for a specific driver row.

     */

    public function getLiveTracking($id, Request $request)

    {
        $schemaReady = $this->isDriverTrackingSchemaReady();

        if (! $schemaReady) {
            return response()->json([
                'success' => true,
                'schema_ready' => false,
                'updated_at' => now()->toISOString(),
                'data' => null,
                'message' => 'drivers table/current_lat/current_lng columns are missing.',
            ]);
        }

        $trackingRow = $this->driverTrackingQuery($request)
            ->where('id', (int) $id)
            ->first();

        if (! $trackingRow) {
            abort(404);
        }

        $trackingData = $this->buildTrackingDataFromDriverRow($trackingRow);

        return response()->json([
            'success' => true,
            'schema_ready' => true,
            'updated_at' => $trackingData['recorded_at'] ?? now()->toISOString(),
            'data' => $trackingData,
            'message' => 'Live tracking data retrieved successfully',
        ]);

    }

    public function debugTrackingMappings(Request $request)

    {
        $vehicleQuery = Vehicle::query()
            ->select(['id', 'user_id', 'vehicle_number'])
            ->where('deleted', 0);

        $this->applyActorScope($vehicleQuery, $request);

        if ($request->filled('vehicle_id')) {
            $vehicleQuery->where('id', (int) $request->query('vehicle_id'));
        }

        $vehicles = $vehicleQuery
            ->orderBy('id')
            ->get();

        $assignedDriversByVehicleId = $this->getAssignedDriversByVehicleId(
            $vehicles->pluck('id')->all(),
            $request
        );

        $rows = [];
        foreach ($vehicles as $vehicle) {
            $assignedDriver = $assignedDriversByVehicleId[$vehicle->id] ?? null;
            $trackingMapping = $this->resolveTrackingMappingForVehicle(
                $vehicle,
                $this->getDriverTrackingLookupByVehicleNumber([$vehicle->vehicle_number], $request),
                $assignedDriver,
                $request
            );
            $trackingDriverId = $trackingMapping['tracking_driver_id'];
            $trackingRow = $trackingDriverId !== null
                ? $this->driverTrackingQuery($request)->where('id', $trackingDriverId)->first()
                : null;

            $rows[] = [
                'vehicle_id' => $vehicle->id,
                'vehicle_user_id' => $this->toNullableInteger($vehicle->user_id ?? null),
                'vehicle_number' => $vehicle->vehicle_number,
                'assigned_driver' => [
                    'id' => $this->toNullableInteger($assignedDriver->id ?? null),
                    'user_id' => $this->toNullableInteger($assignedDriver->user_id ?? null),
                    'name' => $assignedDriver->driver_name ?? null,
                    'phone' => $assignedDriver->driver_phone ?? null,
                    'vehicle_id' => $this->toNullableInteger($assignedDriver->vehicle_id ?? null),
                    'is_assigned' => $this->toNullableInteger($assignedDriver->is_assigned ?? null),
                ],
                'matched_driver' => [
                    'id' => $this->toNullableInteger($trackingRow->id ?? null),
                    'user_id' => $this->toNullableInteger($trackingRow->user_id ?? null),
                    'full_name' => $trackingRow->full_name ?? null,
                    'phone_number' => $trackingRow->phone_number ?? null,
                    'vehicle_number' => $trackingRow->vehicle_number ?? null,
                    'latitude' => $this->toNullableFloat($trackingRow->current_lat ?? null),
                    'longitude' => $this->toNullableFloat($trackingRow->current_lng ?? null),
                    'updated_at' => $this->toIsoDateTime($trackingRow->updated_at ?? null),
                ],
                'mapping_status' => $trackingMapping['status'],
                'mapping_message' => $trackingMapping['message'],
            ];
        }

        return response()->json([
            'success' => true,
            'count' => count($rows),
            'rows' => $rows,
        ]);

    }

    public function updateLiveTracking(Request $request)

    {
        if (! $this->isDriverTrackingSchemaReady()) {
            return response()->json([
                'success' => false,
                'message' => 'drivers table/current_lat/current_lng columns are missing.',
            ], 422);
        }

        $request->validate([
            'driver_id' => 'nullable|integer|min:1',
            'driver_details_id' => 'nullable|integer|min:1',
            'vehicle_id' => 'nullable|integer|min:1',
            'vehicle_number' => 'nullable|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'recorded_at' => 'nullable|date',
        ]);

        $applyScope = $this->resolveActorUserId($request) !== null;

        $recordedAt = $request->filled('recorded_at')
            ? \Carbon\Carbon::parse($request->input('recorded_at'))
            : now();

        $trackingRow = $this->resolveTrackingRowForUpdate($request, $applyScope);
        if (! $trackingRow) {
            $trackingRow = $this->createTrackingRowForUpdate($request, $recordedAt, $applyScope);
        }

        if (! $trackingRow) {
            return response()->json([
                'success' => false,
                'message' => 'Matching driver row not found for live tracking update.',
            ], 404);
        }

        Driver::where('id', (int) $trackingRow->id)
            ->where('deleted', 0)
            ->update([
                'current_lat' => $request->input('latitude'),
                'current_lng' => $request->input('longitude'),
                'updated_at' => $recordedAt->format('Y-m-d H:i:s'),
            ]);

        $vehicleId = $this->resolveVehicleIdForTrackingRow($trackingRow, $request, $applyScope);
        if ($vehicleId !== null) {
            Vehicle::where('id', $vehicleId)->update([
                'current_latitude' => $request->input('latitude'),
                'current_longitude' => $request->input('longitude'),
                'location_source' => 'drivers',
                'location_recorded_at' => $recordedAt,
            ]);
        }

        $freshTrackingRow = $this->driverTrackingQuery($request, $applyScope)
            ->where('id', (int) $trackingRow->id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Live tracking location updated successfully.',
            'updated_at' => $recordedAt->toISOString(),
            'data' => $freshTrackingRow ? $this->buildTrackingDataFromDriverRow($freshTrackingRow) : null,
        ]);

    }

    private function driverTrackingQuery(Request $request, bool $applyScope = true)

    {
        if (! $this->isDriverTrackingSchemaReady()) {
            return DB::query()
                ->fromSub(function ($query) {
                    $query->from('drivers')
                        ->selectRaw('NULL as id')
                        ->selectRaw('NULL as user_id')
                        ->selectRaw('NULL as full_name')
                        ->selectRaw('NULL as phone_number')
                        ->selectRaw('NULL as vehicle_number')
                        ->selectRaw('NULL as vehicle_model')
                        ->selectRaw('NULL as vehicle_capacity')
                        ->selectRaw('NULL as current_lat')
                        ->selectRaw('NULL as current_lng')
                        ->selectRaw('NULL as updated_at')
                        ->selectRaw('NULL as vehicle_id')
                        ->selectRaw("'drivers' as source")
                        ->whereRaw('1 = 0');
                }, 'drivers_fallback');
        }

        $selectColumns = [
            'id',
            'user_id',
            'driver_name as full_name',
            'driver_phone as phone_number',
            'current_lat',
            'current_lng',
            'updated_at',
            'vehicle_id',
            DB::raw("'drivers' as source"),
        ];
        $selectColumns[] = Schema::hasColumn('drivers', 'vehicle_number')
            ? 'vehicle_number'
            : DB::raw('NULL as vehicle_number');
        $selectColumns[] = Schema::hasColumn('drivers', 'vehicle_model')
            ? 'vehicle_model'
            : DB::raw('NULL as vehicle_model');
        $selectColumns[] = Schema::hasColumn('drivers', 'vehicle_capacity')
            ? 'vehicle_capacity'
            : DB::raw('NULL as vehicle_capacity');

        $query = DB::table('drivers')
            ->select($selectColumns)
            ->where('deleted', 0);

        if ($applyScope) {
            $this->applyActorScope($query, $request);
        }

        return $query;

    }

    private function resolveTrackingRowForUpdate(Request $request, bool $applyScope = true)

    {
        $requestedDriverId = $request->filled('driver_id')
            ? (int) $request->input('driver_id')
            : ($request->filled('driver_details_id') ? (int) $request->input('driver_details_id') : null);

        if ($requestedDriverId) {
            return $this->driverTrackingQuery($request, $applyScope)
                ->where('id', $requestedDriverId)
                ->first();
        }

        if ($request->filled('vehicle_id')) {
            $driverId = $this->resolveTrackingDriverIdForVehicle(
                (int) $request->input('vehicle_id'),
                $request,
                $applyScope
            );

            if ($driverId !== null) {
                return $this->driverTrackingQuery($request, $applyScope)
                    ->where('id', $driverId)
                    ->first();
            }
        }

        if ($request->filled('vehicle_number')) {
            $lookup = $this->getDriverTrackingLookupByVehicleNumber([$request->input('vehicle_number')], $request, $applyScope);
            $normalizedVehicleNumber = $this->normalizeVehicleIdentifier($request->input('vehicle_number'));

            if ($normalizedVehicleNumber !== null && isset($lookup[$normalizedVehicleNumber])) {
                return $this->driverTrackingQuery($request, $applyScope)
                    ->where('id', (int) $lookup[$normalizedVehicleNumber]->id)
                    ->first();
            }
        }

        $actorUserId = $this->resolveActorUserId($request);
        if ($actorUserId) {
            return $this->driverTrackingQuery($request, $applyScope)
                ->where('user_id', $actorUserId)
                ->orderByDesc('id')
                ->first();
        }

        return null;

    }

    private function createTrackingRowForUpdate(Request $request, $recordedAt, bool $applyScope = true)

    {
        $vehicle = $this->resolveVehicleForTrackingUpdate($request, $applyScope);
        if (! $vehicle) {
            return null;
        }

        $trackingDriverId = $this->resolveTrackingDriverIdForVehicle((int) $vehicle->id, $request, $applyScope);
        if (! $trackingDriverId) {
            return null;
        }

        $driverQuery = Driver::query()
            ->select([
                'id',
                'user_id',
                'vehicle_id',
                'driver_name',
                'driver_phone',
                'license_no',
            ])
            ->where('deleted', 0)
            ->where('id', $trackingDriverId);

        if ($applyScope) {
            $this->applyActorScope($driverQuery, $request);
        }

        $driver = $driverQuery->first();
        if (! $driver) {
            return null;
        }

        $driverUpdatePayload = [
            'vehicle_id' => $vehicle->id,
            'current_lat' => $request->input('latitude'),
            'current_lng' => $request->input('longitude'),
            'updated_at' => $recordedAt->format('Y-m-d H:i:s'),
        ];

        if (Schema::hasColumn('drivers', 'vehicle_number')) {
            $driverUpdatePayload['vehicle_number'] = $vehicle->vehicle_number;
        }

        if (Schema::hasColumn('drivers', 'vehicle_model')) {
            $driverUpdatePayload['vehicle_model'] = $vehicle->vehicleType->vehicle_type ?? null;
        }

        if (Schema::hasColumn('drivers', 'vehicle_capacity')) {
            $driverUpdatePayload['vehicle_capacity'] = $vehicle->seating_capacity;
        }

        Driver::where('id', $driver->id)->update($driverUpdatePayload);

        Vehicle::where('id', $vehicle->id)->update([
            'driver_id' => $driver->id,
            'current_latitude' => $request->input('latitude'),
            'current_longitude' => $request->input('longitude'),
            'location_source' => 'drivers',
            'location_recorded_at' => $recordedAt,
            'updated_at' => $recordedAt->format('Y-m-d H:i:s'),
        ]);

        return $this->driverTrackingQuery($request, $applyScope)
            ->where('id', (int) $driver->id)
            ->first();

    }

    private function getDriverTrackingLookupByVehicleNumber(array $vehicleNumbers, Request $request, bool $applyScope = true): array

    {
        if (! $this->isDriverTrackingSchemaReady() || ! Schema::hasColumn('drivers', 'vehicle_number')) {
            return [];
        }

        $normalizedVehicleNumbers = collect($vehicleNumbers)
            ->map(fn ($vehicleNumber) => $this->normalizeVehicleIdentifier($vehicleNumber))
            ->filter()
            ->unique()
            ->values();

        if ($normalizedVehicleNumbers->isEmpty()) {
            return [];
        }

        $query = DB::table('drivers')
            ->select([
                'id',
                'user_id',
                'vehicle_id',
                'vehicle_number',
                'is_assigned',
                'current_lat',
                'current_lng',
                'updated_at',
            ])
            ->where('deleted', 0)
            ->whereIn(
                DB::raw("LOWER(REPLACE(TRIM(vehicle_number), ' ', ''))"),
                $normalizedVehicleNumbers->all()
            )
            ->orderByDesc('is_assigned')
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($applyScope) {
            $this->applyActorScope($query, $request);
        }

        $lookup = [];
        foreach ($query->get() as $driverRow) {
            $normalizedVehicleNumber = $this->normalizeVehicleIdentifier($driverRow->vehicle_number ?? null);

            if ($normalizedVehicleNumber === null || isset($lookup[$normalizedVehicleNumber])) {
                continue;
            }

            $lookup[$normalizedVehicleNumber] = $driverRow;
        }

        return $lookup;

    }

    private function resolveTrackingDriverIdForVehicle(int $vehicleId, Request $request, bool $applyScope = true): ?int

    {
        if ($vehicleId <= 0) {
            return null;
        }

        $assignedDriver = $this->getAssignedDriverForVehicleId($vehicleId, $request, $applyScope);
        if ($assignedDriver) {
            return $this->toNullableInteger($assignedDriver->id ?? null);
        }

        $vehicleQuery = Vehicle::query()
            ->select(['id', 'vehicle_number', 'driver_id'])
            ->where('deleted', 0)
            ->where('id', $vehicleId);

        if ($applyScope) {
            $this->applyActorScope($vehicleQuery, $request);
        }

        $vehicle = $vehicleQuery->first();
        if (! $vehicle) {
            return null;
        }

        $vehicleDriverId = $this->toNullableInteger($vehicle->driver_id ?? null);
        if ($vehicleDriverId !== null) {
            $driverQuery = Driver::query()
                ->select('id')
                ->where('deleted', 0)
                ->where('id', $vehicleDriverId);

            if ($applyScope) {
                $this->applyActorScope($driverQuery, $request);
            }

            $driverId = $driverQuery->value('id');
            if ($driverId) {
                return (int) $driverId;
            }
        }

        $lookup = $this->getDriverTrackingLookupByVehicleNumber([$vehicle->vehicle_number], $request, $applyScope);
        $normalizedVehicleNumber = $this->normalizeVehicleIdentifier($vehicle->vehicle_number);

        if ($normalizedVehicleNumber !== null && isset($lookup[$normalizedVehicleNumber])) {
            return $this->toNullableInteger($lookup[$normalizedVehicleNumber]->id ?? null);
        }

        return null;

    }

    private function resolveTrackingDriverId(int $driverIdentifier, Request $request, bool $applyScope = true): ?int

    {
        if ($driverIdentifier <= 0) {
            return null;
        }

        $driverQuery = Driver::query()
            ->select(['id', 'vehicle_id'])
            ->where('deleted', 0)
            ->where('id', $driverIdentifier);

        if ($applyScope) {
            $this->applyActorScope($driverQuery, $request);
        }

        $driver = $driverQuery->first();
        if ($driver) {
            return (int) $driver->id;
        }

        return $this->resolveTrackingDriverIdForVehicle($driverIdentifier, $request, $applyScope);

    }

    private function resolveVehicleForTrackingUpdate(Request $request, bool $applyScope = true): ?Vehicle

    {
        $vehicleQuery = Vehicle::query()
            ->with('vehicleType')
            ->where('deleted', 0);

        if ($request->filled('vehicle_id')) {
            $vehicleQuery->where('id', (int) $request->input('vehicle_id'));
        } elseif ($request->filled('vehicle_number')) {
            $normalizedVehicleNumber = $this->normalizeVehicleIdentifier($request->input('vehicle_number'));
            if ($normalizedVehicleNumber === null) {
                return null;
            }

            $vehicleQuery->whereRaw(
                "LOWER(REPLACE(TRIM(vehicle_number), ' ', '')) = ?",
                [$normalizedVehicleNumber]
            );
        } else {
            return null;
        }

        if ($applyScope) {
            $this->applyActorScope($vehicleQuery, $request);
        }

        return $vehicleQuery->first();

    }

    private function getDriverDetailsLookupByVehicleNumber(array $vehicleNumbers, Request $request, bool $applyScope = true): array

    {
        if (! $this->isDriverDetailsLookupSchemaReady()) {
            return [];
        }

        $normalizedVehicleNumbers = collect($vehicleNumbers)
            ->map(fn ($vehicleNumber) => $this->normalizeVehicleIdentifier($vehicleNumber))
            ->filter()
            ->unique()
            ->values();

        if ($normalizedVehicleNumbers->isEmpty()) {
            return [];
        }

        $query = DB::table('driverdetails')
            ->select([
                'id',
                'vehicleNumber as vehicle_number',
            ])
            ->whereIn(
                DB::raw("LOWER(REPLACE(TRIM(vehicleNumber), ' ', ''))"),
                $normalizedVehicleNumbers->all()
            )
            ->orderByDesc('id');

        if ($applyScope) {
            $this->applyActorScope($query, $request, 'userId');
        }

        $lookup = [];
        foreach ($query->get() as $driverRow) {
            $normalizedVehicleNumber = $this->normalizeVehicleIdentifier($driverRow->vehicle_number ?? null);

            if ($normalizedVehicleNumber === null || isset($lookup[$normalizedVehicleNumber])) {
                continue;
            }

            $lookup[$normalizedVehicleNumber] = $driverRow;
        }

        return $lookup;

    }

    private function resolveDriverDetailsIdForVehicle(int $vehicleId, Request $request, bool $applyScope = true): ?int

    {
        if ($vehicleId <= 0 || ! $this->isDriverDetailsLookupSchemaReady()) {
            return null;
        }

        $trackingRow = $this->findDriverDetailsRowByVehicleId($vehicleId, $request, $applyScope);
        if ($trackingRow) {
            return $this->toNullableInteger($trackingRow->id);
        }

        $vehicleQuery = Vehicle::query()
            ->select(['id', 'vehicle_number'])
            ->where('deleted', 0)
            ->where('id', $vehicleId);

        if ($applyScope) {
            $this->applyActorScope($vehicleQuery, $request);
        }

        $vehicle = $vehicleQuery->first();
        if (! $vehicle) {
            return null;
        }

        $lookup = $this->getDriverDetailsLookupByVehicleNumber([$vehicle->vehicle_number], $request, $applyScope);
        $trackingMapping = $this->resolveTrackingMappingForVehicle(
            $vehicle,
            $lookup,
            $this->getAssignedDriverForVehicleId($vehicleId, $request, $applyScope),
            $request,
            $applyScope
        );

        return $trackingMapping['tracking_driver_id'];

    }

    private function resolveTrackingDriverDetailsId(int $driverIdentifier, Request $request, bool $applyScope = true): ?int

    {
        if ($driverIdentifier <= 0) {
            return null;
        }

        $trackingRow = $this->driverDetailsTrackingQuery($request, $applyScope)
            ->where('id', $driverIdentifier)
            ->first();

        if ($trackingRow) {
            return $driverIdentifier;
        }

        $driverQuery = Driver::query()
            ->select([
                'id',
                'user_id',
                'vehicle_id',
                'driver_name',
                'driver_phone',
                'is_assigned',
            ])
            ->where('deleted', 0)
            ->where('id', $driverIdentifier);

        if ($applyScope) {
            $this->applyActorScope($driverQuery, $request);
        }

        $driver = $driverQuery->first();
        if (! $driver) {
            return null;
        }

        $vehicleId = $this->toNullableInteger($driver->vehicle_id ?? null);
        if ($vehicleId !== null) {
            $resolvedDriverDetailsId = $this->resolveDriverDetailsIdForVehicle(
                $vehicleId,
                $request,
                $applyScope
            );

            if ($resolvedDriverDetailsId !== null) {
                return $resolvedDriverDetailsId;
            }
        }

        $resolvedDriverDetailsId = $this->resolveDriverDetailsIdFromDriverUserId(
            $driver,
            $request,
            $applyScope
        );

        if ($resolvedDriverDetailsId !== null) {
            return $resolvedDriverDetailsId;
        }

        return $this->resolveDriverDetailsIdFromAssignedDriver(
            null,
            $driver->driver_name ?? null,
            $driver->driver_phone ?? null,
            $request,
            $applyScope
        );

    }

    private function getAssignedDriversByVehicleId(array $vehicleIds, Request $request, bool $applyScope = true): array

    {
        $vehicleIds = collect($vehicleIds)
            ->map(fn ($vehicleId) => $this->toNullableInteger($vehicleId))
            ->filter()
            ->unique()
            ->values();

        if ($vehicleIds->isEmpty()) {
            return [];
        }

        $driverQuery = Driver::query()
            ->select([
                'id',
                'user_id',
                'vehicle_id',
                'driver_name',
                'driver_phone',
                'current_lat',
                'current_lng',
                'is_assigned',
            ])
            ->where('deleted', 0)
            ->whereIn('vehicle_id', $vehicleIds->all())
            ->orderByDesc('is_assigned')
            ->orderByDesc('id');

        if ($applyScope) {
            $this->applyActorScope($driverQuery, $request);
        }

        $assignedDriversByVehicleId = [];
        foreach ($driverQuery->get() as $driver) {
            $vehicleId = $this->toNullableInteger($driver->vehicle_id);

            if ($vehicleId === null || isset($assignedDriversByVehicleId[$vehicleId])) {
                continue;
            }

            $assignedDriversByVehicleId[$vehicleId] = $driver;
        }

        return $assignedDriversByVehicleId;

    }

    private function getAssignedDriverForVehicleId(int $vehicleId, Request $request, bool $applyScope = true): ?Driver

    {
        return $this->getAssignedDriversByVehicleId([$vehicleId], $request, $applyScope)[$vehicleId] ?? null;

    }

    private function resolveTrackingMappingForVehicle(
        Vehicle $vehicle,
        array $trackingDriversByVehicleNumber,
        ?Driver $assignedDriver,/*  */
        Request $request,
        bool $applyScope = true
    ): array

    {
        if (! $this->isDriverTrackingSchemaReady()) {
            return [
                'tracking_driver_id' => null,
                'status' => 'not_configured',
                'message' => 'Tracking unavailable: drivers table/current_lat/current_lng columns are missing.',
            ];
        }

        $trackingDriverId = $this->toNullableInteger($assignedDriver->id ?? null);
        $noLocationMessage = 'Current lat and lng not found for this vehicle or driver.';

        if ($trackingDriverId !== null && $this->hasTrackingCoordinates($assignedDriver)) {
            return [
                'tracking_driver_id' => $trackingDriverId,
                'status' => 'mapped',
                'message' => 'Tracking available.',
            ];
        }

        if ($trackingDriverId === null) {
            $trackingDriverId = $this->resolveTrackingDriverIdForVehicle(
                (int) $vehicle->id,
                $request,
                $applyScope
            );
        }

        $normalizedVehicleNumber = $this->normalizeVehicleIdentifier($vehicle->vehicle_number);
        if (
            $normalizedVehicleNumber !== null
            && isset($trackingDriversByVehicleNumber[$normalizedVehicleNumber])
        ) {
            $trackingDriverRow = $trackingDriversByVehicleNumber[$normalizedVehicleNumber];

            if ($this->hasTrackingCoordinates($trackingDriverRow)) {
                return [
                    'tracking_driver_id' => $this->toNullableInteger($trackingDriverRow->id ?? null),
                    'status' => 'mapped',
                    'message' => 'Tracking available.',
                ];
            }

            return [
                'tracking_driver_id' => $this->toNullableInteger($trackingDriverRow->id ?? null),
                'status' => 'no_location',
                'message' => $noLocationMessage,
            ];
        }

        if ($trackingDriverId !== null) {
            return [
                'tracking_driver_id' => $trackingDriverId,
                'status' => 'no_location',
                'message' => $noLocationMessage,
            ];
        }

        return [
            'tracking_driver_id' => null,
            'status' => 'not_mapped',
            'message' => 'Tracking unavailable: no assigned/current driver row found for this vehicle in drivers table.',
        ];

    }

    private function resolveDriverDetailsIdFromDriverUserId(?Driver $assignedDriver, Request $request, bool $applyScope = true): ?int

    {
        if (! $this->isDriverDetailsSchemaReady()) {
            return null;
        }

        $driverUserId = $this->toNullableInteger($assignedDriver->login_user_id ?? $assignedDriver->user_id ?? null);
        if ($driverUserId === null) {
            return null;
        }

        $vehicleNumber = null;
        $vehicleId = $this->toNullableInteger($assignedDriver->vehicle_id ?? null);
        if ($assignedDriver && $assignedDriver->vehicle_id) {
            $vehicleQuery = Vehicle::query()
                ->select('vehicle_number')
                ->where('id', $assignedDriver->vehicle_id)
                ->where('deleted', 0);

            if ($applyScope) {
                $this->applyActorScope($vehicleQuery, $request);
            }
            $vehicleNumber = optional($vehicleQuery->first())->vehicle_number;
        }

        $candidates = $this->driverDetailsTrackingQuery($request, $applyScope)
            ->where('userId', $driverUserId)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $bestMatchId = null;
        $bestScore = -1;

        foreach ($candidates as $candidate) {
            $score = 0;

            if (
                $vehicleId !== null
                && $this->toNullableInteger($candidate->vehicle_id ?? null) === $vehicleId
            ) {
                $score += 8;
            }

            if (($candidate->phone_number ?? null) === ($assignedDriver->driver_phone ?? null)) {
                $score += 5;
            }

            if (($candidate->full_name ?? null) === ($assignedDriver->driver_name ?? null)) {
                $score += 4;
            }

            if (($candidate->vehicle_number ?? null) === $vehicleNumber) {
                $score += 3;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatchId = $this->toNullableInteger($candidate->id ?? null);
            }
        }

        return $bestScore > 0 ? $bestMatchId : null;

    }

    private function buildTrackingDataFromDriverRow($driverRow): array

    {
        $latitude = $this->toNullableFloat($driverRow->current_lat ?? null);
        $longitude = $this->toNullableFloat($driverRow->current_lng ?? null);
        $recordedAt = $this->toIsoDateTime($driverRow->updated_at ?? null);
        $hasLiveLocation = $latitude !== null && $longitude !== null;

        return [
            'id' => $this->toNullableInteger($driverRow->id ?? null),
            'user_id' => $this->toNullableInteger($driverRow->user_id ?? null),
            'vehicle_id' => $this->toNullableInteger($driverRow->vehicle_id ?? null),
            'driver_name' => $driverRow->full_name ?? null,
            'vehicle_number' => $driverRow->vehicle_number ?: ('Driver #' . ($driverRow->id ?? '')),
            'vehicle_type' => $driverRow->vehicle_model ?? 'Unknown',
            'vehicle_capacity' => $this->toNullableInteger($driverRow->vehicle_capacity ?? null),
            'phone_number' => $driverRow->phone_number ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'speed_kmh' => null,
            'status' => $hasLiveLocation ? 1 : 0,
            'source' => $driverRow->source ?? 'drivers',
            'heading' => null,
            'recorded_at' => $recordedAt,
            'is_simulated' => false,
        ];

    }

    private function hasTrackingCoordinates($trackingRow): bool

    {
        return $this->toNullableFloat($trackingRow->current_lat ?? null) !== null
            && $this->toNullableFloat($trackingRow->current_lng ?? null) !== null;

    }

    private function isDriverTrackingSchemaReady(): bool

    {
        return Schema::hasTable('drivers')
            && Schema::hasColumns('drivers', [
                'id',
                'user_id',
                'current_lat',
                'current_lng',
            ]);

    }

    private function isDriverDetailsSchemaReady(): bool

    {
        return Schema::hasTable('driverdetails')
            && Schema::hasColumns('driverdetails', [
                'id',
                'userId',
                'currentLat',
                'currentLng',
            ]);

    }

    private function isDriverDetailsLookupSchemaReady(): bool

    {
        return Schema::hasTable('driverdetails')
            && Schema::hasColumns('driverdetails', [
                'id',
                'userId',
                'vehicleNumber',
            ]);

    }

    private function normalizeVehicleIdentifier($value): ?string

    {
        if ($value === null) {
            return null;
        }

        $normalizedValue = strtolower(str_replace(' ', '', trim((string) $value)));

        return $normalizedValue !== '' ? $normalizedValue : null;

    }

    private function resolveDriverDetailsIdFromAssignedDriver(
        ?string $vehicleNumber,
        ?string $driverName,
        ?string $driverPhone,
        Request $request,
        bool $applyScope = true
    ): ?int

    {
        if (! $this->isDriverDetailsExtendedLookupSchemaReady()) {
            return null;
        }

        $normalizedVehicleNumber = $this->normalizeVehicleIdentifier($vehicleNumber);
        $normalizedDriverName = $this->normalizeLooseIdentifier($driverName);
        $normalizedDriverPhone = $this->normalizePhoneNumber($driverPhone);

        if ($normalizedVehicleNumber === null && $normalizedDriverName === null && $normalizedDriverPhone === null) {
            return null;
        }

        $query = DB::table('driverdetails')
            ->select([
                'id',
                'vehicleNumber as vehicle_number',
                'fullName as full_name',
                'phoneNumber as phone_number',
            ])
            ->orderByDesc('id');

        if ($applyScope) {
            $this->applyActorScope($query, $request, 'userId');
        }

        $query->where(function ($query) use ($normalizedVehicleNumber, $normalizedDriverName, $normalizedDriverPhone) {
            $isFirstCondition = true;

            if ($normalizedVehicleNumber !== null) {
                $query->whereRaw(
                    "LOWER(REPLACE(TRIM(vehicleNumber), ' ', '')) = ?",
                    [$normalizedVehicleNumber]
                );
                $isFirstCondition = false;
            }

            if ($normalizedDriverPhone !== null) {
                $method = $isFirstCondition ? 'whereRaw' : 'orWhereRaw';
                $query->{$method}(
                    "REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phoneNumber), ' ', ''), '-', ''), '(', ''), ')', '') = ?",
                    [$normalizedDriverPhone]
                );
                $isFirstCondition = false;
            }

            if ($normalizedDriverName !== null) {
                $method = $isFirstCondition ? 'whereRaw' : 'orWhereRaw';
                $query->{$method}(
                    "LOWER(REPLACE(TRIM(fullName), ' ', '')) = ?",
                    [$normalizedDriverName]
                );
            }
        });

        $bestMatchId = null;
        $bestMatchScore = -1;

        foreach ($query->limit(20)->get() as $driverRow) {
            $score = 0;

            if (
                $normalizedVehicleNumber !== null
                && $this->normalizeVehicleIdentifier($driverRow->vehicle_number ?? null) === $normalizedVehicleNumber
            ) {
                $score += 4;
            }

            if (
                $normalizedDriverPhone !== null
                && $this->normalizePhoneNumber($driverRow->phone_number ?? null) === $normalizedDriverPhone
            ) {
                $score += 3;
            }

            if (
                $normalizedDriverName !== null
                && $this->normalizeLooseIdentifier($driverRow->full_name ?? null) === $normalizedDriverName
            ) {
                $score += 2;
            }

            if ($score > $bestMatchScore) {
                $bestMatchScore = $score;
                $bestMatchId = $this->toNullableInteger($driverRow->id ?? null);
            }
        }

        return $bestMatchId;

    }

    private function resolveVehicleIdForTrackingRow($trackingRow, Request $request, bool $applyScope = true): ?int

    {
        $vehicleIdFromRequest = $this->toNullableInteger($request->input('vehicle_id'));
        if ($vehicleIdFromRequest !== null) {
            $vehicleQuery = Vehicle::query()
                ->select('id')
                ->where('deleted', 0)
                ->where('id', $vehicleIdFromRequest);

            if ($applyScope) {
                $this->applyActorScope($vehicleQuery, $request);
            }

            if ($vehicleQuery->exists()) {
                return $vehicleIdFromRequest;
            }
        }

        $vehicleIdFromTrackingRow = $this->toNullableInteger($trackingRow->vehicle_id ?? null);
        if ($vehicleIdFromTrackingRow !== null) {
            return $vehicleIdFromTrackingRow;
        }

        $normalizedVehicleNumber = $this->normalizeVehicleIdentifier($trackingRow->vehicle_number ?? null);
        if ($normalizedVehicleNumber === null) {
            return null;
        }

        $vehicleQuery = Vehicle::query()
            ->select(['id', 'vehicle_number'])
            ->where('deleted', 0);

        if ($applyScope) {
            $this->applyActorScope($vehicleQuery, $request);
        }

        foreach ($vehicleQuery->get() as $vehicle) {
            if ($this->normalizeVehicleIdentifier($vehicle->vehicle_number) === $normalizedVehicleNumber) {
                return $this->toNullableInteger($vehicle->id);
            }
        }

        return null;

    }

    private function syncDriverDetailsVehicleRow(Vehicle $vehicle, Request $request): void

    {
        $driverId = $vehicle->driver_id ?: $this->resolveAssignedDriverIdForVehicleSync((int) $vehicle->id, $request);
        if (! $driverId) {
            return;
        }

        Driver::where('id', (int) $driverId)
            ->where('deleted', 0)
            ->update([
                'vehicle_id' => $vehicle->id,
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ]);

        Vehicle::where('id', $vehicle->id)->update([
            'driver_id' => (int) $driverId,
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ]);

    }

    private function resolveAssignedDriverIdForVehicleSync(int $vehicleId, Request $request, bool $applyScope = true): ?int

    {
        if ($vehicleId <= 0) {
            return null;
        }

        $assignedDriver = $this->getAssignedDriverForVehicleId($vehicleId, $request, $applyScope);

        return $assignedDriver ? $this->toNullableInteger($assignedDriver->id ?? null) : null;

    }

    private function findDriverDetailsRowByVehicleId(int $vehicleId, Request $request, bool $applyScope = true)

    {
        if ($vehicleId <= 0 || ! Schema::hasTable('driverdetails') || ! Schema::hasColumn('driverdetails', 'vehicleId')) {
            return null;
        }

        $query = DB::table('driverdetails')
            ->select(['id', 'vehicleId'])
            ->where('vehicleId', $vehicleId)
            ->orderByDesc('id');

        if ($applyScope) {
            $this->applyActorScope($query, $request, 'userId');
        }

        return $query->first();

    }

    private function isDriverDetailsExtendedLookupSchemaReady(): bool

    {
        return Schema::hasTable('driverdetails')
            && Schema::hasColumns('driverdetails', [
                'id',
                'userId',
                'vehicleNumber',
                'fullName',
                'phoneNumber',
            ]);

    }

    private function normalizeLooseIdentifier($value): ?string

    {
        if ($value === null) {
            return null;
        }

        $normalizedValue = strtolower(str_replace(' ', '', trim((string) $value)));

        return $normalizedValue !== '' ? $normalizedValue : null;

    }

    private function normalizePhoneNumber($value): ?string

    {
        if ($value === null) {
            return null;
        }

        $normalizedValue = preg_replace('/[^0-9]/', '', (string) $value);

        return $normalizedValue !== '' ? $normalizedValue : null;

    }

    private function toIsoDateTime($value): ?string

    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toISOString();
        } catch (\Throwable $e) {
            return null;
        }

    }

    private function toNullableInteger($value): ?int

    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;

    }

    private function toNullableFloat($value): ?float

    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;

    }



}


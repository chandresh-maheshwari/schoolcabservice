<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Mail\UserCredentialsMail;
use App\Models\Driver;
use App\Models\DriverVehicleHistory;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
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

    private function documentFileRules(string $presenceRule, int $minWidth, int $minHeight, string $label): array
    {
        return [
            $presenceRule,
            'file',
            function ($attribute, $value, $fail) use ($minWidth, $minHeight, $label) {
                $extension = strtolower((string) $value->getClientOriginalExtension());
                $mimeType = strtolower((string) $value->getMimeType());
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
                $allowedPdfMimeTypes = [
                    'application/pdf',
                    'application/x-pdf',
                    'application/acrobat',
                    'applications/vnd.pdf',
                    'text/pdf',
                    'text/x-pdf',
                ];

                $isAllowedImage = str_starts_with($mimeType, 'image/')
                    && in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true);
                $isAllowedPdf = $extension === 'pdf' || in_array($mimeType, $allowedPdfMimeTypes, true);

                if (! in_array($extension, $allowedExtensions, true) && ! $isAllowedPdf && ! $isAllowedImage) {
                    $fail("{$label} must be a JPG, JPEG, PNG, WEBP image or PDF file.");
                    return;
                }

                if (! $value || ! ImageHelper::isImageFile($value)) {
                    return;
                }

                if (! ImageHelper::meetsMinimumDimensions($value, $minWidth, $minHeight)) {
                    $fail("{$label} must be at least {$minWidth} x {$minHeight} pixels when uploading an image.");
                }
            },
        ];
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
                    'license_image'       => $this->documentFileRules('nullable', 800, 600, 'License image'),
                    'adher_card_iamge'    => $this->documentFileRules('nullable', 800, 600, 'Aadhaar image'),

                    'license_no'          => [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('drivers', 'license_no')->where(fn($q) => $q->where('deleted', 0)),
                    ],

                    'license_expiry_date' => 'required|date|after_or_equal:today',
                    'adher_no'            => [
                        'required',
                        'string',
                        'max:20',
                        Rule::unique('drivers', 'adher_no')->where(fn($q) => $q->where('deleted', 0)),
                    ],
                    'experience_years'    => 'required|integer|min:0',
                    'joining_date'        => 'nullable|date',
                    'login_email'         => 'required|email|max:255',
                    'login_username'      => 'required|string|min:4|max:255',
                    'password'            => 'required|string|min:8|same:password_confirmation',
                    'password_confirmation' => 'required|string|min:8',
                ],
                [

                    'driver_image.dimensions'     => 'Driver image must be at least 636 × 424 pixels.',
                    'license_image.dimensions'    => 'License image must be at least 800 × 600 pixels.',
                    'adher_card_iamge.dimensions' => 'Aadhaar image must be at least 800 × 600 pixels.',
                    'license_no.unique'           => 'License number already exists.',
                    'adher_no.unique'             => 'Aadhaar number already exists.',
                ]
            );

            $plainPassword = (string) $request->password;
            $persistedUserId = $this->resolvePersistedUserId($request);
            if (! $persistedUserId) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'User session not found. Please login again.',
                ], 401);
            }

            $selectedVehicle = null;
            if ($request->vehicle_id) {
                $vehicleQuery = Vehicle::where('id', (int) $request->vehicle_id)->where('deleted', 0);
                $this->applyActorScope($vehicleQuery, $request);
                $selectedVehicle = $vehicleQuery->first(['id', 'user_id']);
                if (! $selectedVehicle) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected vehicle is not accessible for current user.',
                    ], 422);
                }
            }

            $ownerUserId = $this->resolveModuleOwnerUserId($request, $persistedUserId, [
                (int) ($selectedVehicle->user_id ?? 0),
            ]);

            $loginUser = $this->createOrRestoreLoginUser([
                'email' => $request->login_email,
                'username' => $request->login_username,
                'password' => $plainPassword,
                'role_name' => 'Driver',
                'first_name' => $request->driver_name,
                'last_name' => 'Driver',
                'mobile' => $request->driver_phone,
            ]);

            $driverPayload = [
                'user_id'             => $ownerUserId,
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
            ];
            if (Schema::hasColumn('drivers', 'login_user_id')) {
                $driverPayload['login_user_id'] = $loginUser->id;
            }

            $driver = Driver::create($driverPayload);

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

            DriverVehicleHistory::where('driver_id', $driver->id)
                ->whereNull('user_id')
                ->update(['user_id' => $ownerUserId]);

            if ($request->vehicle_id) {
                $vehicleQuery = Vehicle::where('id', (int) $request->vehicle_id);
                $this->applyActorScope($vehicleQuery, $request);
                $vehicle = $vehicleQuery->first();

                if ($vehicle) {
                    $vehicle->update([
                        'is_assigned' => 1,
                        'driver_id' => $driver->id,
                    ]);

                    DriverVehicleHistory::create([
                        'user_id'     => $ownerUserId,
                        'driver_id'   => $driver->id,
                        'vehicle_id'  => $vehicle->id,
                        'is_assigned' => 1,
                    ]);
                }
            }

            $this->syncDriverDetailsRow($driver, null, $request, true);

            DB::commit();

            try {
                Mail::to($loginUser->email)->send(
                    new UserCredentialsMail(
                        'Driver',
                        (string) $driver->driver_name,
                        (string) ($loginUser->username ?: $loginUser->email),
                        $plainPassword
                    )
                );
            } catch (\Throwable $e) {
                Log::warning('Driver credentials email send failed', [
                    'driver_id' => $driver->id,
                    'user_id' => $loginUser->id,
                    'error' => $e->getMessage(),
                ]);
            }

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

    public function edit($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $driverQuery = Driver::query();
        $this->applyActorScope($driverQuery);
        $driver = $driverQuery->findOrFail($id);
        $loginUser = null;

        if ((int) ($driver->login_user_id ?? 0) > 0) {
            $loginUser = User::find((int) $driver->login_user_id);
        }

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

        return view('driver.edit', compact('driver', 'vehicles', 'loginUser'));
    }

    /**
     * Update driver data.
     * created by ns
     */
    public function update(Request $request, $schoolSlugOrId, $id = null)
{
    $id = $this->normalizeRouteId($schoolSlugOrId, $id);
    DB::beginTransaction();

    try {

        $driverQuery = Driver::query();
        $this->applyActorScope($driverQuery, $request);
        $driver = $driverQuery->findOrFail($id);
        $previousDriver = clone $driver;
        $oldVehicleId  = $driver->vehicle_id;

        $request->validate(
            [
                'user_id'             => 'nullable|exists:users,id',
                'vehicle_id'          => 'nullable|exists:vehicles,id',
                'vehicle_number'      => 'nullable|string|max:50',
                'login_email'         => 'required|email|max:255',
                'login_username'      => 'required|string|min:4|max:255',
                'password'            => 'nullable|string|min:8|same:password_confirmation',
                'password_confirmation' => '',
                'driver_name'         => 'required|string|max:255',
                'driver_phone'        => 'required|string|max:20',
                'emergency_phone'     => 'nullable|string|max:20',
                'driver_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=636,min_height=424',
                'license_no'          => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('drivers', 'license_no')
                        ->ignore($driver->id)
                        ->where(fn($q) => $q->where('deleted', 0)),
                ],
                'license_expiry_date' => 'nullable|date|after_or_equal:today',
                'license_image'       => $this->documentFileRules(
                    $driver->license_image ? 'nullable' : 'required',
                    800,
                    600,
                    'License image'
                ),
                'adher_no'            => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('drivers', 'adher_no')
                        ->ignore($driver->id)
                        ->where(fn($q) => $q->where('deleted', 0)),
                ],
                'adher_card_iamge'    => $this->documentFileRules(
                    $driver->adher_card_iamge ? 'nullable' : 'required',
                    800,
                    600,
                    'Aadhaar image'
                ),
                'experience_years'    => 'required|integer|min:0',
                'joining_date'        => 'nullable|date',
            ],
            [

                'driver_image.dimensions'     => 'Driver image must be at least 636 × 424 pixels.',
                'license_image.dimensions'    => 'License image must be at least 636 × 424 pixels.',
                'adher_card_iamge.dimensions' => 'Aadhaar image must be at least 636 × 424 pixels.',
                'license_no.unique'           => 'License number already exists.',
                'adher_no.unique'             => 'Aadhaar number already exists.',
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

        $selectedVehicle = null;
        if ($request->vehicle_id) {
            $vehicleScope = Vehicle::where('id', (int) $request->vehicle_id)->where('deleted', 0);
            $this->applyActorScope($vehicleScope, $request);
            $selectedVehicle = $vehicleScope->first(['id', 'user_id']);
            if (! $selectedVehicle) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Selected vehicle is not accessible for current user.',
                ], 422);
            }
        }

        $ownerUserId = $this->resolveModuleOwnerUserId($request, $persistedUserId, [
            (int) ($selectedVehicle->user_id ?? 0),
        ]);

        $loginUser = $this->createOrRestoreLoginUser([
            'existing_user_id' => $driver->login_user_id,
            'email' => $request->login_email,
            'username' => $request->login_username,
            'password' => $request->password,
            'role_name' => 'Driver',
            'first_name' => $request->driver_name,
            'last_name' => '',
            'mobile' => $request->driver_phone,
        ]);

        $driver->update([
            'user_id'             => $ownerUserId,
            'vehicle_id'          => $request->vehicle_id,
            'login_user_id'       => Schema::hasColumn('drivers', 'login_user_id') ? $loginUser->id : ($driver->login_user_id ?? null),
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
                $oldDriverImage,
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
                [800, 600],
                $oldLicenseImage,
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
                [800, 600],
                $oldAdherImage,
                false
            );
            $driver->adher_card_iamge = $newAdherImage;
        }

        $driver->save();


        if ($oldVehicleId && $oldVehicleId != $request->vehicle_id) {
            $oldVehicleQuery = Vehicle::where('id', $oldVehicleId);
            $this->applyActorScope($oldVehicleQuery, $request);
            $oldVehicleQuery->update([
                'is_assigned' => 0,
                'driver_id' => null,
            ]);
        }

        if ($request->vehicle_id) {
            $newVehicleQuery = Vehicle::where('id', $request->vehicle_id);
            $this->applyActorScope($newVehicleQuery, $request);
            $newVehicleQuery->update([
                'is_assigned' => 1,
                'driver_id' => $driver->id,
            ]);
        }

        if ($oldVehicleId != $request->vehicle_id) {
            DriverVehicleHistory::where('driver_id', $driver->id)
                ->whereNull('user_id')
                ->update(['user_id' => $ownerUserId]);

            if ($oldVehicleId) {
                DriverVehicleHistory::where('driver_id', $driver->id)
                    ->where('vehicle_id', $oldVehicleId)
                    ->where('deleted', 0)
                    ->where('is_assigned', 1)
                    ->update(['is_assigned' => 0]);
            }

            if ($request->vehicle_id) {
                DriverVehicleHistory::create([
                    'user_id'     => $ownerUserId,
                    'driver_id'   => $driver->id,
                    'vehicle_id'  => $request->vehicle_id,
                    'is_assigned' => 1,
                ]);
            }
        }

        $this->syncDriverDetailsRow($driver, $previousDriver, $request, false);

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

    private function syncDriverDetailsRow(
        Driver $driver,
        ?Driver $previousDriver = null,
        ?Request $request = null,
        bool $forceInsert = false
    ): void
    {
        $now = now()->format('Y-m-d H:i:s');

        $currentVehicleId = is_numeric($driver->vehicle_id) ? (int) $driver->vehicle_id : null;
        $previousVehicleId = is_numeric($previousDriver?->vehicle_id) ? (int) $previousDriver->vehicle_id : null;

        if ($previousVehicleId && $previousVehicleId !== $currentVehicleId) {
            Vehicle::where('id', $previousVehicleId)
                ->where('driver_id', $driver->id)
                ->update([
                    'driver_id' => null,
                    'updated_at' => $now,
                ]);
        }

        if ($currentVehicleId) {
            Vehicle::where('id', $currentVehicleId)
                ->where('deleted', 0)
                ->update([
                    'driver_id' => $driver->id,
                    'updated_at' => $now,
                ]);
        }
    }

    private function findDriverDetailsRowForDriver(Driver $driver, ?Driver $previousDriver = null, ?Request $request = null)
    {
        if (! Schema::hasTable('driverdetails')) {
            return null;
        }

        $selectColumns = [
            'id',
            'userId',
            'fullName',
            'licenseNumber',
            'phoneNumber',
            'vehicleNumber',
        ];
        if (Schema::hasColumn('driverdetails', 'vehicleId')) {
            $selectColumns[] = 'vehicleId';
        }

        $candidateQuery = DB::table('driverdetails')
            ->select($selectColumns)
            ->orderByDesc('id');

        $candidateQuery->where(function ($query) use ($driver, $previousDriver) {
            $hasCondition = false;

            if (Schema::hasColumn('driverdetails', 'vehicleId')) {
                foreach (array_unique(array_filter([
                    $driver->vehicle_id,
                    $previousDriver?->vehicle_id,
                ], fn ($value) => $value !== null && $value !== '')) as $vehicleId) {
                    $method = $hasCondition ? 'orWhere' : 'where';
                    $query->{$method}('vehicleId', $vehicleId);
                    $hasCondition = true;
                }
            }

            foreach (array_unique(array_filter([
                $driver->login_user_id ?: $driver->user_id,
                $previousDriver?->login_user_id ?: $previousDriver?->user_id,
            ], fn ($value) => $value !== null && $value !== '')) as $userId) {
                $method = $hasCondition ? 'orWhere' : 'where';
                $query->{$method}('userId', $userId);
                $hasCondition = true;
            }

            foreach (array_unique(array_filter([
                $driver->driver_phone,
                $previousDriver?->driver_phone,
            ])) as $phone) {
                $method = $hasCondition ? 'orWhere' : 'where';
                $query->{$method}('phoneNumber', $phone);
                $hasCondition = true;
            }

            foreach (array_unique(array_filter([
                $driver->license_no,
                $previousDriver?->license_no,
            ])) as $licenseNumber) {
                $method = $hasCondition ? 'orWhere' : 'where';
                $query->{$method}('licenseNumber', $licenseNumber);
                $hasCondition = true;
            }

            foreach (array_unique(array_filter([
                $driver->driver_name,
                $previousDriver?->driver_name,
            ])) as $fullName) {
                $method = $hasCondition ? 'orWhere' : 'where';
                $query->{$method}('fullName', $fullName);
                $hasCondition = true;
            }
        });

        $candidates = $candidateQuery->limit(25)->get();
        if ($candidates->isEmpty()) {
            return null;
        }

        $bestMatch = null;
        $bestScore = -1;
        $currentVehicleNumber = $this->resolveVehicleNumberForDriverDetailsSync($driver, $request);
        $previousVehicleNumber = $previousDriver
            ? $this->resolveVehicleNumberForDriverDetailsSync($previousDriver, $request)
            : null;
        $currentVehicleId = $driver->vehicle_id ? (int) $driver->vehicle_id : null;
        $previousVehicleId = $previousDriver?->vehicle_id ? (int) $previousDriver->vehicle_id : null;

        foreach ($candidates as $candidate) {
            $score = 0;

            if (
                Schema::hasColumn('driverdetails', 'vehicleId')
                && $currentVehicleId !== null
                && (int) ($candidate->vehicleId ?? 0) === $currentVehicleId
            ) {
                $score += 8;
            }
            if (
                Schema::hasColumn('driverdetails', 'vehicleId')
                && $previousVehicleId !== null
                && (int) ($candidate->vehicleId ?? 0) === $previousVehicleId
            ) {
                $score += 4;
            }

            if ((string) ($candidate->userId ?? '') !== '' && (int) $candidate->userId === (int) ($driver->login_user_id ?: $driver->user_id)) {
                $score += 5;
            }
            if (($candidate->phoneNumber ?? null) === $driver->driver_phone) {
                $score += 5;
            }
            if (($candidate->licenseNumber ?? null) === $driver->license_no) {
                $score += 4;
            }
            if (($candidate->fullName ?? null) === $driver->driver_name) {
                $score += 3;
            }
            if ($currentVehicleNumber && ($candidate->vehicleNumber ?? null) === $currentVehicleNumber) {
                $score += 2;
            }
            if (
                $previousVehicleNumber
                && $previousVehicleNumber === ($candidate->vehicleNumber ?? null)
                && $score > 0
            ) {
                $score += 1;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $candidate;
            }
        }

        return $bestScore > 0 ? $bestMatch : null;
    }

    private function findDriverDetailsRowByVehicleId($vehicleId, ?Request $request = null)
    {
        $vehicleId = is_numeric($vehicleId) ? (int) $vehicleId : null;
        if (! $vehicleId || ! Schema::hasTable('driverdetails') || ! Schema::hasColumn('driverdetails', 'vehicleId')) {
            return null;
        }

        $query = DB::table('driverdetails')
            ->select(['id', 'vehicleId'])
            ->where('vehicleId', $vehicleId)
            ->orderByDesc('id');

        if ($request) {
            $this->applyActorScope($query, $request, 'userId');
        }

        return $query->first();
    }

    private function resolveVehicleNumberForDriverDetailsSync(Driver $driver, ?Request $request = null): ?string
    {
        if (! $driver->vehicle_id) {
            return null;
        }

        $vehicleQuery = Vehicle::query()
            ->select('vehicle_number')
            ->where('id', $driver->vehicle_id)
            ->where('deleted', 0);

        if ($request) {
            $this->applyActorScope($vehicleQuery, $request);
        }

        return optional($vehicleQuery->first())->vehicle_number;
    }

    /**
     * Soft delete driver record.
     * created by ns
     */

    public function destroy($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $query = Driver::query();
        $this->applyActorScope($query);
        $driver = $query->findOrFail($id);

        if ($driver->vehicle_id) {
            $vehicleQuery = Vehicle::where('id', $driver->vehicle_id);
            $this->applyActorScope($vehicleQuery);
            $vehicleQuery->update([
                'is_assigned' => 0,
                'driver_id' => null,
            ]);
        }

        $driver->deleted = 1;
        $driver->is_assigned = 0;
        $driver->vehicle_id = null;
        $driver->save();

        return response()->json(['success' => true, 'message' => 'Driver deleted Successfully.']);
    }

    /**
     * Toggle driver active/inactive status.
     * created by ns
     */
    public function toggleStatus($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
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
   public function driverImage($schoolSlugOrId, $id = null)
{
    $id = $this->normalizeRouteId($schoolSlugOrId, $id);
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
    public function licenseImage($schoolSlugOrId, $id = null)
{
    $id = $this->normalizeRouteId($schoolSlugOrId, $id);
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
    public function adharCardImage($schoolSlugOrId, $id = null)
{
    $id = $this->normalizeRouteId($schoolSlugOrId, $id);
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
        $schoolNameMap = $this->getSchoolNameMapForDriverIds($driverDetails->pluck('id')->all());
        foreach ($driverDetails as $driver) {
            $data[] = [
                'id'                  => $driver->id,
                'school_name'         => $schoolNameMap[$driver->id] ?? '-',
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
        $drivers = $query->get(['id', 'vehicle_id']);

        $vehicleIds = $drivers
            ->pluck('vehicle_id')
            ->filter()
            ->unique()
            ->values();

        if ($vehicleIds->isNotEmpty()) {
            $vehicleQuery = Vehicle::whereIn('id', $vehicleIds->all());
            $this->applyActorScope($vehicleQuery, $request);
            $vehicleQuery->update([
                'is_assigned' => 0,
                'driver_id' => null,
            ]);
        }

        Driver::whereIn('id', $drivers->pluck('id')->all())->update([
            'deleted' => 1,
            'is_assigned' => 0,
            'vehicle_id' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Selected id deleted Successfully.',
        ]);
    }
}

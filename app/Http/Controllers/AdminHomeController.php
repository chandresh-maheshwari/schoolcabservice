<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Role;
use App\Models\Booking;
use App\Models\Child;
use App\Models\Driver;
use App\Models\Emergency;
use App\Models\Parents;
use App\Models\Rating;
use App\Models\Route;
use App\Models\School;
use App\Models\StopPickup;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class AdminHomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $userId = $user?->id;

        $isAdminUser = (bool) ($user && $user->isAdmin());

        $school = null;
        $schoolId = null;

        if (! $isAdminUser && $userId) {
            $school = School::query()
                ->where('user_id', $userId)
                ->where(function ($q) {
                    $q->where('deleted', 0)->orWhereNull('deleted');
                })
                ->first();
            $schoolId = $school?->id;
        }

        $scopeByUserId = function ($query) use ($isAdminUser, $userId) {
            if ($isAdminUser || ! $userId) {
                return $query;
            }

            return $query->where('user_id', $userId);
        };

        $countNotDeleted = function ($query) {
            return $query->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })->count();
        };

        $stats = [
            'vehicle_types' => $countNotDeleted($scopeByUserId(VehicleType::query())),
            'vehicles'      => $countNotDeleted($scopeByUserId(Vehicle::query())),
            'drivers'       => $countNotDeleted($scopeByUserId(Driver::query())),
            'routes'        => $countNotDeleted($scopeByUserId(Route::query())),
            'bookings'      => Booking::query()
                ->where(function ($q) {
                    $q->where('deleted', 0)->orWhereNull('deleted');
                })
                ->when($isAdminUser, fn ($q) => $q)
                ->when(! $isAdminUser && $schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when(! $isAdminUser && ! $schoolId, fn ($q) => $q->whereRaw('1 = 0'))
                ->count(),
            'emergencies'   => $countNotDeleted($scopeByUserId(Emergency::query())),
            'ratings'       => $countNotDeleted($scopeByUserId(Rating::query())),
            'stop_pickups'  => $countNotDeleted($scopeByUserId(StopPickup::query())),
            'parents'       => $countNotDeleted($scopeByUserId(Parents::query())),
            'children'      => $countNotDeleted($scopeByUserId(Child::query())),
        ];

        $recentBookingsQuery = Booking::query()
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            });

        if (! $isAdminUser) {
            if ($schoolId) {
                $recentBookingsQuery->where('school_id', $schoolId);
            } else {
                $recentBookingsQuery->whereRaw('1 = 0');
            }
        }

        $recentBookings = $recentBookingsQuery
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $bookingSchoolNameMap = DB::table('schools')
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })
            ->whereIn('id', $recentBookings->pluck('school_id')->filter()->all())
            ->pluck('school_name', 'id')
            ->toArray();

        $bookingRouteNameMap = DB::table('routes')
            ->whereIn('id', $recentBookings->pluck('route_id')->filter()->all())
            ->pluck('name', 'id')
            ->toArray();

        $recentEmergenciesQuery = Emergency::query()
            ->with(['driver', 'vehicle'])
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            });

        if (! $isAdminUser && $userId) {
            $recentEmergenciesQuery->where('user_id', $userId);
        }

        $recentEmergencies = $recentEmergenciesQuery
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin_layout.admin_home', compact(
            'stats',
            'school',
            'isAdminUser',
            'recentBookings',
            'bookingSchoolNameMap',
            'bookingRouteNameMap',
            'recentEmergencies',
        ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $user = User::findOrFail($id);
        $roles = Role::query()->notDeleted()->get();
        return view('admin_profile.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $user = User::findOrFail($id);

        $validator = \Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'mobile' => 'sometimes|required|digits:10',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role_id' => 'sometimes|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }

            // Store new photo
            $photoName = time() . '_' . $request->file('photo')->getClientOriginalName();
            $photoPath = $request->file('photo')->storeAs('profile_pictures', $photoName, 'public');
            $user->photo = $photoPath;
        }

        $user->first_name = $request->input('first_name', $user->first_name);
        $user->last_name = $request->input('last_name', $user->last_name);
        $user->mobile = $request->input('mobile', $user->mobile);
        $user->email = $request->input('email', $user->email);
        $user->role_id = $request->input('role_id', $user->role_id);

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json(['success' => true, 'message' => 'User updated Successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Display the user profile page.
     *
     * @return \Illuminate\Http\Response
     */
    public function profile(Request $request, ?string $schoolSlug = null)
    {
        return view('admin_profile.index');
    }

    /**
     * Update the user's profile photo.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePhoto(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = \Validator::make($request->all(), [
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                    Storage::disk('public')->delete($user->photo);
                }

                // Store new photo
                $photoName = time() . '_' . $request->file('photo')->getClientOriginalName();
                $photoPath = $request->file('photo')->storeAs('profile_pictures', $photoName, 'public');
                
                $user->photo = $photoPath;
                $user->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Profile photo updated Successfully.'
                ]);
            }

            // If no new photo is uploaded, return success with existing photo
            return response()->json([
                'success' => true,
                'message' => 'Profile photo remains unchanged.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile photo. Please try again.'
            ], 500);
        }
    }
}

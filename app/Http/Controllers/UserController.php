<?php

namespace App\Http\Controllers;

use App\Models\AccountInformationModel;
use App\Models\AddressAccountInformationModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use App\Models\ProductModel;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Role;
use App\Helpers\IdEncoder;

class UserController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $users = User::select(['id', 'first_name', 'last_name', 'mobile', 'email', 'photo'])->where('deleted', 0);
            return DataTables::of($users)
                ->addColumn('actions', function($row) {
                    return view('components.actions', compact('row'))->render();
                })
                ->make(true);
        }

        return view('users.index');
    }

    public function trash()
    {
        return view('users.trash');
    }

    public function userlist(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);
        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $users = User::getuserdata($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = User::where('deleted', 0)->count();
        $totalRecordwithFilter = User::getuserdataTotal($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);

        $data = [];
        foreach ($users as $key => $values) {
            $data[$key]['id'] = $values->id;
            $data[$key]['first_name'] = $values->first_name ?? '-';
            $data[$key]['last_name'] = $values->last_name ?? '-';
            $data[$key]['mobile'] = $values->mobile ?? '-';
            $data[$key]['email'] = $values->email ?? '-';
            $data[$key]['photo'] = $values->photo ?: $this->defaultUserPhotoPath();
            $data[$key]['status'] = $values->status;
        }

        $output = [
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data" => $data
        ];

        return response()->json($output);
    }

    public function deletedUserList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = (int) $request->input('iDisplayStart', 0);
        $rowperpage = (int) $request->input('iDisplayLength', 25);
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);
        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = trim((string) $request->input('sSearch'));

        $allowedColumns = ['id', 'first_name', 'last_name', 'mobile', 'email', 'status'];
        $resolvedColumnName = in_array($columnName, $allowedColumns, true) ? $columnName : 'id';
        $resolvedSortOrder = in_array($columnSortOrder, ['asc', 'desc'], true) ? $columnSortOrder : 'desc';

        $query = User::query()
            ->select(['id', 'first_name', 'last_name', 'mobile', 'email', 'photo', 'status'])
            ->where('deleted', 1);

        if ($searchValue !== '') {
            $query->where(function ($innerQuery) use ($searchValue) {
                $innerQuery->where('first_name', 'like', '%' . $searchValue . '%')
                    ->orWhere('last_name', 'like', '%' . $searchValue . '%')
                    ->orWhere('mobile', 'like', '%' . $searchValue . '%')
                    ->orWhere('email', 'like', '%' . $searchValue . '%')
                    ->orWhere('status', 'like', '%' . $searchValue . '%');
            });
        }

        $totalRecords = User::where('deleted', 1)->count();
        $totalRecordwithFilter = (clone $query)->count();
        $users = $query
            ->orderBy($resolvedColumnName, $resolvedSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        $data = [];
        foreach ($users as $key => $values) {
            $data[$key]['id'] = $values->id;
            $data[$key]['first_name'] = $values->first_name ?? '-';
            $data[$key]['last_name'] = $values->last_name ?? '-';
            $data[$key]['mobile'] = $values->mobile ?? '-';
            $data[$key]['email'] = $values->email ?? '-';
            $data[$key]['photo'] = $values->photo ?: $this->defaultUserPhotoPath();
            $data[$key]['status'] = $values->status;
        }

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecordwithFilter,
            'data' => $data,
        ]);
    }

    public function create()
    {
        $roles = Role::query()->notDeleted()->get();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'mobile' => 'required|digits:10',
            'email' => 'required|email|unique:users',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => 'required|string|min:8',
        ]);

        $photoPath = $this->defaultUserPhotoPath();
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photos', 'public');
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'mobile' => $request->mobile,
            'photo' => $photoPath,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        // if ($request->hasFile('photo')) {
        //     $data['photo'] = $request->file('photo')->store('photos', 'public');
        // }

        // // User::create($data);
        // $user = User::create([
        //     // 'name' => $fullName,
        //     'first_name' => $request->first_name,
        //     'last_name' => $request->last_name,
        //     'mobile' => $request->mobile,
        //     'photo' => $request->photo,
        //     'email' => $request->email,
        //     'password' => Hash::make($request->password),
        // ]);

        return response()->json(['success' => true, 'message' => 'User registered Successfully.']);
    }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::query()->notDeleted()->get();
        return view('users.edit', compact('user', 'roles'));
    }

    public function apiUpdate(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'mobile' => 'required|digits:10',
            'email' => 'required|email|unique:users,email,' . $id,
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = User::findOrFail($id);
        $data = $request->all();

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $data['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $user->update($data);

        return response()->json(['success' => true, 'message' => 'User updated Successfully.']);
    }


    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['success' => true, 'message' => 'User deleted Successfully.']);
    }

    public function restore($id)
    {
        $user = User::find($id);
        if ($user) {
            $this->restoreUserWithRelatedData($user);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json(['success' => true, 'message' => 'User restored Successfully.']);
            }

            return redirect()->route('users.index')->with('success', 'User restored Successfully.');
        }

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        return redirect()->route('users.index')->with('error', 'User not found.');
    }

    private function restoreUserWithRelatedData(User $user): void
    {
        $userId = (int) ($user->id ?? 0);
        if ($userId <= 0) {
            return;
        }

        DB::transaction(function () use ($user, $userId) {
            $schoolIds = $this->pluckIds('schools', 'id', 'user_id', $userId);
            $parentIds = array_values(array_unique(array_merge(
                $this->pluckIds('parents', 'id', 'user_id', $userId),
                $this->pluckIds('parents', 'id', 'login_user_id', $userId)
            )));
            $driverIds = array_values(array_unique(array_merge(
                $this->pluckIds('drivers', 'id', 'user_id', $userId),
                $this->pluckIds('drivers', 'id', 'login_user_id', $userId)
            )));
            $routeIds = $this->pluckIds('routes', 'id', 'user_id', $userId);

            $childIds = $this->pluckIds('children', 'id', 'user_id', $userId);
            if (!empty($parentIds)) {
                $childIds = array_values(array_unique(array_merge(
                    $childIds,
                    $this->pluckIdsWhereIn('children', 'id', 'parent_id', $parentIds)
                )));
            }
            if (!empty($schoolIds)) {
                $childIds = array_values(array_unique(array_merge(
                    $childIds,
                    $this->pluckIdsWhereIn('children', 'id', 'school_id', $schoolIds)
                )));
            }

            $this->markTableRestoredByColumn('schools', 'user_id', $userId);
            $this->markTableRestoredByColumn('vehicle_types', 'user_id', $userId);
            $this->markTableRestoredByColumn('vehicles', 'user_id', $userId);
            $this->markTableRestoredByColumn('drivers', 'user_id', $userId);
            $this->markTableRestoredByColumn('drivers', 'login_user_id', $userId);
            $this->markTableRestoredByColumn('routes', 'user_id', $userId);
            $this->markTableRestoredByColumn('package_details', 'user_id', $userId);
            $this->markTableRestoredByColumn('stops_pickup', 'user_id', $userId);
            $this->markTableRestoredByColumn('driver_vehicle_histories', 'user_id', $userId);
            $this->markTableRestoredByColumn('parents', 'user_id', $userId);
            $this->markTableRestoredByColumn('parents', 'login_user_id', $userId);
            $this->markTableRestoredByColumn('children', 'user_id', $userId);
            $this->markTableRestoredByColumn('ratings', 'user_id', $userId);
            $this->markTableRestoredByColumn('emergency_incidents', 'user_id', $userId);
            $this->markTableRestoredByColumn('bookings', 'user_id', $userId);
            $this->markTableRestoredByColumn('leave_requests', 'user_id', $userId);
            $this->markTableRestoredByColumn('support_requests', 'user_id', $userId);
            $this->markTableRestoredByColumn('emergency_contacts', 'user_id', $userId);
            $this->markTableRestoredByColumn('parent_profiles', 'user_id', $userId);

            $this->markTableRestoredWhereIn('parents', 'id', $parentIds);
            $this->markTableRestoredWhereIn('children', 'id', $childIds);
            $this->markTableRestoredWhereIn('children', 'parent_id', $parentIds);
            $this->markTableRestoredWhereIn('children', 'school_id', $schoolIds);
            $this->markTableRestoredWhereIn('bookings', 'school_id', $schoolIds);
            $this->markTableRestoredWhereIn('bookings', 'child_id', $childIds);
            $this->markTableRestoredWhereIn('leave_requests', 'parent_id', $parentIds);
            $this->markTableRestoredWhereIn('leave_requests', 'child_id', $childIds);
            $this->markTableRestoredWhereIn('support_requests', 'parent_id', $parentIds);
            $this->markTableRestoredWhereIn('emergency_contacts', 'parent_id', $parentIds);
            $this->markTableRestoredWhereIn('parent_profiles', 'parent_id', $parentIds);
            $this->markTableRestoredWhereIn('emergency_incidents', 'driver_id', $driverIds);
            $this->markTableRestoredWhereIn('ratings', 'driver_id', $driverIds);
            $this->markTableRestoredWhereIn('driver_vehicle_histories', 'driver_id', $driverIds);
            $this->markTableRestoredWhereIn('routes', 'driver_id', $driverIds);
            $this->markTableRestoredWhereIn('stops_pickup', 'route_id', $routeIds);

            $user->deleted = 0;
            $user->save();
        });
    }

    private function pluckIds(string $table, string $idColumn, string $filterColumn, int $value): array
    {
        if ($value <= 0 || !Schema::hasTable($table) || !Schema::hasColumn($table, $idColumn) || !Schema::hasColumn($table, $filterColumn)) {
            return [];
        }

        return DB::table($table)
            ->where($filterColumn, $value)
            ->pluck($idColumn)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
    }

    private function pluckIdsWhereIn(string $table, string $idColumn, string $filterColumn, array $values): array
    {
        $values = array_values(array_unique(array_filter(array_map('intval', $values), fn ($id) => $id > 0)));
        if (empty($values) || !Schema::hasTable($table) || !Schema::hasColumn($table, $idColumn) || !Schema::hasColumn($table, $filterColumn)) {
            return [];
        }

        return DB::table($table)
            ->whereIn($filterColumn, $values)
            ->pluck($idColumn)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
    }

    private function markTableRestoredByColumn(string $table, string $column, int $value): void
    {
        if ($value <= 0 || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        $this->markTableRestoredQuery(DB::table($table)->where($column, $value), $table);
    }

    private function markTableRestoredWhereIn(string $table, string $column, array $values): void
    {
        $values = array_values(array_unique(array_filter(array_map('intval', $values), fn ($id) => $id > 0)));
        if (empty($values) || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        $this->markTableRestoredQuery(DB::table($table)->whereIn($column, $values), $table);
    }

    private function markTableRestoredQuery($query, string $table): void
    {
        $updates = [];

        if (Schema::hasColumn($table, 'deleted')) {
            $updates['deleted'] = 0;
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $updates['deleted_at'] = null;
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $updates['updated_at'] = now();
        }

        if (!empty($updates)) {
            $query->update($updates);
        }
    }

    // public function userlogout(Request $request)
    // {
    //     Session::flush();
    //     return redirect('/signin');
    // }

    public function showUser($id)
    {
        try {
            $decodedId = IdEncoder::decode($id);
            $user = User::findOrFail($decodedId);
            $roles = Role::query()->notDeleted()->get();
            return view('users.show', compact('user', 'roles'));
        } catch (\Exception $e) {
            \Log::error('Error fetching user data', ['error' => $e->getMessage()]);
        }
    }
}

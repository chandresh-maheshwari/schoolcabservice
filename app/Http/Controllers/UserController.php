<?php

namespace App\Http\Controllers;

use App\Models\AccountInformationModel;
use App\Models\AddressAccountInformationModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            $data[$key]['photo'] = $values->photo;
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

        $data = $request->all();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('photos', 'public');
        }

        // User::create($data);
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'mobile' => $request->mobile,
            'photo' => $request->photo,
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
        $user = User::withTrashed()->find($id);
        if ($user) {
            $user->restore();
            return redirect()->route('users.index')->with('success', 'User restored Successfully.');
        }
        return redirect()->route('users.index')->with('error', 'User not found.');
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

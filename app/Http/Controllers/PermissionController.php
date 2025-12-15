<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        return view('permissions.index');
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Permission::create($request->all());

        return response()->json(['success' => true, 'message' => 'Permission created Successfully.']);
    }

    public function edit($id)
    {
        $permission = Permission::findOrFail($id);
        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $permission = Permission::findOrFail($id);
        $permission->update($request->all());

        return response()->json(['success' => true, 'message' => 'Permission updated Successfully.']);
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->deleted = 1;
        $permission->save();

        return response()->json(['success' => true, 'message' => 'Permission deleted Successfully.']);
    }

    public function list(Request $request)
    {
        $searchValue = $request->input('search.value');
        $columnName = $request->input('columns.' . $request->input('order.0.column') . '.data');
        $columnSortOrder = $request->input('order.0.dir');
        $draw = $request->input('draw');
        $row = $request->input('start');
        $rowperpage = $request->input('length');

        $totalRecords = Permission::getPermissionDataTotal($searchValue);
        $totalRecordswithFilter = $totalRecords;

        $records = Permission::getPermissionData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);

        $dataArr = [];
        foreach ($records as $record) {
            $dataArr[] = [
                "id" => $record->id,
                "name" => $record->name,
            ];
        }

        $response = [
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $dataArr,
        ];

        return response()->json($response);
    }

    public function permissionList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id', 'name'])) {
            $columnName = 'id'; 
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $permissions = Permission::getPermissionData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = Permission::count();
        $totalRecordwithFilter = Permission::getPermissionDataTotal($searchValue);

        $data = [];
        foreach ($permissions as $permission) {
            $data[] = [
                'id' => $permission->id,
                'name' => $permission->name ?? '-',
            ];
        }

        $output = [
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data" => $data
        ];

        return response()->json($output);
    }

    public function multiDelete(Request $request)       
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided.']);
        }
        Permission::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected permissions deleted Successfully.']);
    }
}

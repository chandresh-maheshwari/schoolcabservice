<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CMSCategory;

class CMSCategoryController extends Controller
{
    public function index()
    {
        return view('cms_categories.index');
    }

    public function create()
    {
        return view('cms_categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        CMSCategory::create($request->all());

        return response()->json(['success' => true, 'message' => 'Category created Successfully.']);
    }

    public function edit($id)
    {
        $category = CMSCategory::findOrFail($id);
        return view('cms_categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = CMSCategory::findOrFail($id);
        $category->update($request->all());

        return response()->json(['success' => true, 'message' => 'Category updated Successfully.']);
    }

    public function destroy($id)
    {
        $category = CMSCategory::findOrFail($id);
        $category->deleted = 1;
        $category->save();

        return response()->json(['success' => true, 'message' => 'Category deleted Successfully.']);
    }

    public function categoryList(Request $request)
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

        $categories = CMSCategory::getCategoryData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = CMSCategory::count();
        $totalRecordwithFilter = CMSCategory::getCategoryDataTotal($searchValue);

        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'id' => $category->id,
                'name' => $category->name,
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
        CMSCategory::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected categories deleted Successfully.']);
    }
} 
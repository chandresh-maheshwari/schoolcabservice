<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index');
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_link' => 'required|string|max:255',
            'category_icon' => 'required|string|max:255',
            'status' => 'required|boolean',
            'order' => 'required|integer|min:1',
        ]);

        Category::create($request->all());

        return response()->json(['success' => true, 'message' => 'Category created Successfully.']);
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_link' => 'required|string|max:255',
            'category_icon' => 'required|string|max:255',
            'status' => 'required|boolean',
            'order' => 'required|integer|min:1',
        ]);

        $category = Category::findOrFail($id);
        $category->update($request->all());

        return response()->json(['success' => true, 'message' => 'Category updated Successfully.']);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
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

        if (!in_array($columnName, ['id', 'name', 'category_link', 'category_icon', 'status', 'order'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $categories = Category::getCategoryData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = Category::count();
        $totalRecordwithFilter = Category::getCategoryDataTotal($searchValue);

        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'id' => $category->id,
                'name' => $category->name,
                'category_link' => $category->category_link,
                'category_icon' => $category->category_icon,
                'status' => $category->status,
                'order' => $category->order,
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

    public function toggleStatus($id)
    {
        $category = Category::findOrFail($id);
        $category->status = !$category->status;
        $category->save();

        return response()->json(['success' => true, 'message' => 'Category status updated Successfully.']);
    }

    // New function to get all active categories for frontend
    public function getActiveCategories()
    {
        $categories = Category::where('status', 1)
        ->where('deleted', 0)
            ->select('id', 'name', 'category_link', 'category_icon')
            ->orderBy('order', 'asc')
            ->get();
        return response()->json(['success' => true, 'categories' => $categories]);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided.']);
        }
        Category::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected categories deleted Successfully.']);
    }
} 
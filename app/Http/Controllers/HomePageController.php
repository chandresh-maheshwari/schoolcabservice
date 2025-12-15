<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HomePage;
use App\Models\CMSCategory;

class HomePageController extends Controller
{
    public function index()
    {
        return view('home_pages.index');
    }

    public function create()
    {
        $categories = CMSCategory::where('deleted', 0)->get();
        return view('home_pages.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:cms_categories,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'required|string',
        ]);

        $data = $request->all();

        $homePage = HomePage::create($data);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'homepage_' . $homePage->id . '.' . $extension;
            $image->storeAs('home_pages', $imageName, 'public');
            $data['image'] = 'home_pages/' . $imageName;
        }

        $homePage->update($data);

        return response()->json(['success' => true, 'message' => 'Home Page created Successfully.']);
    }

    public function edit($id)
    {
        $homePage = HomePage::findOrFail($id);
        $categories = CMSCategory::where('deleted', 0)->get();
        return view('home_pages.edit', compact('homePage', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:cms_categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'required|string',
        ]);

        $homePage = HomePage::findOrFail($id);
        $data = $request->all();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'homepage_' . $homePage->id . '.' . $extension;
            $image->storeAs('home_pages', $imageName, 'public');
            $data['image'] = 'home_pages/' . $imageName;
        }

        $homePage->update($data);

        return response()->json(['success' => true, 'message' => 'Home Page updated Successfully.']);
    }

    public function destroy($id)
    {
        $homePage = HomePage::findOrFail($id);
        $homePage->deleted = 1;
        $homePage->save();

        return response()->json(['success' => true, 'message' => 'Home Page deleted Successfully.']);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided.']);
        }
        HomePage::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected home pages deleted Successfully.']);
    }

    public function homePageList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id', 'title', 'category', 'image', 'description'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $homePages = HomePage::getHomePageData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = HomePage::count();
        $totalRecordwithFilter = HomePage::getHomePageDataTotal($searchValue);

        $data = [];
        foreach ($homePages as $homePage) {
            $data[] = [
                'id' => $homePage->id,
                'title' => $homePage->title ?? '-',
                'category' => $homePage->category ?? '-',
                'image' => $homePage->image ?? '-',
                'description' => $homePage->description ?? '-',
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
} 
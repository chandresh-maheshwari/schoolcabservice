<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuthorSocial;

class AuthorSocialController extends Controller
{
    public function index()
    {
        return view('author-socials.index');
    }

    public function create()
    {
        return view('author-socials.create');
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'social_link' => 'required|string|max:255',
            'social_icon' => 'required|string|max:255',
            'status' => 'required|boolean',
        ]);

        AuthorSocial::create($request->all());

        return response()->json(['success' => true, 'message' => 'Author Social created Successfully.']);
    }

    public function edit($id)
    {
        $social = AuthorSocial::findOrFail($id);
        return view('author-socials.edit', compact('social'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'social_link' => 'required|string|max:255',
            'social_icon' => 'required|string|max:255',
            'status' => 'required|boolean',
        ]);

        $authorSocial = AuthorSocial::findOrFail($id);
        $authorSocial->update($request->all());

        return response()->json(['success' => true, 'message' => 'Author Social updated Successfully.']);
    }

    public function destroy($id)
    {
        $authorSocial = AuthorSocial::findOrFail($id);
        $authorSocial->deleted = 1;
        $authorSocial-> save();

        return response()->json(['success' => true, 'message' => 'Author Social deleted Successfully.']);
    }

    public function authorSocialList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id', 'name', 'social_link', 'social_icon', 'status'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $authorSocials = AuthorSocial::getAuthorSocialData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = AuthorSocial::count();
        $totalRecordwithFilter = AuthorSocial::getAuthorSocialDataTotal($searchValue);

        $data = [];
        foreach ($authorSocials as $authorSocial) {
            $data[] = [
                'id' => $authorSocial->id,
                'name' => $authorSocial->name,
                'social_link' => $authorSocial->social_link,
                'social_icon' => $authorSocial->social_icon,
                'status' => $authorSocial->status,
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
        $authorSocial = AuthorSocial::findOrFail($id);
        $authorSocial->status = !$authorSocial->status;
        $authorSocial->save();

        return response()->json(['success' => true, 'message' => 'Author Social status updated Successfully.']);
    }

    public function getAllAuthorSocials()
    {
        $authorSocials = AuthorSocial::where('deleted', 0)->get(['id', 'name', 'social_link', 'social_icon', 'status']);
        return response()->json(['data' => $authorSocials]);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided.']);
        }
        AuthorSocial::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected author socials deleted Successfully.']);
    }
} 
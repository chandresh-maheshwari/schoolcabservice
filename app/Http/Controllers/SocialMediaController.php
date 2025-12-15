<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SocialMediaModel;

class SocialMediaController extends Controller
{
    public function index()
    {
        return view('socials-media.index');
    }

    public function create()
    {
        return view('socials-media.create');
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'social_link' => 'required|string|max:255',
            'social_icon' => 'required|string|max:255',
            'status' => 'required|boolean',
        ]);

        SocialMediaModel::create($request->all());

        return response()->json(['success' => true, 'message' => 'Author Social created Successfully.']);
    }

    public function edit($id)
    {
        $social = SocialMediaModel::findOrFail($id);
        return view('socials-media.edit', compact('social'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'social_link' => 'required|string|max:255',
            'social_icon' => 'required|string|max:255',
            'status' => 'required|boolean',
        ]);

        $socialMedia = SocialMediaModel::findOrFail($id);
        $socialMedia->update($request->all());

        return response()->json(['success' => true, 'message' => 'Author Social updated Successfully.']);
    }

    public function destroy($id)
    {
        $socialMedia = SocialMediaModel::findOrFail($id);
        $socialMedia->deleted = 1;
        $socialMedia-> save();

        return response()->json(['success' => true, 'message' => 'Author Social deleted Successfully.']);
    }

    public function socialMediaList(Request $request)
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

        $socialMedias = SocialMediaModel::getSocialMediaData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = SocialMediaModel::count();
        $totalRecordwithFilter = SocialMediaModel::getSocialMediaDataTotal($searchValue);

        $data = [];
        foreach ($socialMedias as $socialMedia) {
            $data[] = [
                'id' => $socialMedia->id,
                'name' => $socialMedia->name,
                'social_link' => $socialMedia->social_link,
                'social_icon' => $socialMedia->social_icon,
                'status' => $socialMedia->status,
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
        $socialMedia = SocialMediaModel::findOrFail($id);
        $socialMedia->status = !$socialMedia->status;
        $socialMedia->save();

        return response()->json(['success' => true, 'message' => 'Author Social status updated Successfully.']);
    }

    public function getAllAuthorSocials()
    {
        $socialMedias = SocialMediaModel::where('deleted', 0)
        ->where('status', 1)
        ->get();
        // ->count();
        // dd($socialMedias);
        return response()->json(['data' => $socialMedias]);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided.']);
        }
        SocialMediaModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected author socials deleted Successfully.']);
    }
} 
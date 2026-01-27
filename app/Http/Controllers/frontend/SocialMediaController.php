<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\SocialMediaSection;
use Illuminate\Http\Request;

class SocialMediaController extends Controller
{
    /**
     * Display Social Media Section listing page.
     * created by ns
     */
    public function index()
    {
        return view('cms.social_media_section.index');
    }

    /**
     * Display Social Media Section create form.
     * created by ns
     */
    public function create()
    {
        return view('cms.social_media_section.create');
    }

    /**
     * Store Social Media Section data.
     * created by ns
     */

     public function store(Request $request)
    {
        $request->validate([
            'social_name' => 'required|string|max:255',
            'social_link' => 'required|string|max:255',
            'social_icon' => 'required|string|max:255',
        ]);

        SocialMediaSection::create($request->all());

        return response()->json(['success' => true, 'message' => 'Author Social created Successfully.']);
    }

    /**
     * Edit Social Media Section data.
     * created by ns
     */
    public function edit($id)
    {
        $social = SocialMediaSection::findOrFail($id);
        return view('cms.social_media_section.edit', compact('social'));
    }

    /**
     * Update Social Media Section data.
     * created by ns
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'social_name' => 'required|string|max:255',
            'social_link' => 'required|string|max:255',
            'social_icon' => 'required|string|max:255',
        ]);

        $socialMedia = SocialMediaSection::findOrFail($id);
        $socialMedia->update($request->all());

        return response()->json(['success' => true, 'message' => 'Author Social updated Successfully.']);
    }

    /**
     * Delete Social Media Section data.
     * created by ns
     */
    public function destroy($id)
    {
        $socialMedia = SocialMediaSection::findOrFail($id);
        $socialMedia->deleted = 1;
        $socialMedia->save();

        return response()->json([
            'success' => true,
            'message' => 'FAQ Section deleted Successfully.',
        ]);
    }

    /**
     * Toggle Social Media Section status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $socialMedia = SocialMediaSection::findOrFail($id);
        $socialMedia->status = $socialMedia->status == 1 ? 0 : 1;
        $socialMedia->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active Social Media Section count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = SocialMediaSection::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Get Social Media Section listing data.
     * created by ns
     */
     public function socialMediaList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id', 'social_name', 'social_link', 'social_icon', 'status'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $socialMedias = SocialMediaSection::getSocialMediaData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = SocialMediaSection::count();
        $totalRecordwithFilter = SocialMediaSection::getSocialMediaDataTotal($searchValue);

        $data = [];
        foreach ($socialMedias as $socialMedia) {
            $data[] = [
                'id' => $socialMedia->id,
                'social_name' => $socialMedia->social_name,
                'social_link' => $socialMedia->social_link,
                'social_icon' => $socialMedia->social_icon,
                  'status'      => $socialMedia->status,
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

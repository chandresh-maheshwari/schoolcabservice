<?php
namespace App\Http\Controllers\Frontend;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\AboutSection;
use Illuminate\Http\Request;

class AboutSectionController extends Controller
{
    /**
     * Display about section listing page.
     * created by ns
     */
    public function index()
    {
        return view('cms.about_section.index');
    }

    /**
     * Display about section create form.
     * created by ns
     */
    public function create()
    {
        return view('cms.about_section.create');
    }

    /**
     * Store about section data.
     * created by ns
     */
    public function store(Request $request)
    {

        $request->validate([
            'title'       => 'required|string|max:255',
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'button_name' => 'nullable|string|max:20',
            'button_link' => 'required|url|max:255',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        $aboutSection = AboutSection::create([
            'title'       => $request->title,
            'name'        => $request->name,
            'description' => $request->description,
            'button_name' => $request->button_name,
            'button_link' => $request->button_link,
            'status'      => 0,
            'deleted'     => 0,
        ]);

        $aboutImage = $request->hasFile('image')
            ? ImageHelper::upload($request, 'image', 'aboutSection', $aboutSection->id, [500, 333])
            : null;

        $aboutSection->update([
            'image' => $aboutImage,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'About Section added successfully',
        ]);
    }

    /**
     * Display about section edit form.
     * created by ns
     */
    public function edit($id)
    {
        $aboutSection = AboutSection::findOrFail($id);
        return view('cms.about_section.edit', compact('aboutSection'));
    }

    /**
     * Update about section data.
     *  created by ns
     */
    public function update(Request $request, $id)
    {
        $aboutSection = AboutSection::findOrFail($id);
        $request->validate([
            'title'       => 'required|string|max:255',
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'button_name' => 'nullable|string|max:20',
            'button_link' => 'required|url|max:255',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);
        $aboutSection->update([
            'title'       => $request->title,
            'name'        => $request->name,
            'description' => $request->description,
            'button_name' => $request->button_name,
            'button_link' => $request->button_link,
        ]);
         if ($request->hasFile('image')) {
    if ($aboutSection->image && file_exists(public_path('storage/' . $aboutSection->image))) {
        unlink(public_path('storage/' . $aboutSection->image));
    }

    $newAboutImage = ImageHelper::upload(
        $request,
        'image',
        'aboutSection',
        $aboutSection->id,
        [500, 333]
    );

    $aboutSection->image = $newAboutImage;
    $aboutSection->save();
}
        return response()->json([
            'success' => true,
            'message' => 'About Section updated successfully',
        ]);
    }
    /**
     * Delete about section data.
     * created by ns
     */
     public function destroy($id)
    {
        $aboutSection = AboutSection::findOrFail($id);
        $aboutSection->deleted = 1;
        $aboutSection->save();

        return response()->json([
            'success' => true,
            'message' => 'About Section deleted Successfully.',
        ]);
    }

    /**
     * Toggle about section active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $aboutSection = AboutSection::findOrFail($id);
        $aboutSection->status = $aboutSection->status == 1 ? 0 : 1;
        $aboutSection->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active about section count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = AboutSection::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

/**
     * Delete about section image.
     * created by ns
     */
    public function aboutImage($id)
    {
        $aboutSection = AboutSection::findOrFail($id);
        if ($aboutSection->image) {
            $imagePath = public_path($aboutSection->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $aboutSection->image = null;
            $aboutSection->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }
    /**
     * Fetch about section list for DataTable.
     * created by ns
     */
    public function aboutSectionList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, 'id');

        // Allowed columns (AboutSection fields)
        $allowedColumns = [
            'id',
            'title',
            'name',
            'description',
            'button_name',
            'button_link',
            'status',
            'created_at',
            'updated_at',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';

        $columnSortOrder = in_array(
            $request->input('sSortDir_0'),
            ['asc', 'desc']
        ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        // Data
        $aboutSections = AboutSection::getAboutSectionData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $row,
            $rowperpage
        );

        // Counts
        $totalRecords          = AboutSection::where('deleted', 0)->count();
        $totalRecordwithFilter = AboutSection::getAboutSectionDataTotal($searchValue);

        $data = [];

        foreach ($aboutSections as $about) {
            $data[] = [
                'id'          => (string) $about->id,
                'title'       => $about->title,
                'name'        => $about->name,
                'description' => $about->description,
                'image'       => $about->image,
                'button_name' => $about->button_name,
                'button_link' => $about->button_link,
                'status'      => $about->status,

            ];
        }

        return response()->json([
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ]);
    }
}

<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\HeroModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class HeroController extends Controller
{
    public function index()
    {
        return view('hero.index');
    }

    public function create()
    {
        return view('hero.create');
    }

    public function store(Request $request)
    {
        // $request->validate([
        //     'title' => 'required|string|max:255',
        //     'image' => 'required|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        //     'description' => 'required|string',
        //     'button_title_1' => 'required|string|max:255',
        //     'button_title_2' => 'required|string|max:255',
        // ]);

        // Enforce single hero record
        // if (HeroModel::where('deleted', 0)->exists()) {
        //     return response()->json(['success' => false, 'message' => 'Hero already exists. Please edit the existing entry.'], 422);
        // }

        $data = $request->all();
        $hero = HeroModel::create($data);

        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'hero_' . $hero->id . '.' . $extension;
            $tmpPath   = $image->getRealPath();
            $destDir   = public_path('storage/hero');
            $destPath  = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (! file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size    = [636, 424];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            // dd($success);
            if (! $success) {
                // $hero->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.',
                ]);
            }

            $data['image'] = 'storage/hero/' . $imageName;
        }

        $hero->update($data);
        return response()->json(['success' => true, 'message' => 'Hero created Successfully.']);
    }

    public function edit($id)
    {
        $hero = HeroModel::findOrFail($id);
        return view('hero.edit', compact('hero'));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        $hero = HeroModel::findOrFail($id);
        $data = $request->except('image');

        // Handle image upload if a new image is provided
        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'hero_' . $hero->id . '.' . $extension;
            $tmpPath   = $image->getRealPath();
            $destDir   = public_path('storage/hero');
            $destPath  = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (! file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size    = [636, 424];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (! $success) {
                // $hero->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.',
                ]);
            }

            $data['image'] = 'storage/hero/' . $imageName;
        }

        $hero->update($data);

        return response()->json(['success' => true, 'message' => 'Hero updated Successfully.']);
    }

    public function destroy($id)
    {
        $hero          = HeroModel::findOrFail($id);
        $hero->deleted = 1;
        $hero->save();

        return response()->json(['success' => true, 'message' => 'Hero deleted Successfully.']);
    }

    public function heroList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = $request->input('iDisplayStart');
        $rowperpage  = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        if (! in_array($columnName, ['id', 'title', 'image', 'description', 'status'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue     = $request->input('sSearch');

        $heroDetails           = HeroModel::getHeroData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords          = HeroModel::count();
        $totalRecordwithFilter = HeroModel::getHeroDataTotal($searchValue);

        $data = [];
        foreach ($heroDetails as $hero) {
            // dd($hero->image);
            $imagePath = public_path($hero->image);

            if (! file_exists($imagePath) || empty($hero->image)) {
                $heroImage = 'images/Default.jpg';
            } else {
                $heroImage = $hero->image;
            }
            $data[] = [
                'id'          => $hero->id,
                'title'       => $hero->title ?? '-',
                // 'image' => $hero->image ?? '-',
                'image'       => $heroImage ?? '-',
                'description' => $hero->description ?? '-',
                'status'      => $hero->status,
            ];
        }

        $output = [
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ];

        return response()->json($output);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided.']);
        }
        HeroModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected categories deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $hero         = HeroModel::findOrFail($id);
        $hero->status = ! $hero->status;
        $hero->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function deleteImage($id)
    {
        $hero = HeroModel::findOrFail($id);
        if ($hero->image) {
            $imagePath = public_path($hero->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $hero->image = null;
            $hero->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    /**
     * Get clients for frontend display
     */
    public function getClientsForFrontend()
    {
        try {
            $clients = HeroModel::where('deleted', 0)
                ->select('id', 'title', 'image', 'description', 'button_title_1', 'button_title_2')
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $clients,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching clients: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get hero data for frontend display with stats
     */
    public function getHeroForFrontend()
    {
        try {
            $heroData = HeroModel::where('deleted', 0)
                ->where('status', 1)
                ->get()
                ->map(function ($hero) {
                    // Remove any leading 'storage/hero/' from the stored image
                    $filename = str_replace('storage/hero/', '', $hero->image);

                    // Full absolute path
                    $imagePath = public_path('storage/hero/' . $filename);

                    if ($hero->image && File::exists($imagePath)) {
                        // URL for frontend
                        $hero->image = asset('storage/hero/' . $filename);
                    } else {
                        // Default image
                        $hero->image = asset('images/Default.jpg');
                    }

                    return $hero;
                });

            return response()->json([
                'success' => true,
                'data'    => $heroData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching hero data: ' . $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Test hero API endpoint
     */
    // public function testHeroApi()
    // {
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Hero API is working!',
    //         'timestamp' => now(),
    //         'test_data' => [
    //             'title' => 'Test Hero Title',
    //             'description' => 'This is a test description',
    //             'button1' => 'Test Button 1',
    //             'button2' => 'Test Button 2'
    //         ]
    //     ]);
    // }

    /**
     * Render the hero section dynamically for the frontend Blade template
     */
    public function showHeroSection()
    {
        $hero = HeroModel::where('deleted', 0)->first();
        return view('templates.hero', compact('hero'));
    }

    // FRONT: Render hero page using cherrypik front layout
    // public function front()
    // {
    //     $hero = HeroModel::where('deleted', 0)->first();
    //     return view('hero.front', compact('hero'));
    // }

    public function getActiveCount()
    {
        $activeCount = HeroModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }
}

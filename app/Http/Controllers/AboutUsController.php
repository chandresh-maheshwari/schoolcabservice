<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\AboutUsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AboutUsController extends Controller
{

    public function index()
    {
        return view('aboutUs.index');
    }

    public function create()
    {
        return view('aboutUs.create');
    }

    public function store(Request $request)
    {
        // $request->validate([
        //     'image' => 'required|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        // ]);
        //  $request->validate([
        //     'title' => 'required|string|max:255',
        //     'image' => 'required|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        //     'description' => 'required|string',
        //     'button_title_1' => 'required|string|max:255',
        //     'button_title_2' => 'required|string|max:255',
        // ]);
        // if (AboutUsModel::where('deleted', 0)->exists()) {
        //     return response()->json(['success' => false, 'message' => 'About Us Data is Already exists. Please edit the existing entry.'], 422);
        // }
        // dd($request->all());
        $data = $request->all();

        $aboutUs = AboutUsModel::create($data);

        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'image_aboutUs_' . $aboutUs->id . '.' . $extension;
            // $image->storeAs('about_us', $imageName, 'public');
            $tmpPath  = $image->getRealPath();
            $destDir  = public_path('storage/about_us');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (! file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size    = [271, 271];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (! $success) {
                // $aboutUs->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.',
                ]);
            }
            $data['image'] = 'storage/about_us/' . $imageName;
        }

        if ($request->hasFile('profile_image')) {
            $image     = $request->file('profile_image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'profile_aboutUs_' . $aboutUs->id . '.' . $extension;
            // $image->storeAs('about_us', $imageName, 'public');
            $tmpPath  = $image->getRealPath();
            $destDir  = public_path('storage/about_us');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (! file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size    = [60, 60];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (! $success) {
                // $aboutUs->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.',
                ]);
            }
            // $data['image'] = 'storage/about_us/' . $imageName;
            $data['profile_image'] = 'storage/about_us/' . $imageName;
        }

        $aboutUs->update($data);

        return response()->json(['success' => true, 'message' => 'About Us page created Successfully.']);
    }

    public function edit($id)
    {
        $aboutUs = AboutUsModel::findOrFail($id);
        return view('aboutUs.edit', compact('aboutUs'));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        $aboutUs = AboutUsModel::findOrFail($id);
        $data    = $request->except('image');

        // Handle image upload if a new image is provided
        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'image_aboutUs_' . $aboutUs->id . '.' . $extension;
            // $image->storeAs('about_us', $imageName, 'public');
            $tmpPath  = $image->getRealPath();
            $destDir  = public_path('storage/about_us');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (! file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size    = [271, 271];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (! $success) {
                // $aboutUs->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.',
                ]);
            }
            // $data['image'] = 'storage/about_us/' . $imageName;
            $data['image'] = 'storage/about_us/' . $imageName;
        }

        if ($request->hasFile('profile_image')) {
            $profile_image = $request->file('profile_image');
            $extension     = $profile_image->getClientOriginalExtension();
            $imageName     = 'profile_aboutUs_' . $aboutUs->id . '.' . $extension;
            // $profile_image->storeAs('about_us', $imageName, 'public');
            $tmpPath  = $profile_image->getRealPath();
            $destDir  = public_path('storage/about_us');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (! file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size    = [60, 60];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (! $success) {
                // $aboutUs->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.',
                ]);
            }
            // $data['image'] = 'storage/about_us/' . $imageName;
            // $data['profile_image'] = 'storage/about_us/' . $imageName;
            $data['profile_image'] = 'storage/about_us/' . $imageName;
        }

        $aboutUs->update($data);

        return response()->json(['success' => true, 'message' => 'About Us updated Successfully.']);
    }

    public function destroy($id)
    {
        $aboutUs          = AboutUsModel::findOrFail($id);
        $aboutUs->deleted = 1;
        $aboutUs->save();

        return response()->json(['success' => true, 'message' => 'About Us deleted Successfully.']);
    }

    public function aboutList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = $request->input('iDisplayStart');
        $rowperpage  = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);
        // Only allow sorting by real columns
        $allowedColumns = [
            'id',
            'title',
            'image',
            'description',
            'profile_name',
            'profile_position',
            'profile_image',
            'contact_number',
            'status',
        ];
        if (! in_array($columnName, $allowedColumns)) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue     = $request->input('sSearch');

        $aboutUs               = AboutUsModel::getAboutUsData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords          = AboutUsModel::count();
        $totalRecordwithFilter = AboutUsModel::getAboutUsDataTotal($searchValue);

        $data = [];
        foreach ($aboutUs as $aboutUs) {
            $imagePath = public_path($aboutUs->image);
            if (! file_exists($imagePath) || empty($aboutUs->image)) {
                $aboutUsImage = 'images/Default.jpg';
            } else {
                $aboutUsImage = $aboutUs->image;
            }
            $data[] = [
                'id'               => $aboutUs->id,
                'title'            => $aboutUs->title ?? '-',
                // 'aboutUs' => $aboutUs->aboutUs ?? '-',
                // 'image' => $aboutUs->image ?? '-',
                'image'            => $aboutUsImage ?? '-',
                'description'      => $aboutUs->description ?? '-',
                'profile_name'     => $aboutUs->profile_name ?? '-',
                'profile_position' => $aboutUs->profile_position ?? '-',
                'profile_image'    => $aboutUs->profile_image ?? '-',
                'contact_number'   => $aboutUs->contact_number ?? '-',
                'status'           => $aboutUs->status,
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
        AboutUsModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected categories deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $aboutUs         = AboutUsModel::findOrFail($id);
        $aboutUs->status = ! $aboutUs->status;
        $aboutUs->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function deleteImage($id)
    {
        $aboutUs = AboutUsModel::findOrFail($id);
        if ($aboutUs->image) {
            $imagePath = public_path($aboutUs->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $aboutUs->image = null;
            $aboutUs->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    public function deleteProfileImage($id)
    {
        $aboutUs = AboutUsModel::findOrFail($id);
        if ($aboutUs->profile_image) {
            $imagePath = public_path($aboutUs->profile_image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $aboutUs->profile_image = null;
            $aboutUs->save();
            return response()->json(['success' => true, 'message' => 'Profile image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No profile image to delete.'], 404);
    }

    public function getActiveCount()
    {
        $activeCount = AboutUsModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }

    /**
     * Get About Us data for frontend
     */
    // public function getAboutUsForFrontend()
    // {
    //     try {
    //         $about = AboutUsModel::where('deleted', 0)
    //             ->where('status', 1)
    //             ->orderBy('id', 'desc')
    //             ->first();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'About Us retrieved successfully',
    //             'data' => $about,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error retrieving about us: ' . $e->getMessage(),
    //             'data' => null,
    //         ], 500);
    //     }
    // }

    public function getAboutUsForFrontend()
    {
        try {
            $about = AboutUsModel::where('deleted', 0)
                ->where('status', 1)
                ->orderBy('id', 'desc')
                ->first();

            if ($about) {
                // Folder path
                $folderPath = public_path('storage/about_us/');
                // Default image
                $defaultImage = asset('images/Default.jpg');

                // --- For image field ---
                if (! empty($about->image)) {
                    $imageName     = basename($about->image);
                    $fullImagePath = $folderPath . $imageName;

                    if (file_exists($fullImagePath)) {
                        $about->image = asset('storage/about_us/' . $imageName);
                    } else {
                        $about->image = $defaultImage;
                    }
                } else {
                    $about->image = $defaultImage;
                }

                // --- For profile_image field ---
                if (! empty($about->profile_image)) {
                    $profileImageName     = basename($about->profile_image);
                    $fullProfileImagePath = $folderPath . $profileImageName;

                    if (file_exists($fullProfileImagePath)) {
                        $about->profile_image = asset('storage/about_us/' . $profileImageName);
                    } else {
                        $about->profile_image = $defaultImage;
                    }
                } else {
                    $about->profile_image = $defaultImage;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'About Us retrieved successfully',
                'data'    => $about,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving about us: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Get clients for frontend display
     */
    // public function getClientsForFrontend()
    // {
    //     try {
    //         $clients = AboutUsModel::where('deleted', 0)
    //             ->select('id', 'title', 'image', 'description', 'button_title_1', 'button_title_2')
    //             ->orderBy('id', 'asc')
    //             ->get();

    //         return response()->json([
    //             'success' => true,
    //             'data' => $clients
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error fetching clients: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
}

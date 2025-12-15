<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\FeatureModel;
use Illuminate\Http\Request;

class FeaturesController extends Controller
{
     public function index()
    {
        return view('features.index');
    }

    public function create()
    {
        return view('features.create');
    }

    public function store(Request $request)
    {
         $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'description' => 'required|string',
        ]);

        // Enforce single feature record
        // if (FeatureModel::where('deleted', 0)->exists()) {
        //     return response()->json(['success' => false, 'message' => 'Feature Already Exists. Please Edit The Existing Entry.'], 422);
        // }

        // $data = $request->except('image');
        $data = $request->all();

        $feature = FeatureModel::create($data);
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'feature_' . $feature->id . '.' . $extension;
            // $image->storeAs('feature', $imageName, 'public');
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/feature');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [636, 424];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $feature->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            $data['image'] = 'storage/feature/' . $imageName;
            // dd($data['image']);
        }

        $feature->update($data);
        return response()->json(['success' => true, 'message' => 'feature created Successfully.']);
    }

    public function edit($id)
    {
        $feature = FeatureModel::findOrFail($id);
        return view('features.edit', compact('feature'));
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'description' => 'required|string',
        ]);

        $feature = FeatureModel::findOrFail($id);
        $data = $request->except('image');

        // Handle image upload if a new image is provided
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'feature_' . $feature->id . '.' . $extension;
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/feature');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [636, 424];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $feature->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }

            $data['image'] = 'storage/feature/' . $imageName;
        }


        $feature->update($data);

        return response()->json(['success' => true, 'message' => 'feature updated Successfully.']);
    }

    public function destroy($id)
    {
        $feature = FeatureModel::findOrFail($id);
        $feature->deleted = 1;
        $feature->save();

        return response()->json(['success' => true, 'message' => 'feature deleted Successfully.']);
    }

    public function featuresList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id', 'title', 'image', 'description', 'status'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $featureDetails = FeatureModel::getFeatureData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = FeatureModel::count();
        $totalRecordwithFilter = FeatureModel::getFeatureDataTotal($searchValue);

        $data = [];
        foreach ($featureDetails as $feature) {
            $imagePath = public_path($feature->image);
            if (!file_exists($imagePath) || empty($feature->image)) {
                $featureImage = 'images/Default.jpg';
            } else {
                $featureImage = $feature->image;
            }
            $data[] = [
                'id' => $feature->id,
                'title' => $feature->title ?? '-',
                'image' => $featureImage ?? '-',
                // 'image' => $feature->image ?? '-',
                'description' => $feature->description ?? '-',
                'status' => $feature->status ?? '-',
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
        FeatureModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected deleted Successfully.']);
    }

     public function toggleStatus($id)
    {
        $feature = FeatureModel::findOrFail($id);
        $feature->status = !$feature->status;
        $feature->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function deleteImage($id)
    {
        $feature = FeatureModel::findOrFail($id);
        if ($feature->image) {
            $imagePath = public_path($feature->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $feature->image = null;
            $feature->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    public function getActiveCount()
    {
        $activeCount = FeatureModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }

        /**
         * Get alternatives for frontend
         */
        public function getFeaturesForFrontend()
        {
            try {
                // dd("asd");
                $items = FeatureModel::where('deleted', 0)
                    ->where('status', 1)
                    ->orderBy('id', 'asc')
                    ->get(['id', 'title', 'image', 'description', 'highlight_number_1', 'hightlight_text_1', 'highlight_icone_1',
                    'highlight_number_2', 'hightlight_text_2', 'highlight_icone_2', 'highlight_number_3', 'hightlight_text_3',
                    'highlight_icone_3', 'status',]);

                return response()->json([
                    'success' => true,
                    'message' => 'Feature retrieved successfully',
                    'data' => $items,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error retrieving Feature: ' . $e->getMessage(),
                    'data' => [],
                ], 500);
            }
        }
}

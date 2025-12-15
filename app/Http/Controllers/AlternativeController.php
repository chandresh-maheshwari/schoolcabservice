<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\AlternativeModel;
use App\Models\ServiceModel;
use Illuminate\Http\Request;
    use Illuminate\Support\Facades\File;

class AlternativeController extends Controller
{
    public function index()
    {
        return view('alternative.index');
    }

    public function create()
    {
        return view('alternative.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $data = $request->all();
        $alternative = AlternativeModel::create($data);
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'alternative_' . $alternative->id . '.' . $extension;
            // $image->storeAs('alternative', $imageName, 'public');
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/alternative');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [254, 160];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $alternative->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least '. $size[0] .'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            $data['image'] = 'storage/alternative/' . $imageName;
            // dd($data['image']);
        }

        $alternative->update($data);
        return response()->json(['success' => true, 'message' => 'Alternative Created Successfully.']);
    }

    public function edit($id)
    {
        $alternative = AlternativeModel::findOrFail($id);
        return view('alternative.edit', compact('alternative'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);
        $alternative = AlternativeModel::findOrFail($id);
        $data = $request->all();

        $alternative->update($data);
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'alternative_' . $alternative->id . '.' . $extension;
            // $image->storeAs('alternative', $imageName, 'public');
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/alternative');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [254, 160];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $alternative->delete  ();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least '. $size[0] .'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            // $data['image'] = 'storage/alternative/' . $imageName;
            $data['image'] = 'storage/alternative/' . $imageName;
            // dd($data['image']);
        }

        $alternative->update($data);

        return response()->json(['success' => true, 'message' => 'Alternative updated Successfully.']);
    }

    public function destroy($id)
    {
        $alternative = AlternativeModel::findOrFail($id);
        $alternative->deleted = 1;
        $alternative->save();

        return response()->json(['success' => true, 'message' => 'Alternative deleted Successfully.']);
    }

    public function alternativeList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id', 'title', 'description', 'service_icon', 'status'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $alternativeDetails = AlternativeModel::getAlternativeData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = AlternativeModel::count();
        $totalRecordwithFilter = AlternativeModel::getAlternativeDataTotal($searchValue);

        $data = [];
        foreach ($alternativeDetails as $alternative) {
            $data[] = [
                'id' => $alternative->id,
                'title' => $alternative->title ?? '-',
                'description' => $alternative->description ?? '-',
                'alternative_icon' => $alternative->alternative_icon ?? '-',
                'button_title' => $alternative->button_title ?? '-',
                'status' => $alternative->status,
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
        AlternativeModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected Data deleted Successfully.']);
    }
    public function toggleStatus($id)
    {
        $alternative = AlternativeModel::findOrFail($id);
        $alternative->status = !$alternative->status;
        $alternative->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function deleteImage($id)
    {
        $alternative = AlternativeModel::findOrFail($id);
        if ($alternative->image) {
            $imagePath = public_path($alternative->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $alternative->image = null;
            $alternative->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    public function getActiveCount()
    {
        $activeCount = AlternativeModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }

    /**
     * Get alternatives for frontend
     */


public function getAlternativesForFrontend()
{
    try {
        $items = AlternativeModel::where('deleted', 0)
            ->where('status', 1)
            ->orderBy('id', 'asc')
            ->get(['id', 'title', 'description', 'alternative_icon as icon', 'image', 'button_title'])
            ->map(function ($item) {
                // Normalize image filename (remove storage path if already stored)
                $filename = str_replace(['storage/alternative/', 'alternative/'], '', $item->image);

                // Build the absolute file path
                $imagePath = public_path('storage/alternative/' . $filename);

                // Check if image exists
                if ($item->image && File::exists($imagePath)) {
                    $item->image = asset('storage/alternative/' . $filename);
                } else {
                    // Fallback to default image
                    $item->image = asset('images/Default.jpg');
                }

                return $item;
            });

        return response()->json([
            'success' => true,
            'message' => 'Alternatives retrieved successfully',
            'data' => $items,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error retrieving alternatives: ' . $e->getMessage(),
            'data' => [],
        ], 500);
    }
}

}

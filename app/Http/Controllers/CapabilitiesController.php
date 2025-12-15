<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\CapabilitiesModel;
use Illuminate\Http\Request;

class CapabilitiesController extends Controller
{
     public function index()
    {
        return view('capabilities.index');
    }

    public function create()
    {
        return view('capabilities.create');
    }

    public function store(Request $request)
    {
         $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $data = $request->all();
        $capability = CapabilitiesModel::create($data);
         if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'capability_' . $capability->id . '.' . $extension;
            // $image->storeAs('capability', $imageName, 'public');
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/capability');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [254, 160];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $capability->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            $data['image'] = 'storage/capability/' . $imageName;
            // dd($data['image']);
        }

        $capability->update($data);
        return response()->json(['success' => true, 'message' => 'Capability Created Successfully.']);
    }

    public function edit($id)
    {
        $capability = CapabilitiesModel::findOrFail($id);
        return view('capabilities.edit', compact('capability'));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        $capability = CapabilitiesModel::findOrFail($id);
        $data = $request->all();
        $capability->update($data);
        // $capability = CapabilitiesModel::create($data);
         if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'capability_' . $capability->id . '.' . $extension;
            // $image->storeAs('capability', $imageName, 'public');
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/capability');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [254, 160];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $capability->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            $data['image'] = 'storage/capability/' . $imageName;
        }

        $capability->update($data);

        return response()->json(['success' => true, 'message' => 'Capability Updated Successfully.']);
    }

    public function destroy($id)
    {
        $capability = CapabilitiesModel::findOrFail($id);
        $capability->deleted = 1;
        $capability->save();

        return response()->json(['success' => true, 'message' => 'Capability Deleted Successfully.']);
    }

    public function capabilityList(Request $request)
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

        $capabilities = CapabilitiesModel::getCapabilityData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = CapabilitiesModel::count();
        $totalRecordwithFilter = CapabilitiesModel::getCapabilityDataTotal($searchValue);

        $data = [];
        foreach ($capabilities as $capability) {
            $data[] = [
                'id' => $capability->id,
                'title' => $capability->title ?? '-',
                'description' => $capability->description ?? '-',
                'capability_icon' => $capability->capability_icon ?? '-',
                'progress_indicator' => $capability->progress_indicator ?? '-',
                'progress_label' => $capability->progress_label ?? '-',
                'status' => $capability->status,
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
        CapabilitiesModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected categories deleted Successfully.']);
    }

     public function toggleStatus($id)
    {
        $capability = CapabilitiesModel::findOrFail($id);
        $capability->status = !$capability->status;
        $capability->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function deleteImage($id)
    {
        $capability = CapabilitiesModel::findOrFail($id);
        if ($capability->image) {
            $imagePath = public_path($capability->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $capability->image = null;
            $capability->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    public function getActiveCount()
    {
        $activeCount = CapabilitiesModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }


     /**
     * Get alternatives for frontend
     */
    public function getCapabilitiesForFrontend()
    {
        try {
            // dd("asd");
            $items = CapabilitiesModel::where('deleted', 0)
                ->where('status', 1)
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Capabilities retrieved successfully',
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving Capabilities: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}

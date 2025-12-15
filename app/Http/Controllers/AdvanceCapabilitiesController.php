<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\AdvanceCapabilitiesModel;
use Illuminate\Http\Request;

class AdvanceCapabilitiesController extends Controller
{
    public function index()
    {
        return view('advance_capabilities.index');
    }

    public function create()
    {
        return view('advance_capabilities.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $data = $request->all();
        $advance_capability = AdvanceCapabilitiesModel::create($data);
        $data = $request->all();
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'advance_capability_' . $advance_capability->id . '.' . $extension;
            // $image->storeAs('advance_capability', $imageName, 'public');
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/advance_capability');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [254, 160];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $advance_capability->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            $data['image'] = 'storage/advance_capability/' . $imageName;
            // dd($data['image']);
        }

        $advance_capability->update($data);
        return response()->json(['success' => true, 'message' => 'Advance Capability created Successfully.']);
    }

    public function edit($id)
    {
        $advance_capability = AdvanceCapabilitiesModel::findOrFail($id);
        return view('advance_capabilities.edit', compact('advance_capability'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $advance_capability = AdvanceCapabilitiesModel::findOrFail($id);
        $data = $request->all();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'advance_capability_' . $advance_capability->id . '.' . $extension;
            // $image->storeAs('advance_capability', $imageName, 'public');
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/advance_capability');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [254, 160];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $advance_capability->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            $data['image'] = 'storage/advance_capability/' . $imageName;
        }

        $advance_capability->update($data);

        return response()->json(['success' => true, 'message' => 'Advance Capability updated Successfully.']);
    }

    public function destroy($id)
    {
        $advance_capability = AdvanceCapabilitiesModel::findOrFail($id);
        $advance_capability->deleted = 1;
        $advance_capability->save();

        return response()->json(['success' => true, 'message' => 'Advance Capability deleted Successfully.']);
    }

    public function advanceCapabilityList(Request $request)
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

        $advance_capability = AdvanceCapabilitiesModel::getAdvanceCapabilityData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = AdvanceCapabilitiesModel::count();
        $totalRecordwithFilter = AdvanceCapabilitiesModel::getAdvanceCapabilityDataTotal($searchValue);

        $data = [];
        foreach ($advance_capability as $advance_capability) {
            $data[] = [
                'id' => $advance_capability->id,
                'title' => $advance_capability->title ?? '-',
                'description' => $advance_capability->description ?? '-',
                'advance_capability_icon' => $advance_capability->advance_capability_icon ?? '-',
                'feature_benifit_1' => $advance_capability->feature_benifit_1 ?? '-',
                'feature_benifit_2' => $advance_capability->feature_benifit_2 ?? '-',
                'feature_status_badge' => $advance_capability->feature_status_badge ?? '-',
                'status' => $advance_capability->status,
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
        AdvanceCapabilitiesModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected Advance Capability deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $advance_capability = AdvanceCapabilitiesModel::findOrFail($id);
        $advance_capability->status = !$advance_capability->status;
        $advance_capability->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function deleteImage($id)
    {
        $advance_capability = AdvanceCapabilitiesModel::findOrFail($id);
        if ($advance_capability->image) {
            $imagePath = public_path($advance_capability->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $advance_capability->image = null;
            $advance_capability->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    public function getActiveCount()
    {
        $activeCount = AdvanceCapabilitiesModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }

     /**
     * Get alternatives for frontend
     */
    public function getAdvanceCapabilitiesForFrontend()
    {
        try {
            // dd("asd");
            $items = AdvanceCapabilitiesModel::where('deleted', 0)
                ->where('status', 1)
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Advance Capabilities retrieved successfully',
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving Advance Capabilities: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}

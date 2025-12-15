<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\ServiceModel;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return view('service.index');
    }

    public function create()
    {
        return view('service.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $data = $request->all();
        $service = ServiceModel::create($data);
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'service_' . $service->id . '.' . $extension;
            // $image->storeAs('service', $imageName, 'public');
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/service');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [254, 160];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $service->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            $data['image'] = 'storage/service/' . $imageName;
        }

        $service->update($data);
        return response()->json(['success' => true, 'message' => 'Service Created Successfully.']);
    }

    public function edit($id)
    {
        $service = ServiceModel::findOrFail($id);
        return view('service.edit', compact('service'));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        $service = ServiceModel::findOrFail($id);
        $data = $request->except('image');

        // Handle image upload if a new image is provided
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'service_' . $service->id . '.' . $extension;
            // $image->storeAs('service', $imageName, 'public');
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/service');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [254, 160];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $service->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            $data['image'] = 'storage/service/' . $imageName;
        }

        $service->update($data);

        return response()->json(['success' => true, 'message' => 'Service Updated Successfully.']);
    }

    public function destroy($id)
    {
        $service = ServiceModel::findOrFail($id);
        $service->deleted = 1;
        $service->save();

        return response()->json(['success' => true, 'message' => 'Service Deleted Successfully.']);
    }

    public function serviceList(Request $request)
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

        $serviceDetails = ServiceModel::getSeriveData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = ServiceModel::count();
        $totalRecordwithFilter = ServiceModel::getSeriveDataTotal($searchValue);

        $data = [];
        foreach ($serviceDetails as $service) {
            $data[] = [
                'id' => $service->id,
                'title' => $service->title ?? '-',
                'description' => $service->description ?? '-',
                'service_icon' => $service->service_icon ?? '-',
                'status' => $service->status,
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
        ServiceModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected Service Deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $service = ServiceModel::findOrFail($id);
        $service->status = !$service->status;
        $service->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function deleteImage($id)
    {
        $service = ServiceModel::findOrFail($id);
        if ($service->image) {
            $imagePath = public_path($service->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $service->image = null;
            $service->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    public function getActiveCount()
    {
        $activeCount = ServiceModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }

    /**
     * Get services for frontend
     */
    public function getServicesForFrontend()
    {
        try {
            // Get all active services
            $services = ServiceModel::where('deleted', 0)
                ->where('status', 1)
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Services retrieved successfully',
                'data' => $services
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving services: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}

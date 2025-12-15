<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\ClientModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    public function index()
    {
        return view('clients.index');
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        // dd("asd");
        $request->validate([
            'image' => 'required|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        $data = $request->all();

        $client = ClientModel::create($data);
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'client_' . $client->id . '.' . $extension;
            // $image->storeAs('client', $imageName, 'public');
            // $data['client'] = 'storage/client/' . $imageName;
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/client');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [161, 60];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $client->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            $data['client'] = 'storage/client/' . $imageName;
        }

        $client->update($data);
        return response()->json(['success' => true, 'message' => 'Client created Successfully.']);
    }

    public function edit($id)
    {
        $client = ClientModel::findOrFail($id);
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        $client = ClientModel::findOrFail($id);
        $data = $request->all();

        // Handle image upload if a new image is provided
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'client_' . $client->id . '.' . $extension;
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/client');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [161, 60];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $client->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            $data['client'] = 'storage/client/' . $imageName;
        }

        $client->update($data);

        return response()->json(['success' => true, 'message' => 'Client updated Successfully.']);
    }

    public function destroy($id)
    {
        $client = ClientModel::findOrFail($id);
        $client->deleted = 1;
        $client->save();

        return response()->json(['success' => true, 'message' => 'Client deleted Successfully.']);
    }

    public function clientList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id', 'client', 'status'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $clients = ClientModel::getClientData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = ClientModel::count();
        $totalRecordwithFilter = ClientModel::getClientDataTotal($searchValue);

        $data = [];
        foreach ($clients as $client) {
            $imagePath = public_path($client->client);

            if (!file_exists($imagePath) || empty($client->client)) {
                // Use default image if file missing or empty
                $clientImage = 'images/Default.jpg';
            } else {
                $clientImage = $client->client;
            }
            $data[] = [
                'id' => $client->id,
                'client' => $clientImage,
                'status' => $client->status,
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
        ClientModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected Client Deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $client = ClientModel::findOrFail($id);
        $client->status = !$client->status;
        $client->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function deleteImage($id)
    {
        $client = ClientModel::findOrFail($id);
        if ($client->client) {
            $imagePath = public_path($client->client);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $client->client = null;
            $client->save();
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
            $clients = ClientModel::where('deleted', 0)
                ->where('status', 1)
                ->get()->map(function ($client) {
                    $imagePath = public_path($client->client);

                    if (empty($client->client) || !file_exists($imagePath)) {
                        $client->client = asset('images/Default.jpg');
                    } else {
                        $client->client = asset($client->client);
                    }

                    return $client;
                });

            // $clients = $clients->map(function ($client) {
            //     $validatedPath = $this->validateAndGetImageUrl($client->client);
            //     $client->client = $validatedPath;
            //     return $client;
            // });

            return response()->json([
                'success' => true,
                'message' => 'Client retrieved successfully',
                'data' => $clients
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching clients: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate image path and return safe URL with fallback
     */
    // private function validateAndGetImageUrl($imagePath)
    // {
    //     // If no image path provided, return default
    //     if (empty($imagePath)) {
    //         return 'images/Default.jpg';
    //     }

    //     // If it's already an absolute URL, return as is
    //     if (preg_match('/^https?:\/\//i', $imagePath)) {
    //         return $imagePath;
    //     }

    //     // Clean the path
    //     $cleanPath = ltrim($imagePath, '/');
    //     $fullPath = public_path($cleanPath);

    //     // Check if file exists and is a valid image
    //     if (file_exists($fullPath) && $this->isValidImage($fullPath)) {
    //         return $imagePath;
    //     }

    //     // Return default image if original doesn't exist or is invalid
    //     return 'images/Default.jpg';
    // }

    /**
     * Check if file is a valid image
     */
    private function isValidImage($filePath)
    {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $mimeType = mime_content_type($filePath);
        return in_array($mimeType, $allowedTypes);
    }
    public function getActiveCount()
    {
        $activeCount = ClientModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }
}

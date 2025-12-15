<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\HeaderModel;
use Illuminate\Http\Request;

class HeaderController extends Controller
{
    public function index()
    {
        return view('header.index');
    }

    public function create()
    {
        return view('header.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'link' => 'required|string|max:255',
            'button_title' => 'required|string|max:255',
            'button_link' => 'required|string|max:255',
            'image' => 'required|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        // Enforce single header record
        // if (HeaderModel::where('deleted', 0)->exists()) {
        //     return response()->json(['success' => false, 'message' => 'header already exists. Please edit the existing entry.'], 422);
        // }

        $data = $request->all();
        $header = HeaderModel::create($data);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'header_' . $header->id . '.' . $extension;
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/header');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [636, 424];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            // dd($success);
            if (!$success) {
                // $header->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }

            $data['image'] = 'storage/header/' . $imageName;
        }

        $header->update($data);
        return response()->json(['success' => true, 'message' => 'header created Successfully.']);
    }

    public function edit($id)
    {
        $header = HeaderModel::findOrFail($id);
        return view('header.edit', compact('header'));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        $header = HeaderModel::findOrFail($id);
        $data = $request->except('image');

        // Handle image upload if a new image is provided
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'header_' . $header->id . '.' . $extension;
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/header');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [636, 424];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $header->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }

            $data['image'] = 'storage/header/' . $imageName;
        }


        $header->update($data);

        return response()->json(['success' => true, 'message' => 'header updated Successfully.']);
    }

    public function destroy($id)
    {
        $header = HeaderModel::findOrFail($id);
        $header->deleted = 1;
        $header->save();

        return response()->json(['success' => true, 'message' => 'header deleted Successfully.']);
    }

    public function headerList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id', 'title', 'link', 'button_title', 'button_link', 'image'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $headerDetails = HeaderModel::getheaderData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = HeaderModel::count();
        $totalRecordwithFilter = HeaderModel::getheaderDataTotal($searchValue);

        $data = [];
        foreach ($headerDetails as $header) {
            // dd($header);
            $data[] = [
                'id' => $header->id,
                'title' => $header->title ?? '-',
                'link' => $header->link ?? '-',
                'button_title' => $header->button_title ?? '-',
                'button_link' => $header->button_link ?? '-',
                'image' => $header->image ?? '-',
                'status' => $header->status,
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
        HeaderModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected categories deleted Successfully.']);
    }


    public function toggleStatus($id)
    {
        $header = HeaderModel::findOrFail($id);
        $header->status = !$header->status;
        $header->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function deleteImage($id)
    {
        $header = HeaderModel::findOrFail($id);
        if ($header->image) {
            $imagePath = public_path($header->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $header->image = null;
            $header->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    /**
     * Frontend - Header config (site name, links, cta)
     */
    public function getHeaderConfig()
    {
        try {
           $data = HeaderModel::where('deleted', 0)
                ->where('status', 1)
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Header config retrieved successfully',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching header config: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get clients for frontend display
     */
    public function getClientsForFrontend()
    {
        try {
            $clients = HeaderModel::where('deleted', 0)
                ->select('id', 'title', 'image', 'description', 'button_title_1', 'button_title_2')
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'success' => true,
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
     * Get header data for frontend display with stats
     */
    public function getheaderForFrontend()
    {
        try {
            $headerData = HeaderModel::where('deleted', 0)
            ->where('status', 1)
                // ->select('id', 'title', 'image', 'description', 'button_title_1', 'button_title_2')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $headerData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching header data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Render the header section dynamically for the frontend Blade template
     */
    public function showheaderSection()
    {
        $header = HeaderModel::where('deleted', 0)->first();
        return view('templates.header', compact('header'));
    }


    public function getActiveCount()
    {
        $activeCount = HeaderModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\TeamsModel;
use App\Models\ServiceModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TeamsController extends Controller
{
    public function index()
    {
        return view('teams.index');
    }

    public function create()
    {
        return view('teams.create');
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);
        $data = $request->all();
        $teams = TeamsModel::create($data);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'teams_' . $teams->id . '.' . $extension;
            // $image->storeAs('teams', $imageName, 'public');
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/teams');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [306, 320];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $teams->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            $data['image'] = 'storage/teams/' . $imageName;
        }

        $teams->update($data);
        return response()->json(['success' => true, 'message' => 'Teams created Successfully.']);
    }

    public function edit($id)
    {
        $teams = TeamsModel::findOrFail($id);
        return view('teams.edit', compact('teams'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);
        $teams = TeamsModel::findOrFail($id);
        $data = $request->all();

        $teams->update($data);
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'teams_' . $teams->id . '.' . $extension;
            // $image->storeAs('teams', $imageName, 'public');
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/teams');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [306, 320];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $teams->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            $data['image'] = 'storage/teams/' . $imageName;
        }

        $teams->update($data);

        return response()->json(['success' => true, 'message' => 'teams updated Successfully.']);
    }

    public function destroy($id)
    {
        $teams = TeamsModel::findOrFail($id);
        $teams->deleted = 1;
        $teams->save();

        return response()->json(['success' => true, 'message' => 'teams deleted Successfully.']);
    }

    public function teamsList(Request $request)
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

        $teamsDetails = TeamsModel::getTeamsData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = TeamsModel::count();
        $totalRecordwithFilter = TeamsModel::getTeamsDataTotal($searchValue);

        $data = [];
        foreach ($teamsDetails as $teams) {
            $imagePath = public_path($teams->image);
            if (!file_exists($imagePath) || empty($teams->image)) {
                $teamsImage = 'images/Default.jpg';
            } else {
                $teamsImage = $teams->image;
            }
            $data[] = [
                'id' => $teams->id,
                'title' => $teams->title ?? '-',
                'description' => $teams->description ?? '-',
                'image' => $teamsImage ?? '-',
                // 'image' => $teams->image ?? '-',
                'role' => $teams->role ?? '-',
                'status' => $teams->status ?? '-',
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
        TeamsModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected Data deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $teams = TeamsModel::findOrFail($id);
        $teams->status = !$teams->status;
        $teams->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function deleteImage($id)
    {
        $teams = TeamsModel::findOrFail($id);
        if ($teams->image) {
            $imagePath = public_path($teams->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $teams->image = null;
            $teams->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    public function getActiveCount()
    {
        $activeCount = TeamsModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }

    /**
     * Get alternatives for frontend
     */
   public function getTeamsForFrontend()
{
    try {
        $items = TeamsModel::where('deleted', 0)
            ->where('status', 1)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($item) {
                // Normalize image filename (remove path if included)
                $filename = str_replace(['storage/teams/', 'teams/'], '', $item->image);

                // Build the full absolute path
                $imagePath = public_path('storage/teams/' . $filename);

                // Check if image file exists
                if ($item->image && File::exists($imagePath)) {
                    $item->image = asset('storage/teams/' . $filename);
                } else {
                    // Fallback to default image
                    $item->image = asset('images/Default.jpg');
                }

                return $item;
            });

        return response()->json([
            'success' => true,
            'message' => 'Teams retrieved successfully',
            'data' => $items,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error retrieving Teams: ' . $e->getMessage(),
            'data' => [],
        ], 500);
    }
}
}

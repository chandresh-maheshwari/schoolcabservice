<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\stats;
use App\Models\AboutUsModel;
use App\Models\StatsModel;
use Illuminate\Http\Request;

class StatsController extends Controller
{

    public function index()
    {
        return view('stats.index');
    }

    public function create()
    {
        return view('stats.create');
    }

    public function store(Request $request)
    {
         $request->validate([
            'stats_title' => 'required|string|max:255',
            'stats_counter' => 'required|string|max:255',
        ]);
        $data = $request->all();

        $stats = StatsModel::create($data);
        //  $hero = HeroModel::create($data);
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'stats_' . $stats->id . '.' . $extension;
            // $image->storeAs('stats', $imageName, 'public');
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/stats');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [254, 160];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $stats->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            $data['image'] = 'storage/stats/' . $imageName;
            // dd($data['image']);
        }

        $stats->update($data);

        return response()->json(['success' => true, 'message' => 'Stats page created Successfully.']);
    }

    public function edit($id)
    {
        $stats = StatsModel::findOrFail($id);
        return view('stats.edit', compact('stats'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'stats_title' => 'required|string|max:255',
            'stats_counter' => 'required|string|max:255',
        ]);
        $stats = StatsModel::findOrFail($id);
        $data = $request->all();
        $stats->update($data);
         if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'stats_' . $stats->id . '.' . $extension;
            // $image->storeAs('stats', $imageName, 'public');
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/stats');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [254, 160];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (!$success) {
                // $stats->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }
            $data['image'] = 'storage/stats/' . $imageName;
            // dd($data['image']);
        }

        $stats->update($data);

        return response()->json(['success' => true, 'message' => 'Stats page Updated Successfully.']);


    }

    public function destroy($id)
    {
        $stats = StatsModel::findOrFail($id);
        $stats->deleted = 1;
        $stats->save();

        return response()->json(['success' => true, 'message' => 'Stats deleted Successfully.']);
    }

    public function statsList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);
        // Only allow sorting by real columns
        $allowedColumns = [
            'id',
            'stats_counter',
            'stat_icon',
            'stats_title',
            'status'
        ];
        if (!in_array($columnName, $allowedColumns)) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $categories = StatsModel::getStatsData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = StatsModel::count();
        $totalRecordwithFilter = StatsModel::getStatsDataTotal($searchValue);

        $data = [];
        foreach ($categories as $stats) {
            $data[] = [
                'id' => $stats->id,
                'stats_counter' => $stats->stats_counter ?? '-',
                'stat_icon' => $stats->stat_icon ?? '-',
                'stats_title' => $stats->stats_title ?? '-',
                'status' => $stats->status,
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
        StatsModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected categories deleted Successfully.']);
    }

      public function toggleStatus($id)
    {
        $stats = StatsModel::findOrFail($id);
        $stats->status = !$stats->status;
        $stats->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function deleteImage($id)
    {
        $stats = StatsModel::findOrFail($id);
        if ($stats->image) {
            $imagePath = public_path($stats->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $stats->image = null;
            $stats->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    public function getActiveCount()
    {
        $activeCount = StatsModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }

    /**
     * Get stats for frontend
     */
    public function getStatsForFrontend()
    {
        try {
            $stats = StatsModel::where('deleted', 0)
                ->where('status', 1)
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Stats retrieved successfully',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving stats: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
    /**
     * Get statss for frontend display
     */
    // public function getClientsForFrontend()
    // {
    //     try {
    //         $statss = StatsModel::where('deleted', 0)
    //             ->select('id', 'title', 'image', 'description', 'button_title_1', 'button_title_2')
    //             ->orderBy('id', 'asc')
    //             ->get();

    //         return response()->json([
    //             'success' => true,
    //             'data' => $statss
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error fetching statss: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
}

<?php

namespace App\Http\Controllers;

use App\Models\WhyUsModel;
use Illuminate\Http\Request;

class WhyUsController extends Controller 
{
     public function index()
    {
        return view('why_us.index');
    }

    public function create()
    {
        return view('why_us.create');
    }

    public function store(Request $request)
    {
         $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $data = $request->all();
        WhyUsModel::create($data);
        return response()->json(['success' => true, 'message' => 'Why Us data created Successfully.']);
    }

    public function edit($id)
    {
        $why_us = WhyUsModel::findOrFail($id);
        return view('why_us.edit', compact('why_us'));
    }

    public function update(Request $request, $id)
    {
     $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $why_us = WhyUsModel::findOrFail($id);
        $data = $request->all();

        // Handle image upload if a new image is provided
        // if ($request->hasFile('image')) {
        //     $image = $request->file('image');
        //     $extension = $image->getClientOriginalExtension();
        //     $imageName = 'hero_' . $why_us->id . '.' . $extension;
        //     $image->storeAs('capability', $imageName, 'public');
        //     $data['image'] = 'storage/capability/' . $imageName;
        // }

        $why_us->update($data);

        return response()->json(['success' => true, 'message' => 'Why Us data updated Successfully.']);
    }

    public function destroy($id)
    {
        $why_us = WhyUsModel::findOrFail($id);
        $why_us->deleted = 1;
        $why_us->save();

        return response()->json(['success' => true, 'message' => 'Why Us data deleted Successfully.']);
    }

    public function whyUsList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id', 'title', 'description', 'status'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $heroDetails = WhyUsModel::getWhyUsData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = WhyUsModel::count();
        $totalRecordwithFilter = WhyUsModel::getWhyUsDataTotal($searchValue);

        $data = [];
        foreach ($heroDetails as $why_us) {
            $data[] = [
                'id' => $why_us->id,
                'title' => $why_us->title ?? '-',
                'description' => $why_us->description ?? '-',
                'status' => $why_us->status ?? '-',
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
        WhyUsModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected Why Us data deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $why_us = WhyUsModel::findOrFail($id);
        $why_us->status = !$why_us->status;
        $why_us->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }
    public function getActiveCount()
    {
        $activeCount = WhyUsModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }

    /**
     * Frontend: Get Why Us cards
     */
    public function getWhyUsForFrontend()
    {
        try {
            $items = WhyUsModel::where('deleted', 0)
                ->where('status', 1)
                ->orderBy('id', 'asc')
                ->get(['id', 'title', 'description']);

            return response()->json([
                'success' => true,
                'message' => 'Why Us retrieved successfully',
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving Why Us: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}

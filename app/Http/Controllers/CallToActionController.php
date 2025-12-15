<?php

namespace App\Http\Controllers;

use App\Models\CallToActionModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CallToActionController extends Controller
{
    public function index()
    {
        return view('call_to_action.index');
    }

    public function create()
    {
        return view('call_to_action.create');
    }

    public function store(Request $request)
    {
        $validator =Validator::make($request->all(), [
            'feature_1' => 'required|string|max:255',
            'feature_2' => 'required|string|max:255',
            'feature_3' => 'required|string|max:255',
            'feature_4' => 'required|string|max:255',
            'button_title' => 'required|string|max:255',

            // ✅ Validate link properly using regex
            'button_link' => [
                'required',
                'regex:/^(https?:\/\/)?([a-z0-9-]+\.)+[a-z]{2,6}(\/[^\s]*)?$/i',
                'max:255'
            ],
        ], [
            'button_link.regex' => 'The button link must be a valid URL (e.g. https://example.com).'
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $call_to_action =   CallToActionModel::create($request->all());
        // dd($call_to_action);
        return response()->json([
            'success' => true,
            'message' => 'Call To Action Data created successfully.'
        ]);
    }

    public function edit($id)
    {
        $call_to_action = CallToActionModel::findOrFail($id);
        return view('call_to_action.edit', compact('call_to_action'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'feature_1' => 'required|string|max:255',
            'feature_2' => 'required|string|max:255',
            'feature_3' => 'required|string|max:255',
            'feature_4' => 'required|string|max:255',
            'button_title' => 'required|string|max:255',

            // ✅ same rule for update
            'button_link' => [
                'required',
                'regex:/^(https?:\/\/)?([a-z0-9-]+\.)+[a-z]{2,6}(\/[^\s]*)?$/i',
                'max:255'
            ],
        ], [
            'button_link.regex' => 'The button link must be a valid URL (e.g. https://example.com).'
        ]);

        $call_to_action = CallToActionModel::findOrFail($id);
        $call_to_action->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Call To Action Data updated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $call_to_action = CallToActionModel::findOrFail($id);
        $call_to_action->deleted = 1;
        $call_to_action->save();

        return response()->json(['success' => true, 'message' => 'Call To Action Data deleted Successfully.']);
    }

    public function callToActionList(Request $request)
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

        $heroDetails = CallToActionModel::getCallToActionData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = CallToActionModel::count();
        $totalRecordwithFilter = CallToActionModel::getCallToActionDataTotal($searchValue);

        $data = [];
        foreach ($heroDetails as $call_to_action) {
            $data[] = [
                'id' => $call_to_action->id,
                'feature_1' => $call_to_action->feature_1 ?? '-',
                'feature_2' => $call_to_action->feature_2 ?? '-',
                'feature_3' => $call_to_action->feature_3 ?? '-',
                'feature_4' => $call_to_action->feature_4 ?? '-',
                'button_title' => $call_to_action->button_title ?? '-',
                'status' => $call_to_action->status ?? '-',
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
        CallToActionModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected Call To Action Data deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $call_to_action = CallToActionModel::findOrFail($id);
        $call_to_action->status = !$call_to_action->status;
        $call_to_action->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function getActiveCount()
    {
        $activeCount = CallToActionModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }

    /**
     * Frontend: Get Call To Action data
     */
    public function getCallToActionForFrontend()
    {
        try {
            $cta = CallToActionModel::where('deleted', 0)
                ->where('status', 1)
                ->orderBy('id', 'desc')
                ->get();
            // ->first(['id', 'feature_1', 'feature_2', 'feature_3', 'feature_4', 'button_title', 'button_link']);

            return response()->json([
                'success' => true,
                'message' => 'Call To Action retrieved successfully',
                'data' => $cta,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving Call To Action: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}

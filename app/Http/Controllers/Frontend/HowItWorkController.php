<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\HowItWork;
use Illuminate\Http\Request;

class HowItWorkController extends Controller
{
    /**
     * Display how it work section listing page.
     * created by ns
     */
    public function index()
    {
        return view('cms.how_it_work_section.index');
    }
/**
 * Display how it work section create form.
 * created by ns
 */
    public function create()
    {
        return view('cms.how_it_work_section.create');
    }

    /**
     * Store how it work section data.
     *
     * created by ns
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'name'          => 'required|string|max:255',
            'description'   => 'string',
            'button_name_1' => 'nullable|string|max:255',
            'button_link_1' => 'nullable|url|max:255',
            'button_name_2' => 'nullable|string|max:255',
            'button_link_2' => 'nullable|url|max:255',
        ]);

        HowItWork::create([
            'title'         => $request->title,
            'name'          => $request->name,
            'description'   => $request->description,
            'button_name_1' => $request->button_name_1,
            'button_link_1' => $request->button_link_1,
            'button_name_2' => $request->button_name_2,
            'button_link_2' => $request->button_link_2,
            'status'        => 0,
            'deleted'       => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'How It Works data added successfully',
        ]);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $howItWork = HowItWork::where('deleted', 0)->findOrFail($id);

        return view('cms.how_it_work_section.edit', compact('howItWork'));
    }

    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'name'          => 'required|string|max:255',
            'description'   => 'string',
            'button_name_1' => 'nullable|string|max:255',
            'button_link_1' => 'nullable|url|max:255',
            'button_name_2' => 'nullable|string|max:255',
            'button_link_2' => 'nullable|url|max:255',
        ]);

        $howItWork = HowItWork::where('deleted', 0)->findOrFail($id);

        $howItWork->update([
            'title'         => $request->title,
            'name'          => $request->name,
            'description'   => $request->description,
            'button_name_1' => $request->button_name_1,
            'button_link_1' => $request->button_link_1,
            'button_name_2' => $request->button_name_2,
            'button_link_2' => $request->button_link_2,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'How It Works data updated successfully',
        ]);
    }

    /**
     * Get how it work section list for datatable.
     * created by ns
     */
    public function howItWorkList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, 'id');

        // Allowed columns (how_it_works fields)
        $allowedColumns = [
            'id',
            'title',
            'name',
            'description',
            'button_name_1',
            'button_link_1',
            'button_name_2',
            'button_link_2',
            'status',
            'created_at',
            'updated_at',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';

        $columnSortOrder = in_array(
            $request->input('sSortDir_0'),
            ['asc', 'desc']
        ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        // Data
        $howItWorkData = HowItWork::getHowItWorkData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $row,
            $rowperpage
        );

        // Counts
        $totalRecords          = HowItWork::where('deleted', 0)->count();
        $totalRecordwithFilter = HowItWork::getHowItWorkDataTotal($searchValue);

        $data = [];

        foreach ($howItWorkData as $item) {
            $data[] = [
                'id'            => (string) $item->id,
                'title'         => $item->title,
                'name'          => $item->name,
                'description'   => $item->description,
                'button_name_1' => $item->button_name_1,
                'button_link_1' => $item->button_link_1,
                'button_name_2' => $item->button_name_2,
                'button_link_2' => $item->button_link_2,
                'status'        => $item->status,
            ];
        }

        return response()->json([
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ]);
    }
/**
 * Remove the specified benefit section from storage.
 * created by ns
 */
    public function destroy($id)
    {
        $howItWorks          = HowItWork::findOrFail($id);
        $howItWorks->deleted = 1;
        $howItWorks->save();

        return response()->json([
            'success' => true,
            'message' => 'How It Works deleted Successfully.',
        ]);
    }

    /**
     * Toggle how it works active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $howItWorks         = HowItWork::findOrFail($id);
        $howItWorks->status = $howItWorks->status == 1 ? 0 : 1;
        $howItWorks->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active how it works count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = HowItWork::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Delete multiple how it works sections.
     * created by ns
     */
    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided.',
            ]);
        }

        HowItWork::whereIn('id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected id deleted Successfully.',
        ]);
    }
}

<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\MsbAppSection;
use Illuminate\Http\Request;

class MsbAppSectionController extends Controller
{
    /**
     * Display MSB App Section listing page.
     * created by ns
     */
    public function index()
    {
        return view('cms.msb_app_section.index');
    }

    /**
     * Display MSB App Section create form.
     * created by ns
     */
    public function create()
    {
        return view('cms.msb_app_section.create');
    }

    /**
     * Store MSB App Section data.
     * created by ns
     */
    public function store(Request $request)
    {
        $request->validate([
            'icon'        => 'required|string|max:255',
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'button_name' => 'required|string|max:255',
            'button_link' => 'required|url|max:255',
        ]);

        MsbAppSection::create([
            'icon'        => $request->icon,
            'name'        => $request->name,
            'description' => $request->description,
            'button_name' => $request->button_name,
            'button_link' => $request->button_link,
            'status'      => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'MSB App Section added successfully',
        ]);
    }

    /**
     * Edit MSB App Section data.
     * created by ns
     */
    public function edit($id)
    {
        $msbApp = MsbAppSection::where('deleted', 0)->findOrFail($id);

        return view('cms.msb_app_section.edit', compact('msbApp'));
    }

    /**
     * Update MSB App Section data.
     * created by ns
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'icon'        => 'required|string|max:255',
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'button_name' => 'required|string|max:255',
            'button_link' => 'required|url|max:255',
        ]);

        $msbApp              = MsbAppSection::findOrFail($id);
        $msbApp->icon        = $request->icon;
        $msbApp->name        = $request->name;
        $msbApp->description = $request->description;
        $msbApp->button_name = $request->button_name;
        $msbApp->button_link = $request->button_link;
        $msbApp->save();

        return response()->json([
            'success' => true,
            'message' => 'MSB App Section updated successfully',
        ]);
    }

    /**
     * Get MSB App Section listing data.
     * created by ns
     */

    public function msbAppSectionList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, 'id');

        // Allowed columns
        $allowedColumns = [
            'id',
            'icon',
            'name',
            'description',
            'button_name',
            'button_link',
            'status',
            'created_at',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';

        $columnSortOrder = in_array(
            $request->input('sSortDir_0'),
            ['asc', 'desc']
        ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        // 📦 Data
        $sectionData = MsbAppSection::getMsbAppSectionData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $row,
            $rowperpage
        );

        // 🔢 Counts
        $totalRecords          = MsbAppSection::where('deleted', 0)->count();
        $totalRecordwithFilter = MsbAppSection::getMsbAppSectionDataTotal($searchValue);

        $data = [];

        foreach ($sectionData as $section) {
            $data[] = [
                'id'          => (string) $section->id,
                'icon'        => $section->icon,
                'name'        => $section->name,
                'description' => $section->description,
                'button_name' => $section->button_name,
                'button_link' => $section->button_link,
                'status'      => $section->status,
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
 * Delete MSB App Section (soft delete).
 * created by ns
 */
    public function destroy($id)
    {
        $msbApp          = MsbAppSection::findOrFail($id);
        $msbApp->deleted = 1;
        $msbApp->save();

        return response()->json([
            'success' => true,
            'message' => 'MSB App Section deleted Successfully.',
        ]);
    }

    /**
     * Toggle MSB App Section active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $msbApp         = MsbAppSection::findOrFail($id);
        $msbApp->status = $msbApp->status == 1 ? 0 : 1;
        $msbApp->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active MSB APP count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = MsbAppSection::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }
}

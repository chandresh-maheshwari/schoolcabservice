<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\FaqSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FaqSectionController extends Controller
{
    /**
     * Display a listing of the FAQ sections.
     */
    public function index()
    {
        return view('cms.faq_section.index');
    }

    /**
     * Show the form for creating a new FAQ section.
     */
    public function create()
    {
        return view('cms.faq_section.create');
    }

    /**
     * Store a newly created FAQ section in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'question' => 'required|string|max:255',
                'answer'   => 'required|string',
            ]);

            FaqSection::create([
                'question' => $validated['question'],
                'answer'   => $validated['answer'],
                'status'   => 0,
                'deleted'  => 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'FAQ added successfully',
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'type'    => 'validation',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display FAQ section edit form.
     * created by ns
     */
    public function edit($id)
    {
        $faqSection = FaqSection::where('deleted', 0)->findOrFail($id);

        return view('cms.faq_section.edit', compact('faqSection'));
    }

    /**
     * Update FAQ section data.
     *  created by ns
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'question' => 'required|string|max:255',
                'answer'   => 'required|string',
            ]);

            $faqSection = FaqSection::findOrFail($id);
            $faqSection->update([
                'question' => $validated['question'],
                'answer'   => $validated['answer'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'FAQ updated successfully',
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'type'    => 'validation',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get FAQ section list for datatable.
     * created by ns
     */
    public function faqList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, 'id');

        $allowedColumns = [
            'id',
            'question',
            'answer',
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
        $howItWorkData = FaqSection::getFaqData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $row,
            $rowperpage
        );

        // Counts
        $totalRecords          = FaqSection::where('deleted', 0)->count();
        $totalRecordwithFilter = FaqSection::getFaqDataTotal($searchValue);

        $data = [];

        foreach ($howItWorkData as $item) {
            $data[] = [
                'id'       => (string) $item->id,
                'question' => $item->question,
                'answer'   => $item->answer,
                'status'   => $item->status,
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
     * Remove the specified FAQ section from storage.
     * created by ns
     */
    public function destroy($id)
    {
        $faqSection          = FaqSection::findOrFail($id);
        $faqSection->deleted = 1;
        $faqSection->save();

        return response()->json([
            'success' => true,
            'message' => 'FAQ Section deleted Successfully.',
        ]);
    }

    /**
     * Toggle FAQ section active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $faqSection         = FaqSection::findOrFail($id);
        $faqSection->status = $faqSection->status == 1 ? 0 : 1;
        $faqSection->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active benefit section count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = FaqSection::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }


     public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided.',
            ]);
        }

        FaqSection::whereIn('id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected id deleted Successfully.',
        ]);
    }
}

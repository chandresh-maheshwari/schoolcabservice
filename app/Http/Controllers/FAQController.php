<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FAQ;
// use App\Models\FAQCategory;

class FAQController extends Controller
{
    public function index()
    {
        return view('faqs.index');
    }

    public function create()
    {
        // $categories = FAQCategory::where('deleted', 0)->get();
        return view('faqs.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            // 'category_id' => 'required|exists:faq_categories,id',
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        FAQ::create($request->all());

        return response()->json(['success' => true, 'message' => 'FAQ created Successfully.']);
    }

    public function edit($id)
    {
        $faq = FAQ::findOrFail($id);
        // $categories = FAQCategory::where('deleted', 0)->get();
        return view('faqs.edit', compact('faq'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            // 'category_id' => 'required|exists:faq_categories,id',
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $faq = FAQ::findOrFail($id);
        $faq->update($request->all());

        return response()->json(['success' => true, 'message' => 'FAQ updated Successfully.']);
    }

    public function destroy($id)
    {
        $faq = FAQ::findOrFail($id);
        $faq->deleted = 1;
        $faq->save();

        return response()->json(['success' => true, 'message' => 'FAQ deleted Successfully.']);
    }

    // public function list(Request $request)
    // {
    //     $searchValue = $request->input('search.value');
    //     $columnName = $request->input('columns.' . $request->input('order.0.column') . '.data');
    //     $columnSortOrder = $request->input('order.0.dir');
    //     $draw = $request->input('draw');
    //     $row = $request->input('start');
    //     $rowperpage = $request->input('length');

    //     $totalRecords = FAQ::getFAQDataTotal($searchValue);
    //     $totalRecordswithFilter = $totalRecords;

    //     $records = FAQ::getFAQData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);

    //     $dataArr = [];
    //     foreach ($records as $record) {
    //         $dataArr[] = [
    //             "id" => $record->id,
    //             "category" => $record->category,
    //             "question" => $record->question,
    //             "answer" => $record->answer,
    //         ];
    //     }

    //     $response = [
    //         "draw" => intval($draw),
    //         "iTotalRecords" => $totalRecords,
    //         "iTotalDisplayRecords" => $totalRecordswithFilter,
    //         "aaData" => $dataArr,
    //     ];

    //     return response()->json($response);
    // }

    public function faqList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id', 'category', 'question', 'answer', 'status'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $faqs = FAQ::getFAQData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = FAQ::count();
        $totalRecordwithFilter = FAQ::getFAQDataTotal($searchValue);

        $data = [];
        foreach ($faqs as $faq) {
            $data[] = [
                'id' => $faq->id,
                // 'category' => $faq->category,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'status' => $faq->status,
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

    public function show()
    {
        return view('faq.show');
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided.']);
        }
        FAQ::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected FAQs deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $pricing = FAQ::findOrFail($id);
        $pricing->status = !$pricing->status;
        $pricing->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function getActiveCount()
    {
        $activeCount = FAQ::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }

     /**
     * Get alternatives for frontend
     */
    public function getFaqForFrontend()
    {
        try {
            $items = FAQ::where('deleted', 0)  
                ->where('status', 1)
                ->orderBy('id', 'asc')
                ->get();
               
            return response()->json([
                'success' => true,
                'message' => 'Faq retrieved successfully',
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving Faq: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}

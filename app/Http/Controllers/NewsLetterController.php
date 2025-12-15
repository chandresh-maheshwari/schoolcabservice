<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;


class NewsLetterController extends Controller
{
   public function index()
    {
        // dd("asd");
        return view('news_letter.index');
    }
     public function newsList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id', 'email'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $newsDetails = News::getNewsData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = News::count();
        $totalRecordwithFilter = News::getNewsDataTotal($searchValue);

        $data = [];
        foreach ($newsDetails as $news) {
            $data[] = [
                'id' => $news->id,
                'email' => $news->email ?? '-',
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

     public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:newsletters,email'
        ]);

        News::create([
            'email' => $request->email
        ]);


        return response()->json(['message' => 'Subscribed successfully!']);
    }
}

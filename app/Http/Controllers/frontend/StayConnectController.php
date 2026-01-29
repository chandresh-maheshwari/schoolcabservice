<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\StayConnect;
use Illuminate\Http\Request;

class StayConnectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('cms.stay_connect_section.index');
    }

     public function stayConnectList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id','name', 'company', 'email'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $stayConnected = StayConnect::getStayConnect($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = StayConnect::count();
        $totalRecordwithFilter = StayConnect::getStayConnectTotal($searchValue);

        $data = [];
        foreach ($stayConnected as $connected) {
            $data[] = [
                'id' => $connected->id,
                'name' => $connected->name ?? '-',
                'company' => $connected->company ?? '-',
                'email' => $connected->email ?? '-',
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
}

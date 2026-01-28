<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactMessageSection;

class ContactMessageController extends Controller
{
    public function index()
    {
        return view('cms.contact_message_section.index');
    }

    public function contactMessageList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id','name', 'email', 'message'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $contactMessages = ContactMessageSection::getContactMessages($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = ContactMessageSection::count();
        $totalRecordwithFilter = ContactMessageSection::getContactMessagesTotal($searchValue);

        $data = [];
        foreach ($contactMessages as $message) {
            $data[] = [
                'id' => $message->id,
                'name' => $message->name ?? '-',
                'email' => $message->email ?? '-',
                'message' => $message->message ?? '-',
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

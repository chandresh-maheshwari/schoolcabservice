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


     public function store(Request $request)
    {
        $request->validate([
            'name'       => 'nullable|string|max:255',
             'email'  => 'nullable|string',
            'company'        => 'nullable|string|max:255',
            'message'        => 'nullable|string|max:255',
        ]);

        ContactMessageSection::create([
            'name'       => $request->name,
            'email'  => $request->email,
            'company'        => $request->company,
            'message'        => $request->message,
            'status'      => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contact Message Section added successfully',
        ]);
    }

    public function contactMessageList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id','name', 'email', 'message' , 'company'])) {
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
                'company' => $message->company ?? '-',
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

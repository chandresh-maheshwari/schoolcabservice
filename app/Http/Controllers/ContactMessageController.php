<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContactMessage;

class ContactMessageController extends Controller
{
    public function index()
    {
        return view('contact_messages.index');
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'first_name' => 'required|string|max:255',
    //         'last_name' => 'required|string|max:255',
    //         'email' => 'required|email|max:255',
    //         'subject' => 'required|string|max:255',
    //         'message' => 'required|string',
    //     ]);

    //     ContactMessage::create($request->all());

    //     return response()->json(['success' => true, 'message' => 'Your message has been sent Successfully.']);
    // }

    // public function edit($id)
    // {
    //     $contactMessage = ContactMessage::findOrFail($id);
    //     return view('contact_messages.edit', compact('contactMessage'));
    // }

    // public function update(Request $request, $id)
    // {
    //     $request->validate([
    //         'first_name' => 'required|string|max:255',
    //         'last_name' => 'required|string|max:255',
    //         'email' => 'required|email|max:255',
    //         'subject' => 'required|string|max:255',
    //         'message' => 'required|string',
    //     ]);

    //     $contactMessage = ContactMessage::findOrFail($id);
    //     $contactMessage->update($request->all());

    //     return response()->json(['success' => true, 'message' => 'Contact message updated Successfully.']);
    // }

    // public function destroy($id)
    // {
    //     $contactMessage = ContactMessage::findOrFail($id);
    //     $contactMessage->delete();

    //     return response()->json(['success' => true, 'message' => 'Contact message deleted Successfully.']);
    // }

    public function contactMessageList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id', 'first_name', 'last_name', 'email', 'subject', 'message'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $contactMessages = ContactMessage::getContactMessages($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = ContactMessage::count();
        $totalRecordwithFilter = ContactMessage::getContactMessagesTotal($searchValue);

        $data = [];
        foreach ($contactMessages as $message) {
            $data[] = [
                'id' => $message->id,
                'first_name' => $message->first_name ?? '-',
                'last_name' => $message->last_name ?? '-',
                'email' => $message->email ?? '-',
                'subject' => $message->subject ?? '-',
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
<?php

namespace App\Http\Controllers;

use App\Models\ContactModel;
use App\Models\ContactMessage;
use App\Models\ServiceModel;
use Illuminate\Http\Request;

class ContactController extends Controller
{
     public function index()
    {
        return view('contacts.index');
    }

    public function create()
    {
        return view('contacts.create');
    }

    public function store(Request $request)
    {
         $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location_title' => 'required|string|max:255',
            'location' => 'required|string',
            'contact_title' => 'required|string|max:255',
            'contact_1' => 'required',
            'contact_2' => 'required',
            'email_title' => 'required|string|max:255',
            'email_1' => 'required|string|max:255',
            'email_2' => 'required|string|max:255',
            'contact_form_title' => 'required|string|max:255',
            'contact_form_description' => 'required|string',
        ]);
        $data = $request->all();        
        $contact = ContactModel::create($data);

        //  if ($request->hasFile('image')) {
        //     $image = $request->file('image');
        //     $extension = $image->getClientOriginalExtension();
        //     $imageName = 'teams_' . $contact->id . '.' . $extension;
        //     $image->storeAs('contact', $imageName, 'public');
        //     $data['image'] = 'storage/contact/' . $imageName;
        // }

        $contact->update($data);
        return response()->json(['success' => true, 'message' => 'contact created Successfully.']);
    }

    public function edit($id)
    {
        $contact = ContactModel::findOrFail($id);
        return view('contacts.edit', compact('contact'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location_title' => 'required|string|max:255',
            'location' => 'required|string',
            'contact_title' => 'required|string|max:255',
            'contact_1' => 'required',
            'contact_2' => 'required',
            'email_title' => 'required|string|max:255',
            'email_1' => 'required|string|max:255',
            'email_2' => 'required|string|max:255',
            'contact_form_title' => 'required|string|max:255',
            'contact_form_description' => 'required|string',
        ]);

        $contact = ContactModel::findOrFail($id);
        $data = $request->all();
        $contact->update($data);        

        return response()->json(['success' => true, 'message' => 'Contact Updated Successfully.']);
    }

    public function destroy($id)
    {
        $contact = ContactModel::findOrFail($id);
        $contact->deleted = 1;
        $contact->save();

        return response()->json(['success' => true, 'message' => 'contact deleted Successfully.']);
    }

    public function contactList(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = $request->input('iDisplayStart');
        $rowperpage = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName = $request->input('mDataProp_' . $indexColumn);

        if (!in_array($columnName, ['id', 'title', 'description', 'location_title', 'location', 'contact_title', 'contact_1', 'contact_2', 
        'email_title', 'email_1', 'email_2', 'status' 
        ])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue = $request->input('sSearch');

        $contactsDetails = ContactModel::getContactsData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords = ContactModel::count();
        $totalRecordwithFilter = ContactModel::getContactsDataTotal($searchValue);

        $data = [];
        foreach ($contactsDetails as $contacts) {
            $data[] = [
                'id' => $contacts->id,
                'title' => $contacts->title ?? '-',
                'description' => $contacts->description ?? '-',
                'location_title' => $contacts->location_title ?? '-',
                'location' => $contacts->location ?? '-',
                'contact_title' => $contacts->contact_title ?? '-',
                'contact_1' => $contacts->contact_1 ?? '-',
                'contact_2' => $contacts->contact_2 ?? '-',
                'email_title' => $contacts->email_title ?? '-',
                'email_1' => $contacts->email_1 ?? '-',
                'email_2' => $contacts->email_2 ?? '-',
                'status' => $contacts->status ?? '-',
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
        ContactModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected Data deleted Successfully.']);
    }

   public function toggleStatus($id)
    {
        $contacts = ContactModel::findOrFail($id);
        $contacts->status = !$contacts->status;
        $contacts->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function getActiveCount()
    {
        $activeCount = ContactModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }

    /**
     * Get contacts for frontend
     */
    public function getContactsForFrontend()
    {
        try {
            $contact = ContactModel::where('deleted', 0)
                ->where('status', 1)
                ->orderBy('id', 'desc')
                ->first();

            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'No contact information found',
                    'data' => null,
                ], 404);
            }

            // Format the data for frontend
            $formattedData = [
                'title' => $contact->title ?? 'Contact Info',
                'description' => $contact->description ?? 'Get in touch with us',
                // 'form_title' => 'Get In Touch',
                // 'form_description' => 'Send us a message and we\'ll get back to you',
                'loading_text' => 'Loading...',
                'sent_text' => 'Your message has been sent. Thank you!',
                'button_text' => 'Send Message',
                'addresses' => [
                    $contact->location ?? 'No address provided'
                ],
                'phones' => [
                    $contact->contact_1 ?? '',
                    $contact->contact_2 ?? ''
                ],
                'emails' => [
                    $contact->email_1 ?? '',
                    $contact->email_2 ?? ''
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Contact information retrieved successfully',
                'data' => $formattedData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving contact information: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Get teams for frontend (keeping the original method)
     */
    public function getTeamsForFrontend()
    {
        try {
            $items = ContactModel::where('deleted', 0)
                ->where('status', 1)
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Contacts retrieved successfully',
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving Contacts: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Store contact message from frontend form
     */
    public function storeContactMessage(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
            ]);

            $contactMessage = ContactMessage::create([
                'first_name' => $request->name,
                'last_name' => '',
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your message has been sent successfully!',
                'data' => $contactMessage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending message: ' . $e->getMessage(),
            ], 500);
        }
    }
}

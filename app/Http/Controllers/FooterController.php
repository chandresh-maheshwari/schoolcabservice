<?php
namespace App\Http\Controllers;

use App\Models\FooterModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class FooterController extends Controller
{
    public function index()
    {
        return view('footer.index');
    }

    public function create()
    {
        return view('footer.create');
    }

    public function store(Request $request)
    {
        // Validate request (add/remove rules as needed)
        $rules = [
            'title'                => 'required|string|max:255',
            'footer_link'          => 'nullable|string|max:255',
            'location'             => 'nullable|string|max:255',
            'contact_title'        => 'nullable|string|max:255',
            'contact'              => 'nullable|string|max:255',
            'email_title'          => 'nullable|string|max:255',
            'email'                => 'nullable|email|max:255',

            'footer_link_title'    => 'nullable|string|max:255',
            'page_title_1'         => 'nullable|string|max:255',
            'page_link_1'          => 'nullable|string|max:255',
            'page_title_2'         => 'nullable|string|max:255',
            'page_link_2'          => 'nullable|string|max:255',
            'page_title_3'         => 'nullable|string|max:255',
            'page_link_3'          => 'nullable|string|max:255',
            'page_title_4'         => 'nullable|string|max:255',
            'page_link_4'          => 'nullable|string|max:255',

            'footer_service_title' => 'nullable|string|max:255',
            'service_title_1'      => 'nullable|string|max:255',
            'service_link_1'       => 'nullable|string|max:255',
            'service_title_2'      => 'nullable|string|max:255',
            'service_link_2'       => 'nullable|string|max:255',
            'service_title_3'      => 'nullable|string|max:255',
            'service_link_3'       => 'nullable|string|max:255',
            'service_title_4'      => 'nullable|string|max:255',
            'service_link_4'       => 'nullable|string|max:255',

            'follow_us'            => 'nullable|string|max:255',
            'description'          => 'nullable|string',
            'copy_right_text'      => 'nullable|string|max:255',
            'status'               => 'nullable|integer',
            'image'                => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ];

        $messages = [
            'email.email'  => 'Please enter a valid email address.',
            'email.unique' => 'This email already exists.',
        ];

        try {
            $validated = $request->validate($rules, $messages);

                                // Prepare data for create
            $data = $validated; // start with validated inputs

            // Handle file upload (optional)
            // if ($request->hasFile('image')) {
            //     $image = $request->file('image');
            //     $ext = $image->getClientOriginalExtension();
            //     // Use a unique name (or change to any naming convention)
            //     $imageName = 'footer_' . Str::random(10) . '.' . $ext;
            //     $image->storeAs('footer', $imageName, 'public'); // stores in storage/app/public/footer
            //     $data['image'] = 'storage/footer/' . $imageName; // path you can save in DB (public accessible)
            // }

            // Create footer record
            $footer = FooterModel::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Footer created successfully.',
                'data'    => $footer,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Log exception in real app: Log::error($e);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating footer.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function subscribeStore(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|unique:newsletter,email',
            ], [
                'email.unique'   => 'This email already exists.',
                'email.required' => 'Email is required.',
                'email.email'    => 'Please enter a valid email address.',
            ]);

            $newsletter = \App\Models\News::create([
                'email' => $request->email,
            ]);

            $mailDetails = [
                'email' => $newsletter->email,
            ];

            $superAdminRole = \App\Models\Role::where('name', 'Super Admin')->first();
            if ($superAdminRole) {
                $superAdmins = \App\Models\User::where('role_id', $superAdminRole->id)->get();
            } else {
                $superAdmins = collect();
            }

            foreach ($superAdmins as $admin) {
                $mailDetail = \App\Models\MailDetail::create([
                    'user_id'      => $newsletter->id,
                    'email_type'   => 'Newsletter Subscription',
                    'email_to'     => $admin->email,
                    'mail_details' => json_encode($mailDetails),
                ]);

                try {
                    Mail::to($admin->email)->queue(new \App\Mail\NewsletterSubscribed($mailDetails));
                    $mailDetail->update(['is_sent' => 1]);
                } catch (\Exception $e) {
                    $mailDetail->update(['is_sent' => 0]);
                }
            }

            return response()->json(['success' => true, 'message' => 'Newsletter subscribed successfully!'], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $footer = FooterModel::findOrFail($id);
        return view('footer.edit', compact('footer'));
    }

    public function update(Request $request, $id)
    {
        // $request->validate([
        //     'title' => 'required|string|max:255',
        //     'description' => 'required|string',
        //     'location_title' => 'required|string|max:255',
        //     'location' => 'required|string',
        //     'contact_title' => 'required|string|max:255',
        //     'contact_1' => 'required',
        //     'contact_2' => 'required',
        //     'email_title' => 'required|string|max:255',
        //     'email_1' => 'required|string|max:255',
        //     'email_2' => 'required|string|max:255',
        // ]);

        $footer = FooterModel::findOrFail($id);
        $data   = $request->all();
        $footer->update($data);

        return response()->json(['success' => true, 'message' => 'footer Updated Successfully.']);
    }

    public function destroy($id)
    {
        $footer          = FooterModel::findOrFail($id);
        $footer->deleted = 1;
        $footer->save();

        return response()->json(['success' => true, 'message' => 'footer deleted Successfully.']);
    }

    public function contactList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = $request->input('iDisplayStart');
        $rowperpage  = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        if (! in_array($columnName, [
            'id',
            'title',
            'location',
            'contact',
            'email',
            'status',
        ])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue     = $request->input('sSearch');

        $heroDetails           = FooterModel::getFooterData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords          = FooterModel::count();
        $totalRecordwithFilter = FooterModel::getFooterDataTotal($searchValue);

        $data = [];
        foreach ($heroDetails as $footer) {
            $data[] = [
                'id'       => $footer->id,
                'title'    => $footer->title ?? '-',
                // 'link' => $footer->link ?? '-',
                'location' => $footer->location ?? '-',
                // 'contact_title' => $footer->contact_title ?? '-',
                'contact'  => $footer->contact ?? '-',
                // 'email_title' => $footer->email_title ?? '-',
                'email'    => $footer->email ?? '-',
                'status'   => $footer->status ?? '-',
                // 'footer_link_title_1' => $footer->footer_link_title_1 ?? '-',
                // 'page_title_1' => $footer->page_title_1 ?? '-',
                // 'page_link_1' => $footer->page_link_1 ?? '-',
                // 'page_title_2' => $footer->page_title_2 ?? '-',
                // 'page_link_2' => $footer->page_link_2 ?? '-',
                // 'page_title_3' => $footer->page_title_3 ?? '-',
                // 'page_link_3' => $footer->page_link_3 ?? '-',
                // 'page_title_4' => $footer->page_title_4 ?? '-',
                // 'page_link_4' => $footer->page_link_4 ?? '-',
                // 'footer_link_title_2' => $footer->footer_link_title_2 ?? '-',
                // 'service_title_1' => $footer->service_title_1 ?? '-',
                // 'service_link_1' => $footer->service_link_1 ?? '-',
                // 'service_title_2' => $footer->service_title_2 ?? '-',
                // 'service_link_2' => $footer->service_link_2 ?? '-',
                // 'service_title_3' => $footer->service_title_3 ?? '-',
                // 'service_link_3' => $footer->service_link_3 ?? '-',
                // 'service_title_4' => $footer->service_title_4 ?? '-',
                // 'service_link_4' => $footer->service_link_4 ?? '-',
                // 'footer_link_title_3' => $footer->footer_link_title_3 ?? '-',
                // 'description' => $footer->description ?? '-',
                // 'copy_right_text' => $footer->copy_right_text ?? '-',
            ];
        }

        $output = [
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ];

        return response()->json($output);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided.']);
        }
        FooterModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected Data deleted Successfully.']);
    }
    public function toggleStatus($id)
    {
        $footer         = FooterModel::findOrFail($id);
        $footer->status = ! $footer->status;
        $footer->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function getActiveCount()
    {
        $activeCount = FooterModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }

    /**
     * Get footer data for frontend
     */
    public function getFooterForFrontend()
    {
        try {
            $footer = FooterModel::where('deleted', 0)
                ->where('status', 1)
                ->orderBy('id', 'desc')
                ->first();

            if (! $footer) {
                return response()->json([
                    'success' => false,
                    'message' => 'No footer information found',
                    'data'    => null,
                ], 404);
            }

            $data = [
                'title'                => $footer->title,
                'address'              => $footer->location,
                'contact_title'        => $footer->contact_title,
                'contact'              => $footer->contact,
                'email_title'          => $footer->email_title,
                'email'                => $footer->email,
                'footer_link_title'    => $footer->footer_link_title,
                'footer_links'         => [
                    ['title' => $footer->page_title_1, 'link' => $footer->page_link_1],
                    ['title' => $footer->page_title_2, 'link' => $footer->page_link_2],
                    ['title' => $footer->page_title_3, 'link' => $footer->page_link_3],
                    ['title' => $footer->page_title_4, 'link' => $footer->page_link_4],
                ],
                'footer_service_title' => $footer->footer_service_title,
                'footer_services'      => [
                    ['title' => $footer->service_title_1, 'link' => $footer->service_link_1],
                    ['title' => $footer->service_title_2, 'link' => $footer->service_link_2],
                    ['title' => $footer->service_title_3, 'link' => $footer->service_link_3],
                    ['title' => $footer->service_title_4, 'link' => $footer->service_link_4],
                ],
                'follow_us'            => $footer->follow_us,
                'description'          => $footer->description,
                'copy_right_text'      => $footer->copy_right_text,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Footer information retrieved successfully',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving footer information: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }
}

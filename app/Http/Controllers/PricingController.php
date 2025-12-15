<?php
namespace App\Http\Controllers;

use App\Models\PricingModel;
use App\Models\Role;
use App\Models\TrainingPackage;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PricingController extends Controller
{
    public function index()
    {
        return view('pricing.index');
    }

    public function create()
    {
        // $categories = Category::where('deleted', 0)->get();
        return view('pricing.create');
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'title'         => 'required|string|max:255',
            'currency'      => 'required|string|max:255',
            'amount'        => 'required',
            'period'        => 'required|string|max:255',
            'description'   => 'required|string|max:255',
            'feature_title' => 'required|string|max:255',
            'button_title'  => 'required|string|max:255',
            'button_link'   => [
                'required',
                'regex:/^(https?:\/\/)?([a-z0-9-]+\.)+[a-z]{2,6}(\/[^\s]*)?$/i',
                'max:255',
            ],
        ], [
            'button_link.regex' => 'The button link must be a valid URL (e.g. https://example.com).',
        ]);
        $data    = $request->all();
        $pricing = PricingModel::create($data);

        return response()->json(['success' => true, 'message' => 'pricing created Successfully.']);
    }

    public function edit($id)
    {
        // $categories = Category::where('deleted', 0)->get();
        $pricing = PricingModel::findOrFail($id);
        return view('pricing.edit', compact('pricing'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'currency'      => 'required|string|max:255',
            'amount'        => 'required',
            'period'        => 'required|string|max:255',
            'description'   => 'required|string|max:255',
            'feature_title' => 'required|string|max:255',
            'button_title'  => 'required|string|max:255',
            'button_link'   => [
                'required',
                'regex:/^(https?:\/\/)?([a-z0-9-]+\.)+[a-z]{2,6}(\/[^\s]*)?$/i',
                'max:255',
            ],
        ], [
            'button_link.regex' => 'The button link must be a valid URL (e.g. https://example.com).',
        ]);

        $pricing = PricingModel::findOrFail($id);
        $data    = $request->all();

        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'portfolio_' . $pricing->id . '.' . $extension;
            $image->storeAs('pricing', $imageName, 'public');
            $data['image'] = 'storage/pricing/' . $imageName;
        }

        $pricing->update($data);

        return response()->json(['success' => true, 'message' => 'pricing updated Successfully.']);
    }

    public function destroy($id)
    {
        $pricing          = PricingModel::findOrFail($id);
        $pricing->deleted = 1;
        $pricing->save();

        return response()->json(['success' => true, 'message' => 'pricing deleted Successfully.']);
    }

    public function pricingList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = $request->input('iDisplayStart');
        $rowperpage  = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        if (! in_array($columnName, ['id', 'title', 'currency', 'amount', 'period', 'description', 'status'])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue     = $request->input('sSearch');

        $pricingDetails        = PricingModel::getPricingData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords          = PricingModel::count();
        $totalRecordwithFilter = PricingModel::getPricingDataTotal($searchValue);

        $data = [];
        foreach ($pricingDetails as $pricing) {
            $data[] = [
                'id'          => $pricing->id,
                'title'       => $pricing->title ?? '-',
                'currency'    => $pricing->currency ?? '-',
                'amount'      => $pricing->amount ?? '-',
                'period'      => $pricing->period ?? '-',
                'description' => $pricing->description ?? '-',
                'status'      => $pricing->status,
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
        PricingModel::whereIn('id', $ids)->update(['deleted' => 1]);
        return response()->json(['success' => true, 'message' => 'Selected Data deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $pricing         = PricingModel::findOrFail($id);
        $pricing->status = ! $pricing->status;
        $pricing->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    public function getActiveCount()
    {
        $activeCount = PricingModel::where('deleted', 0)->where('status', 1)->count();
        return response()->json(['count' => $activeCount]);
    }

    /**
     * Get alternatives for frontend
     */
    public function getpricingForFrontend()
    {
        try {
            // dd("asd");
            $items = PricingModel::where('deleted', 0)
                ->where('status', 1)
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Pricing retrieved successfully',
                'data'    => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving Pricing: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }
//   public function packageStore(Request $request)
// {
//     try {
//         $request->validate([
//             'firstName' => 'required|string|max:255',
//             'lastName' => 'required|string|max:255',
//             'email' => 'required|email|max:255|unique:inquiry_training,email',
//             'contactNumber' => 'nullable|digits_between:1,11|regex:/^[0-9]+$/',
//             'technologies' => 'required|string|max:255',
//             'description' => 'nullable|string',
//             'cv' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,csv|max:2048',
//         ], [
//             'email.unique' => 'Email must be unique.',
//             'contactNumber.digits_between' => 'Contact number must be up to 11 digits.',
//             'contactNumber.regex' => 'Contact number must contain only numbers.',
//         ]);

//         $fileName = null;
//         if ($request->hasFile('cv')) {
//             $file = $request->file('cv');
//             $fileName = $file->getClientOriginalName(); // store only name
//             $file->storeAs('uploads/training_files', $fileName, 'public');
//         }

//         $packageData = new TrainingPackage();
//         $packageData->first_name = $request->firstName;
//         $packageData->last_name = $request->lastName;
//         $packageData->email = $request->email;
//         $packageData->contact_number = $request->contactNumber;
//         $packageData->technologies = $request->technologies;
//         $packageData->description = $request->description;
//         $packageData->cv = $fileName;
//         $packageData->save();

//  Mail::to($request->email)->send(new NewsletterSubscribed($request->email));
//         return response()->json([
//             'success' => true,
//             'message' => 'Training submission saved successfully!',
//             'data' => $packageData,
//         ], 200);

//     } catch (ValidationException $e) {
//         return response()->json([
//             'success' => false,
//             'errors' => $e->errors(),
//         ], 422);

//     } catch (Exception $e) {
//         return response()->json([
//             'success' => false,
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }

    public function packageStore(Request $request)
    {
        try {
            $request->validate([
                'firstName'     => 'required|string|max:255',
                'lastName'      => 'required|string|max:255',
                'email'         => 'required|email|max:255|unique:inquiry_training,email',
                'contactNumber' => 'nullable|digits_between:1,11|regex:/^[0-9]+$/',
                'technologies'  => 'required|string|max:255',
                'description'   => 'nullable|string',
                'cv'            => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,csv|max:2048',
            ], [
                'email.unique'                 => 'Email must be unique.',
                'contactNumber.digits_between' => 'Contact number must be up to 11 digits.',
                'contactNumber.regex'          => 'Contact number must contain only numbers.',
            ]);

            $fileName = null;
            if ($request->hasFile('cv')) {
                $file     = $request->file('cv');
                $fileName = $file->getClientOriginalName();
                $file->storeAs('uploads/training_files', $fileName, 'public');
            }

            $packageData                 = new TrainingPackage();
            $packageData->first_name     = $request->firstName;
            $packageData->last_name      = $request->lastName;
            $packageData->email          = $request->email;
            $packageData->contact_number = $request->contactNumber;
            $packageData->technologies   = $request->technologies;
            $packageData->description    = $request->description;
            $packageData->cv             = $fileName;
            $packageData->save();

            $mailDetails = [
                'userName'     => $packageData->first_name . ' ' . $packageData->last_name,
                'email'        => $packageData->email,
                'technologies' => $packageData->technologies,
                'description'  => $packageData->description ?? 'N/A',
            ];

            $superAdminRole = Role::where('name', 'Super Admin')->first();

            if ($superAdminRole) {
                $superAdmins = User::where('role_id', $superAdminRole->id)->get();
            } else {
                $superAdmins = collect();
            }

            foreach ($superAdmins as $admin) {
                $mailDetail = \App\Models\MailDetail::create([
                    'user_id'      => $packageData->id,
                    'email_type'   => 'Training Submission',
                    'email_to'     => $admin->email,
                    'mail_details' => json_encode($mailDetails),
                ]);

                try {
                    Mail::to($admin->email)->queue(new \App\Mail\TrainingSubmission($mailDetails));
                    $mailDetail->update(['is_sent' => 1]);
                } catch (\Exception $e) {
                    $mailDetail->update(['is_sent' => 0]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Training submission saved successfully!',
                'data'    => $packageData,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}

<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\Parents;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ParentController extends Controller
{
    /**
     * Display Child And Parent listing page.
     * created by ns
     */

    public function index()
    {
        return view('parent.index');
    }

    /**
     * Fetch cities based on selected state.
     * created by ns
     */
    public function getCities(Request $request)
    {
        $response = Http::post(
            'https://countriesnow.space/api/v0.1/countries/state/cities',
            [
                'country' => 'India',
                'state'   => $request->state,
            ]
        );

        return response()->json($response->json()['data']);
    }

    /**
     * Display Child And Parent create form.
     * created by ns
     */
    public function create()
    {

        $states = State::orderBy('name')->get();
        return view('parent.create', compact('states'));
    }

    /**
     * Store Child And Parent data.
     * created by ns
     */
    public function store(Request $request)
    {

        $request->validate([
            'father_name'                => 'required|string|max:255',
            'mother_name'                => 'required|string|max:255',
            'contact_number'             => 'required|string|max:20',
            'alternative_contact_number' => 'nullable|string|max:20',
            'email'                      => 'nullable|email',
            'address_1'                  => 'required|string',
            'address_2'                  => 'nullable|string',
            'city'                       => 'required|string',
            'state'                      => 'required|string',
            'pincode'                    => 'required|string|max:10',
            'father_adhaar_card_image'   => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'mother_adhaar_card_image'   => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        $parent = Parents::create([
            'father_name'                => $request->father_name,
            'mother_name'                => $request->mother_name,
            'contact_number'             => $request->contact_number,
            'alternative_contact_number' => $request->alternative_contact_number,
            'email'                      => $request->email,
            'address_1'                  => $request->address_1,
            'address_2'                  => $request->address_2,
            'city'                       => $request->city,
            'state'                      => $request->state,
            'pincode'                    => $request->pincode,
            'status'                     => 0,
            'deleted'                    => 0,
        ]);

        $fatherAdhaar = $request->hasFile('father_adhaar_card_image')
            ? ImageHelper::upload($request, 'father_adhaar_card_image', 'parent', $parent->_id, [636, 424])
            : null;

        $motherAdhaar = $request->hasFile('mother_adhaar_card_image')
            ? ImageHelper::upload($request, 'mother_adhaar_card_image', 'parent', $parent->_id, [800, 600])
            : null;

        $parent->update([
            'father_adhaar_card_image' => $fatherAdhaar,
            'mother_adhaar_card_image' => $motherAdhaar,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Parent added successfully',
        ]);
    }

    /**
     * Display Child And Parent edit form.
     * created by ns
     */
    public function edit($id)
    {
        $child = Parents::where('_id', $id)
            ->where('deleted', 0)
            ->firstOrFail();

        $states = State::orderBy('name')->get();
        return view('parent.edit', compact('child', 'states'
        ));
    }

    /**
     * Update Child And Parent data.
     * created by ns
     */
    public function update(Request $request, $id)
    {
        $child = Parents::where('_id', $id)
            ->where('deleted', 0)
            ->firstOrFail();

        $request->validate([
            'father_name'                => 'required|string|max:255',
            'mother_name'                => 'required|string|max:255',
            'contact_number'             => 'required|string|max:20',
            'alternative_contact_number' => 'nullable|string|max:20',
            'email'                      => 'nullable|email',
            'address_1'                  => 'nullable|string',
            'address_2'                  => 'nullable|string',
            'city'                       => 'required|string',
            'state'                      => 'required|string',
            'pincode'                    => 'required|string|max:10',
            'father_adhaar_card_image'   => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'mother_adhaar_card_image'   => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        $child->update([
            'father_name'                => $request->father_name,
            'mother_name'                => $request->mother_name,

            'contact_number'             => $request->contact_number,
            'alternative_contact_number' => $request->alternative_contact_number,
            'email'                      => $request->email,
            'address_1'                  => $request->address_1,
            'address_2'                  => $request->address_2,
            'city'                       => $request->city,
            'state'                      => $request->state,
            'pincode'                    => $request->pincode,
        ]);

        if ($request->hasFile('father_adhaar_card_image')) {
            if ($child->father_adhaar_card_image && file_exists(public_path('storage/' . $child->father_adhaar_card_image))) {
                unlink(public_path('storage/' . $child->father_adhaar_card_image));
            }

            $newFatherImage = ImageHelper::upload(
                $request,
                'father_adhaar_card_image',
                'parents',
                $child->id,
                [636, 424]
            );

            $child->father_adhaar_card_image = $newFatherImage;
        }

        if ($request->hasFile('mother_adhaar_card_image')) {
            if ($child->mother_adhaar_card_image && file_exists(public_path('storage/' . $child->mother_adhaar_card_image))) {
                unlink(public_path('storage/' . $child->mother_adhaar_card_image));
            }

            $newMotherImage = ImageHelper::upload(
                $request,
                'mother_adhaar_card_image',
                'parents',
                $child->id,
                [636, 424]
            );

            $child->mother_adhaar_card_image = $newMotherImage;
        }
$child->save();
        return response()->json([
            'success' => true,
            'message' => 'Parent updated successfully',
        ]);
    }

    /**
     * Soft delete Child And Parent record.
     * created by ns
     */
    public function destroy($id)
    {
        $Parent          = Parents::findOrFail($id);
        $Parent->deleted = 1;
        $Parent->save();

        return response()->json([
            'success' => true,
            'message' => 'Parent deleted Successfully.',
        ]);
    }

    /**
     * Toggle Child And Parent active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $Parent         = Parents::findOrFail($id);
        $Parent->status = $Parent->status == 1 ? 0 : 1;
        $Parent->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active Child And Parent count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = Parents::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }


    public function parentAdhaarImage($id)
    {
        $parent = Parents::findOrFail($id);
        if ($parent->father_adhaar_card_image) {
            $imagePath = public_path($parent->father_adhaar_card_image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $parent->father_adhaar_card_image = null;
            $parent->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    public function motherAdhaarImage($id)
    {
        $parent = Parents::findOrFail($id);
        if ($parent->mother_adhaar_card_image) {
            $imagePath = public_path($parent->mother_adhaar_card_image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $parent->mother_adhaar_card_image = null;
            $parent->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    /**
     * Fetch Child And Parent list for DataTable.
     * created by ns
     */
    public function parentList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = $request->input('iDisplayStart');
        $rowperpage  = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        // Allowed columns for sorting
        if (! in_array($columnName, [
            'id',
            'father_name',
            'mother_name',
            'email',
            'state',
            'city',
            'pincode',
            'father_adhaar_card_image',
            'mother_adhaar_card_image',
            'contact_number',
            'alternative_contact_number',
            'status',
        ])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue     = $request->input('sSearch');

        // Get data from model
        $parentDetails = Parents::getParentData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $draw,
            $row,
            $rowperpage
        );

        $totalRecords          = Parents::count();
        $totalRecordwithFilter = Parents::getParentDataTotal($searchValue);

        $data = [];
        foreach ($parentDetails as $parent) {
            $data[] = [
                'id'                         => $parent->id,
                'father_name'                => $parent->father_name,
                'mother_name'                => $parent->mother_name,
                'email'                      => $parent->email,
                'city'                       => $parent->city,
                'state'                      => $parent->state,
                'pincode'                    => $parent->pincode,
                'contact_number'             => $parent->contact_number,
                'alternative_contact_number' => $parent->alternative_contact_number,
                'status'                     => $parent->status,
            ];
        }

        return response()->json([
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ]);
    }
}

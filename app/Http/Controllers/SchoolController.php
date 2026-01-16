<?php
namespace App\Http\Controllers;

use App\Models\School;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SchoolController extends Controller
{
    /**
     * Display school listing page.
     * created by ns
     */
    public function index()
    {
        return view('school.index');
    }

    /**
     * Display school create form.
     * created by ns
     */
    public function create()
    {
        $states = State::orderBy('name')->get();
        return view('school.create', compact('states'));
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
     * Fetch pincode list by city name.
     * created by ns
     */
    public function getPincode($city)
    {
        $response = Http::get(
            "https://api.postalpincode.in/postoffice/" . urlencode($city)
        );

        if ($response->successful()) {
            return response()->json($response->json()[0]['PostOffice']);
        }

        return response()->json([]);
    }

    /**
     * Store school data.
     * created by ns
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_code' => 'required|string|max:255|unique:schools,school_code',
            'phone'       => 'required|string|max:20',
            'email'       => 'required|email|max:255',
            'address'     => 'required|string',
            'city'        => 'required|string|max:255',
            'state'       => 'required|string|max:255',
            'pincode'     => 'required|string|max:10',
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
        ]);

        // default flags
        $validated['status']  = 0;
        $validated['deleted'] = 0;

        School::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'School created Successfully.',
        ]);
    }

    /**
     * Display school edit form.
     * created by ns
     */
    public function edit($id)
    {
        $school = School::findOrFail($id);
        $states = State::orderBy('name')->get();

        return view('school.edit', compact('school', 'states'));
    }

    /**
     * Update school data.
     * created by ns
     */
    public function update(Request $request, $id)
    {
        $school = School::findOrFail($id);

        $request->validate([
            'school_name' => 'required|string|max:255',
            'school_code' => 'required|string|max:255|unique:schools,school_code,' . $school->id,
            'phone'       => 'required|string|max:20',
            'email'       => 'required|email|max:255',
            'address'     => 'required|string',
            'city'        => 'required|string|max:255',
            'state'       => 'required|string|max:255',
            'pincode'     => 'required|string|max:10',
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
        ]);

        $school->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'School updated successfully.',
        ]);
    }

    /**
     * Soft delete school record.
     * created by ns
     */
    public function destroy($id)
    {
        $school          = School::findOrFail($id);
        $school->deleted = 1;
        $school->save();

        return response()->json([
            'success' => true,
            'message' => 'School deleted Successfully.',
        ]);
    }

    /**
     * Toggle school active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $school         = School::findOrFail($id);
        $school->status = $school->status == 1 ? 0 : 1;
        $school->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active school count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = School::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Fetch school list for DataTable.
     * created by ns
     */
    public function schoolList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = $request->input('iDisplayStart');
        $rowperpage  = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        if (! in_array($columnName, [
            'id',
            'school_name',
            'school_code',
            'phone',
            'email',
            'address',
            'city',
            'state',
            'pincode',
            'latitude',
            'longitude',
            'status',
        ])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue     = $request->input('sSearch');

        $schoolDetails         = School::getSchoolData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage);
        $totalRecords          = School::count();
        $totalRecordwithFilter = School::getSchoolDataTotal($searchValue);

        $data = [];
        foreach ($schoolDetails as $school) {
            $data[] = [
                'id'          => $school->id,
                'school_name' => $school->school_name,
                'school_code' => $school->school_code,
                'phone'       => $school->phone,
                'email'       => $school->email,
                'address'     => $school->address,
                'city'        => $school->city,
                'state'       => $school->state,
                'pincode'     => $school->pincode,
                'latitude'    => $school->latitude,
                'longitude'   => $school->longitude,
                'status'      => $school->status,
            ];
        }

        return response()->json([
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ]);
    }

      /**
     * Soft delete multiple School.
     * created by ns
     */
    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided.',
            ]);
        }

        School::whereIn('_id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected id deleted Successfully.',
        ]);
    }
}

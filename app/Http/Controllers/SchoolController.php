<?php
namespace App\Http\Controllers;

use App\Models\School;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SchoolController extends Controller
{
    public function index()
    {
        return view('school.index');
    }

    public function create()
    {
        $states = State::orderBy('name')->get();
        return view('school.create', compact('states'));
    }

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

    // ✅ defaults explicitly set
    $validated['status']  = 0;
    $validated['deleted'] = 0;

    School::create($validated);

    return response()->json([
        'success' => true,
        'message' => 'School created Successfully.'
    ]);
}


    public function edit($id)
    {
        $school = School::findOrFail($id);
        $states = State::orderBy('name')->get();

        return view('school.edit', compact('school', 'states'));
    }

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

        $data = $request->all();

        $school->update($data);

        return response()->json([
            'success' => true,
            'message' => 'School updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $school          = School::findOrFail($id);
        $school->deleted = 1;
        $school->save();

        return response()->json(['success' => true, 'message' => 'School deleted Successfully.']);
    }

    public function toggleStatus($id)
    {
        $school         = School::findOrFail($id);
      $school->status = $school->status == 1 ? 0 : 1;
        $school->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

   public function getActiveCount()
{
    $activeCount = School::where('deleted', 0)
        ->where('status', 1)
        ->count();

    return response()->json(['count' => $activeCount]);
}
    public function schoolList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = $request->input('iDisplayStart');
        $rowperpage  = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        if (! in_array($columnName, ['id', 'school_name', 'school_code', 'phone', 'email', 'address ', 'city', 'state', 'pincode', 'latitude', 'longitude', 'status'])) {
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
                // 'user_id'             => $school->user_id,
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

        $output = [
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ];

        return response()->json($output);
    }

}

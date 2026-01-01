<?php
namespace App\Http\Controllers;

use App\Models\ChildParent;
use App\Models\Route;
use App\Models\School;
use App\Models\State;
use App\Models\StopPickup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChildParentController extends Controller
{
    /**
     * Display Child And Parent listing page.
     * created by ns
     */

    public function index()
    {
        return view('child_parent.index');
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
        // Schools
        $schools = School::where('deleted', 0)
            ->select('_id', 'school_name')
            ->get();

        // Routes
        $routes = Route::where('deleted', 0)
            ->select('_id', 'name')
            ->get();

        // Pickup & Stops
        $stops = StopPickup::where('deleted', 0)
            ->select('_id', 'pickup_name', 'stop_name')
            ->get();

        return view('child_parent.create', compact('schools', 'routes', 'stops', 'states'));
    }

    /**
     * Store Child And Parent data.
     * created by ns
     */
    public function store(Request $request)
    {

        $request->validate([
            'child_name'                 => 'required|string|max:255',
            'gender'                     => 'required|string',
            'date_of_birth'              => 'required|date',
            'class'                      => 'required|string|max:50',
            'section'                    => 'required|string|max:10',
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
            'school_id'                  => 'required',
            'pickup_id'                  => 'required',
            'stop_id'                    => 'required',
            'route_id'                   => 'required',
        ]);

        // 🔹 Fetch related names using IDs
        $school = School::where('_id', $request->school_id)
            ->where('deleted', 0)
            ->firstOrFail();

        $route = Route::where('_id', $request->route_id)
            ->where('deleted', 0)
            ->firstOrFail();

        $stopPickup = StopPickup::where('_id', $request->pickup_id)
            ->where('deleted', 0)
            ->firstOrFail();

        ChildParent::create([
            'child_name'                 => $request->child_name,
            'gender'                     => $request->gender,
            'date_of_birth'              => $request->date_of_birth,
            'class'                      => $request->class,
            'section'                    => $request->section,
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
            'school_id'                  => $school->school_name,
            'route_id'                   => $route->name,
            'pickup_id'                  => $stopPickup->pickup_name,
            'stop_id'                    => $stopPickup->stop_name,
            'status'                     => 0,
            'deleted'                    => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Child added successfully',
        ]);
    }

    /**
     * Display Child And Parent edit form.
     * created by ns
     */
    public function edit($id)
    {
        $child = ChildParent::where('_id', $id)
            ->where('deleted', 0)
            ->firstOrFail();

        $states  = State::orderBy('name')->get();
        $schools = School::where('deleted', 0)
            ->select('_id', 'school_name')
            ->get();
        $routes = Route::where('deleted', 0)
            ->select('_id', 'name')
            ->get();
        $stops = StopPickup::where('deleted', 0)
            ->select('_id', 'pickup_name', 'stop_name')
            ->get();
        return view('child_parent.edit', compact('child', 'schools', 'routes', 'stops', 'states'
        ));
    }

    /**
     * Update Child And Parent data.
     * created by ns
     */
    public function update(Request $request, $id)
    {
        $child = ChildParent::where('_id', $id)
            ->where('deleted', 0)
            ->firstOrFail();

        $request->validate([
            'child_name'                 => 'required|string|max:255',
            'gender'                     => 'required|string',
            'date_of_birth'              => 'required|date',
            'class'                      => 'required|string|max:50',
            'section'                    => 'required|string|max:10',
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
            'school_id'                  => 'required',
            'route_id'                   => 'required',
            'pickup_id'                  => 'required',
            'stop_id'                    => 'required',
        ]);

        // 🔹 Fetch related records by ID
        $school = School::where('_id', $request->school_id)
            ->where('deleted', 0)
            ->firstOrFail();

        $route = Route::where('_id', $request->route_id)
            ->where('deleted', 0)
            ->firstOrFail();

        $stopPickup = StopPickup::where('_id', $request->pickup_id)
            ->where('deleted', 0)
            ->firstOrFail();

        $child->update([
            'child_name'                 => $request->child_name,
            'gender'                     => $request->gender,
            'date_of_birth'              => $request->date_of_birth,
            'class'                      => $request->class,
            'section'                    => $request->section,

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
            'school_id'                  => $school->school_name,
            'route_id'                   => $route->name,
            'pickup_id'                  => $stopPickup->pickup_name,
            'stop_id'                    => $stopPickup->stop_name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Child updated successfully',
        ]);
    }

    /**
     * Soft delete Child And Parent record.
     * created by ns
     */
    public function destroy($id)
    {
        $childParent          = ChildParent::findOrFail($id);
        $childParent->deleted = 1;
        $childParent->save();

        return response()->json([
            'success' => true,
            'message' => 'Child Parent deleted Successfully.',
        ]);
    }

    /**
     * Toggle Child And Parent active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $childParent         = ChildParent::findOrFail($id);
        $childParent->status = $childParent->status == 1 ? 0 : 1;
        $childParent->save();

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
        $activeCount = ChildParent::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Fetch Child And Parent list for DataTable.
     * created by ns
     */
    public function childParentList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = $request->input('iDisplayStart');
        $rowperpage  = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        // Allowed columns for sorting
        if (! in_array($columnName, [
            'id',
            'child_name',
            'gender',
            'date_of_birth',
            'class',
            'section',
            'father_name',
            'mother_name',
            'contact_number',
            'email',
            'city',
            'state',
            'pincode',
            'school_id',
            'route_id',
            'pickup_id',
            'stop_id',
            'status',
        ])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue     = $request->input('sSearch');

        // Get data from model
        $childDetails = ChildParent::getChildData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $draw,
            $row,
            $rowperpage
        );

        $totalRecords          = ChildParent::count();
        $totalRecordwithFilter = ChildParent::getChildDataTotal($searchValue);

        $data = [];
        foreach ($childDetails as $child) {
            $data[] = [
                'id'                         => $child->id,
                'child_name'                 => $child->child_name,
                'gender'                     => $child->gender,
                'date_of_birth'              => $child->date_of_birth,
                'class'                      => $child->class,
                'section'                    => $child->section,
                'father_name'                => $child->father_name,
                'mother_name'                => $child->mother_name,
                'contact_number'             => $child->contact_number,
                'alternative_contact_number' => $child->alternative_contact_number,
                'email'                      => $child->email,
                'city'                       => $child->city,
                'state'                      => $child->state,
                'pincode'                    => $child->pincode,
                'school_id'                  => $child->school_id,
                'route_id'                   => $child->route_id,
                'pickup_id'                  => $child->pickup_id,
                'stop_id'                    => $child->stop_id,
                'status'                     => $child->status,
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

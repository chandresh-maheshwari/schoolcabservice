<?php
namespace App\Http\Controllers;

use App\Models\School;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $request->validate([
            'state' => 'required|string|max:255',
        ]);

        $state = trim((string) $request->state);

        $cacheKey = 'india_state_cities_' . md5(strtolower($state));
        $cities = Cache::remember($cacheKey, now()->addDays(7), function () use ($state) {
            return $this->fetchCitiesByState($state);
        });

        return response()->json([
            'success' => true,
            'cities'  => $cities,
        ]);
    }

    private function fetchCitiesByState(string $state): array
    {
        $endpoint = 'https://countriesnow.space/api/v0.1/countries/state/cities';

        $requests = [
            Http::acceptJson()
                ->connectTimeout(6)
                ->timeout(15)
                ->retry(2, 300)
                ->post($endpoint, [
                    'country' => 'India',
                    'state'   => $state,
                ]),
            Http::asForm()
                ->acceptJson()
                ->connectTimeout(6)
                ->timeout(15)
                ->retry(1, 300)
                ->post($endpoint, [
                    'country' => 'India',
                    'state'   => $state,
                ]),
        ];

        foreach ($requests as $response) {
            if (! $response->successful()) {
                continue;
            }

            $payload = $response->json();
            $cities  = data_get($payload, 'data', []);

            if (is_array($cities)) {
                $cities = array_values(array_unique(array_filter(array_map(function ($city) {
                    return trim((string) $city);
                }, $cities))));
                sort($cities);
                return $cities;
            }
        }

        Log::warning('School getCities failed to load cities', ['state' => $state]);
        return [];
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
            'phone'       => 'required|digits_between:10,11',
            'email'       => 'required|email|max:255',
            'address'     => 'required|string',
            'city'        => 'required|string|max:255',
            'state'       => 'required|string|max:255',
            'pincode'     => 'required|string|max:10',
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
        ]);

        // default flags
        $validated['user_id'] = $this->resolvePersistedUserId($request);
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
        $query = School::query();
        $this->applyActorScope($query);
        $school = $query->findOrFail($id);

        $states = State::orderBy('name')->get();

        return view('school.edit', compact('school', 'states'));
    }

    /**
     * Update school data.
     * created by ns
     */
    public function update(Request $request, $id)
    {
        $query = School::query();
        $this->applyActorScope($query, $request);
        $school = $query->findOrFail($id);

        $request->validate([
            'school_name' => 'required|string|max:255',
            'school_code' => 'required|string|max:255|unique:schools,school_code,' . $school->id,
            'phone'       => 'required|digits_between:10,11',
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
        $query = School::query();
        $this->applyActorScope($query);
        $school = $query->findOrFail($id);

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
        $query = School::query();
        $this->applyActorScope($query);
        $school = $query->findOrFail($id);

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
        $query = School::where('deleted', 0)->where('status', true);
        $this->applyActorScope($query);
        $activeCount = $query->count();

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

        $query = School::where('deleted', 0);
        $this->applyActorScope($query, $request);
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('school_name', 'like', "%$searchValue%")
                    ->orWhere('school_code', 'like', "%$searchValue%")
                    ->orWhere('phone', 'like', "%$searchValue%")
                    ->orWhere('email', 'like', "%$searchValue%")
                    ->orWhere('city', 'like', "%$searchValue%")
                    ->orWhere('state', 'like', "%$searchValue%")
                    ->orWhere('pincode', 'like', "%$searchValue%")
                    ->orWhere('latitude', 'like', "%$searchValue%")
                    ->orWhere('longitude', 'like', "%$searchValue%");
            });
        }

        $totalRecordwithFilter = (clone $query)->count();
        $schoolDetails         = $query
            ->orderBy($columnName, in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'desc')
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();

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

        $query = School::whereIn('id', $ids);
        $this->applyActorScope($query, $request);
        $query->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected id deleted Successfully.',
        ]);
    }
}

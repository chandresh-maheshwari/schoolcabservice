<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\Parents;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

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

        Log::warning('Parent getCities failed to load cities', ['state' => $state]);
        return [];
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
    DB::beginTransaction();

    try {

        $request->validate([
            'father_name'                => 'required|string|max:255',
            'mother_name'                => 'required|string|max:255',
            'contact_number'             => 'required|digits_between:10,11',
            'alternative_contact_number' => 'nullable|digits_between:10,11',
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
            'user_id'                    => $this->resolveActorUserId($request),
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

        $fatherAdhaar = null;
        if ($request->hasFile('father_adhaar_card_image')) {
            $fatherAdhaar = ImageHelper::upload(
                $request,
                'father_adhaar_card_image',
                'parent',
                $parent->id,
                [636, 424],
                null,
                false
            );

            if (!$fatherAdhaar) {
                throw new \Exception('Father Aadhaar upload failed');
            }
        }

        $motherAdhaar = null;
        if ($request->hasFile('mother_adhaar_card_image')) {
            $motherAdhaar = ImageHelper::upload(
                $request,
                'mother_adhaar_card_image',
                'parent',
                $parent->id,
                [800, 600],
                null,
                false
            );

            if (!$motherAdhaar) {
                throw new \Exception('Mother Aadhaar upload failed');
            }
        }

        $parent->update([
            'father_adhaar_card_image' => $fatherAdhaar,
            'mother_adhaar_card_image' => $motherAdhaar,
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Parent added successfully',
        ], 200);

    } catch (ValidationException $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => implode('<br>', collect($e->errors())->flatten()->toArray()),
        ], 200);

    } catch (\Exception $e) {

        DB::rollBack();

        Log::error('Parent Store Error', [
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 200);
    }
}

    /**
     * Display Child And Parent edit form.
     * created by ns
     */
    public function edit($id)
    {
        $child = Parents::where('id', $id)
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
    DB::beginTransaction();

    try {

        $child = Parents::where('id', $id)
            ->where('deleted', 0)
            ->firstOrFail();

        $request->validate([
            'father_name'                => 'required|string|max:255',
            'mother_name'                => 'required|string|max:255',
            'contact_number'             => 'required|digits_between:10,11',
            'alternative_contact_number' => 'nullable|digits_between:10,11',
            'email'                      => 'nullable|email',
            'address_1'                  => 'nullable|string',
            'address_2'                  => 'nullable|string',
            'city'                       => 'required|string',
            'state'                      => 'required|string',
            'pincode'                    => 'required|string|max:10',
            'father_adhaar_card_image'   => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'mother_adhaar_card_image'   => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        $oldFatherImage = $child->father_adhaar_card_image;
        $oldMotherImage = $child->mother_adhaar_card_image;

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

            $newFatherImage = ImageHelper::upload(
                $request,
                'father_adhaar_card_image',
                'parent',
                $child->id,
                [636, 424],
                null,
                false
            );

            if (!$newFatherImage) {
                throw new \Exception('Father Adhaar Card Image upload failed');
            }

            $child->father_adhaar_card_image = $newFatherImage;
        }

        if ($request->hasFile('mother_adhaar_card_image')) {

            $newMotherImage = ImageHelper::upload(
                $request,
                'mother_adhaar_card_image',
                'parent',
                $child->id,
                [636, 424],
                null,
                false
            );

            if (!$newMotherImage) {
                throw new \Exception('Mother Adhaar Card Image upload failed');
            }

            $child->mother_adhaar_card_image = $newMotherImage;
        }

        $child->save();

        DB::commit();

        if (isset($newFatherImage) && $oldFatherImage &&
            file_exists(public_path('storage/' . $oldFatherImage))) {
            unlink(public_path('storage/' . $oldFatherImage));
        }

        if (isset($newMotherImage) && $oldMotherImage &&
            file_exists(public_path('storage/' . $oldMotherImage))) {
            unlink(public_path('storage/' . $oldMotherImage));
        }

        return response()->json([
            'success' => true,
            'message' => 'Parent updated successfully',
        ], 200);

    } catch (ValidationException $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => implode('<br>', collect($e->errors())->flatten()->toArray()),
        ], 200);

    } catch (\Exception $e) {

        DB::rollBack();

        Log::error('Parent Update Error', [
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 200);
    }
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

    /**
     * Delete father adhaar image.
     * created by ns
     */
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

    /**
     * Delete mother adhaar image.
     * created by ns
     */
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

        $query = Parents::where(function ($q) {
            $q->where('deleted', 0)->orWhereNull('deleted');
        });
        $this->applyActorScope($query, $request);
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('father_name', 'like', "%$searchValue%")
                    ->orWhere('mother_name', 'like', "%$searchValue%")
                    ->orWhere('email', 'like', "%$searchValue%")
                    ->orWhere('city', 'like', "%$searchValue%")
                    ->orWhere('state', 'like', "%$searchValue%")
                    ->orWhere('pincode', 'like', "%$searchValue%")
                    ->orWhere('contact_number', 'like', "%$searchValue%")
                    ->orWhere('alternative_contact_number', 'like', "%$searchValue%");
            });
        }

        $totalRecordwithFilter = (clone $query)->count();
        $parentDetails = $query
            ->orderBy($columnName, in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'desc')
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();

        $data = [];
        $schoolNameMap = $this->getSchoolNameMapForUserIds($parentDetails->pluck('user_id')->all());
        foreach ($parentDetails as $parent) {
            $data[] = [
                'id'                         => $parent->id,
                'school_name'                => $schoolNameMap[$parent->user_id] ?? '-',
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

    /**
     * Multi delete Child And Parent records.
     * created by ns
     */
    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided for deletion.',
            ]);
        }

        Parents::whereIn('id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected parents deleted successfully',
        ]);
    }
}

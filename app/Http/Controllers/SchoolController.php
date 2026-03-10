<?php
namespace App\Http\Controllers;

use App\Models\School;
use App\Models\State;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Mail\SchoolCredentialsMail;

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
     * Display deleted school listing page (trash).
     */
    public function trash()
    {
        return view('school.trash');
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
            'email'       => 'required|email|max:255|unique:users,email',
            'password'    => 'required|string|min:8|max:50',
            'address'     => 'required|string',
            'city'        => 'required|string|max:255',
            'state'       => 'required|string|max:255',
            'pincode'     => 'required|string|max:10',
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
        ]);

        $schoolRole = Role::firstOrCreate(['name' => 'School']);

        $schoolUser = User::create([
            'first_name' => $validated['school_name'],
            'last_name'  => 'School',
            'mobile'     => $validated['phone'],
            'email'      => $validated['email'],
            'username'   => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'role_id'    => $schoolRole->id,
        ]);

        // default flags
        $validated['user_id'] = $schoolUser->id;
        $validated['slug']    = $this->uniqueSchoolSlug((string) $validated['school_name']);
        $validated['status']  = 0;
        $validated['deleted'] = 0;

        $plainPassword = (string) $validated['password'];
        unset($validated['password']);

        $school = School::create($validated);

        try {
            $loginUrl = url('/' . $school->slug);
            Mail::to($schoolUser->email)->send(
                new SchoolCredentialsMail(
                    (string) $school->school_name,
                    $loginUrl,
                    $plainPassword
                )
            );
        } catch (\Throwable $e) {
            Log::warning('School credentials email send failed', [
                'school_id' => $school->id,
                'user_id'   => $schoolUser->id,
                'error'     => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'School created Successfully.',
            'slug'    => $school->slug,
        ]);
    }

    private function uniqueSchoolSlug(string $schoolName): string
    {
        $base = Str::slug($schoolName);
        if ($base === '') {
            $base = 'school';
        }

        $slug = $base;
        $i    = 2;
        while (School::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
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

        $validated = $request->validate([
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
            'primary_color'   => ['nullable', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'secondary_color' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'header_title'    => 'nullable|string|max:255',
            'logo'            => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'logo_mini'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'favicon'         => 'nullable|image|mimes:png,ico,jpg,jpeg,webp|max:1024',
        ]);

        $data = collect($validated)->only([
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
            'primary_color',
            'secondary_color',
            'header_title',
        ])->toArray();

        $stripStoragePrefix = function (?string $path): ?string {
            $path = trim((string) $path);
            if ($path === '') {
                return null;
            }

            $path = ltrim($path, '/');
            if (Str::startsWith($path, 'storage/')) {
                $path = Str::after($path, 'storage/');
            }

            return $path !== '' ? $path : null;
        };

        if ($request->hasFile('logo')) {
            $newPath = $request->file('logo')->storePublicly("schools/{$school->id}/branding", 'public');
            $data['logo_path'] = $newPath;

            $oldPath = $stripStoragePrefix($school->logo_path);
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        if ($request->hasFile('logo_mini')) {
            $newPath = $request->file('logo_mini')->storePublicly("schools/{$school->id}/branding", 'public');
            $data['logo_mini_path'] = $newPath;

            $oldPath = $stripStoragePrefix($school->logo_mini_path);
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        if ($request->hasFile('favicon')) {
            $newPath = $request->file('favicon')->storePublicly("schools/{$school->id}/branding", 'public');
            $data['favicon_path'] = $newPath;

            $oldPath = $stripStoragePrefix($school->favicon_path);
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $school->update($data);

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

        DB::transaction(function () use ($school) {
            $this->cascadeDeleteSchoolData($school);
        });

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
     * Fetch deleted school list for DataTable (trash).
     */
    public function deletedSchoolList(Request $request)
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

        $query = School::where('deleted', 1);
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
        $schools = $query->get();

        DB::transaction(function () use ($schools) {
            foreach ($schools as $school) {
                $this->cascadeDeleteSchoolData($school);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Selected id deleted Successfully.',
        ]);
    }

    /**
     * Restore a soft-deleted school and all related data.
     */
    public function restore(Request $request, $id)
    {
        $query = School::where('deleted', 1);
        $this->applyActorScope($query, $request);
        $school = $query->findOrFail($id);

        DB::transaction(function () use ($school) {
            $this->cascadeRestoreSchoolData($school);
        });

        return response()->json([
            'success' => true,
            'message' => 'School restored Successfully.',
        ]);
    }

    /**
     * Permanently delete a soft-deleted school after exporting all related data to an Excel-readable file.
     */
    public function forceDelete(Request $request, $id)
    {
        if (! $this->isPrivilegedActor($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $school = School::where('deleted', 1)->findOrFail($id);

        $driver = DB::getDriverName();
        $exportRelativePath = DB::transaction(function () use ($school, $driver) {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }

            try {
                $exportRelativePath = $this->exportDeletedSchoolDataToXls($school);
                $this->cascadeForceDeleteSchoolData($school);
                return $exportRelativePath;
            } finally {
                if ($driver === 'mysql') {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                }
            }
        });

        return response()->json([
            'success'      => true,
            'message'      => 'School permanently deleted Successfully.',
            'download_url' => route('school.export.download', ['file' => basename($exportRelativePath)]),
        ]);
    }

    /**
     * Download a previously generated school export.
     */
    public function downloadExport(Request $request, string $file)
    {
        if (! $this->isPrivilegedActor($request)) {
            abort(403);
        }

        $file = basename($file);
        if (! str_ends_with(strtolower($file), '.xls')) {
            abort(404);
        }

        $relativePath = 'exports/schools/' . $file;
        if (! Storage::disk('local')->exists($relativePath)) {
            abort(404);
        }

        $absolutePath = storage_path('app/' . $relativePath);
        return response()->download($absolutePath, $file, [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }

    private function cascadeDeleteSchoolData(School $school): void
    {
        $school->deleted = 1;
        $school->save();

        if (is_numeric($school->user_id) && (int) $school->user_id > 0) {
            DB::table('users')
                ->where('id', (int) $school->user_id)
                ->update([
                    'deleted'     => 1,
                    'updated_at'  => now(),
                    'remember_token' => null,
                ]);
        }

        $userId = (int) ($school->user_id ?: 0);
        $schoolId = (int) $school->id;

        $tablesByUserId = [
            'vehicle_types',
            'vehicles',
            'drivers',
            'routes',
            'package_details',
            'stops_pickup',
            'driver_vehicle_histories',
            'parents',
            'children',
            'ratings',
            'emergency_incidents',
            'bookings',
        ];

        foreach ($tablesByUserId as $table) {
            $this->markTableDeleted($table, function ($query) use ($table, $userId) {
                if ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
                    $query->where('user_id', $userId);
                    return true;
                }

                return false;
            });
        }

        $this->markTableDeleted('bookings', function ($query) use ($schoolId) {
            if ($schoolId > 0) {
                $query->where('school_id', $schoolId);
                return true;
            }

            return false;
        });
    }

    private function cascadeForceDeleteSchoolData(School $school): void
    {
        $userId   = (int) ($school->user_id ?: 0);
        $schoolId = (int) $school->id;

        // Delete in dependency-safe order (children first). Example: vehicles.vehicle_type_id has RESTRICT.
        $tablesByUserId = [
            'bookings',
            'emergency_incidents',
            'ratings',
            'children',
            'parents',
            'driver_vehicle_histories',
            'stops_pickup',
            'package_details',
            'routes',
            'drivers',
            'vehicles',
            'vehicle_types',
        ];

        foreach ($tablesByUserId as $table) {
            $this->forceDeleteFromTable($table, function ($query) use ($table, $userId) {
                if ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
                    $query->where('user_id', $userId);
                    return true;
                }

                return false;
            });
        }

        $this->forceDeleteFromTable('bookings', function ($query) use ($schoolId) {
            if ($schoolId > 0) {
                $query->where('school_id', $schoolId);
                return true;
            }

            return false;
        });

        if ($userId > 0) {
            DB::table('users')->where('id', $userId)->delete();
        }

        $school->delete();
    }

    private function exportDeletedSchoolDataToXls(School $school): string
    {
        $schoolId = (int) $school->id;
        $userId   = (int) ($school->user_id ?: 0);

        $timestamp = now()->format('Ymd_His');
        $safeSlug  = preg_replace('/[^a-z0-9_-]+/i', '-', (string) ($school->slug ?: $school->school_code ?: $school->id));
        $filename  = "school_{$schoolId}_{$safeSlug}_{$timestamp}.xls";
        $relative  = "exports/schools/{$filename}";

        $sections = [];

        $sections[] = $this->htmlTableSection('schools', DB::table('schools')->where('id', $schoolId)->get());
        if ($userId > 0 && Schema::hasTable('users')) {
            $sections[] = $this->htmlTableSection('users', DB::table('users')->where('id', $userId)->get());
        }

        $tablesByUserId = [
            'vehicle_types',
            'vehicles',
            'drivers',
            'routes',
            'package_details',
            'stops_pickup',
            'driver_vehicle_histories',
            'parents',
            'children',
            'ratings',
            'emergency_incidents',
            'bookings',
        ];

        foreach ($tablesByUserId as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if ($userId <= 0 || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            $rows = DB::table($table)->where('user_id', $userId)->get();
            $sections[] = $this->htmlTableSection($table, $rows);
        }

        if (Schema::hasTable('bookings') && $schoolId > 0 && Schema::hasColumn('bookings', 'school_id')) {
            $rows = DB::table('bookings')->where('school_id', $schoolId)->get();
            $sections[] = $this->htmlTableSection('bookings (by school_id)', $rows);
        }

        $title = 'Deleted School Export - ' . e($school->school_name ?: ('School #' . $schoolId));

        $html = '<html><head><meta charset="utf-8"></head><body>';
        $html .= "<h2>{$title}</h2>";
        $html .= '<p>Generated at: ' . e(now()->toDateTimeString()) . '</p>';
        $html .= implode('<br><br>', $sections);
        $html .= '</body></html>';

        Storage::disk('local')->put($relative, $html);
        return $relative;
    }

    private function htmlTableSection(string $label, $rows): string
    {
        $rowsArray = collect($rows)->map(function ($row) {
            return (array) $row;
        })->values();

        $html = '<h3>' . e($label) . '</h3>';

        if ($rowsArray->isEmpty()) {
            return $html . '<p>No rows</p>';
        }

        $columns = array_keys($rowsArray->first());
        $html .= '<table border="1" cellpadding="4" cellspacing="0"><thead><tr>';
        foreach ($columns as $col) {
            $html .= '<th>' . e((string) $col) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rowsArray as $row) {
            $html .= '<tr>';
            foreach ($columns as $col) {
                $value = $row[$col] ?? null;
                if (is_bool($value)) {
                    $value = $value ? 1 : 0;
                } elseif (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                $html .= '<td>' . e((string) ($value ?? '')) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    private function cascadeRestoreSchoolData(School $school): void
    {
        $school->deleted = 0;
        $school->save();

        if (is_numeric($school->user_id) && (int) $school->user_id > 0) {
            DB::table('users')
                ->where('id', (int) $school->user_id)
                ->update([
                    'deleted'    => 0,
                    'updated_at' => now(),
                ]);
        }

        $userId   = (int) ($school->user_id ?: 0);
        $schoolId = (int) $school->id;

        $tablesByUserId = [
            'vehicle_types',
            'vehicles',
            'drivers',
            'routes',
            'package_details',
            'stops_pickup',
            'driver_vehicle_histories',
            'parents',
            'children',
            'ratings',
            'emergency_incidents',
            'bookings',
        ];

        foreach ($tablesByUserId as $table) {
            $this->markTableRestored($table, function ($query) use ($table, $userId) {
                if ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
                    $query->where('user_id', $userId);
                    return true;
                }

                return false;
            });
        }

        $this->markTableRestored('bookings', function ($query) use ($schoolId) {
            if ($schoolId > 0) {
                $query->where('school_id', $schoolId);
                return true;
            }

            return false;
        });
    }

    private function markTableDeleted(string $table, \Closure $where): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $query = DB::table($table);
        $applied = $where($query);
        if ($applied !== true) {
            return;
        }

        $updates = [];

        if (Schema::hasColumn($table, 'deleted')) {
            $updates['deleted'] = 1;
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $updates['deleted_at'] = now();
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $updates['updated_at'] = now();
        }

        if (empty($updates)) {
            return;
        }

        $query->update($updates);
    }

    private function markTableRestored(string $table, \Closure $where): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $query   = DB::table($table);
        $applied = $where($query);
        if ($applied !== true) {
            return;
        }

        $updates = [];

        if (Schema::hasColumn($table, 'deleted')) {
            $updates['deleted'] = 0;
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $updates['deleted_at'] = null;
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $updates['updated_at'] = now();
        }

        if (empty($updates)) {
            return;
        }

        $query->update($updates);
    }

    private function forceDeleteFromTable(string $table, \Closure $where): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $query   = DB::table($table);
        $applied = $where($query);
        if ($applied !== true) {
            return;
        }

        $query->delete();
    }
}

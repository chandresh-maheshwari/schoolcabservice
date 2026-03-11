<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\Child;
use App\Models\Parents;
use App\Models\Route;
use App\Models\School;
use App\Models\StopPickup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ChildController extends Controller
{
    private function applySchoolPanelScope($query, Request $request)
    {
        $currentSchool = $request->attributes->get('current_school');
        if (is_object($currentSchool) && isset($currentSchool->id) && is_numeric($currentSchool->id)) {
            return $query->where('school_id', (int) $currentSchool->id);
        }

        return $this->applyActorScope($query, $request);
    }

    private function resolveSchoolIdForSchoolUser(Request $request): ?int
    {
        $actor = Auth::user();
        if (! $actor || ! method_exists($actor, 'isSchool') || ! $actor->isSchool()) {
            return null;
        }

        $schoolSlug = (string) $request->route('schoolSlug');
        $schoolSlug = trim($schoolSlug);

        $schoolQuery = School::query()->where('deleted', 0);
        if ($schoolSlug !== '') {
            $schoolQuery->where('slug', $schoolSlug);
        } else {
            $schoolQuery->where('user_id', (int) $actor->id);
        }

        $schoolId = $schoolQuery->orderByDesc('id')->value('id');
        return $schoolId ? (int) $schoolId : null;
    }

    public function index()
    {
        return view('child.index');
    }

    public function create()
    {
        $parents = Parents::select('id', 'father_name')
            ->where('deleted', 0)
            ->get();

        $isSchoolUser = Auth::user() && method_exists(Auth::user(), 'isSchool') && Auth::user()->isSchool();
        $defaultSchoolId = $this->resolveSchoolIdForSchoolUser(request());
        $schoolDataQuery = School::select('id', 'school_name')->where('deleted', 0);
        if ($isSchoolUser && $defaultSchoolId) {
            $schoolDataQuery->where('id', $defaultSchoolId);
        }
        $schoolData = $schoolDataQuery->get();
        $defaultSchoolName = $defaultSchoolId
            ? (string) School::where('id', $defaultSchoolId)->value('school_name')
            : null;

        $routeData = Route::select('id', 'name')
            ->get();

        $stopPickData = StopPickup::select('id', 'pickup_name', 'stop_name')
            ->where('deleted', 0)
            ->get();
        return view('child.create', compact(
            'parents',
            'schoolData',
            'routeData',
            'stopPickData',
            'isSchoolUser',
            'defaultSchoolId',
            'defaultSchoolName'
        ));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $actor = Auth::user();
            $isSchoolUser = $actor && method_exists($actor, 'isSchool') && $actor->isSchool();

            $rules = [
                'child_name'    => 'required|string|max:255',
                'parent_id'     => 'nullable|integer|exists:parents,id',
                'pickup_name'   => 'required',
                'stop_name'     => 'required|string',
                'route_id'      => 'required',
                'gender'        => 'required|string',
                'date_of_birth' => 'required|date',
                'class'         => 'required|string|max:255',
                'section'       => 'required|string|max:20',
            ];

            if (! $isSchoolUser) {
                $rules['school_id'] = 'required|string|max:255';
            }

            $request->validate($rules);

            $schoolId = $isSchoolUser ? $this->resolveSchoolIdForSchoolUser($request) : $request->school_id;
            if ($isSchoolUser && ! $schoolId) {
                throw new \Exception('School not resolved for this user.');
            }

            $child = Child::create([
                'user_id'       => $this->resolveActorUserId($request),
                'child_name'    => $request->child_name,
                'parent_id'     => $request->input('parent_id') ?: null,
                'school_id'     => $schoolId,
                'pickup_name'   => $request->pickup_name,
                'stop_name'     => $request->stop_name,
                'route_id'      => $request->route_id,
                'gender'        => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'class'         => $request->class,
                'section'       => $request->section,
                'status'        => 0,
                'deleted'       => 0,
            ]);

            $image = null;
            if ($request->hasFile('image')) {
                $image = ImageHelper::upload($request, 'image', 'child', $child->id, [636, 424], null, false);
                if (! $image) {
                    throw new \Exception('Child image upload failed');
                }
            }

            $childAdhaarImage = null;
            if ($request->hasFile('child_adhaar_card_image')) {
                $childAdhaarImage = ImageHelper::upload(
                    $request,
                    'child_adhaar_card_image',
                    'child',
                    $child->id,
                    [800, 600], null,
                false

                );
                if (! $childAdhaarImage) {
                    throw new \Exception('Child Aadhaar upload failed');
                }
            }

            $child->update([
                'image'                   => $image,
                'child_adhaar_card_image' => $childAdhaarImage,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Child created successfully',
                'id'      => $child->id,
            ], 200);

        } catch (ValidationException $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => implode('<br>', collect($e->errors())->flatten()->toArray()),
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Child Store Error', [
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

    // Supports both admin/api routes: `/admin/child/{id}/edit` and school routes:
    // `/{schoolSlug}/child/{id}/edit`.
    public function edit($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $request = request();
        $query = Child::where('id', $id)
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            });
        $this->applySchoolPanelScope($query, $request);
        $child = $query->firstOrFail();

        // Parents list
        $parents = Parents::select('id', 'father_name')
            ->where('deleted', 0)
            ->get();

        // School list
        $isSchoolUser = Auth::user() && method_exists(Auth::user(), 'isSchool') && Auth::user()->isSchool();
        $defaultSchoolId = $this->resolveSchoolIdForSchoolUser($request);
        $schoolDataQuery = School::select('id', 'school_name')->where('deleted', 0);
        if ($isSchoolUser && $defaultSchoolId) {
            $schoolDataQuery->where('id', $defaultSchoolId);
        }
        $schoolData = $schoolDataQuery->get();
        $defaultSchoolName = $defaultSchoolId
            ? (string) School::where('id', $defaultSchoolId)->value('school_name')
            : null;
        // Route list
        $routeData = Route::select('id', 'name')
        // ->where('deleted', 0)
            ->get();

        // Pickup + Stop list
        $stopPickData = StopPickup::select('id', 'pickup_name', 'stop_name')
            ->where('deleted', 0)
            ->get();

        return view('child.edit', compact(
            'child',
            'parents',
            'schoolData',
            'routeData',
            'stopPickData',
            'isSchoolUser',
            'defaultSchoolId',
            'defaultSchoolName'
        ));
    }

    // Supports both admin/api routes and school routes under `{schoolSlug}` prefix.
    public function update(Request $request, $schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        DB::beginTransaction();

        try {

            $actor = Auth::user();
            $isSchoolUser = $actor && method_exists($actor, 'isSchool') && $actor->isSchool();

            $rules = [
                'child_name'    => 'required|string|max:255',
                'parent_id'     => 'nullable|integer|exists:parents,id',
                'pickup_name'   => 'required',
                'stop_name'     => 'required|string',
                'route_id'      => 'required',
                'gender'        => 'required|string',
                'date_of_birth' => 'required|date',
                'class'         => 'required|string|max:255',
                'section'       => 'required|string|max:20',
            ];

            if (! $isSchoolUser) {
                $rules['school_id'] = 'required|string|max:255';
            }

            $request->validate($rules);

            $query = Child::where('id', $id)
                ->where(function ($q) {
                    $q->where('deleted', 0)->orWhereNull('deleted');
                });
            $this->applySchoolPanelScope($query, $request);
            $child = $query->firstOrFail();

            $oldImage  = $child->image;
            $oldAdhaar = $child->child_adhaar_card_image;

            $schoolId = $isSchoolUser ? $this->resolveSchoolIdForSchoolUser($request) : $request->school_id;
            if ($isSchoolUser && ! $schoolId) {
                throw new \Exception('School not resolved for this user.');
            }

            $payload = [
                'child_name'    => $request->child_name,
                'school_id'     => $schoolId,
                'pickup_name'   => $request->pickup_name,
                'stop_name'     => $request->stop_name,
                'route_id'      => $request->route_id,
                'gender'        => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'class'         => $request->class,
                'section'       => $request->section,
            ];

            if ($request->filled('parent_id')) {
                $payload['parent_id'] = (int) $request->input('parent_id');
            }

            $child->update($payload);

            if ($request->hasFile('image')) {

                $newImage = ImageHelper::upload(
                    $request,
                    'image',
                    'child',
                    $child->id,
                    [636, 424],
                     null,
                false

                );

                if (! $newImage) {
                    throw new \Exception('Child image upload failed');
                }

                $child->image = $newImage;
            }

            if ($request->hasFile('child_adhaar_card_image')) {

                $newAdhaarImage = ImageHelper::upload(
                    $request,
                    'child_adhaar_card_image',
                    'child',
                    $child->id,
                    [800, 600],
                     null,
                false

                );

                if (! $newAdhaarImage) {
                    throw new \Exception('Child Aadhaar image upload failed');
                }

                $child->child_adhaar_card_image = $newAdhaarImage;
            }

            $child->save();

            DB::commit();

            if (isset($newImage) && $oldImage && file_exists(public_path('storage/' . $oldImage))) {
                unlink(public_path('storage/' . $oldImage));
            }

            if (isset($newAdhaarImage) && $oldAdhaar &&
                file_exists(public_path('storage/' . $oldAdhaar))) {
                unlink(public_path('storage/' . $oldAdhaar));
            }

            return response()->json([
                'success' => true,
                'message' => 'Child updated successfully',
            ], 200);

        } catch (ValidationException $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => implode('<br>', collect($e->errors())->flatten()->toArray()),
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Child Update Error', [
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
     * Soft delete Child record.
     * created by ns
     */

    // Supports both admin/api routes and school routes under `{schoolSlug}` prefix.
    public function destroy($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $request = request();
        $query = Child::where('id', (int) $id);
        $this->applySchoolPanelScope($query, $request);
        $child = $query->firstOrFail();
        $child->deleted = 1;
        $child->save();

        return response()->json(['success' => true, 'message' => 'Child deleted Successfully.']);
    }

    /**
     * Toggle Child active/inactive status.
     * created by ns
     */
    // Supports both admin/api routes and school routes under `{schoolSlug}` prefix.
    public function toggleStatus($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $request = request();
        $query = Child::where('id', (int) $id);
        $this->applySchoolPanelScope($query, $request);
        $child = $query->firstOrFail();
        $child->status = $child->status == 1 ? 0 : 1;
        $child->save();

        return response()->json(['success' => true, 'message' => 'Status Updated Successfully.']);
    }

    /**
     * Get active Child count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = Child::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Delete Child profile image.
     * created by ns
     */
    public function childImage($id)
    {
        $child = Child::findOrFail($id);
        if ($child->image) {
            $imagePath = public_path($child->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $child->image = null;
            $child->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    /**
     * Delete Adhaar Card image for child.
     * created by ns
     */
    public function childAdhaarImage($id)
    {
        $child = Child::findOrFail($id);
        if ($child->child_adhaar_card_image) {
            $imagePath = public_path($child->child_adhaar_card_image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $child->child_adhaar_card_image = null;
            $child->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    /**
     * Fetch child list for DataTable.
     * created by ns
     */
    public function childList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = $request->input('iDisplayStart');
        $rowperpage  = $request->input('iDisplayLength');
        $indexColumn = $request->input('iSortCol_0');
        $columnName  = $request->input('mDataProp_' . $indexColumn);

        $allowedColumns = [
            'id',
            'child_name',
            'gender',
            'class',
            'section',
            'status',
            'date_of_birth',
        ];

        if (! in_array($columnName, $allowedColumns, true)) {
            $columnName = 'id';
        }

        $columnSortOrder = in_array($request->input('sSortDir_0'), ['asc', 'desc'], true)
            ? $request->input('sSortDir_0')
            : 'desc';

        $searchValue = $request->input('sSearch');

        $query = Child::with(['parent', 'school', 'route'])
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            });

        $this->applyActorScope($query, $request);
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('child_name', 'like', "%$searchValue%")
                    ->orWhere('gender', 'like', "%$searchValue%")
                    ->orWhere('class', 'like', "%$searchValue%")
                    ->orWhere('section', 'like', "%$searchValue%")
                    ->orWhereHas('parent', function ($parentQuery) use ($searchValue) {
                        $parentQuery->where('father_name', 'like', "%$searchValue%")
                            ->orWhere('mother_name', 'like', "%$searchValue%")
                            ->orWhere('contact_number', 'like', "%$searchValue%");
                    })
                    ->orWhereHas('school', function ($schoolQuery) use ($searchValue) {
                        $schoolQuery->where('school_name', 'like', "%$searchValue%");
                    })
                    ->orWhereHas('route', function ($routeQuery) use ($searchValue) {
                        $routeQuery->where('name', 'like', "%$searchValue%");
                    });
            });
        }

        $totalRecordwithFilter = (clone $query)->count();

        $childDetails = $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();

        $data = [];
        foreach ($childDetails as $child) {
            $data[] = [
                'id'            => $child->id,
                'child_name'    => $child->child_name,
                'father_name'   => optional($child->parent)->father_name,
                'school_name'   => optional($child->school)->school_name,
                'name'          => optional($child->route)->name ?? '-',
                'gender'        => $child->gender,
                'date_of_birth' => $child->date_of_birth
                    ? \Illuminate\Support\Carbon::parse($child->date_of_birth)->format('Y-m-d')
                    : null,
                'class'         => $child->class,
                'section'       => $child->section,
                'status'        => $child->status,
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
     * Soft delete multiple Child records.
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

        Child::whereIn('id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected children deleted successfully',
        ]);
    }

    /**
     * Attach a parent to an existing child record.
     */
    public function setParent(Request $request, $id)
    {
        $request->validate([
            'parent_id' => 'required|integer|exists:parents,id',
        ]);

        $query = Child::where('id', (int) $id)->where(function ($q) {
            $q->where('deleted', 0)->orWhereNull('deleted');
        });
        $this->applyActorScope($query, $request);

        $child = $query->firstOrFail();
        $child->parent_id = (int) $request->input('parent_id');
        $child->save();

        return response()->json([
            'success' => true,
            'message' => 'Parent linked successfully.',
        ]);
    }
}

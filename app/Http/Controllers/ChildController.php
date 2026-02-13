<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\Child;
use App\Models\Parents;
use App\Models\Route;
use App\Models\School;
use App\Models\StopPickup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ChildController extends Controller
{
    public function index()
    {
        return view('child.index');
    }

    public function create()
    {
        $parents = Parents::select('id', 'father_name')
            ->where('deleted', 0)
            ->get();

        $schoolData = School::select('id', 'school_name')
            ->where('deleted', 0)
            ->get();

        $routeData = Route::select('id', 'name')
            ->get();

        $stopPickData = StopPickup::select('id', 'pickup_name', 'stop_name')
            ->where('deleted', 0)
            ->get();
        return view('child.create', compact('parents', 'schoolData', 'routeData', 'stopPickData'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'child_name'    => 'required|string|max:255',
                'parent_id'     => 'required|string|max:255',
                'school_id'     => 'required|string|max:255',
                'pickup_name'   => 'required',
                'stop_name'     => 'required|string',
                'route_id'      => 'required',
                'gender'        => 'required|string',
                'date_of_birth' => 'required|date',
                'class'         => 'required|string|max:255',
                'section'       => 'required|string|max:20',
            ]);

            $child = Child::create([
                'child_name'    => $request->child_name,
                'parent_id'     => $request->parent_id,
                'school_id'     => $request->school_id,
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

    public function edit($id)
    {
        // Child record
        $child = Child::where('id', $id)
            ->where('deleted', 0)
            ->firstOrFail();

        // Parents list
        $parents = Parents::select('id', 'father_name')
            ->where('deleted', 0)
            ->get();

        // School list
        $schoolData = School::select('id', 'school_name')
            ->where('deleted', 0)
            ->get();
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
            'stopPickData'
        ));
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'child_name'    => 'required|string|max:255',
                'parent_id'     => 'required|string|max:255',
                'school_id'     => 'required|string|max:255',
                'pickup_name'   => 'required',
                'stop_name'     => 'required|string',
                'route_id'      => 'required',
                'gender'        => 'required|string',
                'date_of_birth' => 'required|date',
                'class'         => 'required|string|max:255',
                'section'       => 'required|string|max:20',
            ]);

            $child = Child::where('id', $id)
                ->where('deleted', 0)
                ->firstOrFail();

            $oldImage  = $child->image;
            $oldAdhaar = $child->child_adhaar_card_image;

            $child->update([
                'child_name'    => $request->child_name,
                'parent_id'     => $request->parent_id,
                'school_id'     => $request->school_id,
                'pickup_name'   => $request->pickup_name,
                'stop_name'     => $request->stop_name,
                'route_id'      => $request->route_id,
                'gender'        => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'class'         => $request->class,
                'section'       => $request->section,
            ]);

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

    public function destroy($id)
    {
        $child          = Child::findOrFail($id);
        $child->deleted = 1;
        $child->save();

        return response()->json(['success' => true, 'message' => 'Child deleted Successfully.']);
    }

    /**
     * Toggle Child active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $child         = Child::findOrFail($id);
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

        if (! in_array($columnName, [
            'gender',
            'class',
            'section',
            'status',
        ])) {
            $columnName = 'id';
        }

        $columnSortOrder = $request->input('sSortDir_0');
        $searchValue     = $request->input('sSearch');

        $childDetails = Child::getChildData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $draw,
            $row,
            $rowperpage
        );

        $totalRecords          = Child::count();
        $totalRecordwithFilter = Child::getChildDataTotal($searchValue);

        $data = [];
        foreach ($childDetails as $child) {
            $data[] = [
                'id'            => $child->id,
                'child_name'    => $child->child_name,
                'father_name'   => optional($child->parent)->father_name,
                'school_name'   => optional($child->school)->school_name,
                'name'          => $child->route_id ?? '-',
                'gender'        => $child->gender,
                'date_of_birth' => optional($child->date_of_birth)->format('Y-m-d'),
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
}

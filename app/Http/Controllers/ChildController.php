<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\Child;
use App\Models\Parents;
use App\Models\Route;
use App\Models\School;
use App\Models\StopPickup;
use Illuminate\Http\Request;

class ChildController extends Controller
{
    public function index()
    {
        return view('child.index');
    }

    public function create()
    {
        $parents = Parents::select('father_name')
            ->where('deleted', 0)
            ->get();

        $schoolData = School::select('school_name')
            ->where('deleted', 0)
            ->get();

        $routeData = Route::select('name')
            ->where('deleted', 0)
            ->get();

        $stopPickData = StopPickup::select('pickup_name', 'stop_name')
            ->where('deleted', 0)
            ->get();
        return view('child.create', compact('parents', 'schoolData', 'routeData', 'stopPickData'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'parent_id'     => 'required|string|max:255',
            'school_id'     => 'required|string|max:255',
            'pickup_name'   => 'required',
            'stop_name'     => 'required|string',
            'route_id'      => 'required',
            'gender'        => 'required|string',
            'date_of_birth' => 'required|date|max:255',
            'class'         => 'required|string|max:255',
            'section'       => 'required|string|max:20',
        ]);

        try {
            $child = Child::create([
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

            $Image = $request->hasFile('image')
                ? ImageHelper::upload($request, 'image', 'child', $child->_id, [636, 424])
                : null;

            $childAdhaarImage = $request->hasFile('child_adhaar_card_image')
                ? ImageHelper::upload($request, 'child_adhaar_card_image', 'child', $child->_id, [800, 600])
                : null;

            $child->update([
                'image'                   => $Image,
                'child_adhaar_card_image' => $childAdhaarImage,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Child created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function edit($id)
    {
        // Child record
        $child = Child::where('_id', $id)
            ->where('deleted', 0)
            ->firstOrFail();

        // Parents list
        $parents = Parents::select('_id', 'father_name')
            ->where('deleted', 0)
            ->get();

        // School list
        $schoolData = School::select('_id', 'school_name')
            ->where('deleted', 0)
            ->get();

        // Route list
        $routeData = Route::select('_id', 'name')
            ->where('deleted', 0)
            ->get();

        // Pickup + Stop list
        $stopPickData = StopPickup::select('pickup_name', 'stop_name')
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
        $request->validate([
            'parent_id'     => 'required|string|max:255',
            'school_id'     => 'required|string|max:255',
            'pickup_name'   => 'required',
            'stop_name'     => 'required|string',
            'route_id'      => 'required',
            'gender'        => 'required|string',
            'date_of_birth' => 'required|date|max:255',
            'class'         => 'required|string|max:255',
            'section'       => 'required|string|max:20',
        ]);

        try {
            $child = Child::where('_id', $id)
                ->where('deleted', 0)
                ->firstOrFail();

            $child->update([
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

            /* ================= IMAGE UPDATE ================= */

            // 🔹 Child Image
            if ($request->hasFile('image')) {

                // delete old image
                if ($child->image && file_exists(public_path('storage/' . $child->image))) {
                    unlink(public_path('storage/' . $child->image));
                }

                $newImage = ImageHelper::upload(
                    $request,
                    'image',
                    'child',
                    $child->_id,
                    [636, 424]
                );

                $child->image = $newImage;
            }

            // 🔹 Adhaar Image
            if ($request->hasFile('child_adhaar_card_image')) {

                if ($child->child_adhaar_card_image &&
                    file_exists(public_path('storage/' . $child->child_adhaar_card_image))) {
                    unlink(public_path('storage/' . $child->child_adhaar_card_image));
                }

                $newAdhaarImage = ImageHelper::upload(
                    $request,
                    'child_adhaar_card_image',
                    'child',
                    $child->_id,
                    [800, 600]
                );

                $child->child_adhaar_card_image = $newAdhaarImage;
            }

            // 🔥 IMPORTANT
            $child->save();

            return response()->json([
                'success' => true,
                'message' => 'Child updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 422);
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
            $child->license_image = null;
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
            $columnName = '_id';
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

        Child::whereIn('_id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected children deleted successfully',
        ]);
    }
}

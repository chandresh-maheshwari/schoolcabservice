<?php
namespace App\Http\Controllers\Frontend;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\BenefitSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BenefitSectionController extends Controller
{
    /**
     * Display benefit section listing page.
     * created by ns
     */
    public function index()
    {
        return view('cms.benefit_section.index');
    }

    /**
     * Display benefit section create form.
     * created by ns
     */
    public function create()
    {
        return view('cms.benefit_section.create');
    }

    /**
     * Store benefit section data.
     * created by ns
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate(
                [
                    'name'        => 'required|string|max:255',
                    'short_des'   => 'required|string|max:255',
                    'description' => 'required|string',
                    'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=750,min_height=680',
                ],
                [
                    'image.dimensions' => 'Image must be at least 750 × 680 pixels.',
                ]
            );

            $benefitSection = BenefitSection::create([
                'name'        => $request->name,
                'short_des'   => $request->short_des,
                'description' => $request->description,
                'status'      => 0,
                'deleted'     => 0,
            ]);

            if ($request->hasFile('image')) {
                $benefitImage = ImageHelper::upload(
                    $request,
                    'image',
                    'benefitSection',
                    $benefitSection->id,
                    [750, 680],
                       null,
                false
                );

                $benefitSection->image = $benefitImage;
                $benefitSection->save();
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Benefit Section added successfully',
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->first()[0],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Display benefit section edit form.
     * created by ns
     */
    public function edit($id)
    {
        $benefitSection = BenefitSection::findOrFail($id);
        return view('cms.benefit_section.edit', compact('benefitSection'));
    }

    /**
     * Update benefit section data.
     *  created by ns
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $benefitSection = BenefitSection::findOrFail($id);

            $request->validate(
                [
                    'name'        => 'required|string|max:255',
                    'short_des'   => 'required|string|max:255',
                    'description' => 'required|string',
                    'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=750,min_height=680',
                ],
                [
                    'image.dimensions' => 'Image must be at least 750 × 680 pixels.',
                ]
            );

            $oldImage = $benefitSection->image;

            $data = [
                'name'        => $request->name,
                'short_des'   => $request->short_des,
                'description' => $request->description,
            ];

            if ($request->hasFile('image')) {

                $newBenefitImage = ImageHelper::upload(
                    $request,
                    'image',
                    'benefitSection',
                    $benefitSection->id,
                    [750, 680],
                       null,
                false
                );

                $data['image'] = $newBenefitImage;
            }

            $benefitSection->update($data);

            DB::commit();
            if (
                isset($newBenefitImage) &&
                $oldImage &&
                file_exists(public_path('storage/benefitSection/' . $oldImage))
            ) {
                unlink(public_path('storage/benefitSection/' . $oldImage));
            }

            return response()->json([
                'success' => true,
                'message' => 'Benefit Section updated successfully',
            ], 200);

        } catch (ValidationException $e) {

            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->first()[0],
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Delete benefit section image.
     * created by ns
     */
    public function benefitImage($id)
    {
        $benefitSection = BenefitSection::findOrFail($id);
        if ($benefitSection->image) {
            $imagePath = public_path($benefitSection->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $benefitSection->image = null;
            $benefitSection->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    /**
     * Get benefit section list for datatable.
     * created by ns
     */
    public function benefitList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, 'id');

        // Allowed columns (Benefit fields)
        $allowedColumns = [
            'id',
            'name',
            'short_des',
            'description',
            'image',
            'status',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';

        $columnSortOrder = in_array(
            $request->input('sSortDir_0'),
            ['asc', 'desc']
        ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        // Data
        $benefits = BenefitSection::getBenefitData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $row,
            $rowperpage
        );

        // Counts
        $totalRecords          = BenefitSection::where('deleted', 0)->count();
        $totalRecordwithFilter = BenefitSection::getBenefitDataTotal($searchValue);

        $data = [];

        foreach ($benefits as $benefit) {
            $data[] = [
                'id'          => (string) $benefit->id,
                'name'        => $benefit->name,
                'short_des'   => $benefit->short_des,
                'description' => $benefit->description,
                'image'       => $benefit->image,
                'status'      => $benefit->status,
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
 * Remove the specified benefit section from storage.
 * created by ns
 */
    public function destroy($id)
    {
        $benefitSection          = BenefitSection::findOrFail($id);
        $benefitSection->deleted = 1;
        $benefitSection->save();

        return response()->json([
            'success' => true,
            'message' => 'Benefit Section deleted Successfully.',
        ]);
    }

    /**
     * Toggle benefit section active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $benefitSection         = BenefitSection::findOrFail($id);
        $benefitSection->status = $benefitSection->status == 1 ? 0 : 1;
        $benefitSection->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active benefit section count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = BenefitSection::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Delete multiple benefit sections.
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

        BenefitSection::whereIn('id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected id deleted Successfully.',
        ]);
    }
}


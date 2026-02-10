<?php
namespace App\Http\Controllers\Frontend;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\TestimonialSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TestimonialSectionController extends Controller
{
    /**
     * Display testimonial section listing page.
     * created by ns
     */
    public function index()
    {
        return view('cms.testimonial_section.index');
    }
/**
 * Display testimonial section create form.
 * created by ns
 */
    public function create()
    {
        return view('cms.testimonial_section.create');
    }

    /**
     * Store testimonial section data.
     * created by ns
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'name'          => 'required|string|max:255',
                'description'   => 'required|string',
                'profile_image' => 'required|image|mimes:jpg,jpeg,png,webp',
                'designation'   => 'required|string|max:255',
                'tagline'       => 'required|string|max:255',
                'rating'        => 'required|integer|min:1|max:5',
            ]);

            $testimonialSection = TestimonialSection::create([
                'name'        => $validated['name'],
                'description' => $validated['description'],
                'designation' => $validated['designation'],
                'tagline'     => $validated['tagline'],
                'rating'      => $validated['rating'],
                'status'      => 0,
                'deleted'     => 0,
            ]);

            $testimonialImage = ImageHelper::upload(
                $request,
                'profile_image',
                'testimonialSection',
                $testimonialSection->id,
                [300, 300]
            );

            if (! $testimonialImage) {
                throw new \Exception('Profile image upload failed');
            }

            $testimonialSection->update([
                'profile_image' => $testimonialImage,
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Testimonial Section added successfully',
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'type'    => 'validation',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Testimonial Store Error', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'type'    => 'exception',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Display testimonial section edit form.
     * created by ns
     */
    public function edit($id)
    {
        $testimonialSection = TestimonialSection::findOrFail($id);
        return view('cms.testimonial_section.edit', compact('testimonialSection'));
    }
    /**
     * Update testimonial section data.
     *  created by ns
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $testimonialSection = TestimonialSection::findOrFail($id);

            $validated = $request->validate([
                'name'          => 'required|string|max:255',
                'description'   => 'required|string',
                'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
                'designation'   => 'required|string|max:255',
                'tagline'       => 'required|string|max:255',
                'rating'        => 'required|integer|min:1|max:5',
            ]);

            $testimonialSection->update([
                'name'        => $validated['name'],
                'description' => $validated['description'],
                'designation' => $validated['designation'],
                'tagline'     => $validated['tagline'],
                'rating'      => $validated['rating'],
            ]);

            if ($request->hasFile('profile_image')) {
                $testimonialImage = ImageHelper::upload(
                    $request,
                    'profile_image',
                    'testimonialSection',
                    $testimonialSection->id,
                    [300, 300]
                );

                if (! $testimonialImage) {
                    throw new \Exception('Profile image upload failed');
                }

                $testimonialSection->update([
                    'profile_image' => $testimonialImage,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Testimonial Section updated successfully',
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'type'    => 'validation',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Testimonial Update Error', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'type'    => 'exception',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get testimonial section data for datatable.
     * created by ns
     */
    public function testimonialList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, 'id');

        // Allowed columns (Testimonial fields)
        $allowedColumns = [
            'id',
            'name',
            'designation',
            'tagline',
            'description',
            'profile_image',
            'rating',
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
        $testimonials = TestimonialSection::getTestimonialData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $row,
            $rowperpage
        );

        // Counts
        $totalRecords          = TestimonialSection::where('deleted', 0)->count();
        $totalRecordwithFilter =
        TestimonialSection::getTestimonialDataTotal($searchValue);

        $data = [];

        foreach ($testimonials as $testimonial) {
            $data[] = [
                'id'            => (string) $testimonial->id,
                'name'          => $testimonial->name,
                'designation'   => $testimonial->designation,
                'tagline'       => $testimonial->tagline,
                'description'   => $testimonial->description,
                'profile_image' => $testimonial->profile_image,
                'rating'        => $testimonial->rating,
                'status'        => $testimonial->status,
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
     * Delete testimonial section.
     * created by ns
     */
    public function destroy($id)
    {
        $testimonialSection          = TestimonialSection::findOrFail($id);
        $testimonialSection->deleted = 1;
        $testimonialSection->save();

        return response()->json([
            'success' => true,
            'message' => 'Testimonial Section deleted Successfully.',
        ]);
    }

    /**
     * Toggle testimonial section active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $testimonialSection         = TestimonialSection::findOrFail($id);
        $testimonialSection->status = $testimonialSection->status == 1 ? 0 : 1;
        $testimonialSection->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active testimonial section count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = TestimonialSection::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    public function testimonialImage($id)
    {
        $testimonial = TestimonialSection::findOrFail($id);

        if (! empty($testimonial->profile_image)) {

            $imagePath = public_path($testimonial->profile_image);

            if (file_exists($imagePath)) {
                unlink($imagePath);
            }

            $testimonial->profile_image = null;
            $testimonial->save();

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No image to delete.',
        ]);
    }

    /**
     * Delete multiple testimonial sections.
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

        TestimonialSection::whereIn('id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected id deleted Successfully.',
        ]);
    }
}

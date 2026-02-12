<?php
namespace App\Http\Controllers\Frontend;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\ClientSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientSectionController extends Controller
{
    /**
     * Display client section listing page.
     * created by ns
     */
    public function index()
    {
        return view('cms.client_section.index');
    }

    /**
     * Display client section create form.
     * created by ns
     */
    public function create()
    {
        return view('cms.client_section.create');
    }

    /**
     * Store client section data.
     * created by ns
     */
    public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $request->validate(
            [
                'name'  => 'nullable|string|max:255',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=180,min_height=100',
            ],
            [
                'image.dimensions' => 'Client image must be at least 180 × 100 pixels.',
            ]
        );

        $clientSection = ClientSection::create([
            'name'    => $request->name,
            'status'  => 0,
            'deleted' => 0,
        ]);

        if ($request->hasFile('image')) {
            $clientImage = ImageHelper::upload(
                $request,
                'image',
                'clientSection',
                $clientSection->id,
                [180, 100]
            );

            $clientSection->image = $clientImage;
            $clientSection->save();
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Client Section added successfully',
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
     * Edit client section data.
     * created by ns
     */
    public function edit($id)
    {
        $clientSection = ClientSection::findOrFail($id);
        return view('cms.client_section.edit', compact('clientSection'));
    }

    /**
     * Update client section data.
     *  created by ns
     */
   public function update(Request $request, $id)
{
    DB::beginTransaction();

    try {

        $clientSection = ClientSection::findOrFail($id);

        $request->validate(
            [
                'name'  => 'nullable|string|max:255',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|dimensions:min_width=180,min_height=100',
            ],
            [
                'image.dimensions' => 'Client image must be at least 180 × 100 pixels.',
            ]
        );

        $oldImage = $clientSection->image;

        $data = $request->only(['name']);

        if ($request->hasFile('image')) {

            $newImage = ImageHelper::upload(
                $request,
                'image',
                'clientSection',
                $clientSection->id,
                [180, 100]
            );

            $data['image'] = $newImage;
        }

        $clientSection->update($data);

        DB::commit();

        if (
            isset($newImage) &&
            $oldImage &&
            file_exists(public_path('storage/clientSection/' . $oldImage))
        ) {
            unlink(public_path('storage/clientSection/' . $oldImage));
        }

        return response()->json([
            'success' => true,
            'message' => 'Client Section updated successfully',
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
     * Delete client section data.
     * created by ns
     */
     public function destroy($id)
    {
        $clientSection = ClientSection::findOrFail($id);
        $clientSection->deleted = 1;
        $clientSection->save();

        return response()->json([
            'success' => true,
            'message' => 'Client Section deleted Successfully.',
        ]);
    }

    /**
     * Toggle client section active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $clientSection = ClientSection::findOrFail($id);
        $clientSection->status = $clientSection->status == 1 ? 0 : 1;
        $clientSection->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active client section count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = ClientSection::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Delete client section image.
     * created by ns
     */
    public function clientImage($id)
    {
        $clientSection = ClientSection::findOrFail($id);
        if ($clientSection->image) {
            $imagePath = public_path($clientSection->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $clientSection->image = null;
            $clientSection->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

/**
 * Client section list for datatable.
 * created by ns
 */
    public function clientSectionList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, 'id');

        // Allowed columns
        $allowedColumns = [
            'id',
            'name',
            'image',
            'status',
            'created_at',
            'updated_at',
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
        $clientSections = ClientSection::getClientSectionData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $row,
            $rowperpage
        );

        // Counts
        $totalRecords          = ClientSection::where('deleted', 0)->count();
        $totalRecordwithFilter = ClientSection::getClientSectionDataTotal($searchValue);

        $data = [];

        foreach ($clientSections as $client) {
            $data[] = [
                'id'     => (string) $client->id,
                'name'   => $client->name,
                'image'  => $client->image,
                'status' => $client->status,
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
     * Delete multiple client sections.
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

        ClientSection::whereIn('id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected id deleted Successfully.',
        ]);
    }
}

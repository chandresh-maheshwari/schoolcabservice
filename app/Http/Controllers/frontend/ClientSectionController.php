<?php
namespace App\Http\Controllers\Frontend;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\ClientSection;
use Illuminate\Http\Request;

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

        $request->validate([
            'name'  => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        $clientSection = ClientSection::create([
            'name'    => $request->name,
            'status'  => 0,
            'deleted' => 0,
        ]);

        $clientImage = $request->hasFile('image')
            ? ImageHelper::upload($request, 'image', 'clientSection', $clientSection->id, [180, 100])
            : null;

        $clientSection->update([
            'image' => $clientImage,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client Section added successfully',
        ]);
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
        $clientSection = ClientSection::findOrFail($id);

        $request->validate([
            'name'  => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        $data = [
            'name' => $request->name,
        ];

        if ($request->hasFile('image')) {
            if (
                $clientSection->image &&
                file_exists(public_path('storage/clientSection/' . $clientSection->image))
            ) {
                unlink(public_path('storage/clientSection/' . $clientSection->image));
            }
            $newClientImage = ImageHelper::upload(
                $request,
                'image',
                'clientSection',
                $clientSection->id,
                [180, 100]
            );

            $data['image'] = $newClientImage;
        }

        $clientSection->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Client Section updated successfully',
        ]);
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
}

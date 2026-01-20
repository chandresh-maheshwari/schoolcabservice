<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Display service listing page.
     * created by ns
     */
    public function index()
    {
        return view('cms.service.index');
    }

    /**
     * Display service create form.
     * created by ns
     */
    public function create()
    {
        return view('cms.service.create');
    }
    /**
     * Store service data.
     * created by ns
     */
    public function store(Request $request)
    {
        $request->validate([
            'icon'        => 'required|string|max:255',
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        Service::create([
            'icon'        => $request->icon,
            'name'        => $request->name,
            'description' => $request->description,
            'status'      => 0,
            'deleted'     => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'About Section added successfully',
        ]);
    }
    /**
     * Edit service data.
     * created by ns
     */
    public function edit($id)
    {
        $service = Service::where('deleted', 0)->findOrFail($id);

        return view('cms.service.edit', compact('service'));
    }
    /**
     * Update service data.
     * created by ns
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'icon'        => 'required|string|max:255',
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $service = Service::where('deleted', 0)->findOrFail($id);

        $service->update([
            'icon'        => $request->icon,
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully',
        ]);
    }

    /**
     * Soft delete service data.
     * created by ns
     */
    public function destroy($id)
    {
        $serviceData          = Service::findOrFail($id);
        $serviceData->deleted = 1;
        $serviceData->save();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted Successfully.',
        ]);
    }

    /**
     * Toggle service active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $service         = Service::findOrFail($id);
        $service->status = $service->status == 1 ? 0 : 1;
        $service->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active service count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = Service::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Service listing datatable data.
     * created by ns
     */

    public function serviceList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, '_id');

        // Allowed columns (AboutSection fields)
        $allowedColumns = [
            '_id',
            'icon',
            'name',
            'description',
            'status',
            'created_at',
            'updated_at',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : '_id';

        $columnSortOrder = in_array(
            $request->input('sSortDir_0'),
            ['asc', 'desc']
        ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        // Data
        $serviceData = Service::getServiceData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $row,
            $rowperpage
        );

        // Counts
        $totalRecords          = Service::where('deleted_at', 0)->count();
        $totalRecordwithFilter = Service::getServiceDataTotal($searchValue);

        $data = [];

        foreach ($serviceData as $service) {
            $data[] = [
                'id'          => (string) $service->_id,
                'icon'        => $service->icon,
                'name'        => $service->name,
                'description' => $service->description,
                'status'      => $service->status,

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

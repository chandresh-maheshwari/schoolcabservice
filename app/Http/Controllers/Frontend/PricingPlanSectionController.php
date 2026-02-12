<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PricingPlanSection;
use Illuminate\Http\Request;

class PricingPlanSectionController extends Controller
{
    /**
     * Display pricing plan listing page.
     * created by ns
     */
    public function index()
    {
        return view('cms.price_section.index');
    }

    /**
     * Display pricing plan create form.
     * created by ns
     */
    public function create()
    {
        return view('cms.price_section.create');
    }

    /**
     * Store pricing plan data.
     *
     * created by ns
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'           => 'required|string|max:255',
            'plan_icon'       => 'required|string|max:255',
            'currency_icon'   => 'required|string|max:255',
            'amount'          => 'required|integer|min:1',
            'period'          => 'required|string|max:255',
            'description'     => 'required|string',
            'button_name'     => 'required|string|max:255',
            'button_link'     => 'required|url|max:255',
            'is_most_popular' => 'in:yes,no',
        ]);

        if ($request->is_most_popular === 'yes') {
            PricingPlanSection::where('is_most_popular', 'yes')
                ->update(['is_most_popular' => 'no']);
        }

        PricingPlanSection::create([
            'title'           => $request->title,
            'plan_icon'       => $request->plan_icon,
            'currency_icon'   => $request->currency_icon,
            'amount'          => $request->amount,
            'period'          => $request->period,
            'description'     => $request->description,
            'button_name'     => $request->button_name,
            'button_link'     => $request->button_link,
            'is_most_popular' => $request->is_most_popular,
            'status'          => 0,
            'deleted'         => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pricing Plan added successfully',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $price = PricingPlanSection::where('deleted', 0)->findOrFail($id);

        return view('cms.price_section.edit', compact('price'));
    }

    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title'           => 'required|string|max:255',
            'plan_icon'       => 'required|string|max:255',
            'currency_icon'   => 'required|string|max:255',
            'amount'          => 'required|integer|min:1',
            'period'          => 'required|string|max:255',
            'description'     => 'required|string',
            'button_name'     => 'required|string|max:255',
            'button_link'     => 'required|url|max:255',
            'is_most_popular' => 'in:yes,no',
        ]);

        $plan = PricingPlanSection::where('deleted', 0)->findOrFail($id);

        if ($request->is_most_popular === 'yes') {
            PricingPlanSection::where('is_most_popular', 'yes')
                ->where('id', '!=', $plan->id)
                ->update(['is_most_popular' => 'no']);
        }

        $plan->update([
            'title'           => $request->title,
            'plan_icon'       => $request->plan_icon,
            'currency_icon'   => $request->currency_icon,
            'amount'          => $request->amount,
            'period'          => $request->period,
            'description'     => $request->description,
            'button_name'     => $request->button_name,
            'button_link'     => $request->button_link,
            'is_most_popular' => $request->is_most_popular,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pricing Plan updated successfully',
        ]);
    }

    /**
     * Pricing Plan datatable list data.
     * created by ns
     */
    public function pricingPlanList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, 'id');

        $allowedColumns = [
            'id',
            'title',
            'plan_icon',
            'currency_icon',
            'amount',
            'period',
            'description',
            'button_name',
            'button_link',
            'is_most_popular',
            'status',
            'deleted',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';

        $columnSortOrder = in_array(
            $request->input('sSortDir_0'),
            ['asc', 'desc']
        ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        $pricingPlanData = PricingPlanSection::getPricingPlanData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $row,
            $rowperpage
        );

        $totalRecords          = PricingPlanSection::where('deleted', 0)->count();
        $totalRecordwithFilter = PricingPlanSection::getPricingPlanDataTotal($searchValue);

        $data = [];

        foreach ($pricingPlanData as $plan) {
            $data[] = [
                'id'              => (string) $plan->id,
                'title'           => $plan->title,
                'plan_icon'       => $plan->plan_icon,
                'currency_icon'   => $plan->currency_icon,
                'amount'          => $plan->amount,
                'period'          => $plan->period,
                'is_most_popular' => ucfirst($plan->is_most_popular),
                'description'     => $plan->description,
                'button_name'     => $plan->button_name,
                'button_link'     => $plan->button_link,
                'status'          => $plan->status,
            ];
        }

        return response()->json([
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ]);
    }

    public function destroy($id)
    {
        $pricingPlanSection          = PricingPlanSection::findOrFail($id);
        $pricingPlanSection->deleted = 1;
        $pricingPlanSection->save();

        return response()->json([
            'success' => true,
            'message' => 'Pricing Plan deleted Successfully.',
        ]);
    }

    /**
     * Toggle pricing plan active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $pricingPlanSection         = PricingPlanSection::findOrFail($id);
        $pricingPlanSection->status = $pricingPlanSection->status == 1 ? 0 : 1;
        $pricingPlanSection->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active price section count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = PricingPlanSection::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided.',
            ]);
        }

        PricingPlanSection::whereIn('id', $ids)->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected id deleted Successfully.',
        ]);
    }
}

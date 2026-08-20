<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SubscriptionPayment;
use App\Support\DateFormat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentHistoryController extends Controller
{
    public function index()
    {
        return view('payment_history.index');
    }

    public function list(Request $request)
    {
        $draw = $request->input('sEcho');
        $row = (int) $request->input('iDisplayStart', 0);
        $rowperpage = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName = $request->input('mDataProp_' . $indexColumn, 'id');
        $searchValue = trim((string) $request->input('sSearch', ''));

        $allowedColumns = [
            'id',
            'school_name',
            'child_name',
            'parent_name',
            'package_type',
            'amount',
            'channel',
            'status',
            'receipt_no',
            'reference_no',
            'paid_at',
            'collected_by',
        ];

        $columnName = in_array($columnName, $allowedColumns, true) ? $columnName : 'id';
        $columnSortOrder = in_array($request->input('sSortDir_0'), ['asc', 'desc'], true)
            ? $request->input('sSortDir_0')
            : 'desc';

        $query = SubscriptionPayment::query()
            ->with([
                'subscription.child.school:id,school_name',
                'subscription.child.parent:id,father_name,mother_name',
                'collectedBy:id,first_name,last_name,username,email',
            ]);

        $this->applyPaymentScope($query, $request);
        $totalRecords = (clone $query)->count();

        if ($searchValue !== '') {
            $query->where(function ($paymentQuery) use ($searchValue) {
                $paymentQuery
                    ->where('channel', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%")
                    ->orWhere('amount', 'like', "%{$searchValue}%")
                    ->orWhere('currency', 'like', "%{$searchValue}%")
                    ->orWhere('receipt_no', 'like', "%{$searchValue}%")
                    ->orWhere('reference_no', 'like', "%{$searchValue}%")
                    ->orWhere('order_id', 'like', "%{$searchValue}%")
                    ->orWhere('payment_id', 'like', "%{$searchValue}%")
                    ->orWhereHas('subscription', function ($subscriptionQuery) use ($searchValue) {
                        $subscriptionQuery->where('package_type', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('subscription.child', function ($childQuery) use ($searchValue) {
                        $childQuery->where('child_name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('subscription.child.school', function ($schoolQuery) use ($searchValue) {
                        $schoolQuery->where('school_name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('subscription.child.parent', function ($parentQuery) use ($searchValue) {
                        $parentQuery->where('father_name', 'like', "%{$searchValue}%")
                            ->orWhere('mother_name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('collectedBy', function ($userQuery) use ($searchValue) {
                        $userQuery->where('first_name', 'like', "%{$searchValue}%")
                            ->orWhere('last_name', 'like', "%{$searchValue}%")
                            ->orWhere('username', 'like', "%{$searchValue}%")
                            ->orWhere('email', 'like', "%{$searchValue}%");
                    });
            });
        }

        $totalRecordwithFilter = (clone $query)->count();

        $this->applyPaymentSorting($query, $columnName, $columnSortOrder);

        $payments = $query
            ->skip($row)
            ->take($rowperpage)
            ->get();

        $data = $payments->map(function (SubscriptionPayment $payment) {
            $subscription = $payment->subscription;
            $child = $subscription?->child;
            $parent = $child?->parent;
            $collector = $payment->collectedBy;

            $collectedBy = trim((string) (($collector?->first_name ?? '') . ' ' . ($collector?->last_name ?? '')));
            if ($collectedBy === '') {
                $collectedBy = (string) ($collector?->username ?? $collector?->email ?? '-');
            }

            return [
                'id' => $payment->id,
                'school_name' => $child?->school?->school_name ?? '-',
                'child_name' => $child?->child_name ?? '-',
                'parent_name' => trim((string) (($parent?->father_name ?? '') . ' ' . ($parent?->mother_name ?? ''))) ?: '-',
                'package_type' => $subscription?->package_type ?? '-',
                'amount' => number_format((float) $payment->amount, 2) . ' ' . ($payment->currency ?: 'INR'),
                'channel' => ucfirst((string) ($payment->channel ?: '-')),
                'status' => ucfirst((string) ($payment->status ?: '-')),
                'receipt_no' => $payment->receipt_no ?: '-',
                'reference_no' => $payment->reference_no ?: ($payment->payment_id ?: '-'),
                'paid_at' => $payment->paid_at ? DateFormat::formatDateTime($payment->paid_at, '') : '-',
                'collected_by' => $collectedBy,
            ];
        })->values();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecordwithFilter,
            'data' => $data,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $query = SubscriptionPayment::query()->where('id', (int) $id);
        $this->applyPaymentScope($query, $request);
        $payment = $query->firstOrFail();
        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment history deleted successfully.',
        ]);
    }

    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided for deletion.',
            ], 422);
        }

        $ids = collect($ids)
            ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
            ->filter(fn ($id) => ! is_null($id) && $id > 0)
            ->values()
            ->all();

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid IDs provided for deletion.',
            ], 422);
        }

        $query = SubscriptionPayment::query()->whereIn('id', $ids);
        $this->applyPaymentScope($query, $request);
        $query->delete();

        return response()->json([
            'success' => true,
            'message' => 'Selected payment history records deleted successfully.',
        ]);
    }

    private function applyPaymentScope($query, Request $request): void
    {
        $actor = Auth::user();
        $isSchoolUser = $actor && method_exists($actor, 'isSchool') && $actor->isSchool();

        if (! $isSchoolUser) {
            return;
        }

        $schoolSlug = trim((string) $request->route('schoolSlug'));
        $schoolQuery = School::query()->where('deleted', 0);

        if ($schoolSlug !== '') {
            $schoolQuery->where('slug', $schoolSlug);
        } else {
            $schoolQuery->where('user_id', (int) $actor->id);
        }

        $schoolId = $schoolQuery->value('id');

        if (! $schoolId) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereHas('subscription.child', function ($childQuery) use ($schoolId) {
            $childQuery->where('school_id', (int) $schoolId);
        });
    }

    private function applyPaymentSorting($query, string $columnName, string $columnSortOrder): void
    {
        if (in_array($columnName, ['id', 'amount', 'channel', 'status', 'receipt_no', 'reference_no', 'paid_at'], true)) {
            $query->orderBy($columnName, $columnSortOrder);
            return;
        }

        $query->orderByDesc('paid_at')->orderByDesc('id');
    }
}

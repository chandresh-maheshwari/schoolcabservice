<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\ChildSubscription;
use App\Models\PackageDetail;
use App\Models\SubscriptionPayment;
use App\Models\School;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChildSubscriptionController extends Controller
{
    public function __construct(private readonly PushNotificationService $pushNotifications)
    {
    }

    public function createCashForm(Request $request)
    {
        $actor = Auth::user();
        $isSchoolUser = $actor && method_exists($actor, 'isSchool') && $actor->isSchool();

        $defaultSchoolId = $isSchoolUser ? $this->resolveSchoolIdForSchoolUser($request) : null;
        $defaultSchoolName = $defaultSchoolId
            ? (string) School::where('id', $defaultSchoolId)->value('school_name')
            : null;

        $childrenQuery = Child::query()
            ->select(['id', 'child_name', 'parent_id', 'school_id'])
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            });

        if ($isSchoolUser && $defaultSchoolId) {
            $childrenQuery->where('school_id', (int) $defaultSchoolId);
        }

        $selectedChildId = $request->filled('child_id') && is_numeric($request->query('child_id'))
            ? (int) $request->query('child_id')
            : null;

        $children = $childrenQuery->orderByDesc('id')->limit(500)->get();

        if (
            $selectedChildId
            && ! $children->contains(fn ($child) => (int) $child->id === $selectedChildId)
        ) {
            $selectedChild = Child::query()
                ->select(['id', 'child_name', 'parent_id', 'school_id'])
                ->where('id', $selectedChildId)
                ->where(function ($q) {
                    $q->where('deleted', 0)->orWhereNull('deleted');
                })
                ->first();

            if ($selectedChild && (! $isSchoolUser || (int) $selectedChild->school_id === (int) $defaultSchoolId)) {
                $children->prepend($selectedChild);
            }
        }

        if ($selectedChildId && ! $children->contains(fn ($child) => (int) $child->id === $selectedChildId)) {
            $selectedChildId = null;
        }

        $childrenSchoolIds = $children
            ->pluck('school_id')
            ->map(fn ($schoolId) => (int) $schoolId)
            ->filter(fn ($schoolId) => $schoolId > 0)
            ->unique()
            ->values()
            ->all();

        $schoolNameMap = empty($childrenSchoolIds)
            ? []
            : School::query()
                ->whereIn('id', $childrenSchoolIds)
                ->pluck('school_name', 'id')
                ->mapWithKeys(fn ($schoolName, $schoolId) => [(int) $schoolId => (string) $schoolName])
                ->all();

        $currentSubscription = null;
        if ($selectedChildId) {
            $currentSubscription = ChildSubscription::query()
                ->with(['payments' => function ($query) {
                    $query->orderByDesc('id');
                }])
                ->where('child_id', $selectedChildId)
                ->orderByDesc('is_current')
                ->orderByDesc('id')
                ->first();
        }

        $selectedChild = $selectedChildId
            ? $children->first(fn ($child) => (int) $child->id === (int) $selectedChildId)
            : null;

        $displaySchoolName = $defaultSchoolName;
        if (! $displaySchoolName && $selectedChild) {
            $displaySchoolName = $schoolNameMap[(int) ($selectedChild->school_id ?? 0)] ?? null;
        }
        if (! $displaySchoolName && ! empty($currentSubscription?->child_id)) {
            $subscriptionChildSchoolId = Child::query()
                ->where('id', (int) $currentSubscription->child_id)
                ->value('school_id');
            $displaySchoolName = $schoolNameMap[(int) $subscriptionChildSchoolId]
                ?? School::query()->where('id', (int) $subscriptionChildSchoolId)->value('school_name');
        }

        $packageOptions = $this->subscriptionPackageOptions(
            $request,
            $selectedChild ? (int) ($selectedChild->school_id ?? 0) : null
        );

        return view('subscription.cash_create', compact(
            'children',
            'isSchoolUser',
            'defaultSchoolId',
            'defaultSchoolName',
            'displaySchoolName',
            'packageOptions',
            'selectedChildId',
            'currentSubscription'
        ));
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

    private function subscriptionPackageOptions(Request $request, ?int $schoolId = null)
    {
        $query = PackageDetail::query()
            ->select(['id', 'school_id', 'user_id', 'package_type', 'validity_days'])
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            });

        $this->applySchoolAwareScope(
            $query,
            $request,
            'user_id',
            Schema::hasColumn('package_details', 'school_id') ? 'school_id' : null
        );

        if ($schoolId > 0 && Schema::hasColumn('package_details', 'school_id')) {
            $query->where('school_id', $schoolId);
        }

        return $query
            ->orderBy('package_type')
            ->orderBy('validity_days')
            ->get()
            ->filter(fn ($package) => trim((string) ($package->package_type ?? '')) !== '')
            ->values();
    }

    private function computeExpiresAt(\DateTimeInterface $startsAt, ?string $packageType, ?int $validityDays = null): \DateTimeInterface
    {
        $expiresAt = (new \DateTimeImmutable($startsAt->format('c')));
        $packageType = trim((string) $packageType);

        if ($validityDays !== null && $validityDays > 0) {
            return $expiresAt->modify('+' . $validityDays . ' days');
        }

        if ($packageType === '1day') {
            return $expiresAt->modify('+1 day');
        }
        if ($packageType === '1month') {
            return $expiresAt->modify('+1 month');
        }
        if ($packageType === '1year') {
            return $expiresAt->modify('+1 year');
        }

        // Default: 1 month when unspecified
        return $expiresAt->modify('+1 month');
    }

    private function isFutureDate(\DateTimeInterface $date, \DateTimeInterface $reference): bool
    {
        return $date->getTimestamp() > $reference->getTimestamp();
    }

    private function syncCashPaymentForSubscription(
        ChildSubscription $subscription,
        array $validated,
        string $currency,
        \DateTimeInterface $effectivePaidAt,
        ?int $actorId
    ): SubscriptionPayment {
        $payment = SubscriptionPayment::query()
            ->where('child_subscription_id', (int) $subscription->id)
            ->orderByDesc('id')
            ->first();

        $payload = [
            'channel' => 'cash',
            'status' => 'paid',
            'amount' => (float) $validated['amount'],
            'currency' => $currency,
            'receipt_no' => $validated['receipt_no'] ?? null,
            'reference_no' => $validated['reference_no'] ?? null,
            'collected_by_user_id' => $actorId,
            'paid_at' => $effectivePaidAt->format('Y-m-d H:i:s'),
            'meta' => array_filter([
                'entered_by' => $actorId,
                'updated_from' => 'subscription_cash_form',
            ], fn ($value) => $value !== null && $value !== ''),
        ];

        if ($payment) {
            $payment->update($payload);
            return $payment->fresh();
        }

        return SubscriptionPayment::create(array_merge($payload, [
            'child_subscription_id' => (int) $subscription->id,
        ]));
    }

    /**
     * Cash payment entry from admin/school panel.
     * Creates/updates current subscription and inserts a paid payment record.
     */
    public function storeCash(Request $request)
    {
        $actor = Auth::user();
        $isSchoolUser = $actor && method_exists($actor, 'isSchool') && $actor->isSchool();

        $validated = $request->validate([
            'child_id' => 'required|integer',
            'service_type' => 'nullable|string|max:32', // vehicle/school
            'package_type' => 'required|integer',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:8',
            'paid_at' => 'nullable|date',
            'receipt_no' => 'nullable|string|max:191',
            'reference_no' => 'nullable|string|max:191',
            'notes' => 'nullable|string',
        ]);

        $serviceType = trim((string) ($validated['service_type'] ?? 'vehicle')) ?: 'vehicle';
        if ($isSchoolUser && $serviceType === 'school') {
            $serviceType = 'vehicle';
        }
        $currency = trim((string) ($validated['currency'] ?? 'INR')) ?: 'INR';
        $paidAt = isset($validated['paid_at'])
            ? new \DateTimeImmutable($validated['paid_at'])
            : new \DateTimeImmutable('now');

        $child = Child::query()->findOrFail((int) $validated['child_id']);
        if ($isSchoolUser) {
            $schoolId = $this->resolveSchoolIdForSchoolUser($request);
            if (! $schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'School not resolved for this user.',
                ], 409);
            }

            if ((int) $child->school_id !== (int) $schoolId) {
                return response()->json([
                    'success' => false,
                    'message' => 'This child does not belong to your school.',
                ], 403);
            }
        }

        $packageDetailQuery = PackageDetail::query()
            ->where('id', (int) $validated['package_type'])
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            });
        $this->applySchoolAwareScope(
            $packageDetailQuery,
            $request,
            'user_id',
            Schema::hasColumn('package_details', 'school_id') ? 'school_id' : null
        );
        $packageDetail = $packageDetailQuery->firstOrFail();

        if (
            Schema::hasColumn('package_details', 'school_id')
            && (int) ($packageDetail->school_id ?? 0) > 0
            && (int) ($child->school_id ?? 0) > 0
            && (int) $packageDetail->school_id !== (int) $child->school_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Selected package does not belong to the child school.',
            ], 422);
        }

        $packageType = trim((string) ($packageDetail->package_type ?? ''));
        $packageValidityDays = (int) ($packageDetail->validity_days ?? 0);
        $actorId = $actor ? (int) $actor->id : null;

        try {
            $result = DB::transaction(function () use ($child, $serviceType, $packageType, $packageValidityDays, $currency, $paidAt, $validated, $actorId, $isSchoolUser) {
                $now = new \DateTimeImmutable('now');

                $current = ChildSubscription::query()
                    ->with(['payments' => function ($query) {
                        $query->orderByDesc('id');
                    }])
                    ->where('child_id', (int) $child->id)
                    ->where('service_type', $serviceType)
                    ->where('is_current', 1)
                    ->lockForUpdate()
                    ->first();

                $effectivePaidAt = $paidAt;
                $effectiveExpiresAt = $this->computeExpiresAt($effectivePaidAt, $packageType, $packageValidityDays);

                if (! $this->isFutureDate($effectiveExpiresAt, $now)) {
                    return [
                        'error' => response()->json([
                            'success' => false,
                            'message' => 'Selected paid date is too old. Please choose the current date/time for renewal.',
                        ], 422),
                    ];
                }

                if (
                    $current
                    && $current->status === 'active'
                    && $current->expires_at
                    && $this->isFutureDate(new \DateTimeImmutable($current->expires_at), $now)
                ) {
                    $current->update([
                        'package_type' => $packageType,
                        'status' => 'active',
                        'source' => $isSchoolUser ? 'school_cash' : 'admin_cash',
                        'is_current' => 1,
                        'starts_at' => $effectivePaidAt->format('Y-m-d H:i:s'),
                        'expires_at' => $effectiveExpiresAt->format('Y-m-d H:i:s'),
                        'created_by_user_id' => $actorId,
                        'notes' => $validated['notes'] ?? null,
                    ]);

                    $payment = $this->syncCashPaymentForSubscription(
                        $current,
                        $validated,
                        $currency,
                        $effectivePaidAt,
                        $actorId
                    );

                    return [
                        'subscription' => $current->fresh(['payments']),
                        'payment' => $payment,
                        'updated_existing' => true,
                    ];
                }

                if ($current) {
                    $current->is_current = null;
                    if ($current->expires_at && ! $this->isFutureDate(new \DateTimeImmutable($current->expires_at), $now)) {
                        $current->status = 'expired';
                    }
                    $current->save();
                }

                $subscription = ChildSubscription::create([
                    'child_id' => (int) $child->id,
                    'service_type' => $serviceType,
                    'package_type' => $packageType,
                    'status' => 'active',
                    'source' => $isSchoolUser ? 'school_cash' : 'admin_cash',
                    'is_current' => 1,
                    'starts_at' => $effectivePaidAt->format('Y-m-d H:i:s'),
                    'expires_at' => $effectiveExpiresAt->format('Y-m-d H:i:s'),
                    'created_by_user_id' => $actorId,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $payment = $this->syncCashPaymentForSubscription(
                    $subscription,
                    $validated,
                    $currency,
                    $effectivePaidAt,
                    $actorId
                );

                return [
                    'subscription' => $subscription,
                    'payment' => $payment,
                    'updated_existing' => false,
                ];
            });

            if (isset($result['error'])) {
                return $result['error'];
            }

            if (! empty($result['subscription'])) {
                $this->pushNotifications->sendSubscriptionCreatedNotification($result['subscription']);
            }

            return response()->json([
                'success' => true,
                'message' => ! empty($result['updated_existing'])
                    ? 'Subscription updated successfully.'
                    : 'Cash payment saved and subscription activated.',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function current(Request $request)
    {
        $validated = $request->validate([
            'child_id' => 'required|integer',
            'service_type' => 'nullable|string|max:32',
        ]);

        $serviceType = trim((string) ($validated['service_type'] ?? 'vehicle')) ?: 'vehicle';

        $subscription = ChildSubscription::query()
            ->with(['payments'])
            ->where('child_id', (int) $validated['child_id'])
            ->where('service_type', $serviceType)
            ->where('is_current', 1)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $subscription,
        ]);
    }
}

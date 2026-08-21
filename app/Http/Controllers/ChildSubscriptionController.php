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
use Illuminate\Support\Carbon;
use App\Support\DateFormat;

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
                ->where('is_current', 1)
                ->orderByRaw("CASE WHEN LOWER(TRIM(status)) = 'active' THEN 0 ELSE 1 END")
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

        $packageOptions = $this->subscriptionPackageOptions();
        $selectedPackageOptionId = null;

        if (! empty($currentSubscription?->package_type)) {
            $selectedPackageOptionId = optional(
                $packageOptions->first(function ($packageOption) use ($currentSubscription) {
                    return strcasecmp(
                        trim((string) ($packageOption->package_type ?? '')),
                        trim((string) ($currentSubscription->package_type ?? ''))
                    ) === 0;
                })
            )->id;
        }

        $subscriptionSnapshotMap = [];
        $childIds = $children
            ->pluck('id')
            ->map(fn ($childId) => (int) $childId)
            ->filter(fn ($childId) => $childId > 0)
            ->values()
            ->all();

        if (! empty($childIds)) {
            $subscriptions = ChildSubscription::query()
                ->with(['payments' => function ($query) {
                    $query->orderByDesc('paid_at')->orderByDesc('id');
                }])
                ->whereIn('child_id', $childIds)
                ->where('is_current', 1)
                ->orderByRaw("CASE WHEN LOWER(TRIM(status)) = 'active' THEN 0 ELSE 1 END")
                ->orderByDesc('id')
                ->get()
                ->groupBy(fn ($subscription) => (int) ($subscription->child_id ?? 0));

            foreach ($childIds as $childId) {
                $subscription = optional($subscriptions->get($childId))->first();
                $lastPayment = $subscription?->payments->first();
                $packageOptionId = null;

                if (! empty($subscription?->package_type)) {
                    $packageOptionId = optional(
                        $packageOptions->first(function ($packageOption) use ($subscription) {
                            return strcasecmp(
                                trim((string) ($packageOption->package_type ?? '')),
                                trim((string) ($subscription->package_type ?? ''))
                            ) === 0;
                        })
                    )->id;
                }

                $subscriptionSnapshotMap[$childId] = [
                    'subscription_id' => (int) ($subscription->id ?? 0),
                    'service_type' => (string) ($subscription->service_type ?? ''),
                    'package_option_id' => $packageOptionId ? (int) $packageOptionId : null,
                    'package_type' => (string) ($subscription->package_type ?? ''),
                    'status' => $subscription ? $this->normalizeSubscriptionStatus($subscription->status, $subscription->expires_at) : null,
                    'starts_at_display' => $subscription?->starts_at ? DateFormat::formatDateTime($subscription->starts_at, '') : '',
                    'expires_at_display' => $subscription?->expires_at ? DateFormat::formatDateTime($subscription->expires_at, '') : '',
                    'amount' => $lastPayment ? (string) $lastPayment->amount : '',
                    'currency' => (string) ($lastPayment->currency ?? 'INR'),
                    'paid_at' => $lastPayment?->paid_at ? DateFormat::formatDateTime($lastPayment->paid_at, '') : '',
                    'paid_at_display' => $lastPayment?->paid_at ? DateFormat::formatDateTime($lastPayment->paid_at, '') : '',
                    'receipt_no' => (string) ($lastPayment->receipt_no ?? ''),
                    'reference_no' => (string) ($lastPayment->reference_no ?? ''),
                    'notes' => (string) ($subscription->notes ?? ''),
                ];
            }
        }

        return view('subscription.cash_create', compact(
            'children',
            'isSchoolUser',
            'defaultSchoolId',
            'defaultSchoolName',
            'displaySchoolName',
            'packageOptions',
            'selectedPackageOptionId',
            'selectedChildId',
            'currentSubscription',
            'schoolNameMap',
            'subscriptionSnapshotMap'
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

        $schoolQuery = School::query()->where('deleted', 0)->where('status', 1);
        if ($schoolSlug !== '') {
            $schoolQuery->where('slug', $schoolSlug);
        } else {
            $schoolQuery->where('user_id', (int) $actor->id);
        }

        $schoolId = $schoolQuery->orderByDesc('id')->value('id');
        return $schoolId ? (int) $schoolId : null;
    }

    private function subscriptionPackageOptions()
    {
        return PackageDetail::query()
            ->select(['id', 'school_id', 'user_id', 'package_name', 'package_type', 'booking_type', 'price', 'validity_days'])
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })
            ->where('status', 1)
            ->orderBy('package_type')
            ->orderBy('validity_days')
            ->get()
            ->filter(fn ($package) => trim((string) ($package->package_type ?? '')) !== '')
            ->values();
    }

    private function computeExpiresAt(\DateTimeInterface $startsAt, ?string $packageType, ?int $validityDays = null): \DateTimeInterface
    {
        $startsAtImmutable = (new \DateTimeImmutable($startsAt->format('c')));
        $packageType = strtolower(trim((string) $packageType));

        if ($packageType === 'daily' || $packageType === '1day') {
            return $this->endOfDay($startsAtImmutable);
        }
        if ($packageType === 'monthly' || $packageType === '1month') {
            return $this->endOfDay($startsAtImmutable->modify('+1 month'));
        }
        if ($packageType === 'quarterly') {
            return $this->endOfDay($startsAtImmutable->modify('+3 months'));
        }
        if ($packageType === 'yearly' || $packageType === '1year') {
            return $this->endOfDay($startsAtImmutable->modify('+1 year'));
        }

        if ($validityDays !== null && $validityDays > 0) {
            return $this->endOfDay($startsAtImmutable->modify('+' . max($validityDays - 1, 0) . ' days'));
        }

        // Default: 1 month when unspecified, aligned with package validity display.
        return $this->endOfDay($startsAtImmutable->modify('+1 month'));
    }

    private function endOfDay(\DateTimeImmutable $dateTime): \DateTimeImmutable
    {
        return $dateTime->setTime(23, 59, 59);
    }

    private function isFutureDate(\DateTimeInterface $date, \DateTimeInterface $reference): bool
    {
        return $date->getTimestamp() > $reference->getTimestamp();
    }

    private function normalizeServiceType(?string $serviceType): string
    {
        $normalized = strtolower(trim((string) $serviceType));
        return $normalized !== '' ? $normalized : 'vehicle';
    }

    private function applyServiceTypeFilter($query, ?string $serviceType)
    {
        return $query->whereRaw(
            'LOWER(TRIM(service_type)) = ?',
            [$this->normalizeServiceType($serviceType)]
        );
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

    private function normalizeSubscriptionStatus(?string $status, $expiresAt): string
    {
        $normalizedStatus = trim((string) $status);
        if ($normalizedStatus === '') {
            $normalizedStatus = 'inactive';
        }

        if (
            $normalizedStatus === 'active'
            && ! empty($expiresAt)
            && Carbon::parse($expiresAt)->lt(now())
        ) {
            return 'expired';
        }

        return $normalizedStatus;
    }

    private function formatSubscriptionResponse(?ChildSubscription $subscription, int $childId, string $serviceType): array
    {
        if (! $subscription) {
            return [
                'childId' => $childId,
                'serviceType' => $serviceType,
                'packageType' => null,
                'status' => 'inactive',
                'expiresAt' => null,
                'startedAt' => null,
                'canRenew' => true,
                'canCancel' => false,
                'lastPayment' => null,
            ];
        }

        $subscription->loadMissing(['payments' => function ($query) {
            $query->orderByDesc('paid_at')->orderByDesc('id');
        }]);

        $lastPayment = $subscription->payments->first();
        $normalizedStatus = $this->normalizeSubscriptionStatus(
            $subscription->status,
            $subscription->expires_at
        );

        return [
            'childId' => (int) ($subscription->child_id ?? $childId),
            'serviceType' => (string) ($subscription->service_type ?? $serviceType),
            'packageType' => $subscription->package_type,
            'status' => $normalizedStatus,
            'expiresAt' => optional($subscription->expires_at)->toIso8601String(),
            'startedAt' => optional($subscription->starts_at)->toIso8601String(),
            'canRenew' => true,
            'canCancel' => $normalizedStatus === 'active',
            'lastPayment' => $lastPayment ? [
                'id' => (int) $lastPayment->id,
                'orderId' => $lastPayment->order_id,
                'paymentId' => $lastPayment->payment_id,
                'amount' => $lastPayment->amount,
                'currency' => $lastPayment->currency,
                'status' => $lastPayment->status,
                'packageType' => $subscription->package_type,
                'paidAt' => optional($lastPayment->paid_at)->toIso8601String()
                    ?: optional($lastPayment->updated_at)->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * Cash payment entry from admin/school panel.
     * Creates/updates current subscription and inserts a paid payment record.
     */
    public function storeCash(Request $request)
    {
        $actor = Auth::user();
        $isSchoolUser = $actor && method_exists($actor, 'isSchool') && $actor->isSchool();

        if ($request->filled('paid_at')) {
            $request->merge([
                'paid_at' => DateFormat::toStorageDateTime($request->input('paid_at')),
            ]);
        }

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

        $serviceType = $this->normalizeServiceType($validated['service_type'] ?? 'vehicle');
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
            })
            ->where('status', 1);
        $packageDetail = $packageDetailQuery->firstOrFail();

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
                    ->tap(fn ($query) => $this->applyServiceTypeFilter($query, $serviceType))
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

        $serviceType = $this->normalizeServiceType($validated['service_type'] ?? 'vehicle');
        $childId = (int) $validated['child_id'];

        $baseQuery = ChildSubscription::query()
            ->with(['payments' => function ($query) {
                $query->orderByDesc('paid_at')->orderByDesc('id');
            }])
            ->where('child_id', $childId);

        $this->applyServiceTypeFilter($baseQuery, $serviceType);

        $subscription = (clone $baseQuery)
            ->orderByRaw('CASE WHEN is_current = 1 THEN 0 ELSE 1 END')
            ->orderByRaw("CASE WHEN LOWER(TRIM(status)) = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $this->formatSubscriptionResponse(
                $subscription,
                $childId,
                $serviceType
            ),
        ]);
    }

    public function syncFromMobile(Request $request)
    {
        $validated = $request->validate([
            'child_id' => 'required|integer',
            'service_type' => 'nullable|string|max:32',
            'package_type' => 'nullable|string|max:64',
            'status' => 'nullable|string|max:32',
            'source' => 'nullable|string|max:64',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'payment' => 'nullable|array',
            'payment.channel' => 'nullable|string|max:32',
            'payment.status' => 'nullable|string|max:32',
            'payment.amount' => 'nullable|numeric|min:0',
            'payment.currency' => 'nullable|string|max:8',
            'payment.orderId' => 'nullable|string|max:191',
            'payment.paymentId' => 'nullable|string|max:191',
            'payment.signature' => 'nullable|string|max:255',
            'payment.receiptNo' => 'nullable|string|max:191',
            'payment.referenceNo' => 'nullable|string|max:191',
            'payment.paidAt' => 'nullable|date',
        ]);

        $serviceType = $this->normalizeServiceType($validated['service_type'] ?? 'vehicle');
        $status = trim((string) ($validated['status'] ?? 'active')) ?: 'active';
        $packageType = trim((string) ($validated['package_type'] ?? ''));
        $source = trim((string) ($validated['source'] ?? 'app_sync')) ?: 'app_sync';
        $startsAt = ! empty($validated['starts_at']) ? Carbon::parse($validated['starts_at']) : null;
        $expiresAt = ! empty($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null;
        $paymentPayload = is_array($validated['payment'] ?? null) ? $validated['payment'] : null;

        try {
            $subscription = DB::transaction(function () use (
                $validated,
                $serviceType,
                $status,
                $packageType,
                $source,
                $startsAt,
                $expiresAt,
                $paymentPayload
            ) {
                $current = ChildSubscription::query()
                    ->with(['payments' => function ($query) {
                        $query->orderByDesc('paid_at')->orderByDesc('id');
                    }])
                    ->where('child_id', (int) $validated['child_id'])
                    ->tap(fn ($query) => $this->applyServiceTypeFilter($query, $serviceType))
                    ->where('is_current', 1)
                    ->lockForUpdate()
                    ->first();

                if ($status === 'cancelled') {
                    if ($current) {
                        $current->update([
                            'status' => 'cancelled',
                            'source' => $source,
                            'is_current' => null,
                            'expires_at' => now()->format('Y-m-d H:i:s'),
                            'notes' => $validated['notes'] ?? $current->notes,
                        ]);
                    }

                    return $current;
                }

                if (! $current) {
                    $current = ChildSubscription::create([
                        'child_id' => (int) $validated['child_id'],
                        'service_type' => $serviceType,
                        'package_type' => $packageType !== '' ? $packageType : null,
                        'status' => $status,
                        'source' => $source,
                        'is_current' => 1,
                        'starts_at' => $startsAt?->format('Y-m-d H:i:s'),
                        'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
                        'notes' => $validated['notes'] ?? null,
                    ]);
                } else {
                    $current->update([
                        'package_type' => $packageType !== '' ? $packageType : $current->package_type,
                        'status' => $status,
                        'source' => $source,
                        'is_current' => 1,
                        'starts_at' => $startsAt?->format('Y-m-d H:i:s') ?? $current->starts_at,
                        'expires_at' => $expiresAt?->format('Y-m-d H:i:s') ?? $current->expires_at,
                        'notes' => $validated['notes'] ?? $current->notes,
                    ]);
                }

                if ($paymentPayload) {
                    $paymentQuery = SubscriptionPayment::query()
                        ->where('child_subscription_id', (int) $current->id);

                    if (! empty($paymentPayload['orderId'])) {
                        $paymentQuery->where('order_id', (string) $paymentPayload['orderId']);
                    } elseif (! empty($paymentPayload['paymentId'])) {
                        $paymentQuery->where('payment_id', (string) $paymentPayload['paymentId']);
                    } else {
                        $paymentQuery->latest('id');
                    }

                    $payment = $paymentQuery->first();

                    $paymentData = [
                        'channel' => trim((string) ($paymentPayload['channel'] ?? 'app')),
                        'status' => trim((string) ($paymentPayload['status'] ?? 'paid')),
                        'amount' => (float) ($paymentPayload['amount'] ?? 0),
                        'currency' => trim((string) ($paymentPayload['currency'] ?? 'INR')) ?: 'INR',
                        'order_id' => $paymentPayload['orderId'] ?? null,
                        'payment_id' => $paymentPayload['paymentId'] ?? null,
                        'signature' => $paymentPayload['signature'] ?? null,
                        'receipt_no' => $paymentPayload['receiptNo'] ?? null,
                        'reference_no' => $paymentPayload['referenceNo'] ?? null,
                        'paid_at' => ! empty($paymentPayload['paidAt'])
                            ? Carbon::parse($paymentPayload['paidAt'])->format('Y-m-d H:i:s')
                            : ($startsAt?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s')),
                        'meta' => array_filter([
                            'synced_from' => 'mobile_app',
                        ]),
                    ];

                    if ($payment) {
                        $payment->update($paymentData);
                    } else {
                        SubscriptionPayment::create(array_merge($paymentData, [
                            'child_subscription_id' => (int) $current->id,
                        ]));
                    }
                }

                return $current->fresh(['payments' => function ($query) {
                    $query->orderByDesc('paid_at')->orderByDesc('id');
                }]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Subscription synced successfully.',
                'data' => $this->formatSubscriptionResponse(
                    $subscription,
                    (int) $validated['child_id'],
                    $serviceType
                ),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to sync subscription.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancelFromMobile(Request $request)
    {
        $validated = $request->validate([
            'child_id' => 'required|integer',
            'service_type' => 'nullable|string|max:32',
        ]);

        $serviceType = $this->normalizeServiceType($validated['service_type'] ?? 'vehicle');

        try {
            $subscription = DB::transaction(function () use ($validated, $serviceType) {
                $matchingSubscriptions = ChildSubscription::query()
                    ->with(['payments'])
                    ->where('child_id', (int) $validated['child_id'])
                    ->tap(fn ($query) => $this->applyServiceTypeFilter($query, $serviceType))
                    ->lockForUpdate()
                    ->orderByRaw('CASE WHEN is_current = 1 THEN 0 ELSE 1 END')
                    ->orderByRaw("CASE WHEN LOWER(TRIM(status)) = 'active' THEN 0 ELSE 1 END")
                    ->orderByDesc('id')
                    ->get();

                if ($matchingSubscriptions->isEmpty()) {
                    return null;
                }

                foreach ($matchingSubscriptions as $matchingSubscription) {
                    $matchingSubscription->update([
                        'status' => 'cancelled',
                        'source' => 'app_cancel',
                        'is_current' => null,
                        'expires_at' => now()->format('Y-m-d H:i:s'),
                    ]);
                }

                return $matchingSubscriptions->first()->fresh(['payments']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully.',
                'data' => $this->formatSubscriptionResponse(
                    $subscription,
                    (int) $validated['child_id'],
                    $serviceType
                ),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to cancel subscription.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\ChildSubscription;
use App\Models\SubscriptionPayment;
use App\Models\School;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        return view('subscription.cash_create', compact(
            'children',
            'isSchoolUser',
            'defaultSchoolId',
            'defaultSchoolName',
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

    private function computeExpiresAt(\DateTimeInterface $startsAt, ?string $packageType): \DateTimeInterface
    {
        $expiresAt = (new \DateTimeImmutable($startsAt->format('c')));
        $packageType = trim((string) $packageType);

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
            'package_type' => 'nullable|string|max:32', // 1day/1month/1year
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
        $packageType = $validated['package_type'] ?? null;
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

        $expiresAt = $this->computeExpiresAt($paidAt, $packageType);

        try {
            $result = DB::transaction(function () use ($child, $serviceType, $packageType, $currency, $paidAt, $expiresAt, $validated, $actor, $isSchoolUser) {
                $now = new \DateTimeImmutable('now');

                $current = ChildSubscription::query()
                    ->where('child_id', (int) $child->id)
                    ->where('service_type', $serviceType)
                    ->where('is_current', 1)
                    ->lockForUpdate()
                    ->first();

                if ($current && $current->status === 'active' && $current->expires_at && new \DateTimeImmutable($current->expires_at) > $now) {
                    return [
                        'error' => response()->json([
                            'success' => false,
                            'message' => 'Subscription is already active for this child.',
                        ], 409),
                    ];
                }

                if ($current) {
                    $current->is_current = null;
                    $current->save();
                }

                $subscription = ChildSubscription::create([
                    'child_id' => (int) $child->id,
                    'service_type' => $serviceType,
                    'package_type' => $packageType,
                    'status' => 'active',
                    'source' => $isSchoolUser ? 'school_cash' : 'admin_cash',
                    'is_current' => 1,
                    'starts_at' => $paidAt->format('Y-m-d H:i:s'),
                    'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                    'created_by_user_id' => $actor ? (int) $actor->id : null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $payment = SubscriptionPayment::create([
                    'child_subscription_id' => (int) $subscription->id,
                    'channel' => 'cash',
                    'status' => 'paid',
                    'amount' => (float) $validated['amount'],
                    'currency' => $currency,
                    'receipt_no' => $validated['receipt_no'] ?? null,
                    'reference_no' => $validated['reference_no'] ?? null,
                    'collected_by_user_id' => $actor ? (int) $actor->id : null,
                    'paid_at' => $paidAt->format('Y-m-d H:i:s'),
                    'meta' => [
                        'entered_by' => $actor ? (int) $actor->id : null,
                    ],
                ]);

                return [
                    'subscription' => $subscription,
                    'payment' => $payment,
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
                'message' => 'Cash payment saved and subscription activated.',
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

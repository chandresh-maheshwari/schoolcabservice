<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Child;
use App\Models\ChildSubscription;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Many routes are defined under a `{schoolSlug}` prefix group and then a resource id,
     * e.g. `/{schoolSlug}/child/{child}/edit`. In those cases Laravel will pass the slug
     * as the first argument and the numeric id as the second.
     *
     * Admin/API routes often only pass the numeric id. This helper normalizes both forms.
     */
    protected function normalizeRouteId($maybeIdOrSlug, $maybeId = null): int
    {
        $id = $maybeId ?? $maybeIdOrSlug;
        if (is_numeric($id) && (int) $id > 0) {
            return (int) $id;
        }

        abort(404);
    }

    protected function resolveActorUserId(?Request $request = null): ?int
    {
        $request = $request ?: request();

        $currentUserId = Auth::id();

        if (! $currentUserId) {
            $sessionUserId = Session::get('userid');
            if (is_numeric($sessionUserId) && (int) $sessionUserId > 0) {
                $currentUserId = (int) $sessionUserId;
            }
        }

        if (! $currentUserId) {
            $headerUserId = $request->header('X-Auth-User-Id');
            if (is_numeric($headerUserId) && (int) $headerUserId > 0) {
                $currentUserId = (int) $headerUserId;
            }
        }

        if (! $currentUserId) {
            $inputUserId = $request->input('user_id');
            if (is_numeric($inputUserId) && (int) $inputUserId > 0) {
                $currentUserId = (int) $inputUserId;
            }
        }

        if (! $currentUserId) {
            try {
                if ($request->bearerToken()) {
                    $jwtUser = JWTAuth::parseToken()->authenticate();
                    if ($jwtUser) {
                        Auth::setUser($jwtUser);
                        $currentUserId = (int) $jwtUser->id;
                    }
                }
            } catch (\Throwable $e) {
                $currentUserId = null;
            }
        }

        return $currentUserId ?: null;
    }

    protected function resolveActor(?Request $request = null): ?User
    {
        $request = $request ?: request();

        $authUser = Auth::user();
        if ($authUser instanceof User) {
            return $authUser;
        }

        $actorUserId = $this->resolveActorUserId($request);
        if (! $actorUserId) {
            return null;
        }

        return User::find($actorUserId);
    }

    protected function isPrivilegedActor(?Request $request = null): bool
    {
        $actor = $this->resolveActor($request);
        return $actor ? $actor->isAdmin() : false;
    }

    protected function isSchoolActor(?Request $request = null): bool
    {
        $actor = $this->resolveActor($request);
        return $actor ? $actor->isSchool() : false;
    }

    protected function shouldRestrictToActorData(?Request $request = null): bool
    {
        return ! $this->isPrivilegedActor($request);
    }

    protected function applyActorScope($query, ?Request $request = null, string $userColumn = 'user_id')
    {
        if (! $this->shouldRestrictToActorData($request)) {
            return $query;
        }

        $actorUserId = $this->resolveActorUserId($request ?: request());
        if (! $actorUserId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($userColumn, $actorUserId);
    }

    protected function resolvePersistedUserId(Request $request): ?int
    {
        $actorUserId = $this->resolveActorUserId($request);
        if (! $actorUserId) {
            return null;
        }

        if ($this->isPrivilegedActor($request)) {
            $inputUserId = $request->input('user_id');
            if (is_numeric($inputUserId) && (int) $inputUserId > 0) {
                return (int) $inputUserId;
            }
        }

        return (int) $actorUserId;
    }

    protected function getSchoolNameMapForUserIds(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map(function ($value) {
            return is_numeric($value) ? (int) $value : null;
        }, $userIds), fn ($value) => $value && $value > 0)));

        if (empty($userIds)) {
            return [];
        }

        return DB::table('schools')
            ->where('deleted', 0)
            ->whereIn('user_id', $userIds)
            ->pluck('school_name', 'user_id')
            ->toArray();
    }

    protected function resolveChildModuleEntityIds(?int $childId, ?Request $request = null): array
    {
        $request = $request ?: request();
        $normalizedChildId = is_numeric($childId) && (int) $childId > 0 ? (int) $childId : null;

        $entityIds = [
            'child' => $normalizedChildId,
            'parent' => null,
            'booking' => null,
            'subscription' => null,
        ];

        if (! $normalizedChildId) {
            return $entityIds;
        }

        $requestedBookingId = $request->query('booking_id');
        if (is_numeric($requestedBookingId) && (int) $requestedBookingId > 0) {
            $entityIds['booking'] = (int) $requestedBookingId;
        }

        $requestedSubscriptionId = $request->query('subscription_id');
        if (is_numeric($requestedSubscriptionId) && (int) $requestedSubscriptionId > 0) {
            $entityIds['subscription'] = (int) $requestedSubscriptionId;
        }

        $childQuery = Child::query()
            ->with('parent')
            ->where('id', $normalizedChildId)
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            });

        $currentSchool = $request->attributes->get('current_school');
        if (is_object($currentSchool) && isset($currentSchool->id) && is_numeric($currentSchool->id)) {
            $childQuery->where('school_id', (int) $currentSchool->id);
        } else {
            $this->applyActorScope($childQuery, $request);
        }

        $child = $childQuery->first();
        if (! $child) {
            return $entityIds;
        }

        $entityIds['child'] = (int) $child->id;
        $entityIds['parent'] = $child->parent_id ? (int) $child->parent_id : null;

        if (! $entityIds['booking'] && Schema::hasColumn('bookings', 'child_id')) {
            $directBookingQuery = Booking::query()
                ->where(function ($q) {
                    $q->where('deleted', 0)->orWhereNull('deleted');
                })
                ->where('child_id', (int) $child->id);

            $this->applyActorScope($directBookingQuery, $request);

            $directBookingId = $directBookingQuery->orderByDesc('id')->value('id');
            $entityIds['booking'] = $directBookingId ? (int) $directBookingId : null;
        }

        $contactNumbers = array_values(array_unique(array_filter([
            trim((string) optional($child->parent)->contact_number),
            trim((string) optional($child->parent)->alternative_contact_number),
        ])));

        if (! $entityIds['booking'] && $child->school_id && $child->route_id && ! empty($contactNumbers)) {
            $bookingQuery = Booking::query()
                ->where(function ($q) {
                    $q->where('deleted', 0)->orWhereNull('deleted');
                })
                ->where('school_id', (int) $child->school_id)
                ->where('route_id', (int) $child->route_id)
                ->whereIn('contact_number', $contactNumbers);

            $this->applyActorScope($bookingQuery, $request);

            $bookingId = $bookingQuery->orderByDesc('id')->value('id');
            $entityIds['booking'] = $bookingId ? (int) $bookingId : null;
        }

        if (! $entityIds['subscription']) {
            $subscriptionQuery = ChildSubscription::query()
                ->where('child_id', (int) $child->id)
                ->orderByDesc('is_current')
                ->orderByDesc('id');

            $subscriptionId = $subscriptionQuery->value('id');
            $entityIds['subscription'] = $subscriptionId ? (int) $subscriptionId : null;
        }

        return $entityIds;
    }

    protected function defaultUserPhotoPath(): string
    {
        return 'profile_pictures/default-user.svg';
    }

    protected function createOrRestoreLoginUser(array $payload): User
    {
        $email = trim((string) ($payload['email'] ?? ''));
        $username = trim((string) ($payload['username'] ?? ''));
        $plainPassword = (string) ($payload['password'] ?? '');
        $roleName = trim((string) ($payload['role_name'] ?? ''));
        $existingUserId = is_numeric($payload['existing_user_id'] ?? null)
            ? (int) $payload['existing_user_id']
            : null;

        if ($email === '' || $username === '' || $roleName === '') {
            throw ValidationException::withMessages([
                'credentials' => ['Login email, username, and role are required.'],
            ]);
        }

        $currentUser = $existingUserId ? User::find($existingUserId) : null;
        if (! $currentUser && $existingUserId) {
            $currentUser = User::where('id', $existingUserId)->orderBy('id')->first();
        }

        if (! $currentUser && $plainPassword === '') {
            throw ValidationException::withMessages([
                'password' => ['Password is required for a new login user.'],
            ]);
        }

        $existingByEmailQuery = User::where('email', $email)->orderBy('id');
        $existingByUsernameQuery = User::where('username', $username)->orderBy('id');

        if ($currentUser) {
            $existingByEmailQuery->where('id', '!=', $currentUser->id);
            $existingByUsernameQuery->where('id', '!=', $currentUser->id);
        }

        $existingByEmail = $existingByEmailQuery->first();
        $existingByUsername = $existingByUsernameQuery->first();

        if (
            $existingByEmail
            && $existingByUsername
            && (int) $existingByEmail->id !== (int) $existingByUsername->id
        ) {
            throw ValidationException::withMessages([
                'login_email' => ['Email is already linked to another account.'],
                'login_username' => ['Username is already linked to another account.'],
            ]);
        }

        $existingUser = $currentUser ?: $existingByEmail ?: $existingByUsername;

        if ($existingUser && (int) ($existingUser->deleted ?? 0) === 0) {
            if (! $currentUser || (int) $existingUser->id !== (int) $currentUser->id) {
                throw ValidationException::withMessages([
                    'login_email' => ['An active user already exists with this email or username.'],
                ]);
            }
        }

        $role = Role::firstOrCreate(['name' => $roleName]);

        $userPayload = [
            'first_name' => (string) ($payload['first_name'] ?? ''),
            'last_name' => (string) ($payload['last_name'] ?? ''),
            'mobile' => $payload['mobile'] ?? null,
            'email' => $email,
            'username' => $username,
            'photo' => ! empty($payload['photo'])
                ? ltrim((string) $payload['photo'], '/')
                : ltrim((string) ($existingUser->photo ?? $this->defaultUserPhotoPath()), '/'),
            'role_id' => $role->id,
        ];

        if ($plainPassword !== '') {
            $userPayload['password'] = Hash::make($plainPassword);
        }

        if ($existingUser) {
            $existingUser->update($userPayload);
            DB::table('users')
                ->where('id', $existingUser->id)
                ->update([
                    'deleted' => 0,
                    'remember_token' => null,
                    'updated_at' => now(),
                ]);

            return $existingUser->fresh();
        }

        return User::create($userPayload);
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\Booking;
use App\Models\Child;
use App\Models\ChildSubscription;
use App\Models\Role;
use App\Models\School;
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

    protected function applySchoolAwareScope($query, ?Request $request = null, string $userColumn = 'user_id', ?string $schoolColumn = null)
    {
        $request = $request ?: request();

        if (! $this->shouldRestrictToActorData($request)) {
            return $query;
        }

        $actorUserId = $this->resolveActorUserId($request);
        if (! $actorUserId) {
            return $query->whereRaw('1 = 0');
        }

        $schoolId = $this->resolveSchoolIdFromContext($request);
        if ($schoolId && $schoolColumn) {
            return $query->where(function ($scopeQuery) use ($schoolColumn, $schoolId, $userColumn, $actorUserId) {
                $scopeQuery->where($schoolColumn, $schoolId)
                    ->orWhere($userColumn, $actorUserId);
            });
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

    protected function resolveSchoolOwnerUserId(?Request $request = null): ?int
    {
        $request = $request ?: request();
        $currentSchool = $request->attributes->get('current_school');

        if (is_object($currentSchool) && isset($currentSchool->user_id) && is_numeric($currentSchool->user_id)) {
            return (int) $currentSchool->user_id;
        }

        if (is_array($currentSchool) && is_numeric($currentSchool['user_id'] ?? null)) {
            return (int) $currentSchool['user_id'];
        }

        $impersonatedSchoolId = Session::get('impersonated_school_id');
        if (is_numeric($impersonatedSchoolId) && (int) $impersonatedSchoolId > 0) {
            $schoolUserId = School::query()
                ->where('deleted', 0)
                ->where('id', (int) $impersonatedSchoolId)
                ->value('user_id');

            if (is_numeric($schoolUserId) && (int) $schoolUserId > 0) {
                return (int) $schoolUserId;
            }
        }

        $schoolSlug = trim((string) $request->route('schoolSlug'));
        if ($schoolSlug !== '') {
            $schoolUserId = School::query()
                ->where('deleted', 0)
                ->whereRaw('LOWER(slug) = ?', [strtolower($schoolSlug)])
                ->value('user_id');

            if (is_numeric($schoolUserId) && (int) $schoolUserId > 0) {
                return (int) $schoolUserId;
            }
        }

        return null;
    }

    protected function resolveSchoolIdFromContext(?Request $request = null, ?int $ownerUserId = null): ?int
    {
        $request = $request ?: request();
        $currentSchool = $request->attributes->get('current_school');

        if (is_object($currentSchool) && isset($currentSchool->id) && is_numeric($currentSchool->id)) {
            return (int) $currentSchool->id;
        }

        if (is_array($currentSchool) && is_numeric($currentSchool['id'] ?? null)) {
            return (int) $currentSchool['id'];
        }

        $impersonatedSchoolId = Session::get('impersonated_school_id');
        if (is_numeric($impersonatedSchoolId) && (int) $impersonatedSchoolId > 0) {
            return (int) $impersonatedSchoolId;
        }

        $schoolSlug = trim((string) $request->route('schoolSlug'));
        if ($schoolSlug !== '') {
            $schoolId = School::query()
                ->where('deleted', 0)
                ->whereRaw('LOWER(slug) = ?', [strtolower($schoolSlug)])
                ->value('id');

            if (is_numeric($schoolId) && (int) $schoolId > 0) {
                return (int) $schoolId;
            }
        }

        if ($this->isPrivilegedActor($request)) {
            $requestedSchoolId = $request->input('school_id');
            if (is_numeric($requestedSchoolId) && (int) $requestedSchoolId > 0) {
                return (int) $requestedSchoolId;
            }
        }

        $ownerUserId = $ownerUserId ?: $this->resolveActorUserId($request);
        if ($ownerUserId) {
            $schoolId = School::query()
                ->where('deleted', 0)
                ->where('user_id', (int) $ownerUserId)
                ->value('id');

            if (is_numeric($schoolId) && (int) $schoolId > 0) {
                return (int) $schoolId;
            }
        }

        return null;
    }

    protected function resolveModuleSchoolId(?Request $request = null, ?int $fallbackSchoolId = null, array $candidateSchoolIds = [], ?int $ownerUserId = null): ?int
    {
        $request = $request ?: request();

        $contextSchoolId = $this->resolveSchoolIdFromContext($request, $ownerUserId);
        if ($contextSchoolId) {
            return $contextSchoolId;
        }

        foreach ($candidateSchoolIds as $candidateSchoolId) {
            if (is_numeric($candidateSchoolId) && (int) $candidateSchoolId > 0) {
                return (int) $candidateSchoolId;
            }
        }

        if (is_numeric($fallbackSchoolId) && (int) $fallbackSchoolId > 0) {
            return (int) $fallbackSchoolId;
        }

        if ($ownerUserId) {
            return $this->resolveSchoolIdFromContext($request, $ownerUserId);
        }

        return null;
    }

    protected function resolveModuleOwnerUserId(?Request $request = null, ?int $fallbackUserId = null, array $candidateUserIds = []): ?int
    {
        $request = $request ?: request();

        $schoolOwnerUserId = $this->resolveSchoolOwnerUserId($request);
        if ($schoolOwnerUserId) {
            return $schoolOwnerUserId;
        }

        if ($this->isPrivilegedActor($request)) {
            $requestedUserId = $request->input('user_id');
            if (is_numeric($requestedUserId) && (int) $requestedUserId > 0) {
                return (int) $requestedUserId;
            }
        }

        foreach ($candidateUserIds as $candidateUserId) {
            if (is_numeric($candidateUserId) && (int) $candidateUserId > 0) {
                return (int) $candidateUserId;
            }
        }

        if (is_numeric($fallbackUserId) && (int) $fallbackUserId > 0) {
            return (int) $fallbackUserId;
        }

        return $this->resolvePersistedUserId($request);
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

    protected function resolveSchoolSearchIds(string $searchValue): array
    {
        $searchValue = trim($searchValue);
        if ($searchValue === '') {
            return [
                'school_ids' => [],
                'user_ids' => [],
            ];
        }

        $matchingSchools = School::query()
            ->where('deleted', 0)
            ->where('school_name', 'like', '%' . $searchValue . '%')
            ->get(['id', 'user_id']);

        return [
            'school_ids' => $matchingSchools->pluck('id')
                ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all(),
            'user_ids' => $matchingSchools->pluck('user_id')
                ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all(),
        ];
    }

    protected function getSchoolNameMapForSchoolIds(array $schoolIds): array
    {
        $schoolIds = array_values(array_unique(array_filter(array_map(function ($value) {
            return is_numeric($value) ? (int) $value : null;
        }, $schoolIds), fn ($value) => $value && $value > 0)));

        if (empty($schoolIds)) {
            return [];
        }

        return DB::table('schools')
            ->where('deleted', 0)
            ->whereIn('id', $schoolIds)
            ->pluck('school_name', 'id')
            ->toArray();
    }

    protected function getSchoolNameMapForRouteIds(array $routeIds): array
    {
        $routeIds = array_values(array_unique(array_filter(array_map(function ($value) {
            return is_numeric($value) ? (int) $value : null;
        }, $routeIds), fn ($value) => $value && $value > 0)));

        if (empty($routeIds)) {
            return [];
        }

        $routes = DB::table('routes')
            ->whereIn('id', $routeIds)
            ->select('id', 'school_id', 'user_id')
            ->get();

        $schoolNamesBySchoolId = $this->getSchoolNameMapForSchoolIds($routes->pluck('school_id')->all());
        $schoolNamesByUserId = $this->getSchoolNameMapForUserIds($routes->pluck('user_id')->all());
        $resolved = [];

        foreach ($routes as $route) {
            $resolved[(int) $route->id] = $schoolNamesBySchoolId[(int) ($route->school_id ?? 0)]
                ?? $schoolNamesByUserId[(int) ($route->user_id ?? 0)]
                ?? '-';
        }

        return $resolved;
    }

    protected function getSchoolNameMapForVehicleIds(array $vehicleIds): array
    {
        $vehicleIds = array_values(array_unique(array_filter(array_map(function ($value) {
            return is_numeric($value) ? (int) $value : null;
        }, $vehicleIds), fn ($value) => $value && $value > 0)));

        if (empty($vehicleIds)) {
            return [];
        }

        $vehicleColumns = ['id', 'user_id', 'vehicle_type_id'];
        if (Schema::hasColumn('vehicles', 'school_id')) {
            $vehicleColumns[] = 'school_id';
        }

        $vehicles = DB::table('vehicles')
            ->whereIn('id', $vehicleIds)
            ->select($vehicleColumns)
            ->get();

        $schoolNamesByUserId = $this->getSchoolNameMapForUserIds($vehicles->pluck('user_id')->all());
        $schoolNamesBySchoolId = Schema::hasColumn('vehicles', 'school_id')
            ? $this->getSchoolNameMapForSchoolIds($vehicles->pluck('school_id')->all())
            : [];
        $vehicleTypeIds = $vehicles->pluck('vehicle_type_id')
            ->filter(fn ($value) => is_numeric($value) && (int) $value > 0)
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
        $vehicleTypeSchoolNames = [];

        if (! empty($vehicleTypeIds)) {
            $vehicleTypeColumns = ['id', 'user_id'];
            if (Schema::hasColumn('vehicle_types', 'school_id')) {
                $vehicleTypeColumns[] = 'school_id';
            }

            $vehicleTypes = DB::table('vehicle_types')
                ->whereIn('id', $vehicleTypeIds)
                ->select($vehicleTypeColumns)
                ->get();

            $vehicleTypeSchoolNamesByUserId = $this->getSchoolNameMapForUserIds($vehicleTypes->pluck('user_id')->all());
            $vehicleTypeSchoolNamesBySchoolId = Schema::hasColumn('vehicle_types', 'school_id')
                ? $this->getSchoolNameMapForSchoolIds($vehicleTypes->pluck('school_id')->all())
                : [];

            foreach ($vehicleTypes as $vehicleType) {
                $vehicleTypeSchoolNames[(int) $vehicleType->id] = $vehicleTypeSchoolNamesBySchoolId[(int) ($vehicleType->school_id ?? 0)]
                    ?? $vehicleTypeSchoolNamesByUserId[(int) ($vehicleType->user_id ?? 0)]
                    ?? null;
            }
        }
        $resolved = [];
        $unresolvedVehicleIds = [];

        foreach ($vehicles as $vehicle) {
            $schoolName = $schoolNamesBySchoolId[(int) ($vehicle->school_id ?? 0)]
                ?? $schoolNamesByUserId[(int) ($vehicle->user_id ?? 0)]
                ?? $vehicleTypeSchoolNames[(int) ($vehicle->vehicle_type_id ?? 0)]
                ?? null;
            if ($schoolName) {
                $resolved[(int) $vehicle->id] = $schoolName;
                continue;
            }

            $unresolvedVehicleIds[] = (int) $vehicle->id;
        }

        if (! empty($unresolvedVehicleIds)) {
            $routes = DB::table('routes')
                ->whereIn('bus_id', $unresolvedVehicleIds)
                ->where(function ($query) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                })
                ->orderByDesc('id')
                ->select('bus_id', 'school_id', 'user_id')
                ->get();

            $schoolNamesBySchoolId = $this->getSchoolNameMapForSchoolIds($routes->pluck('school_id')->all());
            $routeUserNames = $this->getSchoolNameMapForUserIds($routes->pluck('user_id')->all());

            foreach ($routes as $route) {
                $vehicleId = (int) ($route->bus_id ?? 0);
                if ($vehicleId <= 0 || isset($resolved[$vehicleId])) {
                    continue;
                }

                $resolved[$vehicleId] = $schoolNamesBySchoolId[(int) ($route->school_id ?? 0)]
                    ?? $routeUserNames[(int) ($route->user_id ?? 0)]
                    ?? '-';
            }
        }

        foreach ($vehicleIds as $vehicleId) {
            $resolved[$vehicleId] = $resolved[$vehicleId] ?? '-';
        }

        return $resolved;
    }

    protected function getSchoolNameMapForDriverIds(array $driverIds): array
    {
        $driverIds = array_values(array_unique(array_filter(array_map(function ($value) {
            return is_numeric($value) ? (int) $value : null;
        }, $driverIds), fn ($value) => $value && $value > 0)));

        if (empty($driverIds)) {
            return [];
        }

        $driverColumns = ['id', 'user_id', 'vehicle_id'];
        if (Schema::hasColumn('drivers', 'school_id')) {
            $driverColumns[] = 'school_id';
        }

        $drivers = DB::table('drivers')
            ->whereIn('id', $driverIds)
            ->select($driverColumns)
            ->get();

        $schoolNamesByUserId = $this->getSchoolNameMapForUserIds($drivers->pluck('user_id')->all());
        $schoolNamesBySchoolId = Schema::hasColumn('drivers', 'school_id')
            ? $this->getSchoolNameMapForSchoolIds($drivers->pluck('school_id')->all())
            : [];
        $schoolNamesByVehicleId = $this->getSchoolNameMapForVehicleIds($drivers->pluck('vehicle_id')->all());
        $resolved = [];
        $unresolvedDriverIds = [];

        foreach ($drivers as $driver) {
            $driverId = (int) $driver->id;
            $schoolName = $schoolNamesBySchoolId[(int) ($driver->school_id ?? 0)]
                ?? $schoolNamesByUserId[(int) ($driver->user_id ?? 0)]
                ?? $schoolNamesByVehicleId[(int) ($driver->vehicle_id ?? 0)]
                ?? null;

            if ($schoolName) {
                $resolved[$driverId] = $schoolName;
                continue;
            }

            $unresolvedDriverIds[] = $driverId;
        }

        if (! empty($unresolvedDriverIds)) {
            $routes = DB::table('routes')
                ->whereIn('driver_id', $unresolvedDriverIds)
                ->where(function ($query) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                })
                ->orderByDesc('id')
                ->select('driver_id', 'school_id', 'user_id')
                ->get();

            $schoolNamesBySchoolId = $this->getSchoolNameMapForSchoolIds($routes->pluck('school_id')->all());
            $routeUserNames = $this->getSchoolNameMapForUserIds($routes->pluck('user_id')->all());

            foreach ($routes as $route) {
                $driverId = (int) ($route->driver_id ?? 0);
                if ($driverId <= 0 || isset($resolved[$driverId])) {
                    continue;
                }

                $resolved[$driverId] = $schoolNamesBySchoolId[(int) ($route->school_id ?? 0)]
                    ?? $routeUserNames[(int) ($route->user_id ?? 0)]
                    ?? '-';
            }
        }

        foreach ($driverIds as $driverId) {
            $resolved[$driverId] = $resolved[$driverId] ?? '-';
        }

        return $resolved;
    }

    protected function getSchoolNameMapForVehicleTypeIds(array $vehicleTypeIds): array
    {
        $vehicleTypeIds = array_values(array_unique(array_filter(array_map(function ($value) {
            return is_numeric($value) ? (int) $value : null;
        }, $vehicleTypeIds), fn ($value) => $value && $value > 0)));

        if (empty($vehicleTypeIds)) {
            return [];
        }

        $vehicleTypeColumns = ['id', 'user_id'];
        if (Schema::hasColumn('vehicle_types', 'school_id')) {
            $vehicleTypeColumns[] = 'school_id';
        }

        $vehicleTypes = DB::table('vehicle_types')
            ->whereIn('id', $vehicleTypeIds)
            ->select($vehicleTypeColumns)
            ->get();

        $schoolNamesByUserId = $this->getSchoolNameMapForUserIds($vehicleTypes->pluck('user_id')->all());
        $schoolNamesBySchoolId = Schema::hasColumn('vehicle_types', 'school_id')
            ? $this->getSchoolNameMapForSchoolIds($vehicleTypes->pluck('school_id')->all())
            : [];
        $resolved = [];
        $unresolvedVehicleTypeIds = [];

        foreach ($vehicleTypes as $vehicleType) {
            $vehicleTypeId = (int) $vehicleType->id;
            $schoolName = $schoolNamesBySchoolId[(int) ($vehicleType->school_id ?? 0)]
                ?? $schoolNamesByUserId[(int) ($vehicleType->user_id ?? 0)]
                ?? null;
            if ($schoolName) {
                $resolved[$vehicleTypeId] = $schoolName;
                continue;
            }

            $unresolvedVehicleTypeIds[] = $vehicleTypeId;
        }

        if (! empty($unresolvedVehicleTypeIds)) {
            $vehicles = DB::table('vehicles')
                ->whereIn('vehicle_type_id', $unresolvedVehicleTypeIds)
                ->where(function ($query) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                })
                ->orderByDesc('id')
                ->select('vehicle_type_id', 'id')
                ->get();

            $schoolNamesByVehicleId = $this->getSchoolNameMapForVehicleIds($vehicles->pluck('id')->all());

            foreach ($vehicles as $vehicle) {
                $vehicleTypeId = (int) ($vehicle->vehicle_type_id ?? 0);
                if ($vehicleTypeId <= 0 || isset($resolved[$vehicleTypeId])) {
                    continue;
                }

                $resolved[$vehicleTypeId] = $schoolNamesByVehicleId[(int) ($vehicle->id ?? 0)] ?? '-';
            }
        }

        foreach ($vehicleTypeIds as $vehicleTypeId) {
            $resolved[$vehicleTypeId] = $resolved[$vehicleTypeId] ?? '-';
        }

        return $resolved;
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

    protected function storeLoginUserPhotoFromUpload($image, int $userId): ?string
    {
        if (! $image || $userId <= 0) {
            return null;
        }

        $extension = strtolower((string) $image->getClientOriginalExtension());
        if ($extension === '') {
            $extension = 'jpg';
        }

        $imageName = 'user_' . $userId . '.' . $extension;
        $tmpPath = $image->getRealPath();
        $publicDestinationDirectory = public_path('storage/profile_pictures');
        $publicDestinationPath = $publicDestinationDirectory . DIRECTORY_SEPARATOR . $imageName;
        $storageMirrorDirectory = storage_path('app/public/profile_pictures');
        $storageMirrorPath = $storageMirrorDirectory . DIRECTORY_SEPARATOR . $imageName;

        if (! is_dir($publicDestinationDirectory) && ! @mkdir($publicDestinationDirectory, 0777, true) && ! is_dir($publicDestinationDirectory)) {
            return null;
        }

        // Preserve the uploaded photo better for mobile profile avatars.
        // If resizing fails (small image, unsupported GD codec, etc.), keep
        // the original upload instead of failing the entire profile update.
        $stored = ImageHelper::cropAndResize($tmpPath, $publicDestinationPath, 320, 320, false);
        if (! $stored && ! @copy($tmpPath, $publicDestinationPath)) {
            return null;
        }

        // Best-effort mirror for environments that still read from the
        // framework storage path directly. The public copy is the one the app
        // serves, so a mirror failure should not break profile saving.
        if (
            $storageMirrorDirectory !== $publicDestinationDirectory &&
            (
                (is_dir($storageMirrorDirectory) || @mkdir($storageMirrorDirectory, 0777, true) || is_dir($storageMirrorDirectory))
                && ! @copy($publicDestinationPath, $storageMirrorPath)
            )
        ) {
            // Ignore storage mirror failures. The public file already exists.
        }

        return 'profile_pictures/' . $imageName;
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

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

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
}

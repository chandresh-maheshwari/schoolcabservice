<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSchoolSlugMatchesUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (! $user) {
            abort(404);
        }

        $routeSlug = (string) $request->route('schoolSlug');
        $routeSlug = trim($routeSlug);
        if ($routeSlug === '') {
            abort(404);
        }

        $normalizedSlug = strtolower($routeSlug);

        // Always allow the user to access the panel for the school they are linked to
        // (even if role detection is misconfigured).
        $ownedSchool = School::where('deleted', 0)
            ->where('user_id', $user->id)
            ->whereRaw('LOWER(slug) = ?', [$normalizedSlug])
            ->first();

        if ($ownedSchool) {
            $school = $ownedSchool;
        } else {
            $isPrivileged = (method_exists($user, 'isAdmin') && $user->isAdmin())
                || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());

            // School users should be able to access the slug panel they logged into.
            // Data access is still constrained by per-module scoping + permission middleware.
            $isSchoolUser = method_exists($user, 'isSchool') && $user->isSchool();
            if (! $isPrivileged && ! $isSchoolUser) {
                abort(404);
            }

            $school = School::where('deleted', 0)
                ->whereRaw('LOWER(slug) = ?', [$normalizedSlug])
                ->first();

            if (! $school) {
                abort(404);
            }
        }

        // Reuse later in controllers/views if needed.
        $request->attributes->set('current_school', $school);

        return $next($request);
    }
}


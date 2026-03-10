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
        if (! $user || ! method_exists($user, 'isSchool') || ! $user->isSchool()) {
            abort(404);
        }

        $routeSlug = (string) $request->route('schoolSlug');
        $routeSlug = trim($routeSlug);
        if ($routeSlug === '') {
            abort(404);
        }

        $school = School::where('deleted', 0)->where('user_id', $user->id)->first();
        if (! $school || trim((string) $school->slug) === '') {
            abort(404);
        }

        if (! hash_equals(strtolower($school->slug), strtolower($routeSlug))) {
            abort(404);
        }

        // Reuse later in controllers/views if needed.
        $request->attributes->set('current_school', $school);

        return $next($request);
    }
}


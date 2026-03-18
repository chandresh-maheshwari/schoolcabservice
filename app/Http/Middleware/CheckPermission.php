<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\School;
use App\Support\PermissionName;

class CheckPermission
{
    private function isApiRequest(Request $request): bool
    {
        return $request->expectsJson() || $request->is('api/*');
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            if ($this->isApiRequest($request)) {
                return response()->json(['success' => false, 'message' => 'User not authenticated.'], 401);
            }

            return redirect('/');
        }

        $routeName = Route::currentRouteName();
        
        if (!$routeName) {
            return $next($request);
        }

        $permissionName = PermissionName::normalize($routeName);
        if ($permissionName === null) {
            return $next($request);
        }

        if (!Auth::user()->canAccessAdminRoute($permissionName)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to perform this action.'], 403);
            }

            $user = Auth::user();
            if ($user && method_exists($user, 'isSchool') && $user->isSchool()) {
                $schoolSlug = $request->route('schoolSlug') ?: School::where('deleted', 0)->where('user_id', $user->id)->value('slug');
                $schoolSlug = trim((string) $schoolSlug);
                if ($schoolSlug !== '' && Route::has('school.dashboard')) {
                    return redirect()->route('school.dashboard', ['schoolSlug' => $schoolSlug])
                        ->with('error', 'You do not have permission to access this page.');
                }
            }

            return redirect()->route('admin_layout.index')
                ->with('error', 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}

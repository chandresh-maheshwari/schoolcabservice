<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\School;

class CheckPermission
{
    private function isApiRequest(Request $request): bool
    {
        return $request->expectsJson() || $request->is('api/*');
    }

    /**
     * Map route names (especially API helper endpoints) to the CRUD permission name
     * shown/managed in the Role UI.
     *
     * Returns null to bypass permission checks for the route.
     */
    private function mapRouteNameToPermission(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        $name = $routeName;
        if (str_starts_with($name, 'api.')) {
            $name = substr($name, 4);
        }

        // Auth/session helper endpoints should be available for any authenticated user.
        $alwaysAllowed = [
            'logout',
            'refreshToken',
            'sendOtp',
            'verifyOtp',
            'resetnewPassword',
        ];
        if (in_array($name, $alwaysAllowed, true)) {
            return null;
        }

        // DataTable endpoints (legacy names without module prefix).
        $singleNameMap = [
            'rolelist' => 'roles.index',
            'userlist' => 'users.index',
            'toggle-user-status' => 'users.update',
        ];
        if (isset($singleNameMap[$name])) {
            return $singleNameMap[$name];
        }

        $parts = explode('.', $name);
        if (count($parts) < 2) {
            return $name;
        }

        $action = $parts[count($parts) - 1];
        $actionLower = strtolower($action);

         $actionMap = [
             'list' => 'index',
            'deleted-list' => 'trash',
             'multi-delete' => 'destroy',
             'togglestatus' => 'update',
             'update-photo' => 'update',
             'deleteimage' => 'update',
             'vehicleimage' => 'update',
            'rcimage' => 'update',
            'insuranceimage' => 'update',
            'licenseimage' => 'update',
            'adharcardimage' => 'update',
            'childimage' => 'update',
            'childadhaarimage' => 'update',
            'aboutimage' => 'update',
            'changepassword' => 'update',
            'delete' => 'destroy',
            'delete-all' => 'destroy',
            'force-delete' => 'destroy',
        ];

        if (isset($actionMap[$actionLower])) {
            $parts[count($parts) - 1] = $actionMap[$actionLower];
        }

        return implode('.', $parts);
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

        $permissionName = $this->mapRouteNameToPermission($routeName);
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

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Helpers\SchoolBranding;
use App\Models\School;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        User::observe(UserObserver::class);

        View::composer('*', function ($view) {
            $view->with('schoolBranding', SchoolBranding::current());

            $schoolSlug = null;
            $authPermissions = [];
            $authIsSuperAdmin = false;
            $authCanAccessAllAdminRoutes = false;
            try {
                $user = Auth::user();
                if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
                    $authCanAccessAllAdminRoutes = true;
                }

                if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                    $authIsSuperAdmin = true;
                }

                if ($user && isset($user->role_id) && is_numeric($user->role_id)) {
                    $roleId = (int) $user->role_id;
                    $authPermissions = DB::table('role_permission')
                        ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
                        ->where('role_permission.role_id', $roleId)
                        ->where('permissions.deleted', 0)
                        ->pluck('permissions.name')
                        ->map(fn ($name) => (string) $name)
                        ->values()
                        ->all();
                }

                if ($user && method_exists($user, 'isSchool') && $user->isSchool()) {
                    $schoolSlug = School::where('deleted', 0)
                        ->where('user_id', $user->id)
                        ->value('slug');
                }
            } catch (\Throwable $e) {
                $schoolSlug = null;
            }

            // Ensure critical management pages remain visible for Super Admins even if permissions are missing.
            if ($authIsSuperAdmin) {
                $authPermissions = array_values(array_unique(array_merge($authPermissions, [
                    'roles.index',
                    'roles.create',
                    'roles.store',
                    'roles.edit',
                    'roles.update',
                    'roles.destroy',
                    'permissions.index',
                    'permissions.create',
                    'permissions.store',
                    'permissions.edit',
                    'permissions.update',
                    'permissions.destroy',
                ])));
            }

            $view->with('currentSchoolSlug', $schoolSlug);
            $view->with('authPermissionNames', $authPermissions);
            $view->with('authIsSuperAdmin', $authIsSuperAdmin);
            $view->with('authCanAccessAllAdminRoutes', $authCanAccessAllAdminRoutes);
        });

        // Auto-fill creator id for any model/table that has a user_id column.
        EloquentModel::creating(function ($model) {
            if (! empty($model->getAttribute('user_id'))) {
                return;
            }

            $currentUserId = Auth::id();

            if (! $currentUserId) {
                $sessionUserId = Session::get('userid');
                if (is_numeric($sessionUserId) && (int) $sessionUserId > 0) {
                    $currentUserId = (int) $sessionUserId;
                }
            }

            if (! $currentUserId) {
                try {
                    if (request()->bearerToken()) {
                        $jwtUser = JWTAuth::parseToken()->authenticate();
                        if ($jwtUser) {
                            Auth::setUser($jwtUser);
                            $currentUserId = $jwtUser->id;
                        }
                    }
                } catch (\Throwable $e) {
                    $currentUserId = null;
                }
            }

            if (! $currentUserId) {
                $headerUserId = request()->header('X-Auth-User-Id');
                if (is_numeric($headerUserId) && (int) $headerUserId > 0) {
                    $currentUserId = (int) $headerUserId;
                }
            }

            if (! $currentUserId) {
                $inputUserId = request()->input('user_id');
                if (is_numeric($inputUserId) && (int) $inputUserId > 0) {
                    $currentUserId = (int) $inputUserId;
                }
            }

            if (! $currentUserId) {
                return;
            }

            static $tableHasUserId = [];
            $table = $model->getTable();

            if (! array_key_exists($table, $tableHasUserId)) {
                try {
                    $tableHasUserId[$table] = Schema::hasTable($table) && Schema::hasColumn($table, 'user_id');
                } catch (\Throwable $e) {
                    $tableHasUserId[$table] = false;
                }
            }

            if (! $tableHasUserId[$table]) {
                return;
            }

            $model->setAttribute('user_id', $currentUserId);
        });
    }
}

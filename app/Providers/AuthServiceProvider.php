<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, string $ability) {
            if (! $user instanceof User) {
                return null;
            }

            $normalized = trim((string) $ability);
            if ($normalized === '') {
                return null;
            }

            // Keep `@can('api.vehicle.store')` compatible with our route-permission system.
            if (str_starts_with($normalized, 'api.')) {
                $normalized = substr($normalized, 4);
            }

            // Map helper endpoints to CRUD permission names.
            $parts = explode('.', $normalized);
            if (count($parts) >= 2) {
                $action = strtolower((string) $parts[count($parts) - 1]);
                $map = [
                    'list' => 'index',
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

                if (isset($map[$action])) {
                    $parts[count($parts) - 1] = $map[$action];
                    $normalized = implode('.', $parts);
                }
            }

            return $user->canAccessAdminRoute($normalized);
        });
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Tymon\JWTAuth\Facades\JWTAuth;

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

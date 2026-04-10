<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('routes')) {
            Schema::create('routes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('school_id')->nullable();
                $table->string('name');
                $table->unsignedBigInteger('bus_id')->nullable();
                $table->unsignedBigInteger('driver_id')->nullable();
                $table->json('route_json')->nullable();
                $table->tinyInteger('status')->default(0);
                $table->tinyInteger('deleted')->default(0);
                $table->timestamps();

                $table->index('user_id');
                $table->index('school_id');
                $table->index('bus_id');
                $table->index('driver_id');
            });

            return;
        }

        Schema::table('routes', function (Blueprint $table) {
            if (! Schema::hasColumn('routes', 'route_json')) {
                $table->json('route_json')->nullable()->after('driver_id');
            }

            if (! Schema::hasColumn('routes', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->index('user_id');
            }

            if (! Schema::hasColumn('routes', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('user_id');
                $table->index('school_id');
            }

            if (! Schema::hasColumn('routes', 'status')) {
                $table->tinyInteger('status')->default(0)->after('route_json');
            }

            if (! Schema::hasColumn('routes', 'deleted')) {
                $table->tinyInteger('deleted')->default(0)->after('status');
            }
        });

        $selectColumns = ['id', 'route_json'];
        if (Schema::hasColumn('routes', 'geojson')) {
            $selectColumns[] = 'geojson';
        }
        if (Schema::hasColumn('routes', 'stops')) {
            $selectColumns[] = 'stops';
        }

        $routes = DB::table('routes')
            ->select($selectColumns)
            ->orderBy('id')
            ->get();

        foreach ($routes as $route) {
            $existingRouteJson = $this->decodeJson($route->route_json);
            if (is_array($existingRouteJson) && ! empty($existingRouteJson)) {
                continue;
            }

            $legacyGeoJson = property_exists($route, 'geojson')
                ? $this->decodeJson($route->geojson)
                : null;
            $legacyStops = property_exists($route, 'stops')
                ? $this->decodeJson($route->stops)
                : null;

            $normalizedRouteJson = [
                'geojson' => is_array($legacyGeoJson) ? $legacyGeoJson : null,
                'stops' => is_array($legacyStops) ? array_values($legacyStops) : [],
            ];

            DB::table('routes')
                ->where('id', $route->id)
                ->update([
                    'route_json' => json_encode($normalizedRouteJson),
                ]);
        }

        Schema::table('routes', function (Blueprint $table) {
            if (Schema::hasColumn('routes', 'geojson')) {
                $table->dropColumn('geojson');
            }

            if (Schema::hasColumn('routes', 'stops')) {
                $table->dropColumn('stops');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('routes')) {
            return;
        }

        Schema::table('routes', function (Blueprint $table) {
            if (! Schema::hasColumn('routes', 'geojson')) {
                $table->json('geojson')->nullable()->after('driver_id');
            }

            if (! Schema::hasColumn('routes', 'stops')) {
                $table->json('stops')->nullable()->after('geojson');
            }
        });

        $routes = DB::table('routes')
            ->select('id', 'route_json')
            ->orderBy('id')
            ->get();

        foreach ($routes as $route) {
            $decodedRouteJson = $this->decodeJson($route->route_json);

            DB::table('routes')
                ->where('id', $route->id)
                ->update([
                    'geojson' => json_encode($decodedRouteJson['geojson'] ?? null),
                    'stops' => json_encode($decodedRouteJson['stops'] ?? []),
                ]);
        }

        Schema::table('routes', function (Blueprint $table) {
            if (Schema::hasColumn('routes', 'route_json')) {
                $table->dropColumn('route_json');
            }
        });
    }

    private function decodeJson($value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'vehicle_types',
        'vehicles',
        'drivers',
        'stops_pickup',
        'driver_vehicle_histories',
        'driver_vehicle_histories',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'school_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable()->after('user_id');
                $table->index('school_id');
            });
        }

        $this->backfillSchoolIds();
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'school_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('school_id');
            });
        }
    }

    private function backfillSchoolIds(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'school_id')) {
                continue;
            }

            DB::table($tableName)
                ->join('schools', function ($join) use ($tableName) {
                    $join->on($tableName.'.user_id', '=', 'schools.user_id')
                        ->where('schools.deleted', 0);
                })
                ->whereNull($tableName.'.school_id')
                ->update([$tableName.'.school_id' => DB::raw('schools.id')]);
        }

        if (Schema::hasTable('routes') && Schema::hasColumn('routes', 'school_id')) {
            if (Schema::hasColumn('vehicles', 'school_id')) {
                DB::table('vehicles')
                    ->join('routes', 'routes.bus_id', '=', 'vehicles.id')
                    ->whereNull('vehicles.school_id')
                    ->whereNotNull('routes.school_id')
                    ->update(['vehicles.school_id' => DB::raw('routes.school_id')]);
            }

            if (Schema::hasColumn('drivers', 'school_id')) {
                DB::table('drivers')
                    ->join('routes', 'routes.driver_id', '=', 'drivers.id')
                    ->whereNull('drivers.school_id')
                    ->whereNotNull('routes.school_id')
                    ->update(['drivers.school_id' => DB::raw('routes.school_id')]);
            }

            if (Schema::hasColumn('stops_pickup', 'school_id')) {
                DB::table('stops_pickup')
                    ->join('routes', 'routes.id', '=', 'stops_pickup.route_id')
                    ->whereNull('stops_pickup.school_id')
                    ->whereNotNull('routes.school_id')
                    ->update(['stops_pickup.school_id' => DB::raw('routes.school_id')]);
            }
        }

        if (Schema::hasColumn('vehicles', 'school_id') && Schema::hasColumn('vehicle_types', 'school_id')) {
            DB::table('vehicle_types')
                ->join('vehicles', 'vehicles.vehicle_type_id', '=', 'vehicle_types.id')
                ->whereNull('vehicle_types.school_id')
                ->whereNotNull('vehicles.school_id')
                ->update(['vehicle_types.school_id' => DB::raw('vehicles.school_id')]);
        }

        if (Schema::hasColumn('driver_vehicle_histories', 'school_id') && Schema::hasColumn('vehicles', 'school_id')) {
            DB::table('driver_vehicle_histories')
                ->join('vehicles', 'vehicles.id', '=', 'driver_vehicle_histories.vehicle_id')
                ->whereNull('driver_vehicle_histories.school_id')
                ->whereNotNull('vehicles.school_id')
                ->update(['driver_vehicle_histories.school_id' => DB::raw('vehicles.school_id')]);
        }
    }
};

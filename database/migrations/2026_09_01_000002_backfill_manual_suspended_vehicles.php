<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')
            || ! Schema::hasTable('emergency_incidents')
            || ! Schema::hasColumn('vehicles', 'manual_suspended')
            || ! Schema::hasColumn('vehicles', 'availability_status')) {
            return;
        }

        // Existing suspended vehicles without an active incident were suspended manually.
        DB::table('vehicles')
            ->whereRaw("LOWER(TRIM(COALESCE(availability_status, 'available'))) = 'emergency'")
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('emergency_incidents')
                    ->whereColumn('emergency_incidents.vehicle_id', 'vehicles.id')
                    ->where('emergency_incidents.deleted', 0)
                    ->where('emergency_incidents.status', 1);
            })
            ->update(['manual_suspended' => true]);
    }

    public function down(): void
    {
        // Backfilled state represents user intent and must not be removed on rollback.
    }
};

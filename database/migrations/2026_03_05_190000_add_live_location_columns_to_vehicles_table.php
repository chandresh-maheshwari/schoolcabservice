<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'current_latitude')) {
                $table->decimal('current_latitude', 10, 7)->nullable()->after('insurance_image');
            }
            if (! Schema::hasColumn('vehicles', 'current_longitude')) {
                $table->decimal('current_longitude', 10, 7)->nullable()->after('current_latitude');
            }
            if (! Schema::hasColumn('vehicles', 'current_speed_kmh')) {
                $table->decimal('current_speed_kmh', 6, 2)->nullable()->after('current_longitude');
            }
            if (! Schema::hasColumn('vehicles', 'location_source')) {
                $table->string('location_source', 50)->nullable()->default('openstream')->after('current_speed_kmh');
            }
            if (! Schema::hasColumn('vehicles', 'location_recorded_at')) {
                $table->timestamp('location_recorded_at')->nullable()->after('location_source');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $columns = [
                'current_latitude',
                'current_longitude',
                'current_speed_kmh',
                'location_source',
                'location_recorded_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('vehicles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};


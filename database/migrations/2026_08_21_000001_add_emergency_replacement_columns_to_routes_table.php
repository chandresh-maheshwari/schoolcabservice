<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('routes')) {
            return;
        }

        Schema::table('routes', function (Blueprint $table) {
            if (! Schema::hasColumn('routes', 'emergency_original_bus_id')) {
                $table->unsignedBigInteger('emergency_original_bus_id')->nullable()->after('bus_id');
            }

            if (! Schema::hasColumn('routes', 'emergency_replacement_bus_id')) {
                $table->unsignedBigInteger('emergency_replacement_bus_id')->nullable()->after('emergency_original_bus_id');
            }

            if (! Schema::hasColumn('routes', 'emergency_replaced_at')) {
                $table->timestamp('emergency_replaced_at')->nullable()->after('emergency_replacement_bus_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('routes')) {
            return;
        }

        Schema::table('routes', function (Blueprint $table) {
            $columns = [
                'emergency_original_bus_id',
                'emergency_replacement_bus_id',
                'emergency_replaced_at',
            ];

            $existingColumns = array_values(array_filter($columns, fn ($column) => Schema::hasColumn('routes', $column)));
            if (! empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};

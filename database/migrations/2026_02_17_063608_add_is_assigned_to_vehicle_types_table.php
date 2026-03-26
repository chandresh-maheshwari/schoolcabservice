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
        if (! Schema::hasTable('vehicle_types') || Schema::hasColumn('vehicle_types', 'is_assigned')) {
            return;
        }

        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->integer('is_assigned')->default(0)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('vehicle_types') || ! Schema::hasColumn('vehicle_types', 'is_assigned')) {
            return;
        }

        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->dropColumn('is_assigned');
        });
    }
};

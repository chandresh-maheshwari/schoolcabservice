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
        if (! Schema::hasTable('stops_pickup') || ! Schema::hasColumn('stops_pickup', 'name_id') || Schema::hasColumn('stops_pickup', 'route_id')) {
            return;
        }

        Schema::table('stops_pickup', function (Blueprint $table) {
            $table->renameColumn('name_id', 'route_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('stops_pickup') || ! Schema::hasColumn('stops_pickup', 'route_id') || Schema::hasColumn('stops_pickup', 'name_id')) {
            return;
        }

        Schema::table('stops_pickup', function (Blueprint $table) {
            $table->renameColumn('route_id', 'name_id');
        });
    }
};

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
        if (! Schema::hasTable('stops_pickup') || ! Schema::hasColumn('stops_pickup', 'pickup_name')) {
            return;
        }

        Schema::table('stops_pickup', function (Blueprint $table) {
            $table->text('pickup_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('stops_pickup') || ! Schema::hasColumn('stops_pickup', 'pickup_name')) {
            return;
        }

        Schema::table('stops_pickup', function (Blueprint $table) {
            $table->string('pickup_name', 255)->nullable()->change();
        });
    }
};

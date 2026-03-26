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
        if (! Schema::hasTable('routes') || Schema::hasColumn('routes', 'status')) {
            return;
        }

        Schema::table('routes', function (Blueprint $table) {
            $table->tinyInteger('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('routes') || ! Schema::hasColumn('routes', 'status')) {
            return;
        }

        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

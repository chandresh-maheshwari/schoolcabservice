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
        if (! Schema::hasTable('children') || Schema::hasColumn('children', 'child_name')) {
            return;
        }

        Schema::table('children', function (Blueprint $table) {
            $table->string('child_name')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('children') || ! Schema::hasColumn('children', 'child_name')) {
            return;
        }

        Schema::table('children', function (Blueprint $table) {
            $table->dropColumn('child_name');
        });
    }
};

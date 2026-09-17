<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['parents', 'drivers', 'vehicles'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'current_address')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->text('current_address')->nullable()->after('user_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['parents', 'drivers', 'vehicles'] as $tableName) {
            if (Schema::hasColumn($tableName, 'current_address')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('current_address');
                });
            }
        }
    }
};

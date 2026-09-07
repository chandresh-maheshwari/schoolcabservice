<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles') || Schema::hasColumn('vehicles', 'manual_suspended')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $column = $table->boolean('manual_suspended')->default(false);
            if (Schema::hasColumn('vehicles', 'availability_status')) {
                $column->after('availability_status');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'manual_suspended')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('manual_suspended');
            });
        }
    }
};

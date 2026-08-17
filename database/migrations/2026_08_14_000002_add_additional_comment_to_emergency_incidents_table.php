<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('emergency_incidents') || Schema::hasColumn('emergency_incidents', 'additional_comment')) {
            return;
        }

        Schema::table('emergency_incidents', function (Blueprint $table) {
            $table->text('additional_comment')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('emergency_incidents') || ! Schema::hasColumn('emergency_incidents', 'additional_comment')) {
            return;
        }

        Schema::table('emergency_incidents', function (Blueprint $table) {
            $table->dropColumn('additional_comment');
        });
    }
};

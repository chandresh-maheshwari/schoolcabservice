<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('package_details') || Schema::hasColumn('package_details', 'school_id')) {
            return;
        }

        Schema::table('package_details', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable()->after('user_id');
            $table->index('school_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('package_details') || ! Schema::hasColumn('package_details', 'school_id')) {
            return;
        }

        Schema::table('package_details', function (Blueprint $table) {
            $table->dropIndex(['school_id']);
            $table->dropColumn('school_id');
        });
    }
};

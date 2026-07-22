<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('package_details') || ! Schema::hasColumn('package_details', 'school_id')) {
            return;
        }

        DB::statement('ALTER TABLE package_details MODIFY school_id VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('package_details') || ! Schema::hasColumn('package_details', 'school_id')) {
            return;
        }

        DB::statement('ALTER TABLE package_details MODIFY school_id BIGINT UNSIGNED NULL');
    }
};

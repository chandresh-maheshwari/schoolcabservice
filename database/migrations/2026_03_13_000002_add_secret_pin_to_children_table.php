<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('children')) {
            return;
        }

        if (! Schema::hasColumn('children', 'secret_pin')) {
            Schema::table('children', function (Blueprint $table) {
                $table->string('secret_pin', 10)->nullable()->after('route_id');
            });
        }

        // Backfill existing children with a random 4-digit PIN (MySQL).
        // Keeps PIN null for deleted children if needed.
        try {
            DB::statement("
                UPDATE children
                SET secret_pin = LPAD(FLOOR(RAND() * 9000) + 1000, 4, '0')
                WHERE (secret_pin IS NULL OR secret_pin = '')
                  AND COALESCE(deleted, 0) = 0
            ");
        } catch (\Throwable $e) {
            // Ignore if database doesn't support the statement in some environments.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('children') && Schema::hasColumn('children', 'secret_pin')) {
            Schema::table('children', function (Blueprint $table) {
                $table->dropColumn('secret_pin');
            });
        }
    }
};


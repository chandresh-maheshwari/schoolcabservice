<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'dashboard_card_order')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('dashboard_card_order')->nullable()->after('followers');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'dashboard_card_order')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('dashboard_card_order');
            });
        }
    }
};

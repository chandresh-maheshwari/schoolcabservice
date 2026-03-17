<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('drivers') && ! Schema::hasColumn('drivers', 'login_user_id')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->unsignedBigInteger('login_user_id')->nullable()->after('user_id');
                $table->index('login_user_id', 'drivers_login_user_id_index');
            });
        }

        if (Schema::hasTable('parents') && ! Schema::hasColumn('parents', 'login_user_id')) {
            Schema::table('parents', function (Blueprint $table) {
                $table->unsignedBigInteger('login_user_id')->nullable()->after('user_id');
                $table->index('login_user_id', 'parents_login_user_id_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('drivers') && Schema::hasColumn('drivers', 'login_user_id')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->dropIndex('drivers_login_user_id_index');
                $table->dropColumn('login_user_id');
            });
        }

        if (Schema::hasTable('parents') && Schema::hasColumn('parents', 'login_user_id')) {
            Schema::table('parents', function (Blueprint $table) {
                $table->dropIndex('parents_login_user_id_index');
                $table->dropColumn('login_user_id');
            });
        }
    }
};

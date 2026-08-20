<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('routes')) {
            return;
        }

        Schema::table('routes', function (Blueprint $table) {
            if (! Schema::hasColumn('routes', 'state')) {
                $table->string('state', 255)->nullable()->after('name');
                $table->index('state');
            }

            if (! Schema::hasColumn('routes', 'city')) {
                $table->string('city', 255)->nullable()->after('state');
                $table->index('city');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('routes')) {
            return;
        }

        Schema::table('routes', function (Blueprint $table) {
            if (Schema::hasColumn('routes', 'city')) {
                $table->dropIndex(['city']);
                $table->dropColumn('city');
            }

            if (Schema::hasColumn('routes', 'state')) {
                $table->dropIndex(['state']);
                $table->dropColumn('state');
            }
        });
    }
};

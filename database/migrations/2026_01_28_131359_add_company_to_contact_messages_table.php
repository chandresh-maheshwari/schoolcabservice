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
        if (! Schema::hasTable('contact_messages') || Schema::hasColumn('contact_messages', 'company')) {
            return;
        }

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('company')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('contact_messages') || ! Schema::hasColumn('contact_messages', 'company')) {
            return;
        }

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn('company');
        });
    }
};

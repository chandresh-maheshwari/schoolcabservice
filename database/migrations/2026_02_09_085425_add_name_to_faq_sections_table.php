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
        if (! Schema::hasTable('faq_sections') || Schema::hasColumn('faq_sections', 'name')) {
            return;
        }

        Schema::table('faq_sections', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('faq_sections') || ! Schema::hasColumn('faq_sections', 'name')) {
            return;
        }

        Schema::table('faq_sections', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};

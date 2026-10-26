<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['drivers', 'parents', 'children', 'vehicles'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'document_field_sources')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->json('document_field_sources')->nullable());
            }
        }
    }

    public function down(): void
    {
        foreach (['drivers', 'parents', 'children', 'vehicles'] as $table) {
            if (Schema::hasColumn($table, 'document_field_sources')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('document_field_sources'));
            }
        }
    }
};

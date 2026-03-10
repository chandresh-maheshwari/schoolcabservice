<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'primary_color')) {
                $table->string('primary_color', 20)->nullable()->after('slug');
            }
            if (! Schema::hasColumn('schools', 'secondary_color')) {
                $table->string('secondary_color', 20)->nullable()->after('primary_color');
            }
            if (! Schema::hasColumn('schools', 'header_title')) {
                $table->string('header_title')->nullable()->after('secondary_color');
            }
            if (! Schema::hasColumn('schools', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('header_title');
            }
            if (! Schema::hasColumn('schools', 'logo_mini_path')) {
                $table->string('logo_mini_path')->nullable()->after('logo_path');
            }
            if (! Schema::hasColumn('schools', 'favicon_path')) {
                $table->string('favicon_path')->nullable()->after('logo_mini_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            foreach (['favicon_path', 'logo_mini_path', 'logo_path', 'header_title', 'secondary_color', 'primary_color'] as $column) {
                if (Schema::hasColumn('schools', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};


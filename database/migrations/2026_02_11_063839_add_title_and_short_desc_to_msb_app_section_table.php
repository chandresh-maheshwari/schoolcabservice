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
        if (! Schema::hasTable('msb_app_section')) {
            return;
        }

        Schema::table('msb_app_section', function (Blueprint $table) {
            if (! Schema::hasColumn('msb_app_section', 'title')) {
                $table->string('title')->nullable()->after('id');
            }
            if (! Schema::hasColumn('msb_app_section', 'short_desc')) {
                $table->text('short_desc')->nullable()->after('title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('msb_app_section')) {
            return;
        }

        Schema::table('msb_app_section', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('msb_app_section', 'title')) {
                $columns[] = 'title';
            }
            if (Schema::hasColumn('msb_app_section', 'short_desc')) {
                $columns[] = 'short_desc';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};

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
        Schema::table('msb_app_section', function (Blueprint $table) {
           $table->string('title')->nullable()->after('id');
        $table->text('short_desc')->nullable()->after('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('msb_app_section', function (Blueprint $table) {
                    $table->dropColumn(['title', 'short_desc']);

        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_details', function (Blueprint $table) {
            $table->string('short_description', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Preserve saved descriptions longer than 255 characters on rollback.
    }
};

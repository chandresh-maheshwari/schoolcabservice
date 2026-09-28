<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('children') && ! Schema::hasColumn('children', 'child_aadhaar_number')) {
            Schema::table('children', function (Blueprint $table) {
                $table->string('child_aadhaar_number', 12)->nullable()->after('child_adhaar_card_back_image');
                $table->index('child_aadhaar_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('children') && Schema::hasColumn('children', 'child_aadhaar_number')) {
            Schema::table('children', function (Blueprint $table) {
                $table->dropIndex(['child_aadhaar_number']);
                $table->dropColumn('child_aadhaar_number');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parents')) {
            return;
        }

        Schema::table('parents', function (Blueprint $table) {
            if (! Schema::hasColumn('parents', 'father_aadhaar_number')) {
                $table->string('father_aadhaar_number', 12)->nullable()->after('pincode');
            }

            if (! Schema::hasColumn('parents', 'mother_aadhaar_number')) {
                $table->string('mother_aadhaar_number', 12)->nullable()->after('father_aadhaar_number');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('parents')) {
            return;
        }

        Schema::table('parents', function (Blueprint $table) {
            if (Schema::hasColumn('parents', 'father_aadhaar_number')) {
                $table->dropColumn('father_aadhaar_number');
            }

            if (Schema::hasColumn('parents', 'mother_aadhaar_number')) {
                $table->dropColumn('mother_aadhaar_number');
            }
        });
    }
};

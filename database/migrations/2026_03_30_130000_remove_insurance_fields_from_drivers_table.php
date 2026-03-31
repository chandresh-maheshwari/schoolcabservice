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
        if (! Schema::hasTable('drivers')) {
            return;
        }

        Schema::table('drivers', function (Blueprint $table) {
            if (Schema::hasColumn('drivers', 'insurance_expiry_date')) {
                $table->dropColumn('insurance_expiry_date');
            }

            if (Schema::hasColumn('drivers', 'insurance_number')) {
                $table->dropColumn('insurance_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('drivers')) {
            return;
        }

        Schema::table('drivers', function (Blueprint $table) {
            if (! Schema::hasColumn('drivers', 'insurance_number')) {
                $table->string('insurance_number', 50)->nullable()->after('license_image');
            }

            if (! Schema::hasColumn('drivers', 'insurance_expiry_date')) {
                $table->date('insurance_expiry_date')->nullable()->after('insurance_number');
            }
        });
    }
};

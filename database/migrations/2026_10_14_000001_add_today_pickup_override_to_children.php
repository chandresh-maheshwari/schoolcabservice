<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('children', 'today_pickup_name')) {
            Schema::table('children', function (Blueprint $table) {
                $table->string('today_pickup_name')->nullable();
            });
        }
        if (! Schema::hasColumn('children', 'today_pickup_date')) {
            Schema::table('children', function (Blueprint $table) {
                $table->date('today_pickup_date')->nullable();
            });
        }
    }

    public function down(): void
    {
        // These columns are shared with the Node backend; preserve existing overrides.
    }
};

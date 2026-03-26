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
        if (! Schema::hasTable('driver_vehicle_histories')) {
            Schema::create('driver_vehicle_histories', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('driver_id')->nullable();
                $table->unsignedBigInteger('vehicle_id')->nullable();

                $table->tinyInteger('is_assigned')->default(0);
                $table->tinyInteger('deleted')->nullable();

                $table->timestamps();

                // Indexes
                $table->index('driver_id');
                $table->index('vehicle_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_vehicle_histories');
    }
};

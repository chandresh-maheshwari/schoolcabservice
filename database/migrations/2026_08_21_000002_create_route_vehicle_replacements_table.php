<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('route_vehicle_replacements')) {
            return;
        }

        Schema::create('route_vehicle_replacements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('route_id')->index();
            $table->unsignedBigInteger('original_bus_id')->nullable()->index();
            $table->unsignedBigInteger('replaced_bus_id')->nullable()->index();
            $table->unsignedBigInteger('replacement_bus_id')->index();
            $table->timestamp('replaced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_vehicle_replacements');
    }
};

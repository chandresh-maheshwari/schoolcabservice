<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('child_trip_pins')) {
            Schema::create('child_trip_pins', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('child_id');
                $table->unsignedBigInteger('trip_id')->nullable();
                $table->unsignedBigInteger('route_id')->nullable();
                $table->unsignedBigInteger('driver_user_id')->nullable();
                $table->string('trip_type', 32)->nullable();
                $table->string('pin', 4);
                $table->dateTime('expires_at');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();

                $table->index('child_id', 'child_trip_pins_child_id_idx');
                $table->index('trip_id', 'child_trip_pins_trip_id_idx');
                $table->index('expires_at', 'child_trip_pins_expires_at_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('child_trip_pins');
    }
};

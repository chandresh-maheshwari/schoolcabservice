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
        if (! Schema::hasTable('stops_pickup')) {
            Schema::create('stops_pickup', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('name_id')->nullable();
                $table->string('pickup_name')->nullable();
                $table->string('stop_name')->nullable();

                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();

                $table->integer('sequence_order')->nullable();

                $table->tinyInteger('status')->nullable();
                $table->tinyInteger('deleted')->nullable();

                $table->timestamps();

                // Index
                $table->index('name_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stops_pickup');
    }
};

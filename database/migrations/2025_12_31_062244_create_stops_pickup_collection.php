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
        Schema::create('stops_pickup', function (Blueprint $collection) {
            $collection->objectId('name')->nullable();
            $collection->string('pickup_name')->nullable();
             $collection->string('stop_name')->nullable();
            $collection->integer('latitude')->nullable();
            $collection->integer('longitude')->nullable();
            $collection->integer('sequence_order')->nullable();
            $collection->integer('status')->nullable();
            $collection->integer('deleted')->nullable();
            $collection->timestamp('created_at')->nullable();
            $collection->timestamp('updated_at')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stops_pickup');
    }
};

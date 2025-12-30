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
        Schema::create('ratings', function (Blueprint $collection) {
            $collection->objectId('user_id')->nullable();
            $collection->objectId('driver_name')->nullable();
             $collection->objectId('vehicle_number')->nullable();
            $collection->integer('rating')->nullable();
            $collection->string('comments')->nullable();
            $collection->integer('deleted')->nullable();
            $collection->timestamp('created_at')->nullable();
            $collection->timestamp('updated_at')->nullable();

            // Indexes
            $collection->index('user_id');
            $collection->index('driver_name');
            $collection->index('vehicle_number');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};

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
        Schema::create('emergency_incidents_collection', function (Blueprint $collection) {
            // MongoDB auto creates _id (ObjectId)

            $collection->objectId('user_id')->nullable();
            $collection->objectId('driver_id')->nullable();
            $collection->objectId('vehicle_id')->nullable();
            $collection->string('reported_by')->nullable();
            $collection->string('emergency_type');
            $collection->string('description')->nullable();
            $collection->integer('contact_number')->nullable();
            $collection->integer('status')->nullable();
            $collection->integer('deleted')->nullable();
            $collection->timestamp('created_at')->nullable();
            $collection->timestamp('updated_at')->nullable();

            // Indexes
            $collection->index('user_id');
            $collection->index('driver_id');
            $collection->index('vehicle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('emergency_incidents_collection');
    }
};

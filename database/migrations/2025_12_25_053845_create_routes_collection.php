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
      Schema::connection('mongodb')->create('routes', function (Blueprint $collection) {

            // MongoDB auto creates _id (ObjectId)

            $collection->objectId('school_id');
            $collection->string('name');

            $collection->objectId('bus_id')->nullable();
            $collection->objectId('driver_id')->nullable();

            $collection->json('geojson')->nullable();
            $collection->json('stops')->nullable();

            $collection->timestamp('created_at')->nullable();

            // Indexes
            $collection->index('school_id');
            $collection->index('bus_id');
            $collection->index('driver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('routes');
    }
};

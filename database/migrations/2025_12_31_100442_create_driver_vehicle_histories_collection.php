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
        Schema::create('driver_vehicle_histories',  function (Blueprint $collection) {
             $collection->objectId('driver_name')->nullable();
            $collection->objectId('vehicle_number')->nullable();
             $collection->integer('is_assigned')->default(0);
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
        Schema::dropIfExists('driver_vehicle_histories');
    }
};

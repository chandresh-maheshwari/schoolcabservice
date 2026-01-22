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
      Schema::create('routes', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('school_id')->nullable();
    $table->string('name');

    $table->unsignedBigInteger('bus_id')->nullable();
    $table->unsignedBigInteger('driver_id')->nullable();

    $table->json('geojson')->nullable();
    $table->json('stops')->nullable();

    $table->timestamps();

    // Indexes
    $table->index('school_id');
    $table->index('bus_id');
    $table->index('driver_id');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};

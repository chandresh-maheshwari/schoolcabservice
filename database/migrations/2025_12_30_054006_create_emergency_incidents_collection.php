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
       Schema::create('emergency_incidents', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('user_id')->nullable();
    $table->unsignedBigInteger('driver_id')->nullable();
    $table->unsignedBigInteger('vehicle_id')->nullable();

    $table->string('reported_by')->nullable();
    $table->string('emergency_type');
    $table->text('description')->nullable();

    $table->string('contact_number')->nullable();

    $table->tinyInteger('status')->nullable();
    $table->tinyInteger('deleted')->nullable();

    $table->timestamps();

    // Indexes
    $table->index('user_id');
    $table->index('driver_id');
    $table->index('vehicle_id');
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

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
      Schema::create('bookings', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('user_id')->nullable();
    $table->unsignedBigInteger('school_id')->nullable();
    $table->unsignedBigInteger('route_id')->nullable();
    $table->unsignedBigInteger('package_type_id')->nullable();
    $table->unsignedBigInteger('booking_type_id')->nullable();

    $table->string('short_description')->nullable();

    $table->decimal('latitude', 10, 8)->nullable();
    $table->decimal('longitude', 11, 8)->nullable();

    $table->string('payment_status')->nullable();
    $table->string('payment_mode')->nullable();

    $table->string('contact_number')->nullable();

    $table->tinyInteger('status')->nullable();
    $table->tinyInteger('deleted')->nullable();

    $table->timestamps();

    // Indexes
    $table->index('user_id');
    $table->index('school_id');
    $table->index('route_id');
    $table->index('package_type_id');
    $table->index('booking_type_id');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

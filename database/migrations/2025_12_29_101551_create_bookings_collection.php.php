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
        Schema::connection('mongodb')->create('bookings', function (Blueprint $collection) {

            $collection->objectId('user_id')->nullable();
            $collection->objectId('school_id')->nullable();
            $collection->objectId('route_id')->nullable();
            $collection->objectId('package_type')->nullable();
            $collection->objectId('booking_type')->nullable();
            $collection->string('short_description')->nullable();
            $collection->integer('latitude')->nullable();
            $collection->integer('longitude')->nullable();
            $collection->string('payment_status')->nullable();
            $collection->string('payment_mode')->nullable();
            $collection->integer('contact_number')->nullable();
            $collection->integer('status')->nullable();
            $collection->integer('deleted')->nullable();
            $collection->timestamp('created_at')->nullable();
            $collection->timestamp('updated_at')->nullable();

            $collection->index('user_id');
            $collection->index('school_id');
            $collection->index('route_id');
            $collection->index('package_type');
            $collection->index('booking_type');

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

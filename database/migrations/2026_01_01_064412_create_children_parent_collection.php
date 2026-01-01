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
        Schema::create('children_parent', function (Blueprint $collection) {

            $collection->string('child_name')->nullable();
            $collection->string('gender')->nullable();
            $collection->date('date_of_birth')->nullable();
            $collection->string('class')->nullable();
            $collection->string('section')->nullable();
            $collection->string('father_name')->nullable();
            $collection->string('mother_name')->nullable();
            $collection->integer('contact_number')->nullable();
            $collection->integer('alternative_contact_number')->nullable();
            $collection->string('email')->nullable();
            $collection->string('address_1')->nullable();
            $collection->string('address_2')->nullable();
            $collection->string('city')->nullable();
            $collection->string('state')->nullable();
            $collection->integer('pincode')->nullable();
            $collection->objectId('school_id')->nullable();
            $collection->objectId('pickup_id')->nullable();
            $collection->objectId('stop_id')->nullable();
            $collection->objectId('route_id')->nullable();
            $collection->integer('status')->nullable();
            $collection->integer('deleted')->nullable();
            $collection->timestamp('created_at')->nullable();
            $collection->timestamp('updated_at')->nullable();


             $collection->index('school_id');
            $collection->index('pickup_id');
            $collection->index('stop_id');
            $collection->index('route_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('children_parent');
    }
};

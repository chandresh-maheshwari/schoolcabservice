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
        Schema::create('children', function (Blueprint $collection) {
            $collection->objectId('parent_id')->nullable();
            $collection->objectId('school_id')->nullable();
            $collection->objectId('pickup_name')->nullable();
            $collection->objectId('stop_name')->nullable();
            $collection->objectId('route_id')->nullable();
            $collection->string('gender')->nullable();
            $collection->date('date_of_birth')->nullable();
            $collection->string('image')->nullable();
            $collection->string('child_adhaar_card_image')->nullable();
            $collection->string('class')->nullable();
            $collection->string('section')->nullable();
            $collection->integer('status')->nullable();
            $collection->integer('deleted')->nullable();
            $collection->timestamp('created_at')->nullable();
            $collection->timestamp('updated_at')->nullable();
            // Indexes
            $collection->index('parent_id');
            $collection->index('school_id');
            $collection->index('pickup_name');
            $collection->index('stop_name');
            $collection->index('route_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};

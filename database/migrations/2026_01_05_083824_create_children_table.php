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
       Schema::create('children', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('parent_id')->nullable();
    $table->unsignedBigInteger('school_id')->nullable();
    $table->unsignedBigInteger('pickup_name')->nullable();
    $table->unsignedBigInteger('stop_name')->nullable();
    $table->unsignedBigInteger('route_id')->nullable();

    $table->string('gender')->nullable();
    $table->date('date_of_birth')->nullable();
    $table->string('image')->nullable();
    $table->string('child_adhaar_card_image')->nullable();
    $table->string('class')->nullable();
    $table->string('section')->nullable();

    $table->tinyInteger('status')->nullable();
    $table->tinyInteger('deleted')->nullable();

    $table->timestamps();

    // Indexes
    $table->index('parent_id');
    $table->index('school_id');
    $table->index('pickup_name');
    $table->index('stop_name');
    $table->index('route_id');
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

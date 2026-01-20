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
     Schema::create('package_details', function (Blueprint $table) {
    $table->id();

    $table->string('package_name')->nullable();
    $table->string('package_type')->nullable();
    $table->string('booking_type')->nullable();

    $table->decimal('price', 10, 2)->nullable();

    $table->string('short_description')->nullable();
    $table->longText('description')->nullable();

    $table->integer('validity_days')->nullable();

    $table->tinyInteger('status')->nullable();
    $table->tinyInteger('deleted')->nullable();

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::connection('mongodb')->dropIfExists('package_details');
    }
};

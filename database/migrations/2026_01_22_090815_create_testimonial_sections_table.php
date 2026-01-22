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
        Schema::create('testimonial_sections', function (Blueprint $table) {
            $table->id();
             $table->string('name')->nullable();
             $table->text('description')->nullable();
             $table->string('profile_image')->nullable();
            $table->string('designation')->nullable();
            $table->string('tagline')->nullable();
            $table->integer('rating')->default(0);
              $table->tinyInteger('status')->default(0);
            $table->tinyInteger('deleted')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonial_sections');
    }
};

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
        if (! Schema::hasTable('about_sections')) {
            Schema::create('about_sections', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->string('button_name')->nullable();
                $table->string('button_link')->nullable();
                $table->tinyInteger('status')->nullable();
                $table->tinyInteger('deleted')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_sections');
    }
};

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
        if (! Schema::hasTable('how_it_works')) {
            Schema::create('how_it_works', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->string('button_name_1')->nullable();
                $table->string('button_link_1')->nullable();
                $table->string('button_name_2')->nullable();
                $table->string('button_link_2')->nullable();
                $table->integer('status')->default(0);
                $table->integer('deleted')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('how_it_works');
    }
};

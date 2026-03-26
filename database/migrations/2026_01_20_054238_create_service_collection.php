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
        if (! Schema::hasTable('service')) {
            Schema::create('service', function (Blueprint $table) {
                $table->id();
                $table->string('icon')->nullable();
                $table->string('name')->nullable();
                $table->text('description')->nullable();
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
        Schema::dropIfExists('service');
    }
};

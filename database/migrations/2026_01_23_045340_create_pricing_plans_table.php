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
        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
             $table->string('title')->nullable();
            $table->string('plan_icon')->nullable();
            $table->string('currency_icon')->nullable();
            $table->decimal('amount');
            $table->string('period')->nullable(); // monthly / yearly
            $table->text('description')->nullable();
            $table->string('button_name')->nullable();
            $table->string('button_link')->nullable();
            $table->enum('is_most_popular', ['yes', 'no'])->default('no');
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
        Schema::dropIfExists('pricing_plans');
    }
};

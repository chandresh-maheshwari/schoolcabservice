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
        if (! Schema::hasTable('faq_sections')) {
            Schema::create('faq_sections', function (Blueprint $table) {
                $table->id();
                $table->string('question')->nullable();
                $table->text('answer')->nullable();
                $table->tinyInteger('status')->default(0);
                $table->tinyInteger('deleted')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faq_sections');
    }
};

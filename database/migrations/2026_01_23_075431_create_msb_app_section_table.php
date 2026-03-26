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
        if (! Schema::hasTable('msb_app_section')) {
            Schema::create('msb_app_section', function (Blueprint $table) {
                $table->id();
                $table->string('icon');
                $table->string('name');
                $table->text('description');
                $table->string('button_name');
                $table->string('button_link');
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
        Schema::dropIfExists('msb_app_section');
    }
};

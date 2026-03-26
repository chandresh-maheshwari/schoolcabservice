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
        if (! Schema::hasTable('parents')) {
            Schema::create('parents', function (Blueprint $table) {
                $table->id();

                $table->string('father_name')->nullable();
                $table->string('mother_name')->nullable();
                $table->string('email')->nullable();
                $table->string('address_1')->nullable();
                $table->string('address_2')->nullable();
                $table->string('state')->nullable();
                $table->string('city')->nullable();
                $table->string('pincode')->nullable();

                $table->string('father_adhaar_card_image')->nullable();
                $table->string('mother_adhaar_card_image')->nullable();

                $table->string('contact_number')->nullable();
                $table->string('alternative_contact_number')->nullable();

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
        Schema::dropIfExists('parent');
    }
};

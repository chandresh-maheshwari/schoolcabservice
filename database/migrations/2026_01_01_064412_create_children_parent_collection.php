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
        Schema::create('parent', function (Blueprint $collection) {

            $collection->string('father_name')->nullable();
            $collection->string('mother_name')->nullable();
            $collection->string('email')->nullable();
            $collection->string('address_1')->nullable();
            $collection->string('address_2')->nullable();
            $collection->string('state')->nullable();
            $collection->string('city')->nullable();
            $collection->integer('pincode')->nullable();
            $collection->string('father_adhaar_card_image')->nullable();
            $collection->string('mother_adhaar_card_image')->nullable();
            $collection->integer('contact_number')->nullable();
            $collection->integer('alternative_contact_number')->nullable();
            $collection->integer('status')->nullable();
            $collection->integer('deleted')->nullable();
            $collection->timestamp('created_at')->nullable();
            $collection->timestamp('updated_at')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent');
    }
};

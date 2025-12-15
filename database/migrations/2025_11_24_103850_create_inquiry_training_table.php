<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInquiryTrainingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inquiry_training', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable(); // varchar(255)
            $table->string('last_name')->nullable();  // varchar(255)
            $table->string('email')->nullable();      // varchar(255)
            $table->string('contact_number', 15)->nullable(); // varchar(15)
            $table->string('description')->nullable(); // varchar(255)
            $table->string('technologies')->nullable(); // varchar(255)
            $table->string('cv')->nullable(); // varchar(255)
            $table->integer('deleted')->default(0); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inquiry_training');
    }
}

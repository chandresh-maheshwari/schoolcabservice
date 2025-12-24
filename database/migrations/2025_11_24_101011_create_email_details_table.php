<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_details', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->nullable(); // int(10) unsigned, nullable
            $table->string('email_type')->nullable();
            $table->string('email_to')->nullable();
            $table->text('mail_details')->nullable();
            $table->integer('is_sent')->default(0);
            $table->timestamp('deleted_at')->nullable();
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
        Schema::dropIfExists('email_details');
    }
}

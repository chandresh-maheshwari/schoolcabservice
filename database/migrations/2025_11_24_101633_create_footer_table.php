<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFooterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('footer', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('footer_link')->nullable();
            $table->string('location')->nullable();
            $table->string('contact_title')->nullable();
            $table->string('contact')->nullable();
            $table->string('email_title')->nullable();
            $table->string('email')->nullable();
            $table->string('footer_link_title')->nullable();
            $table->string('page_title_1')->nullable();
            $table->string('page_link_1')->nullable();
            $table->string('page_title_2')->nullable();
            $table->string('page_link_2')->nullable();
            $table->string('page_title_3')->nullable();
            $table->string('page_link_3')->nullable();
            $table->string('page_title_4')->nullable();
            $table->string('page_link_4')->nullable();
            $table->string('footer_service_title')->nullable();
            $table->string('service_title_1')->nullable();
            $table->string('service_link_1')->nullable();
            $table->string('service_title_2')->nullable();
            $table->string('service_link_2')->nullable();
            $table->string('service_title_3')->nullable();
            $table->string('service_link_3')->nullable();
            $table->string('service_title_4')->nullable();
            $table->string('service_link_4')->nullable();
            $table->string('follow_us')->nullable();
            $table->string('description')->nullable();
            $table->string('copy_right_text')->nullable();
            $table->integer('status')->default(0);
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
        Schema::dropIfExists('footer');
    }
}

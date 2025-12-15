<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortfolioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('portfolio', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('short_desc')->nullable();
            $table->longText('description')->nullable();
            $table->string('image')->nullable();

            $table->string('portfolio_info_title_1')->nullable();
            $table->string('portfolio_info_1')->nullable();

            $table->string('portfolio_info_title_2')->nullable();
            $table->string('portfolio_info_2')->nullable();

            $table->string('portfolio_info_title_3')->nullable();
            $table->string('portfolio_info_3')->nullable();

            $table->string('portfolio_info_title_4')->nullable();
            $table->string('portfolio_info_4')->nullable();

            $table->string('button_title')->nullable();
            $table->string('button_link')->nullable();

            $table->integer('category_id')->nullable();

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
        Schema::dropIfExists('portfolio');
    }
}

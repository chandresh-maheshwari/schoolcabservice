<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeaturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('image')->nullable();
            $table->longText('description')->nullable();
            $table->string('highlight_number_1')->nullable();
            $table->string('hightlight_text_1')->nullable();
            $table->string('highlight_icone_1')->nullable();
            $table->string('highlight_number_2')->nullable();
            $table->string('hightlight_text_2')->nullable();
            $table->string('highlight_icone_2')->nullable();
            $table->string('highlight_number_3')->nullable();
            $table->string('hightlight_text_3')->nullable();
            $table->string('highlight_icone_3')->nullable();
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
        Schema::dropIfExists('features');
    }
}

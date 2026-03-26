<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHeroSectionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('hero_section')) {
            Schema::create('hero_section', function (Blueprint $table) {
                $table->id();

                $table->string('title')->nullable();
                $table->string('image')->nullable();
                $table->longText('description')->nullable();
                $table->string('button_title_1')->nullable();
                $table->string('button_title_2')->nullable();
                $table->integer('stat_counter_1')->default(0)->nullable();
                $table->string('stat_title_1')->nullable();
                $table->string('stat_icon_1')->nullable();
                $table->integer('stat_counter_2')->default(0);
                $table->string('stat_title_2')->nullable();
                $table->string('stat_icon_2')->nullable();
                $table->integer('stat_counter_3')->default(0);
                $table->string('stat_title_3')->nullable();
                $table->string('stat_icon_3')->nullable();
                $table->integer('status')->default(0);
                $table->integer('deleted')->default(0)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hero_section');
    }
}

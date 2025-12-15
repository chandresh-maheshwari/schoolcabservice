<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallToActionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_to_action', function (Blueprint $table) {
            $table->id();
            $table->string('badge_title')->nullable();
            $table->string('badge_icon')->nullable();
            $table->string('feature_1')->nullable();
            $table->string('feature_2')->nullable();
            $table->string('feature_3')->nullable();
            $table->string('feature_4')->nullable();
            $table->string('stat_icon_1')->nullable();
            $table->string('stat_count_1')->nullable();
            $table->string('stat_text_1')->nullable();
            $table->string('stat_icon_2')->nullable();
            $table->string('stat_count_2')->nullable();
            $table->string('stat_text_2')->nullable();
            $table->string('button_title')->nullable();
            $table->string('button_link')->nullable();
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
        Schema::dropIfExists('call_to_action');
    }
}

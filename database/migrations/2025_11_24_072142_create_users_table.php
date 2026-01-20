<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
                        $table->id();

            $table->unsignedInteger('role_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('mobile', 15)->nullable();
            $table->string('photo')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->longText('following')->nullable();
            $table->longText('followers')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->integer('status')->default(0);
            $table->integer('deleted')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}

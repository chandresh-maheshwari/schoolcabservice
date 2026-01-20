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
        Schema::create('drivers', function (Blueprint $table) {
                        $table->id();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone', 20)->nullable();
            $table->string('driver_image')->nullable();
            $table->string('emergency_phone', 20)->nullable();
            $table->string('license_no')->unique();
            $table->date('license_expiry_date')->nullable();
            $table->string('license_image')->nullable();
            $table->string('adher_no', 20)->nullable();
            $table->string('adher_card_iamge')->nullable();

            // Other Details
            $table->integer('experience_years')->default(0);
            $table->integer('status')->default(0); // 1 = Active, 0 = Inactive
            $table->integer('is_assigned')->default(0);
            $table->date('joining_date')->nullable();
            $table->integer('deleted')->default(0);

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->foreign('vehicle_id')
                ->references('id')->on('vehicles')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};

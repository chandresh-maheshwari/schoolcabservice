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
        Schema::create('vehicles', function (Blueprint $table) {
                        $table->id();

            $table->string('vehicle_number')->unique();
            $table->string('vehicle_image')->nullable();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->onDelete('restrict');
            $table->integer('seating_capacity');
            $table->string('rc_number')->nullable();
            $table->date('rc_expiry_date')->nullable();
            $table->string('rc_image')->nullable();
            $table->string('insurance_number', 50)->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->string('insurance_image')->nullable();
            $table->integer('is_assigned')->default(0);
            $table->integer('status')->default(0);
            $table->integer('deleted')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

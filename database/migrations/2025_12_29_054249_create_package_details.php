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
       Schema::connection('mongodb')->create('package_details', function (Blueprint $collection) {

           $collection->string('package_name')->nullable();
            $collection->string('package_type')->nullable();
            $collection->string('booking_type')->nullable();
            $collection->string('price')->nullable();
            $collection->string('short_description')->nullable();
            $collection->longText('description')->nullable();
            $collection->integer('validity_days')->nullable();
            $collection->integer('status')->nullable();
            $collection->integer('deleted')->nullable();
            $collection->timestamp('created_at')->nullable();
            $collection->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::connection('mongodb')->dropIfExists('package_details');
    }
};

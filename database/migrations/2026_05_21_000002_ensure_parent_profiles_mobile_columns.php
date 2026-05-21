<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parent_profiles')) {
            Schema::create('parent_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('email')->nullable();
                $table->string('full_name')->nullable();
                $table->string('mother_name')->nullable();
                $table->string('phone_number', 30)->nullable();
                $table->string('alternate_phone', 30)->nullable();
                $table->text('home_address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('pincode', 20)->nullable();
                $table->string('emergency_contact', 30)->nullable();
                $table->string('profile_image_url', 5000)->nullable();
                $table->timestamp('createdAt')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index('user_id');
                $table->index('parent_id');
                $table->index('email');
            });

            return;
        }

        Schema::table('parent_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('parent_profiles', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->index();
            }
            if (! Schema::hasColumn('parent_profiles', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->index();
            }
            if (! Schema::hasColumn('parent_profiles', 'email')) {
                $table->string('email')->nullable()->index();
            }
            if (! Schema::hasColumn('parent_profiles', 'full_name')) {
                $table->string('full_name')->nullable();
            }
            if (! Schema::hasColumn('parent_profiles', 'mother_name')) {
                $table->string('mother_name')->nullable();
            }
            if (! Schema::hasColumn('parent_profiles', 'phone_number')) {
                $table->string('phone_number', 30)->nullable();
            }
            if (! Schema::hasColumn('parent_profiles', 'alternate_phone')) {
                $table->string('alternate_phone', 30)->nullable();
            }
            if (! Schema::hasColumn('parent_profiles', 'home_address')) {
                $table->text('home_address')->nullable();
            }
            if (! Schema::hasColumn('parent_profiles', 'city')) {
                $table->string('city')->nullable();
            }
            if (! Schema::hasColumn('parent_profiles', 'state')) {
                $table->string('state')->nullable();
            }
            if (! Schema::hasColumn('parent_profiles', 'pincode')) {
                $table->string('pincode', 20)->nullable();
            }
            if (! Schema::hasColumn('parent_profiles', 'emergency_contact')) {
                $table->string('emergency_contact', 30)->nullable();
            }
            if (! Schema::hasColumn('parent_profiles', 'profile_image_url')) {
                $table->string('profile_image_url', 5000)->nullable();
            }
            if (! Schema::hasColumn('parent_profiles', 'createdAt')) {
                $table->timestamp('createdAt')->nullable();
            }
            if (! Schema::hasColumn('parent_profiles', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Keep the profile data; this migration only ensures columns required by the mobile app.
    }
};

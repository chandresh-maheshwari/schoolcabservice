<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables where user_id should exist for creator tracking.
     *
     * @var array<int, string>
     */
    private array $tables = [
        'vehicle_types',
        'vehicles',
        'schools',
        'routes',
        'package_details',
        'stops_pickup',
        'driver_vehicle_histories',
        'parents',
        'children',
        'about_sections',
        'service',
        'how_it_works',
        'client_section',
        'benefit_section',
        'testimonial_sections',
        'faq_sections',
        'pricing_plans',
        'msb_app_section',
        'social_media',
        'contact_messages',
        'hero_section',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->index('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};


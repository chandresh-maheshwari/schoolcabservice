<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        $indexExists = collect(Schema::getIndexes('vehicles'))
            ->contains(fn (array $index) => ($index['name'] ?? null) === 'vehicles_vehicle_number_unique');

        if ($indexExists) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropUnique('vehicles_vehicle_number_unique');
            });
        }
    }

    public function down(): void
    {
        // The index cannot be restored safely after duplicate archived numbers exist.
    }
};

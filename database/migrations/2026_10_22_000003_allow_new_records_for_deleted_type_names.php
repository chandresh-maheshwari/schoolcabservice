<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['vehicle_types' => 'vehicle_type', 'emergency_types' => 'emergency_type'] as $tableName => $column) {
            if (!Schema::hasTable($tableName)) continue;
            $indexName = $tableName.'_'.$column.'_unique';
            if (collect(Schema::getIndexes($tableName))->contains(fn ($index) => $index['name'] === $indexName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName, $column) {
                    $table->dropUnique($indexName);
                    $table->index($column);
                });
            }
        }
    }

    public function down(): void
    {
        // Archived and new records may now share a name; preserve both.
    }
};

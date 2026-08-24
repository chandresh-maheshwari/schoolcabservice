<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('route_vehicle_replacements')) {
            return;
        }

        Schema::table('route_vehicle_replacements', function (Blueprint $table) {
            if (! Schema::hasColumn('route_vehicle_replacements', 'vehicle_id')) {
                $table->unsignedBigInteger('vehicle_id')->nullable()->after('route_id');
            }

            if (! Schema::hasColumn('route_vehicle_replacements', 'replacement_vehicle_id')) {
                $table->unsignedBigInteger('replacement_vehicle_id')->nullable()->after('vehicle_id');
            }

            if (! Schema::hasColumn('route_vehicle_replacements', 'is_suspended')) {
                $table->tinyInteger('is_suspended')->default(0)->after('replacement_vehicle_id');
            }
        });

        $legacyColumnsPresent = Schema::hasColumn('route_vehicle_replacements', 'original_bus_id')
            && Schema::hasColumn('route_vehicle_replacements', 'replaced_bus_id')
            && Schema::hasColumn('route_vehicle_replacements', 'replacement_bus_id');

        if ($legacyColumnsPresent) {
            $legacyRows = DB::table('route_vehicle_replacements')
                ->orderBy('route_id')
                ->orderBy('id')
                ->get();

            DB::table('route_vehicle_replacements')->truncate();

            if ($legacyRows->isNotEmpty()) {
                foreach ($legacyRows->groupBy('route_id') as $routeId => $rows) {
                    $this->backfillLegacyRows((int) $routeId, $rows);
                }
            } elseif (
                Schema::hasTable('routes')
                && Schema::hasColumn('routes', 'emergency_original_bus_id')
                && Schema::hasColumn('routes', 'emergency_replacement_bus_id')
            ) {
                $routeFallbackRows = DB::table('routes')
                    ->whereNotNull('emergency_original_bus_id')
                    ->whereNotNull('emergency_replacement_bus_id')
                    ->get(['id', 'emergency_original_bus_id', 'emergency_replacement_bus_id', 'emergency_replaced_at']);

                foreach ($routeFallbackRows as $row) {
                    $this->backfillRouteFallbackRow($row);
                }
            }
        }

        Schema::table('route_vehicle_replacements', function (Blueprint $table) {
            $dropColumns = [];

            foreach (['original_bus_id', 'replaced_bus_id', 'replacement_bus_id'] as $column) {
                if (Schema::hasColumn('route_vehicle_replacements', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });

        if (Schema::hasTable('routes')) {
            Schema::table('routes', function (Blueprint $table) {
                $dropColumns = [];

                foreach (['emergency_original_bus_id', 'emergency_replacement_bus_id', 'emergency_replaced_at'] as $column) {
                    if (Schema::hasColumn('routes', $column)) {
                        $dropColumns[] = $column;
                    }
                }

                if (! empty($dropColumns)) {
                    $table->dropColumn($dropColumns);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('routes')) {
            Schema::table('routes', function (Blueprint $table) {
                if (! Schema::hasColumn('routes', 'emergency_original_bus_id')) {
                    $table->unsignedBigInteger('emergency_original_bus_id')->nullable()->after('bus_id');
                }

                if (! Schema::hasColumn('routes', 'emergency_replacement_bus_id')) {
                    $table->unsignedBigInteger('emergency_replacement_bus_id')->nullable()->after('emergency_original_bus_id');
                }

                if (! Schema::hasColumn('routes', 'emergency_replaced_at')) {
                    $table->timestamp('emergency_replaced_at')->nullable()->after('emergency_replacement_bus_id');
                }
            });
        }

        if (! Schema::hasTable('route_vehicle_replacements')) {
            return;
        }

        Schema::table('route_vehicle_replacements', function (Blueprint $table) {
            if (! Schema::hasColumn('route_vehicle_replacements', 'original_bus_id')) {
                $table->unsignedBigInteger('original_bus_id')->nullable()->after('route_id');
            }

            if (! Schema::hasColumn('route_vehicle_replacements', 'replaced_bus_id')) {
                $table->unsignedBigInteger('replaced_bus_id')->nullable()->after('original_bus_id');
            }

            if (! Schema::hasColumn('route_vehicle_replacements', 'replacement_bus_id')) {
                $table->unsignedBigInteger('replacement_bus_id')->nullable()->after('replaced_bus_id');
            }
        });
    }

    private function backfillLegacyRows(int $routeId, Collection $rows): void
    {
        $replacementPairs = [];

        foreach ($rows as $row) {
            $originalBusId = (int) ($row->original_bus_id ?? 0);
            $replacedBusId = (int) ($row->replaced_bus_id ?? 0);
            $replacementBusId = (int) ($row->replacement_bus_id ?? 0);

            if ($replacedBusId <= 0 || $replacementBusId <= 0) {
                continue;
            }

            $replacementPairs[] = [
                'original_bus_id' => $originalBusId > 0 ? $originalBusId : $replacedBusId,
                'vehicle_id' => $replacedBusId,
                'replacement_vehicle_id' => $replacementBusId,
                'replacement_bus_id' => $replacementBusId,
                'is_suspended' => 1,
                'replaced_at' => $row->replaced_at ?? now(),
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        }

        if (empty($replacementPairs)) {
            return;
        }

        foreach ($replacementPairs as $pair) {
            DB::table('route_vehicle_replacements')->insert([
                'route_id' => $routeId,
                'original_bus_id' => $pair['original_bus_id'],
                'replaced_bus_id' => $pair['vehicle_id'],
                'replacement_bus_id' => $pair['replacement_bus_id'],
                'vehicle_id' => $pair['vehicle_id'],
                'replacement_vehicle_id' => $pair['replacement_vehicle_id'],
                'is_suspended' => $pair['is_suspended'],
                'replaced_at' => $pair['replaced_at'],
                'created_at' => $pair['created_at'],
                'updated_at' => $pair['updated_at'],
            ]);
        }

        $lastReplacementVehicleId = (int) end($replacementPairs)['replacement_vehicle_id'];
        if ($lastReplacementVehicleId > 0) {
            DB::table('route_vehicle_replacements')->insert([
                'route_id' => $routeId,
                'original_bus_id' => (int) $replacementPairs[0]['original_bus_id'],
                'replaced_bus_id' => $lastReplacementVehicleId,
                'replacement_bus_id' => $lastReplacementVehicleId,
                'vehicle_id' => $lastReplacementVehicleId,
                'replacement_vehicle_id' => null,
                'is_suspended' => 0,
                'replaced_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function backfillRouteFallbackRow(object $row): void
    {
        $routeId = (int) ($row->id ?? 0);
        $originalBusId = (int) ($row->emergency_original_bus_id ?? 0);
        $replacementBusId = (int) ($row->emergency_replacement_bus_id ?? 0);

        if ($routeId <= 0 || $originalBusId <= 0 || $replacementBusId <= 0) {
            return;
        }

        $timestamp = $row->emergency_replaced_at ?? now();

        DB::table('route_vehicle_replacements')->insert([
            'route_id' => $routeId,
            'original_bus_id' => $originalBusId,
            'replaced_bus_id' => $originalBusId,
            'replacement_bus_id' => $replacementBusId,
            'vehicle_id' => $originalBusId,
            'replacement_vehicle_id' => $replacementBusId,
            'is_suspended' => 1,
            'replaced_at' => $timestamp,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        DB::table('route_vehicle_replacements')->insert([
            'route_id' => $routeId,
            'original_bus_id' => $originalBusId,
            'replaced_bus_id' => $replacementBusId,
            'replacement_bus_id' => $replacementBusId,
            'vehicle_id' => $replacementBusId,
            'replacement_vehicle_id' => null,
            'is_suspended' => 0,
            'replaced_at' => $timestamp,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
};

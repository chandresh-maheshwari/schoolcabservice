<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('driverdetails')) {
            return;
        }

        if (! Schema::hasColumn('driverdetails', 'vehicleId')) {
            Schema::table('driverdetails', function (Blueprint $table) {
                $table->unsignedBigInteger('vehicleId')->nullable()->after('userId');
            });
        }

        if ($this->hasUniqueUserIdIndex()) {
            Schema::table('driverdetails', function (Blueprint $table) {
                $table->dropUnique('userId');
            });
        }

        if (! $this->hasIndex('driverdetails', 'driverdetails_userid_index')) {
            Schema::table('driverdetails', function (Blueprint $table) {
                $table->index('userId', 'driverdetails_userid_index');
            });
        }

        if (! $this->hasIndex('driverdetails', 'driverdetails_vehicleid_index')) {
            Schema::table('driverdetails', function (Blueprint $table) {
                $table->index('vehicleId', 'driverdetails_vehicleid_index');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('driverdetails')) {
            return;
        }

        if ($this->hasIndex('driverdetails', 'driverdetails_vehicleid_index')) {
            Schema::table('driverdetails', function (Blueprint $table) {
                $table->dropIndex('driverdetails_vehicleid_index');
            });
        }

        if (Schema::hasColumn('driverdetails', 'vehicleId')) {
            Schema::table('driverdetails', function (Blueprint $table) {
                $table->dropColumn('vehicleId');
            });
        }

        if ($this->hasIndex('driverdetails', 'driverdetails_userid_index')) {
            Schema::table('driverdetails', function (Blueprint $table) {
                $table->dropIndex('driverdetails_userid_index');
            });
        }

        if (! $this->hasDuplicateUserIds() && ! $this->hasUniqueUserIdIndex()) {
            Schema::table('driverdetails', function (Blueprint $table) {
                $table->unique('userId', 'userId');
            });
        } elseif (! $this->hasIndex('driverdetails', 'driverdetails_userid_index')) {
            Schema::table('driverdetails', function (Blueprint $table) {
                $table->index('userId', 'driverdetails_userid_index');
            });
        }
    }

    private function hasUniqueUserIdIndex(): bool
    {
        $indexes = DB::select("SHOW INDEX FROM driverdetails WHERE Key_name = 'userId'");

        foreach ($indexes as $index) {
            if ((int) ($index->Non_unique ?? 1) === 0) {
                return true;
            }
        }

        return false;
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }

    private function hasDuplicateUserIds(): bool
    {
        return DB::table('driverdetails')
            ->select('userId')
            ->whereNotNull('userId')
            ->groupBy('userId')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }
};

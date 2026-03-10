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

        $userIdForeignKey = $this->getUserIdForeignKeyName();
        if ($userIdForeignKey) {
            Schema::table('driverdetails', function (Blueprint $table) use ($userIdForeignKey) {
                $table->dropForeign($userIdForeignKey);
            });
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

        if ($userIdForeignKey && ! $this->getUserIdForeignKeyName()) {
            Schema::table('driverdetails', function (Blueprint $table) use ($userIdForeignKey) {
                $table->foreign('userId', $userIdForeignKey)->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('driverdetails')) {
            return;
        }

        $userIdForeignKey = $this->getUserIdForeignKeyName();
        if ($userIdForeignKey) {
            Schema::table('driverdetails', function (Blueprint $table) use ($userIdForeignKey) {
                $table->dropForeign($userIdForeignKey);
            });
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

        if ($userIdForeignKey && ! $this->getUserIdForeignKeyName()) {
            Schema::table('driverdetails', function (Blueprint $table) use ($userIdForeignKey) {
                $table->foreign('userId', $userIdForeignKey)->references('id')->on('users')->nullOnDelete();
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

    private function getUserIdForeignKeyName(): ?string
    {
        $databaseName = DB::getDatabaseName();

        $foreignKeys = DB::select(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$databaseName, 'driverdetails', 'userId']
        );

        return $foreignKeys[0]->CONSTRAINT_NAME ?? null;
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

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

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'availability_status')) {
                $table->string('availability_status', 30)->nullable()->after('status');
            }

            if (! Schema::hasColumn('vehicles', 'emergency_note')) {
                $table->text('emergency_note')->nullable()->after('availability_status');
            }

            if (! Schema::hasColumn('vehicles', 'emergency_marked_at')) {
                $table->timestamp('emergency_marked_at')->nullable()->after('emergency_note');
            }

            if (! Schema::hasColumn('vehicles', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('emergency_marked_at');
            }

            if (! Schema::hasColumn('vehicles', 'resolved_by')) {
                $table->unsignedBigInteger('resolved_by')->nullable()->after('resolved_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $columns = [
                'availability_status',
                'emergency_note',
                'emergency_marked_at',
                'resolved_at',
                'resolved_by',
            ];

            $existingColumns = array_values(array_filter($columns, fn ($column) => Schema::hasColumn('vehicles', $column)));
            if (! empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};

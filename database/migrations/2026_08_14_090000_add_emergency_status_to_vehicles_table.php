<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'availability_status')) {
                $table->string('availability_status', 30)
                    ->default('available')
                    ->after('is_assigned');
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
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'emergency_marked_at')) {
                $table->dropColumn('emergency_marked_at');
            }

            if (Schema::hasColumn('vehicles', 'resolved_at')) {
                $table->dropColumn('resolved_at');
            }

            if (Schema::hasColumn('vehicles', 'resolved_by')) {
                $table->dropColumn('resolved_by');
            }

            if (Schema::hasColumn('vehicles', 'emergency_note')) {
                $table->dropColumn('emergency_note');
            }

            if (Schema::hasColumn('vehicles', 'availability_status')) {
                $table->dropColumn('availability_status');
            }
        });
    }
};

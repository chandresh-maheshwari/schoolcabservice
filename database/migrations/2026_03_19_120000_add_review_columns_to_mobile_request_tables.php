<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('leave_requests', 'admin_notes')) {
                    $table->text('admin_notes')->nullable()->after('status');
                }

                if (! Schema::hasColumn('leave_requests', 'reviewed_by')) {
                    $table->unsignedBigInteger('reviewed_by')->nullable()->after('admin_notes');
                }

                if (! Schema::hasColumn('leave_requests', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                }
            });
        }

        if (Schema::hasTable('support_requests')) {
            Schema::table('support_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('support_requests', 'admin_notes')) {
                    $table->text('admin_notes')->nullable()->after('status');
                }

                if (! Schema::hasColumn('support_requests', 'reviewed_by')) {
                    $table->unsignedBigInteger('reviewed_by')->nullable()->after('admin_notes');
                }

                if (! Schema::hasColumn('support_requests', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                foreach (['reviewed_at', 'reviewed_by', 'admin_notes'] as $column) {
                    if (Schema::hasColumn('leave_requests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('support_requests')) {
            Schema::table('support_requests', function (Blueprint $table) {
                foreach (['reviewed_at', 'reviewed_by', 'admin_notes'] as $column) {
                    if (Schema::hasColumn('support_requests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

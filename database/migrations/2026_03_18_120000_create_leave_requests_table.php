<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('email')->nullable();
                $table->unsignedBigInteger('child_id')->nullable();
                $table->string('child_name')->nullable();
                $table->date('from_date')->nullable();
                $table->date('to_date')->nullable();
                $table->text('reason')->nullable();
                $table->string('status')->default('requested');
                $table->text('admin_notes')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('createdAt')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index('user_id');
                $table->index('parent_id');
                $table->index('child_id');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};

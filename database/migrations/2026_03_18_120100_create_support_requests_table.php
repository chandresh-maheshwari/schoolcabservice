<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_requests')) {
            Schema::create('support_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('email')->nullable();
                $table->string('category')->nullable();
                $table->string('subject')->nullable();
                $table->text('message')->nullable();
                $table->string('status')->default('open');
                $table->text('admin_notes')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('createdAt')->nullable();
                $table->timestamp('updatedAt')->nullable();

                $table->index('user_id');
                $table->index('parent_id');
                $table->index('status');
                $table->index('category');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};

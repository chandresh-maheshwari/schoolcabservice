<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('push_notification_event_logs')) {
            Schema::create('push_notification_event_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event_key', 120);
                $table->string('entity_type', 80);
                $table->unsignedBigInteger('entity_id');
                $table->string('unique_key', 120);
                $table->text('payload')->nullable();
                $table->timestamp('createdAt')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->unique(['event_key', 'entity_type', 'entity_id', 'unique_key'], 'push_event_logs_unique');
                $table->index(['entity_type', 'entity_id'], 'push_event_logs_entity_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_event_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('push_notification_settings')) {
            Schema::create('push_notification_settings', function (Blueprint $table) {
                $table->id();
                $table->string('event_key', 120)->unique();
                $table->boolean('enabled')->default(true);
                $table->string('title_template', 150);
                $table->string('message_template', 500);
                $table->text('metadata')->nullable();
                $table->timestamp('createdAt')->nullable();
                $table->timestamp('updatedAt')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_settings');
    }
};

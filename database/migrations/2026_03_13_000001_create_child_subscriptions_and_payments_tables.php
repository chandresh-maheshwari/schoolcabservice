<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('child_subscriptions')) {
            Schema::create('child_subscriptions', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('child_id');
                $table->string('service_type', 32)->default('vehicle');
                $table->string('package_type', 32)->nullable();
                $table->string('status', 16)->default('pending'); // pending|active|expired|inactive|cancelled
                $table->string('source', 32)->default('app'); // app|school_cash|admin_cash

                // Use NULL for non-current rows; only one row per child+service may have is_current=1.
                $table->unsignedTinyInteger('is_current')->nullable();

                $table->dateTime('starts_at')->nullable();
                $table->dateTime('expires_at')->nullable();

                // Actor user who created/collected cash (admin/school user). Optional for app online.
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->text('notes')->nullable();

                // Keep camelCase for shared DB (Sequelize default).
                $table->dateTime('createdAt')->nullable();
                $table->dateTime('updatedAt')->nullable();

                $table->index('child_id');
                $table->index(['child_id', 'service_type']);
                $table->index(['service_type', 'status']);
                $table->unique(['child_id', 'service_type', 'is_current'], 'uniq_child_service_current');
            });
        }

        if (! Schema::hasTable('subscription_payments')) {
            Schema::create('subscription_payments', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('child_subscription_id');
                $table->string('channel', 32)->default('cash'); // cash|razorpay|upi|bank
                $table->string('status', 16)->default('created'); // created|paid|failed|refunded
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('currency', 8)->default('INR');

                // Online gateway fields (Razorpay)
                $table->string('order_id')->nullable();
                $table->string('payment_id')->nullable();
                $table->string('signature')->nullable();

                // Cash / offline fields
                $table->string('receipt_no')->nullable();
                $table->string('reference_no')->nullable();
                $table->unsignedBigInteger('collected_by_user_id')->nullable();
                $table->dateTime('paid_at')->nullable();

                $table->json('meta')->nullable();
                // Keep camelCase for shared DB (Sequelize default).
                $table->dateTime('createdAt')->nullable();
                $table->dateTime('updatedAt')->nullable();

                $table->index('child_subscription_id');
                $table->index(['channel', 'status']);
                $table->index('order_id');
                $table->index('payment_id');
                $table->unique(['channel', 'order_id'], 'uniq_payment_channel_order');

                $table
                    ->foreign('child_subscription_id')
                    ->references('id')
                    ->on('child_subscriptions')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('subscription_payments')) {
            Schema::dropIfExists('subscription_payments');
        }

        if (Schema::hasTable('child_subscriptions')) {
            Schema::dropIfExists('child_subscriptions');
        }
    }
};

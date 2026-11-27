<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('operator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('status');
            $table->text('reason');
            $table->unsignedInteger('amount_cents')->default(0);
            $table->string('currency', 3);
            $table->string('provider')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('provider_status')->nullable();
            $table->string('idempotency_key')->unique();
            $table->string('failure_code')->nullable();
            $table->string('failure_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'type'], 'order_operations_order_type_unique');
            $table->unique(['provider', 'provider_reference'], 'order_operations_provider_reference_unique');
            $table->index(['status', 'requested_at']);
        });

        Schema::table('payment_webhook_events', function (Blueprint $table): void {
            $table->foreignId('order_operation_id')
                ->nullable()
                ->after('payment_id')
                ->constrained('order_operations')
                ->nullOnDelete();
        });

        Schema::table('placement_credits', function (Blueprint $table): void {
            $table->timestamp('review_required_at')->nullable()->after('reversal_reason');
            $table->string('review_reason')->nullable()->after('review_required_at');
            $table->index(['review_required_at', 'reversed_at'], 'placement_credits_review_index');
        });
    }

    public function down(): void
    {
        Schema::table('placement_credits', function (Blueprint $table): void {
            $table->dropIndex('placement_credits_review_index');
            $table->dropColumn(['review_required_at', 'review_reason']);
        });

        Schema::table('payment_webhook_events', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('order_operation_id');
        });

        Schema::dropIfExists('order_operations');
    }
};

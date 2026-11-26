<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('package_type_snapshot')->nullable()->after('title_snapshot');
            $table->unsignedSmallInteger('placement_quantity_snapshot')->nullable()->after('package_type_snapshot');
            $table->unsignedSmallInteger('vacancy_duration_days_snapshot')->nullable()->after('placement_quantity_snapshot');
            $table->smallInteger('commercial_priority_snapshot')->nullable()->after('vacancy_duration_days_snapshot');
            $table->json('entitlement_snapshot')->nullable()->after('commercial_priority_snapshot');
        });

        Schema::create('placement_credits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vacancy_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('grant_sequence');
            $table->string('product_code');
            $table->string('package_name_snapshot');
            $table->string('package_type_snapshot');
            $table->unsignedSmallInteger('duration_days_snapshot');
            $table->smallInteger('commercial_priority_snapshot')->default(0);
            $table->json('benefits_snapshot')->nullable();
            $table->timestamp('granted_at');
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason')->nullable();
            $table->timestamps();

            $table->unique(['order_item_id', 'grant_sequence'], 'placement_credits_grant_unique');
            $table->unique('vacancy_id', 'placement_credits_vacancy_unique');
            $table->index(['company_id', 'vacancy_id'], 'placement_credits_company_allocation_index');
            $table->index(['activated_at', 'expires_at'], 'placement_credits_validity_index');
        });

        Schema::create('placement_credit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_credit_id')->constrained()->restrictOnDelete();
            $table->foreignId('vacancy_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type');
            $table->string('deduplication_key')->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['placement_credit_id', 'occurred_at'], 'placement_credit_events_history_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_credit_events');
        Schema::dropIfExists('placement_credits');

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn([
                'package_type_snapshot',
                'placement_quantity_snapshot',
                'vacancy_duration_days_snapshot',
                'commercial_priority_snapshot',
                'entitlement_snapshot',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('provider_payment_id')->nullable()->change();
            $table->string('idempotency_key')->nullable()->unique()->after('order_id');
            $table->string('provider_reference')->nullable()->unique()->after('provider_payment_id');
            $table->json('metadata')->nullable()->after('raw_payload');
            $table->dateTime('completed_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['idempotency_key']);
            $table->dropUnique(['provider_reference']);
            $table->dropColumn(['idempotency_key', 'provider_reference', 'metadata', 'completed_at']);
        });
    }
};

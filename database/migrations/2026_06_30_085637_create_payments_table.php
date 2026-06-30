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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('payment_provider')->default('mollie');
            $table->string('payment_id');
            $table->string('status')
                ->default(\App\Enums\PaymentStatus::Open->value);
            $table->integer('amount_cents');
            $table->string('currency')->default('EUR');
            $table->datetime('paid_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->index('status');
            $table->index(['provider', 'provider_payment_id']);
            $table->index(['order_id', 'status']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

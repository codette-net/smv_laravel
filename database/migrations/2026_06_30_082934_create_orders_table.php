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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->string('status')
                ->default(\App\Enums\OrderStatus::Draft->value);
            $table->integer('subtotal_cents');
            $table->integer('vat_cents')->default(0);
            $table->integer('total_cents');
            $table->string('currency')->default('EUR');
            $table->index('status');
            $table->index(['company_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

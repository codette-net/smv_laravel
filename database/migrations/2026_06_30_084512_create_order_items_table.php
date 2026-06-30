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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')
                ->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('vacancy_id')
                ->nullable()->constrained()->cascadeOnDelete();
            $table->string('title_snapshot');
            $table->integer('price_cents');
            $table->integer('quantity');
            $table->integer('total_cents');
            $table->index('package_id');
            $table->index('vacancy_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

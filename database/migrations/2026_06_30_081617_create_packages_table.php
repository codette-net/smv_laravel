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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('price_cents');
            $table->string('currency')->default('EUR');
            $table->string('vacancy_duration_days')->default('30');
            $table->boolean('includes_featured');
            $table->boolean('includes_social_campaign');
            $table->boolean('includes_newsletter');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->index('is_active');
            $table->index('sort_order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};

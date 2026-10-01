<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_post_vacancy', function (Blueprint $table): void {
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vacancy_id')->constrained()->cascadeOnDelete();
            $table->unique(['blog_post_id', 'vacancy_id']);
        });

        Schema::create('blog_post_company', function (Blueprint $table): void {
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unique(['blog_post_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_company');
        Schema::dropIfExists('blog_post_vacancy');
    }
};

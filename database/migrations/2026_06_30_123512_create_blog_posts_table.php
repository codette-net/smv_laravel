<?php

use App\Enums\BlogPostStatus;
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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();
            $table->string('title');
            $table->string('slug')
                ->unique();
            $table->text('excerpt')
                ->nullable();
            $table->text('content');
            $table->string('status')
                ->default(BlogPostStatus::Draft->value);
            $table->datetime('published_at')
                ->nullable();
            $table->index('status');
            $table->index('published_at');
            $table->index(['status', 'published_at']);
            $table->index(['company_id', 'status']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};

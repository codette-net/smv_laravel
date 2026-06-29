<?php

use App\Enums\CompanyStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')
                ->nullable();
            $table->text('description')
                ->nullable();
            $table->string('email')
                ->nullable()
                ->unique();
            $table->string('phone')
                ->nullable();
            $table->string('website')
                ->nullable();
            $table->string('logo')
                ->nullable();
            $table->string('cover_image')
                ->nullable();
            $table->string('location')
                ->nullable();
            $table->string('linkedin_url')
                ->nullable();
            $table->string('facebook_url')
                ->nullable();
            $table->string('instagram_url')
                ->nullable();
            $table->string('video_url')
                ->nullable();
            $table->string('status')
                ->default(CompanyStatus::Draft->value);
            $table->boolean('is_featured')
                ->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

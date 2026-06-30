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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('candidate_name');
            $table->string('candidate_email');
            $table->string('candidate_phone')->nullable();
            $table->string('candidate_location')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('cv_path')->nullable();
            $table->text('motivation')->nullable();
            $table->string('status')->default(\App\Enums\ApplicationStatus::New->value);
            $table->index('candidate_email');
            $table->index('status');
            $table->index(['vacancy_id', 'status']);
            $table->index(['candidate_id', 'status']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};

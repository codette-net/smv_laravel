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
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('location')->nullable();
            $table->string('application_email')->nullable();
            $table->string('application_url')->nullable();
            $table->integer('salary_min')->nullable();
            $table->integer('salary_max')->nullable();
            $table->integer('rate_min')->nullable();
            $table->integer('rate_max')->nullable();
            $table->string('reference')->nullable();
            $table->string('source_reference')->nullable();
            $table->dateTime('deadline_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->string('application_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_filled')->default(false);
            $table->string('status')->default(\App\Enums\VacancyStatus::Draft->value);
            $table->string('source')->default(\App\Enums\VacancySource::Manual->value);
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacancies');
    }
};

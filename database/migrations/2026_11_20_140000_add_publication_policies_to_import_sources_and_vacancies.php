<?php

use App\Enums\ImportMissingVacancyPolicy;
use App\Enums\ImportPublicationPolicy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_sources', function (Blueprint $table): void {
            $table->string('publication_policy')
                ->default(ImportPublicationPolicy::ReviewRequired->value)
                ->after('is_active');
            $table->string('missing_vacancy_policy')
                ->default(ImportMissingVacancyPolicy::ReviewRequired->value)
                ->after('publication_policy');
        });

        Schema::table('vacancies', function (Blueprint $table): void {
            $table->timestamp('source_offlined_at')->nullable()->after('missing_since');
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table): void {
            $table->dropColumn('source_offlined_at');
        });

        Schema::table('import_sources', function (Blueprint $table): void {
            $table->dropColumn(['publication_policy', 'missing_vacancy_policy']);
        });
    }
};

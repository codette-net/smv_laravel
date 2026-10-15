<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->renameColumn('updated', 'updated_rows');
        });

        Schema::table('imports', function (Blueprint $table) {
            $table->string('source')->nullable()->change();
            $table->foreignId('import_source_id')
                ->nullable()
                ->after('id')
                ->constrained('import_sources')
                ->restrictOnDelete();
            $table->unsignedInteger('imported_rows')->default(0)->after('total_rows');
        });

        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropUnique('vacancies_source_source_reference_unique');
            $table->foreignId('import_source_id')
                ->nullable()
                ->after('company_id')
                ->constrained('import_sources')
                ->restrictOnDelete();
            $table->unique(
                ['import_source_id', 'source_reference'],
                'vacancies_import_source_reference_unique'
            );
        });
    }

    public function down(): void
    {
        $hasLegacyVacancyIdentityDuplicates = DB::table('vacancies')
            ->select('source', 'source_reference')
            ->whereNotNull('source')
            ->whereNotNull('source_reference')
            ->groupBy('source', 'source_reference')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasLegacyVacancyIdentityDuplicates) {
            throw new RuntimeException(
                'Cannot roll back import foundation: vacancies contain duplicate source and source_reference combinations that violate the legacy unique constraint.'
            );
        }

        if (DB::table('imports')->whereNull('source')->exists()) {
            throw new RuntimeException(
                'Cannot roll back import foundation: imports contain null legacy source values that cannot be restored to a non-nullable column.'
            );
        }

        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropUnique('vacancies_import_source_reference_unique');
            $table->dropConstrainedForeignId('import_source_id');
            $table->unique(['source', 'source_reference']);
        });

        Schema::table('imports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('import_source_id');
            $table->dropColumn('imported_rows');
            $table->string('source')->nullable(false)->change();
        });

        Schema::table('imports', function (Blueprint $table) {
            $table->renameColumn('updated_rows', 'updated');
        });
    }
};

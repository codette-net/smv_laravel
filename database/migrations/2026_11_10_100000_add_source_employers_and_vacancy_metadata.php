<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_source_employers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('identity_type');
            $table->string('identity_key', 64);
            $table->string('external_employer_id')->nullable();
            $table->string('source_name')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['import_source_id', 'identity_type', 'identity_key'], 'import_source_employer_identity_unique');
            $table->index(['import_source_id', 'is_active']);
            $table->index('company_id');
        });

        Schema::table('vacancies', function (Blueprint $table): void {
            $table->string('source_requisition_id')->nullable()->after('source_reference');
            $table->string('advertised_state')->nullable()->after('location');
            $table->string('advertised_country', 2)->nullable()->after('advertised_state');
            $table->string('advertised_postal_code', 32)->nullable()->after('advertised_country');
            $table->string('workplace_city')->nullable()->after('advertised_postal_code');
            $table->string('workplace_country', 2)->nullable()->after('workplace_city');
            $table->text('source_salary_text')->nullable()->after('salary_basis');
            $table->string('source_language', 16)->nullable()->after('source_salary_text');
            $table->string('source_product_code')->nullable()->after('source_language');
            $table->index(['import_source_id', 'source_requisition_id'], 'vacancies_source_requisition_index');
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table): void {
            $table->dropIndex('vacancies_source_requisition_index');
            $table->dropColumn([
                'source_requisition_id', 'advertised_state', 'advertised_country',
                'advertised_postal_code', 'workplace_city', 'workplace_country',
                'source_salary_text', 'source_language', 'source_product_code',
            ]);
        });

        Schema::dropIfExists('import_source_employers');
    }
};

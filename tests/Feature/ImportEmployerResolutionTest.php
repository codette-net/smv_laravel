<?php

use App\Enums\ImportEmployerIdentityType;
use App\Enums\ImportFormat;
use App\Enums\ImportTransport;
use App\Imports\VacancyImportRunner;
use App\Models\Company;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\ImportSourceEmployer;
use App\Models\Vacancy;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    foreach (glob(storage_path('framework/employer-resolution-*.json')) ?: [] as $path) {
        unlink($path);
    }
});

function employerResolutionMapping(array $jobs, bool $sourceScoped = true, ?Company $defaultCompany = null): ImportMapping
{
    $path = storage_path('framework/employer-resolution-'.uniqid().'.json');
    file_put_contents($path, json_encode(['jobs' => $jobs], JSON_THROW_ON_ERROR));
    $configuration = ['sample_path' => $path];
    if ($sourceScoped) {
        $configuration['employer_resolution'] = 'source_scoped';
    }
    $source = ImportSource::factory()->create([
        'company_id' => ($defaultCompany ?? Company::factory()->create())->id,
        'transport' => ImportTransport::Upload,
        'format' => ImportFormat::Json,
        'record_path' => 'jobs.*',
        'selection_rules' => null,
        'configuration' => $configuration,
    ]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    $destinations = [
        'source_reference' => 'id',
        'vacancy.title' => 'title',
        'vacancy.location' => 'city',
        'employer.external_id' => 'employer_id',
        'employer.name' => 'company',
        'employer.source_url' => 'company_url',
        'vacancy.source_requisition_id' => 'requisition_id',
        'vacancy.advertised_state' => 'state',
        'vacancy.advertised_country' => 'country',
        'vacancy.advertised_postal_code' => 'postal_code',
        'vacancy.workplace_city' => 'workplace_city',
        'vacancy.workplace_country' => 'workplace_country',
        'vacancy.source_salary_text' => 'salary',
        'vacancy.source_language' => 'language',
        'vacancy.source_product_code' => 'product',
    ];
    foreach ($destinations as $position => $sourcePath) {
        ImportMappingField::factory()->create([
            'import_mapping_id' => $mapping->id,
            'destination_key' => is_string($position) ? $position : $sourcePath,
            'source_paths' => [is_string($position) ? $sourcePath : ''],
            'position' => array_search($position, array_keys($destinations), true),
        ]);
    }

    return $mapping->fresh(['fields', 'importSource']);
}

test('legacy single employer sources keep assigning their configured company', function () {
    $company = Company::factory()->create();
    $mapping = employerResolutionMapping([['id' => 'legacy-1', 'title' => 'Legacy vacature']], false, $company);

    $run = app(VacancyImportRunner::class)->run($mapping);

    $vacancy = Vacancy::sole();
    expect($run->imported_rows)->toBe(1)
        ->and($vacancy->company_id)->toBe($company->id)
        ->and($vacancy->source_requisition_id)->toBeNull()
        ->and($vacancy->workplace_city)->toBeNull()
        ->and($vacancy->source_salary_text)->toBeNull();
});

test('one source resolves multiple and repeated employer identities while requisition advertisements remain separate', function () {
    $default = Company::factory()->create(['name' => 'Niet gebruiken']);
    $firstCompany = Company::factory()->create(['name' => 'Eerste werkgever', 'website' => 'https://manual.example']);
    $secondCompany = Company::factory()->create(['name' => 'Tweede werkgever']);
    $jobs = [
        ['id' => 'ad-1', 'title' => 'Breda', 'employer_id' => 'EMP-1', 'requisition_id' => 'REQ-1', 'city' => 'Breda', 'state' => 'Noord-Brabant', 'country' => 'NL', 'postal_code' => '0012', 'workplace_city' => 'Antwerpen', 'workplace_country' => 'BE', 'salary' => '€3800 - €4600 per month', 'language' => 'nl', 'product' => 'premium'],
        ['id' => 'ad-2', 'title' => 'Utrecht', 'employer_id' => 'EMP-1', 'requisition_id' => 'REQ-1', 'city' => 'Utrecht', 'country' => 'NL', 'workplace_city' => 'Antwerpen', 'workplace_country' => 'BE'],
        ['id' => 'ad-3', 'title' => 'Andere werkgever', 'employer_id' => 'EMP-2', 'requisition_id' => 'REQ-2', 'city' => 'Gent', 'country' => 'BE'],
    ];
    $mapping = employerResolutionMapping($jobs, true, $default);
    ImportSourceEmployer::factory()->create(['import_source_id' => $mapping->import_source_id, 'company_id' => $firstCompany->id, 'external_employer_id' => 'EMP-1']);
    ImportSourceEmployer::factory()->create(['import_source_id' => $mapping->import_source_id, 'company_id' => $secondCompany->id, 'external_employer_id' => 'EMP-2']);

    $run = app(VacancyImportRunner::class)->run($mapping);
    $first = Vacancy::where('source_reference', 'ad-1')->firstOrFail();

    expect($run->imported_rows)->toBe(3)
        ->and(Vacancy::where('source_requisition_id', 'REQ-1')->count())->toBe(2)
        ->and(Vacancy::where('company_id', $firstCompany->id)->count())->toBe(2)
        ->and(Vacancy::where('company_id', $secondCompany->id)->count())->toBe(1)
        ->and(Vacancy::where('company_id', $default->id)->count())->toBe(0)
        ->and($first->location)->toBe('Breda')
        ->and($first->workplace_city)->toBe('Antwerpen')
        ->and($first->advertised_postal_code)->toBe('0012')
        ->and($first->source_salary_text)->toBe('€3800 - €4600 per month')
        ->and($first->source_language)->toBe('nl')
        ->and($first->source_product_code)->toBe('premium')
        ->and($firstCompany->fresh()->website)->toBe('https://manual.example')
        ->and($firstCompany->fresh()->name)->toBe('Eerste werkgever');
});

test('the same external identity is isolated per source and distinct identities may map to one company', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $first = employerResolutionMapping([['id' => 'one', 'title' => 'One', 'employer_id' => 'SHARED']]);
    $second = employerResolutionMapping([['id' => 'two', 'title' => 'Two', 'employer_id' => 'SHARED']]);
    ImportSourceEmployer::factory()->create(['import_source_id' => $first->import_source_id, 'company_id' => $company->id, 'external_employer_id' => 'SHARED']);
    ImportSourceEmployer::factory()->create(['import_source_id' => $second->import_source_id, 'company_id' => $otherCompany->id, 'external_employer_id' => 'SHARED']);
    ImportSourceEmployer::factory()->create(['import_source_id' => $first->import_source_id, 'company_id' => $company->id, 'external_employer_id' => 'SECOND-KEY']);

    app(VacancyImportRunner::class)->run($first);
    app(VacancyImportRunner::class)->run($second);

    expect(Vacancy::where('source_reference', 'one')->value('company_id'))->toBe($company->id)
        ->and(Vacancy::where('source_reference', 'two')->value('company_id'))->toBe($otherCompany->id)
        ->and($company->importSourceEmployerMappings)->toHaveCount(2);
});

test('missing or unknown employer identity never creates a company or falls back and disables missing detection', function () {
    $default = Company::factory()->create();
    $mapping = employerResolutionMapping([
        ['id' => 'missing', 'title' => 'Missing'],
        ['id' => 'unknown', 'title' => 'Unknown', 'employer_id' => 'NO-MAP'],
    ], true, $default);
    $existing = Vacancy::factory()->create(['company_id' => $default->id, 'import_source_id' => $mapping->import_source_id, 'source_reference' => 'old', 'missing_since' => null]);
    $companyCount = Company::count();

    $run = app(VacancyImportRunner::class)->run($mapping);

    expect($run->skipped_rows)->toBe(2)
        ->and($run->imported_rows)->toBe(0)
        ->and(Company::count())->toBe($companyCount)
        ->and(Vacancy::whereIn('source_reference', ['missing', 'unknown'])->exists())->toBeFalse()
        ->and($existing->fresh()->missing_since)->toBeNull()
        ->and($run->importLogs->pluck('context')->pluck('code'))->toContain('missing_detection_skipped');
});

test('reviewed composite identity is exact and uniqueness prevents ambiguous mappings', function () {
    $mapping = employerResolutionMapping([['id' => 'composite', 'title' => 'Composite', 'company' => 'Exact BV', 'company_url' => 'https://partner.example/company/1']]);
    $company = Company::factory()->create();
    $attributes = [
        'import_source_id' => $mapping->import_source_id,
        'company_id' => $company->id,
        'identity_type' => ImportEmployerIdentityType::ReviewedComposite,
        'external_employer_id' => null,
        'source_name' => 'Exact BV',
        'source_url' => 'https://partner.example/company/1',
    ];
    ImportSourceEmployer::create($attributes);

    app(VacancyImportRunner::class)->run($mapping);

    expect(Vacancy::sole()->company_id)->toBe($company->id);
    expect(fn () => ImportSourceEmployer::create($attributes))->toThrow(QueryException::class);
});

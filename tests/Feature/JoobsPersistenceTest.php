<?php

use App\Enums\ApplicationMode;
use App\Enums\ImportEmployerIdentityType;
use App\Enums\ImportFormat;
use App\Enums\ImportMissingVacancyPolicy;
use App\Enums\ImportStatus;
use App\Enums\ImportTransport;
use App\Enums\VacancyStatus;
use App\Imports\Preview\ImportPreview;
use App\Imports\VacancyImportRunner;
use App\Models\Company;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\ImportSourceEmployer;
use App\Models\Vacancy;
use App\Support\Seo\StructuredData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

afterEach(function (): void {
    foreach (glob(storage_path('framework/joobs-persistence-*')) ?: [] as $path) {
        unlink($path);
    }
});

function joobsPersistenceXml(): string
{
    $xml = file_get_contents(base_path('tests/Fixtures/Imports/joobs/joobs-job-feed-v1-example.xml'));

    if ($xml === false) {
        throw new RuntimeException('The Joobs persistence fixture could not be read.');
    }

    return $xml;
}

function joobsPersistenceFeed(string $xml): string
{
    $path = storage_path('framework/joobs-persistence-'.uniqid().'.xml');
    file_put_contents($path, $xml);

    return $path;
}

function replaceJoobsPersistenceFeed(ImportSource $source, string $xml): void
{
    file_put_contents($source->configuration['sample_path'], $xml);
}

function joobsPersistenceMapping(?string $xml = null): array
{
    $company = Company::factory()->create([
        'name' => 'Handmatig beheerd Voorbeeldbedrijf',
        'website' => 'https://manual.example',
        'description' => 'Handmatig profiel',
    ]);
    $source = ImportSource::factory()->create([
        'company_id' => Company::factory(),
        'transport' => ImportTransport::Upload,
        'format' => ImportFormat::Xml,
        'record_path' => 'source.job',
        'selection_rules' => null,
        'missing_vacancy_policy' => ImportMissingVacancyPolicy::AutoOffline,
        'configuration' => [
            'sample_path' => joobsPersistenceFeed($xml ?? joobsPersistenceXml()),
            'source_contract' => 'joobs-1.0',
            'employer_resolution' => 'source_scoped',
            'snapshot_reconciliation' => ['enabled' => true],
        ],
    ]);
    ImportSourceEmployer::create([
        'import_source_id' => $source->id,
        'company_id' => $company->id,
        'identity_type' => ImportEmployerIdentityType::ReviewedComposite,
        'external_employer_id' => null,
        'source_name' => 'Voorbeeldbedrijf NV',
        'source_url' => 'https://joobs.be/nl/companies/voorbeeldbedrijf-nv',
        'is_active' => true,
        'reviewed_at' => now(),
    ]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    $fields = [
        ['source_reference', ['referencenumber']],
        ['employer.name', ['company']],
        ['employer.source_url', ['joobs_companyurl']],
        ['vacancy.title', ['title']],
        ['vacancy.description', ['description']],
        ['vacancy.location', ['city']],
        ['vacancy.advertised_state', ['state']],
        ['vacancy.advertised_country', ['country']],
        ['vacancy.advertised_postal_code', ['postalcode']],
        ['vacancy.workplace_city', ['joobs_workplacecity']],
        ['vacancy.workplace_country', ['joobs_workplacecountry']],
        ['vacancy.source_requisition_id', ['requisitionid']],
        ['vacancy.source_salary_text', ['salary']],
        ['vacancy.source_language', ['joobs_language']],
        ['vacancy.source_product_code', ['joobs_product']],
        ['vacancy.published_at', ['date'], 'transform', ['transform' => 'date_with_offset']],
        ['vacancy.expires_at', ['expirationdate'], 'transform', ['transform' => 'date_end_of_day', 'timezone' => 'Europe/Brussels']],
        ['vacancy.application_mode', [], 'default', ['value' => ApplicationMode::External->value]],
        ['vacancy.application_url', ['url']],
    ];
    foreach ($fields as $position => $field) {
        ImportMappingField::factory()->create([
            'import_mapping_id' => $mapping->id,
            'destination_key' => $field[0],
            'source_paths' => $field[1],
            'operation' => $field[2] ?? 'direct',
            'configuration' => $field[3] ?? [],
            'position' => $position,
        ]);
    }

    return [$source->fresh(), $mapping->fresh('fields'), $company];
}

test('the Joobs fixture persists through the generic importer with stable advertisement identity', function () {
    [$source, $mapping, $company] = joobsPersistenceMapping();
    $companyCount = Company::count();
    $profile = $company->only(['name', 'website', 'description']);

    $preview = app(ImportPreview::class)->build($source, $mapping);
    $first = app(VacancyImportRunner::class)->run($mapping);
    $vacancies = Vacancy::where('import_source_id', $source->id)->orderBy('source_reference')->get()->keyBy('source_reference');
    $antwerp = $vacancies['SM260142-k7Qd2XvP-antwerpen'];
    $breda = $vacancies['SM260142-k7Qd2XvP-breda'];
    $ids = $vacancies->pluck('id')->sort()->values()->all();

    expect($preview['counts']['ready'])->toBe(2)
        ->and($first->status)->toBe(ImportStatus::Completed)
        ->and(data_get($first->mapping, 'snapshot.state'))->toBe('verified_complete')
        ->and($first->imported_rows)->toBe(2)
        ->and($vacancies)->toHaveCount(2)
        ->and($antwerp->source_requisition_id)->toBe('k7Qd2XvP')
        ->and($breda->source_requisition_id)->toBe('k7Qd2XvP')
        ->and($antwerp->id)->not->toBe($breda->id)
        ->and($antwerp->company_id)->toBe($company->id)
        ->and($breda->company_id)->toBe($company->id)
        ->and(Company::count())->toBe($companyCount)
        ->and($company->fresh()->only(['name', 'website', 'description']))->toBe($profile)
        ->and($antwerp->location)->toBe('Antwerpen')
        ->and($breda->location)->toBe('Breda')
        ->and($breda->advertised_state)->toBe('Noord-Brabant')
        ->and($breda->advertised_country)->toBe('NL')
        ->and($breda->advertised_postal_code)->toBe('4811')
        ->and($breda->workplace_city)->toBe('Antwerpen')
        ->and($breda->workplace_country)->toBe('BE')
        ->and($antwerp->source_salary_text)->toBe('€3800 - €4600 per month')
        ->and($antwerp->salary_min)->toBeNull()
        ->and($antwerp->salary_max)->toBeNull()
        ->and($antwerp->source_language)->toBe('nl')
        ->and($antwerp->source_product_code)->toBe('standard')
        ->and($antwerp->published_at->toIso8601String())->toBe('2026-10-08T14:00:00+00:00')
        ->and($antwerp->expires_at->toIso8601String())->toBe('2026-12-07T22:59:59+00:00')
        ->and($antwerp->application_url)->toBe('https://joobs.be/nl/jobs/k7Qd2XvP?utm_source=salesenmarketingvacatures&utm_medium=jobboard&utm_campaign=SM260142&utm_content=antwerpen')
        ->and($antwerp->description)->toContain('<p>', '<strong>Account Manager B2B</strong>', '<ul>');

    $antwerp->update(['commercial_priority_adjustment' => 17]);
    $second = app(VacancyImportRunner::class)->run($mapping);

    expect($second->imported_rows)->toBe(0)
        ->and($second->updated_rows)->toBe(2)
        ->and($antwerp->fresh()->commercial_priority_adjustment)->toBe(17)
        ->and(Vacancy::where('import_source_id', $source->id)->count())->toBe(2)
        ->and(Vacancy::where('import_source_id', $source->id)->pluck('id')->sort()->values()->all())->toBe($ids);
});

test('Joobs descriptions are sanitized and optional fields remain nullable', function () {
    $xml = preg_replace('/<salary>.*?<\/salary>/s', '', joobsPersistenceXml());
    $xml = str_replace('</strong>', '</strong><script>alert("unsafe")</script>', (string) $xml);
    [$source, $mapping] = joobsPersistenceMapping($xml);

    $run = app(VacancyImportRunner::class)->run($mapping);
    $vacancy = Vacancy::where('import_source_id', $source->id)->firstOrFail();

    expect($run->status)->toBe(ImportStatus::Completed)
        ->and($vacancy->source_salary_text)->toBeNull()
        ->and($vacancy->salary_min)->toBeNull()
        ->and($vacancy->description)->toContain('<strong>Account Manager B2B</strong>')
        ->and($vacancy->description)->not->toContain('<script>', 'alert("unsafe")');
});

test('verified Joobs disappearance and reappearance preserve identity and editorial state', function () {
    [$source, $mapping] = joobsPersistenceMapping();
    app(VacancyImportRunner::class)->run($mapping);
    $missing = Vacancy::where('source_reference', 'SM260142-k7Qd2XvP-breda')->firstOrFail();
    $missing->update([
        'status' => VacancyStatus::Archived,
        'commercial_priority_adjustment' => -14,
    ]);
    $oneAdvertisement = preg_replace('/(<job>.*?<\/job>)\s*<job>.*?<\/job>/s', '$1', joobsPersistenceXml(), 1);
    replaceJoobsPersistenceFeed($source, (string) $oneAdvertisement);

    $disappearance = app(VacancyImportRunner::class)->run($mapping);

    expect($disappearance->status)->toBe(ImportStatus::Completed)
        ->and($disappearance->missing_rows)->toBe(1)
        ->and($missing->fresh()->missing_since)->not->toBeNull()
        ->and($missing->fresh()->status)->toBe(VacancyStatus::Archived)
        ->and(Vacancy::where('import_source_id', $source->id)->count())->toBe(2);

    replaceJoobsPersistenceFeed($source, joobsPersistenceXml());
    $reappearance = app(VacancyImportRunner::class)->run($mapping);

    expect($reappearance->restored_rows)->toBe(1)
        ->and($missing->fresh()->missing_since)->toBeNull()
        ->and($missing->fresh()->status)->toBe(VacancyStatus::Archived)
        ->and($missing->fresh()->commercial_priority_adjustment)->toBe(-14)
        ->and($missing->fresh()->id)->toBe($missing->id)
        ->and(Vacancy::where('import_source_id', $source->id)->count())->toBe(2);
});

test('unsafe Joobs snapshots never reconcile or create records', function (Closure $makeUnsafe, ImportStatus $status, string $state) {
    [$source, $mapping] = joobsPersistenceMapping();
    app(VacancyImportRunner::class)->run($mapping);
    $protected = Vacancy::factory()->create([
        'company_id' => Vacancy::where('import_source_id', $source->id)->value('company_id'),
        'import_source_id' => $source->id,
        'source_reference' => 'protected-existing-reference',
        'missing_since' => null,
    ]);
    $makeUnsafe($source);
    $count = Vacancy::count();

    $run = app(VacancyImportRunner::class)->run($mapping);

    expect($run->status)->toBe($status)
        ->and(data_get($run->mapping, 'snapshot.state'))->toBe($state)
        ->and($run->missing_rows)->toBe(0)
        ->and($protected->fresh()->missing_since)->toBeNull()
        ->and(Vacancy::count())->toBe($count);
})->with([
    'duplicate advertisement reference' => [function (ImportSource $source): void {
        replaceJoobsPersistenceFeed($source, str_replace('SM260142-k7Qd2XvP-breda', 'SM260142-k7Qd2XvP-antwerpen', joobsPersistenceXml()));
    }, ImportStatus::Failed, 'read_or_parse_failed'],
    'required identity missing' => [function (ImportSource $source): void {
        replaceJoobsPersistenceFeed($source, preg_replace('/\s*<referencenumber>.*?<\/referencenumber>/s', '', joobsPersistenceXml(), 1));
    }, ImportStatus::Failed, 'read_or_parse_failed'],
    'requisition correlation missing' => [function (ImportSource $source): void {
        replaceJoobsPersistenceFeed($source, preg_replace('/\s*<requisitionid>.*?<\/requisitionid>/s', '', joobsPersistenceXml(), 1));
    }, ImportStatus::Failed, 'read_or_parse_failed'],
    'unsupported feed version' => [function (ImportSource $source): void {
        replaceJoobsPersistenceFeed($source, str_replace('feedversion="joobs-1.0"', 'feedversion="joobs-2.0"', joobsPersistenceXml()));
    }, ImportStatus::Failed, 'read_or_parse_failed'],
    'publication timestamp without offset' => [function (ImportSource $source): void {
        replaceJoobsPersistenceFeed($source, str_replace('2026-10-08T14:00:00Z', '2026-10-08T14:00:00', joobsPersistenceXml()));
    }, ImportStatus::Failed, 'read_or_parse_failed'],
    'unsupported language' => [function (ImportSource $source): void {
        replaceJoobsPersistenceFeed($source, str_replace('<![CDATA[nl]]>', '<![CDATA[xx]]>', joobsPersistenceXml()));
    }, ImportStatus::Failed, 'read_or_parse_failed'],
    'mapping validation failure' => [function (ImportSource $source): void {
        $source->mappings()->firstOrFail()->fields()->where('destination_key', 'vacancy.title')->firstOrFail()
            ->update(['source_paths' => ['missing_title']]);
    }, ImportStatus::Incomplete, 'snapshot_mapping_invalid'],
    'invalid application URL' => [function (ImportSource $source): void {
        replaceJoobsPersistenceFeed($source, preg_replace('/<url>.*?<\/url>/s', '<url>not-a-url</url>', joobsPersistenceXml(), 1));
    }, ImportStatus::Failed, 'read_or_parse_failed'],
    'empty contract feed' => [function (ImportSource $source): void {
        replaceJoobsPersistenceFeed($source, '<source feedversion="joobs-1.0"></source>');
    }, ImportStatus::Failed, 'read_or_parse_failed'],
]);

test('unresolved Joobs employers and persistence failures make reconciliation unsafe', function () {
    [$source, $mapping] = joobsPersistenceMapping();
    app(VacancyImportRunner::class)->run($mapping);
    $protected = Vacancy::factory()->create([
        'company_id' => Vacancy::where('import_source_id', $source->id)->value('company_id'),
        'import_source_id' => $source->id,
        'source_reference' => 'protected-existing-reference',
    ]);
    $source->employerMappings()->update(['is_active' => false]);
    $unresolved = app(VacancyImportRunner::class)->run($mapping);

    expect($unresolved->status)->toBe(ImportStatus::Incomplete)
        ->and(data_get($unresolved->mapping, 'snapshot.state'))->toBe('snapshot_record_unresolved')
        ->and($protected->fresh()->missing_since)->toBeNull();

    $source->employerMappings()->update(['is_active' => true]);
    Event::listen('eloquent.saving: '.Vacancy::class, function (Vacancy $vacancy): void {
        if ($vacancy->source_reference === 'SM260142-k7Qd2XvP-breda') {
            throw new RuntimeException('Joobs persistence failure');
        }
    });
    try {
        $failed = app(VacancyImportRunner::class)->run($mapping);
    } finally {
        Event::forget('eloquent.saving: '.Vacancy::class);
    }

    expect($failed->status)->toBe(ImportStatus::Failed)
        ->and(data_get($failed->mapping, 'snapshot.state'))->toBe('application_failed')
        ->and($protected->fresh()->missing_since)->toBeNull();
});

test('Joobs location metadata drives detail and JobPosting while legacy fallback remains available', function () {
    [$source, $mapping] = joobsPersistenceMapping();
    app(VacancyImportRunner::class)->run($mapping);
    $breda = Vacancy::where('source_reference', 'SM260142-k7Qd2XvP-breda')->firstOrFail();
    $structured = StructuredData::jobPosting($breda);

    expect(data_get($structured, 'jobLocation.address.addressLocality'))->toBe('Antwerpen')
        ->and(data_get($structured, 'jobLocation.address.addressCountry'))->toBe('BE')
        ->and($breda->actualWorkplaceLabel())->toBe('Antwerpen, BE');

    $breda->forceFill(['workplace_city' => null, 'workplace_country' => null])->save();
    $fallback = StructuredData::jobPosting($breda->fresh());

    expect(data_get($fallback, 'jobLocation.address.addressLocality'))->toBe('Breda')
        ->and(data_get($fallback, 'jobLocation.address.addressCountry'))->toBe('NL');
});

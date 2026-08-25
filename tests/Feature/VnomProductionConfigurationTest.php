<?php

use App\Enums\ApplicationMode;
use App\Enums\CategoryType;
use App\Enums\ImportFormat;
use App\Enums\ImportTransport;
use App\Enums\VacancyStatus;
use App\Imports\Data\SourceRecord;
use App\Imports\Mapping\CompensationTextParser;
use App\Imports\Mapping\VacancyDescriptionSanitizer;
use App\Imports\Preview\ImportPreview;
use App\Imports\RecordSelector;
use App\Imports\VacancyImportRunner;
use App\Models\Category;
use App\Models\Company;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\ImportTaxonomyMapping;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    foreach (glob(storage_path('framework/vnom-production-*.xml')) ?: [] as $path) {
        unlink($path);
    }
});

function vnomProductionMapping(string $path): array
{
    $company = Company::factory()->create([
        'name' => 'VNOM',
        'description' => 'Handmatig beheerd VNOM-profiel',
        'email' => 'contact@vnom.test',
        'phone' => '010-1234567',
    ]);
    $source = ImportSource::factory()->create([
        'company_id' => $company->id,
        'name' => 'VNOM vacatures',
        'transport' => ImportTransport::Upload,
        'format' => ImportFormat::Xml,
        'endpoint_url' => null,
        'record_path' => 'job',
        'selection_rules' => [
            'logic' => 'or',
            'rules' => [
                ['field' => 'function', 'operator' => 'contains', 'value' => 'Sales'],
                ['field' => 'function', 'operator' => 'contains', 'value' => 'Marketing'],
            ],
        ],
        'configuration' => ['sample_path' => $path],
    ]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id, 'name' => 'VNOM productie']);
    $fields = [
        ['source_reference', ['identifier'], 'direct', []],
        ['vacancy.title', ['title'], 'direct', []],
        ['vacancy.description', ['description'], 'direct', []],
        ['vacancy.location', ['city'], 'direct', []],
        ['vacancy.published_at', ['date'], 'transform', ['transform' => 'date']],
        ['vacancy.application_mode', [], 'default', ['value' => ApplicationMode::External->value]],
        ['vacancy.application_url', ['applyUrl'], 'direct', []],
        ['vacancy.salary_min', ['salary'], 'transform', ['transform' => 'compensation_text_min']],
        ['vacancy.salary_max', ['salary'], 'transform', ['transform' => 'compensation_text_max']],
        ['vacancy.salary_currency', ['salary'], 'transform', ['transform' => 'compensation_text_currency']],
        ['vacancy.salary_period', ['salary'], 'transform', ['transform' => 'compensation_text_period']],
        ['taxonomy.function_area', ['function'], 'direct', []],
    ];
    foreach ($fields as $position => [$destination, $paths, $operation, $configuration]) {
        ImportMappingField::factory()->create(compact('position', 'operation', 'configuration') + [
            'import_mapping_id' => $mapping->id,
            'destination_key' => $destination,
            'source_paths' => $paths,
        ]);
    }
    $sales = Category::factory()->create(['name' => 'Sales', 'type' => CategoryType::function_area]);
    ImportTaxonomyMapping::create([
        'import_source_id' => $source->id,
        'category_type' => CategoryType::function_area,
        'source_value' => 'Sales',
        'category_id' => $sales->id,
    ]);

    return [$source->fresh('company'), $mapping->fresh('fields'), $sales];
}

function vnomProductionFixture(?string $excludedIdentifier = null, ?string $firstTitle = null, ?string $firstFunction = null): string
{
    $document = new DOMDocument;
    $document->load(base_path('tests/Fixtures/Imports/vnom/jobs_for_test.xml'));
    $xpath = new DOMXPath($document);

    foreach (iterator_to_array($xpath->query('/source/job') ?: []) as $position => $job) {
        $identifier = trim((string) $xpath->evaluate('string(identifier)', $job));
        if ($identifier === $excludedIdentifier) {
            $job->parentNode?->removeChild($job);
        } elseif ($position === 0 && $firstTitle !== null) {
            $title = $xpath->query('title', $job)?->item(0);
            if ($title !== null) {
                $title->nodeValue = $firstTitle;
            }
        }
        if ($position === 0 && $firstFunction !== null) {
            $function = $xpath->query('function', $job)?->item(0);
            if ($function !== null) {
                $function->nodeValue = $firstFunction;
            }
        }
    }

    $path = storage_path('framework/vnom-production-'.uniqid().'.xml');
    $document->save($path);

    return $path;
}

test('generic compensation text parsing supports bounded ranges and warns instead of guessing', function () {
    $parser = app(CompensationTextParser::class);

    expect($parser->parse('€2.900 - €3.500 Month'))->toMatchArray([
        'min' => 2900,
        'max' => 3500,
        'currency' => 'EUR',
        'period' => 'month',
        'warnings' => [],
    ])->and($parser->parse('€45 Hour'))->toMatchArray([
        'min' => 45,
        'max' => null,
        'currency' => 'EUR',
        'period' => 'hour',
    ])->and($parser->parse(null))->toMatchArray([
        'min' => null,
        'max' => null,
        'currency' => null,
        'period' => null,
        'warnings' => [],
    ])->and($parser->parse('€3000 - €3500 Per cycle')['period'])->toBeNull()
        ->and($parser->parse('€3000 - €3500 Per cycle')['warnings'])->not->toBeEmpty();
});

test('imported rich vacancy descriptions retain safe formatting without executable markup', function () {
    $sanitized = app(VacancyDescriptionSanitizer::class)->sanitize(
        '<p onclick="alert(1)">Intro <strong>belangrijk</strong>.</p><script>alert(2)</script><a href="javascript:alert(3)">link</a>',
    );

    expect($sanitized)->toContain('<p>', '<strong>belangrijk</strong>', '<a>link</a>')
        ->not->toContain('onclick', '<script', 'alert(2)', 'javascript:');
});

test('VNOM selection configuration includes Sales and Marketing but excludes unrelated functions', function () {
    $rules = [
        'logic' => 'or',
        'rules' => [
            ['field' => 'function', 'operator' => 'contains', 'value' => 'Sales'],
            ['field' => 'function', 'operator' => 'contains', 'value' => 'Marketing'],
        ],
    ];
    $selector = app(RecordSelector::class);

    expect($selector->matches(new SourceRecord(1, ['function' => 'Sales']), $rules))->toBeTrue()
        ->and($selector->matches(new SourceRecord(2, ['function' => 'Online Marketing']), $rules))->toBeTrue()
        ->and($selector->matches(new SourceRecord(3, ['function' => 'Elektrotechniek']), $rules))->toBeFalse();
});

test('an unresolved VNOM function remains visible as a source-scoped preview resolution issue', function () {
    $path = vnomProductionFixture(null, null, 'Sales Support');
    [$source, $mapping] = vnomProductionMapping($path);

    $preview = app(ImportPreview::class)->build($source, $mapping, 25);
    $record = collect($preview['records'])->first(fn ($record) => $record->outcome->data->get('source_reference') === 'a1WWy000001RVvBMAW');

    expect($preview['counts']['needs_resolution'])->toBe(1)
        ->and($record->outcome->unresolved)->toContain([
            'code' => 'taxonomy_unresolved',
            'field' => 'taxonomy.function_area',
            'source_value' => 'Sales Support',
            'message' => 'Functiegebied: nog niet gekoppeld.',
        ]);
});

test('VNOM configuration previews executes reruns and reports missing and restored jobs generically', function () {
    $path = vnomProductionFixture();
    [$source, $mapping, $sales] = vnomProductionMapping($path);
    $companyBefore = $source->company->only(['name', 'description', 'email', 'phone', 'logo']);

    $preview = app(ImportPreview::class)->build($source, $mapping, 25);
    $firstPreview = $preview['records'][0]->outcome->data;
    expect($preview['counts']['previewed'])->toBe(4)
        ->and($preview['counts']['errors'])->toBe(0)
        ->and($preview['counts']['needs_resolution'])->toBe(0)
        ->and($firstPreview->get('source_reference'))->toBe('a1WWy000001RVvBMAW')
        ->and($firstPreview->get('vacancy.salary_min'))->toBe(2900)
        ->and($firstPreview->get('vacancy.salary_max'))->toBe(3500)
        ->and($firstPreview->get('vacancy.salary_currency'))->toBe('EUR')
        ->and($firstPreview->get('vacancy.salary_period'))->toBe('month')
        ->and($firstPreview->get('vacancy.application_mode'))->toBe('external')
        ->and($firstPreview->get('vacancy.application_url'))->toContain('/sollicitatie-start/');

    $first = app(VacancyImportRunner::class)->run($mapping);
    $vacancy = Vacancy::where('source_reference', 'a1WWy000001RVvBMAW')->firstOrFail();
    $slug = $vacancy->slug;
    expect($first->imported_rows)->toBe(4)
        ->and($first->skipped_rows)->toBe(0)
        ->and($first->failed_rows)->toBe(0)
        ->and($vacancy->company_id)->toBe($source->company_id)
        ->and($vacancy->description)->toContain('<p>', '<ul>', '<li>')
        ->and($vacancy->description)->not->toContain('<script', ' onclick=', ' style=')
        ->and($vacancy->categories()->whereKey($sales)->exists())->toBeTrue()
        ->and($source->company->fresh()->only(['name', 'description', 'email', 'phone', 'logo']))->toBe($companyBefore);

    $vacancy->update(['status' => VacancyStatus::Active]);
    $this->get(route('vacancies.show', $vacancy->fresh()))
        ->assertOk()
        ->assertSee($vacancy->application_url, false)
        ->assertSee('Solliciteer nu');

    $missingReference = 'a1WWy0000027TqXMAU';
    $replacement = vnomProductionFixture($missingReference, 'Commercieel Medewerker vernieuwd');
    $source->update(['configuration' => ['sample_path' => $replacement]]);
    $second = app(VacancyImportRunner::class)->run($mapping);
    expect($second->updated_rows)->toBe(3)
        ->and($second->missing_rows)->toBe(1)
        ->and(Vacancy::where('source_reference', $missingReference)->first()->missing_since)->not->toBeNull()
        ->and(Vacancy::where('source_reference', $missingReference)->exists())->toBeTrue()
        ->and($vacancy->fresh()->slug)->toBe($slug)
        ->and(Vacancy::where('import_source_id', $source->id)->count())->toBe(4);

    $restoredFixture = vnomProductionFixture(null, 'Commercieel Medewerker vernieuwd');
    $source->update(['configuration' => ['sample_path' => $restoredFixture]]);
    $third = app(VacancyImportRunner::class)->run($mapping);
    expect($third->imported_rows)->toBe(0)
        ->and($third->updated_rows)->toBe(4)
        ->and($third->restored_rows)->toBe(1)
        ->and(Vacancy::where('source_reference', $missingReference)->first()->missing_since)->toBeNull()
        ->and(Vacancy::where('import_source_id', $source->id)->count())->toBe(4)
        ->and($source->company->fresh()->only(['name', 'description', 'email', 'phone', 'logo']))->toBe($companyBefore)
        ->and($third->importLogs->pluck('context')->contains(fn (array $context): bool => ($context['code'] ?? null) === 'restored_in_source'))->toBeTrue();
});

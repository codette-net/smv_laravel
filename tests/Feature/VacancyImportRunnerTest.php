<?php

use App\Enums\CategoryType;
use App\Enums\ImportFormat;
use App\Enums\ImportTransport;
use App\Imports\VacancyImportRunner;
use App\Models\Category;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\ImportTaxonomyMapping;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Tags\Tag;

uses(RefreshDatabase::class);

afterEach(function (): void {
    foreach (glob(storage_path('framework/testing-import-*.json')) ?: [] as $path) {
        unlink($path);
    }
});

function runnableMapping(string $title = 'Eerste titel'): ImportMapping
{
    $source = ImportSource::factory()->create(['transport' => ImportTransport::Upload, 'format' => ImportFormat::Json, 'record_path' => 'jobs.*', 'selection_rules' => null, 'configuration' => ['sample_path' => base_path('tests/Fixtures/Imports/orange-career/provisional-example.json')]]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ([['source_reference', ['title']], ['vacancy.title', ['title']]] as $position => [$destination, $paths]) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => $destination, 'source_paths' => $paths, 'position' => $position]);
    }

    return $mapping->fresh('fields');
}

function controlledRunnableMapping(array $jobs): ImportMapping
{
    $path = storage_path('framework/testing-import-'.uniqid().'.json');
    file_put_contents($path, json_encode(['jobs' => $jobs], JSON_THROW_ON_ERROR));
    $source = ImportSource::factory()->create(['transport' => ImportTransport::Upload, 'format' => ImportFormat::Json, 'record_path' => 'jobs.*', 'selection_rules' => null, 'configuration' => ['sample_path' => $path]]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ([['source_reference', ['id']], ['vacancy.title', ['title']], ['taxonomy.function_area', ['function']]] as $position => [$destination, $paths]) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => $destination, 'source_paths' => $paths, 'position' => $position]);
    }

    return $mapping->fresh('fields');
}

function xmlRunnableMapping(string $fixture, array $fields): ImportMapping
{
    $source = ImportSource::factory()->create(['transport' => ImportTransport::Upload, 'format' => ImportFormat::Xml, 'record_path' => 'job', 'selection_rules' => null, 'configuration' => ['sample_path' => base_path("tests/Fixtures/Imports/{$fixture}")]]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ($fields as $position => [$destination, $paths, $operation, $configuration]) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => $destination, 'source_paths' => $paths, 'operation' => $operation, 'configuration' => $configuration, 'position' => $position]);
    }

    return $mapping->fresh('fields');
}

test('runner creates then provider-scoped updates without duplicate vacancies', function () {
    $mapping = runnableMapping();
    $first = app(VacancyImportRunner::class)->run($mapping);
    expect($first->imported_rows)->toBe(2)->and(Vacancy::count())->toBe(2);
    $slug = Vacancy::first()->slug;
    $second = app(VacancyImportRunner::class)->run($mapping);
    expect($second->updated_rows)->toBe(2)->and(Vacancy::count())->toBe(2)->and(Vacancy::first()->slug)->toBe($slug);
});

test('resolved mapped taxonomy attaches without creating categories while unrelated types remain intact', function () {
    $mapping = runnableMapping();
    $sales = Category::factory()->create(['name' => 'Sales medewerker', 'type' => CategoryType::function_area]);
    ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => 'taxonomy.function_area', 'source_paths' => ['title'], 'position' => 2]);
    $before = Category::count();
    app(VacancyImportRunner::class)->run($mapping->fresh('fields'));
    expect(Vacancy::first()->categories()->whereKey($sales)->exists())->toBeTrue()->and(Category::count())->toBe($before);
});

test('mapped tags and compensation persist idempotently while unmapped fields remain untouched', function () {
    $mapping = runnableMapping();
    foreach ([['vacancy.salary_min', ['salary_low']], ['vacancy.salary_max', ['salary_high']], ['vacancy.salary_currency', ['salary_currency']], ['vacancy.salary_period', ['salary_type']], ['vacancy.rate_min', []], ['tags', ['tags']]] as $position => [$destination, $paths]) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => $destination, 'source_paths' => $paths, 'position' => $position + 2]);
    }
    app(VacancyImportRunner::class)->run($mapping->fresh('fields'));
    $vacancy = Vacancy::where('source_reference', 'Sales medewerker')->first();
    expect($vacancy->salary_min)->toBe(2500)->and($vacancy->salary_max)->toBe(3000)->and($vacancy->salary_currency)->toBe('EUR')->and($vacancy->tags->pluck('name')->all())->toContain('SaaS', 'B2B');
    app(VacancyImportRunner::class)->run($mapping->fresh('fields'));
    expect(Tag::count())->toBe(3)->and($vacancy->fresh()->tags)->toHaveCount(2);
});

test('taxonomy values resolve independently and unresolved records do not block later records', function () {
    $mapping = controlledRunnableMapping([['id' => 'A', 'title' => 'A', 'function' => 'Accountmanagement'], ['id' => 'B', 'title' => 'B', 'function' => 'Onbekend'], ['id' => 'C', 'title' => 'C', 'function' => 'Communicatie']]);
    $account = Category::factory()->create(['name' => 'Accountmanagement', 'type' => CategoryType::function_area]);
    $communication = Category::factory()->create(['name' => 'Communicatie', 'type' => CategoryType::function_area]);
    $run = app(VacancyImportRunner::class)->run($mapping);
    expect($run->imported_rows)->toBe(2)->and($run->failed_rows)->toBe(1)->and(Vacancy::count())->toBe(2)
        ->and(Vacancy::where('source_reference', 'A')->first()->categories()->whereKey($account)->exists())->toBeTrue()
        ->and(Vacancy::where('source_reference', 'C')->first()->categories()->whereKey($communication)->exists())->toBeTrue()
        ->and(Vacancy::where('source_reference', 'B')->exists())->toBeFalse();
});

test('VNOM XML persists generic configured mappings and remains idempotent', function () {
    $mapping = xmlRunnableMapping('vnom/jobs_for_test.xml', [['source_reference', ['identifier'], 'direct', []], ['vacancy.title', ['title'], 'direct', []], ['vacancy.description', ['description'], 'direct', []], ['vacancy.location', ['city'], 'direct', []], ['vacancy.application_mode', [], 'default', ['value' => 'external']], ['vacancy.application_url', ['applyUrl'], 'direct', []], ['taxonomy.function_area', ['function'], 'direct', []]]);
    $sales = Category::factory()->create(['name' => 'Sales', 'type' => CategoryType::function_area]);
    ImportTaxonomyMapping::create(['import_source_id' => $mapping->import_source_id, 'category_type' => CategoryType::function_area, 'source_value' => 'Sales', 'source_key' => 'sales', 'category_id' => $sales->id]);
    $run = app(VacancyImportRunner::class)->run($mapping);
    expect($run->imported_rows)->toBeGreaterThan(0)->and(Vacancy::where('import_source_id', $mapping->import_source_id)->exists())->toBeTrue();
    app(VacancyImportRunner::class)->run($mapping);
    expect(Vacancy::where('import_source_id', $mapping->import_source_id)->count())->toBe($run->imported_rows);
});

test('Michael Page XML persists nested configured fields without a provider persister', function () {
    $mapping = xmlRunnableMapping('michael-page/jobs_for_test.xml', [['source_reference', ['uniqueJobID'], 'direct', []], ['vacancy.title', ['title'], 'direct', []], ['vacancy.description', ['description.role'], 'direct', []], ['vacancy.location', ['location.text'], 'direct', []], ['vacancy.application_mode', [], 'default', ['value' => 'external']], ['vacancy.application_url', ['Job_Detail_URL'], 'direct', []], ['taxonomy.function_area', ['sector.term'], 'direct', []], ['taxonomy.sector', ['industry.term'], 'direct', []]]);
    foreach ([['Banking Financial Services', 'function_area'], ['Healthcare & Life Sciences', 'function_area'], ['Retail', 'function_area'], ['Transport & Distribution', 'sector']] as [$name, $type]) {
        Category::factory()->create(['name' => $name, 'type' => CategoryType::from($type)]);
    }
    $run = app(VacancyImportRunner::class)->run($mapping);
    expect($run->imported_rows)->toBeGreaterThan(0)->and(Vacancy::where('import_source_id', $mapping->import_source_id)->first()->location)->not->toBeNull();
});

test('controlled taxonomy types are replaced while unrelated taxonomy types remain attached', function () {
    $mapping = controlledRunnableMapping([['id' => 'update-1', 'title' => 'Bijgewerkt', 'function' => 'Nieuwe functie', 'sector' => 'Nieuwe sector']]);
    ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => 'taxonomy.sector', 'source_paths' => ['sector'], 'position' => 3]);
    $source = $mapping->importSource;
    $oldFunction = Category::factory()->create(['name' => 'Oude functie', 'type' => CategoryType::function_area]);
    $oldSector = Category::factory()->create(['name' => 'Oude sector', 'type' => CategoryType::sector]);
    $experience = Category::factory()->create(['name' => 'Medior', 'type' => CategoryType::experience]);
    $workplace = Category::factory()->create(['name' => 'Hybride', 'type' => CategoryType::workplace]);
    $newFunction = Category::factory()->create(['name' => 'Nieuwe functie', 'type' => CategoryType::function_area]);
    $newSector = Category::factory()->create(['name' => 'Nieuwe sector', 'type' => CategoryType::sector]);
    $vacancy = Vacancy::factory()->create(['company_id' => $source->company_id, 'import_source_id' => $source->id, 'source_reference' => 'update-1']);
    $vacancy->categories()->attach([$oldFunction->id, $oldSector->id, $experience->id, $workplace->id]);
    app(VacancyImportRunner::class)->run($mapping->fresh('fields'));
    $ids = $vacancy->fresh()->categories->pluck('id')->all();
    expect($ids)->toContain($newFunction->id, $newSector->id, $experience->id, $workplace->id)->not->toContain($oldFunction->id, $oldSector->id);
    app(VacancyImportRunner::class)->run($mapping->fresh('fields'));
    expect($vacancy->fresh()->categories)->toHaveCount(4);
});

<?php

use App\Imports\Data\SourceRecord;
use App\Imports\Mapping\ImportMapper;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function mappingWith(array $fields): array
{
    $source = ImportSource::factory()->create();
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ($fields as $position => $field) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'position' => $position, ...$field]);
    }

    return [$source, $mapping->fresh('fields')];
}

test('a reusable mapping belongs to its source and persists ordered fields', function () {
    [$source, $mapping] = mappingWith([['destination_key' => 'vacancy.title', 'operation' => 'direct', 'source_paths' => ['title']]]);
    expect($source->mappings->sole()->is($mapping))->toBeTrue()->and($mapping->fields->sole()->source_paths)->toBe(['title']);
});

test('maps flat nested defaults combine and source identity without writes', function () {
    [$source, $mapping] = mappingWith([
        ['destination_key' => 'source_reference', 'operation' => 'direct', 'source_paths' => ['id']],
        ['destination_key' => 'vacancy.title', 'operation' => 'direct', 'source_paths' => ['job.title']],
        ['destination_key' => 'vacancy.description', 'operation' => 'combine', 'source_paths' => ['role', 'bullets.*'], 'configuration' => ['separator' => '\n']],
        ['destination_key' => 'vacancy.application_url', 'operation' => 'default', 'source_paths' => [], 'configuration' => ['value' => 'https://example.test/apply']],
        ['destination_key' => 'company.name', 'operation' => 'direct', 'source_paths' => ['company.name']],
        ['destination_key' => 'taxonomy.function_area', 'operation' => 'direct', 'source_paths' => ['function']],
    ]);
    $result = app(ImportMapper::class)->map(new SourceRecord(1, ['id' => 1820725136, 'job' => ['title' => 'Accountmanager'], 'role' => '<p>Rol</p>', 'bullets' => ['Een', 'Twee'], 'company' => ['name' => 'Bron BV'], 'function' => 'Sales']), $mapping, $source);
    expect($result->canContinue())->toBeTrue()->and($result->data->get('source_reference'))->toBe('1820725136')->and($result->data->get('vacancy.description'))->toContain('Een')->and($result->data->get('company.name'))->toBe('Bron BV');
    expect($source->company->name)->not->toBe('Bron BV');
});

test('compensation keeps salary and rate independent and warns about unknown periods', function () {
    [$source, $mapping] = mappingWith([
        ['destination_key' => 'source_reference', 'operation' => 'direct', 'source_paths' => ['id']], ['destination_key' => 'vacancy.title', 'operation' => 'direct', 'source_paths' => ['title']],
        ['destination_key' => 'vacancy.salary_min', 'operation' => 'transform', 'source_paths' => ['annual'], 'configuration' => ['transform' => 'annual_salary_to_monthly']], ['destination_key' => 'vacancy.salary_period', 'operation' => 'default', 'source_paths' => [], 'configuration' => ['value' => 'month']],
        ['destination_key' => 'vacancy.rate_min', 'operation' => 'direct', 'source_paths' => ['rate']], ['destination_key' => 'vacancy.rate_period', 'operation' => 'direct', 'source_paths' => ['rate_period']],
    ]);
    $result = app(ImportMapper::class)->map(new SourceRecord(1, ['id' => 'x', 'title' => 'Titel', 'annual' => 60000, 'rate' => 120, 'rate_period' => 'hour']), $mapping, $source);
    expect($result->data->get('vacancy.salary_min'))->toBe(5000)->and($result->data->get('vacancy.rate_min'))->toBe(120)->and($result->data->get('vacancy.rate_period'))->toBe('hour');
});

test('missing identity and unknown destinations or transforms are rejected', function () {
    [$source, $mapping] = mappingWith([['destination_key' => 'vacancy.title', 'operation' => 'direct', 'source_paths' => ['title']]]);
    expect(app(ImportMapper::class)->map(new SourceRecord(1, ['title' => 'Titel']), $mapping, $source)->canContinue())->toBeFalse();
    expect(fn () => ImportMappingField::factory()->create(['destination_key' => 'vacancy.evil']))->toThrow(InvalidArgumentException::class);
});

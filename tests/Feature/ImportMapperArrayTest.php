<?php

use App\Enums\ImportFormat;
use App\Enums\ImportTransport;
use App\Imports\Data\SourcePayload;
use App\Imports\ImportReaderResolver;
use App\Imports\Mapping\ImportMapper;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function xmlRecord(string $fixture)
{
    $source = ImportSource::factory()->make([
        'transport' => ImportTransport::Upload,
        'format' => ImportFormat::Xml,
        'record_path' => 'job',
    ]);
    $records = app(ImportReaderResolver::class)->for($source)->records(
        $source,
        SourcePayload::fromPath(base_path("tests/Fixtures/Imports/{$fixture}")),
    );

    return [$source, $records->current()];
}

function mappingForValues(ImportSource $source, array $fields): ImportMapping
{
    $source->save();
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ($fields as $position => [$destination, $paths, $operation]) {
        ImportMappingField::factory()->create([
            'import_mapping_id' => $mapping->id,
            'destination_key' => $destination,
            'source_paths' => $paths,
            'operation' => $operation,
            'position' => $position,
        ]);
    }

    return $mapping->fresh('fields');
}

test('VNOM scalar CDATA and taxonomy values remain scalar through direct mapping', function () {
    [$source, $record] = xmlRecord('vnom/jobs_for_test.xml');
    $expectedScalars = ['title', 'identifier', 'referencenumber', 'description', 'city', 'salary', 'jobtype', 'experience', 'function', 'category', 'applyUrl'];

    foreach ($expectedScalars as $path) {
        expect($record->get($path))->toBeString();
    }

    $mapping = mappingForValues($source, [
        ['source_reference', ['identifier'], 'direct'],
        ['vacancy.title', ['title'], 'direct'],
        ['vacancy.description', ['description'], 'direct'],
        ['taxonomy.function_area', ['function'], 'direct'],
    ]);
    $result = app(ImportMapper::class)->map($record, $mapping, $source);

    expect($result->errors)->toBeEmpty()
        ->and($result->data->get('source_reference'))->toBe('a1WWy000001RVvBMAW')
        ->and($result->data->get('vacancy.title'))->toBe('Commercieel Medewerker')
        ->and($result->data->get('vacancy.description'))->toStartWith('<p>Zie jij kansen')
        ->and($result->data->get('taxonomy.function_area'))->toBe('Sales');
});

test('repeated XML values remain arrays for destinations that support multiple values', function () {
    [$source, $record] = xmlRecord('xml/repeated-values.xml');
    $mapping = mappingForValues($source, [
        ['source_reference', ['identifier'], 'direct'],
        ['vacancy.title', ['title'], 'direct'],
        ['tags', ['tag'], 'direct'],
    ]);
    $result = app(ImportMapper::class)->map($record, $mapping, $source);

    expect($record->get('tag'))->toBe(['B2B', 'SaaS'])
        ->and($result->errors)->toBeEmpty()
        ->and($result->data->get('tags'))->toBe(['B2B', 'SaaS']);
});

test('an array mapped to a scalar destination produces a structured mapping error', function () {
    [$source, $record] = xmlRecord('xml/repeated-values.xml');
    $mapping = mappingForValues($source, [
        ['source_reference', ['tag'], 'direct'],
        ['vacancy.title', ['title'], 'direct'],
    ]);
    $result = app(ImportMapper::class)->map($record, $mapping, $source);

    expect($result->data->get('source_reference'))->toBeNull()
        ->and($result->errors)->toContain("Bronveld 'tag' bevat meerdere waarden en kan niet direct aan 'Bronreferentie (verplicht)' worden gekoppeld.")
        ->and($result->errors)->toContain('Required mapping value [source_reference] is missing.');
});

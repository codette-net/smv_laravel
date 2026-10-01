<?php

use App\Enums\ImportFormat;
use App\Imports\Data\SourcePayload;
use App\Imports\FieldDiscovery;
use App\Imports\ImportReaderResolver;
use App\Imports\LocalSourceLoader;
use App\Imports\Readers\CsvReader;
use App\Imports\Readers\JsonReader;
use App\Imports\Readers\SpreadsheetReader;
use App\Imports\Readers\XmlReader;
use App\Imports\RecordSelector;
use App\Imports\SourceFetcher;
use App\Models\ImportSource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function importFixture(string $path): string
{
    return base_path("tests/Fixtures/Imports/{$path}");
}

function importSourceFor(ImportFormat $format, ?string $recordPath = null): ImportSource
{
    return ImportSource::factory()->make(['format' => $format, 'record_path' => $recordPath]);
}

function readRecords(ImportSource $source, string $fixture, ?int $limit = null): array
{
    $records = [];
    foreach (app(ImportReaderResolver::class)->for($source)->records($source, SourcePayload::fromPath(importFixture($fixture))) as $record) {
        $records[] = $record;
        if ($limit !== null && count($records) >= $limit) {
            break;
        }
    }

    return $records;
}

test('the reader resolver selects a reader for each supported format', function (ImportFormat $format, string $reader) {
    expect(app(ImportReaderResolver::class)->for(importSourceFor($format)))->toBeInstanceOf($reader);
})->with([
    [ImportFormat::Json, JsonReader::class],
    [ImportFormat::Xml, XmlReader::class],
    [ImportFormat::Csv, CsvReader::class],
    [ImportFormat::Xlsx, SpreadsheetReader::class],
]);

test('the reader resolver rejects unsupported formats', function () {
    expect(fn () => app(ImportReaderResolver::class)->for('pdf'))->toThrow('not supported');
});

test('the provisional JSON fixture supports nested record paths and repeated values', function () {
    $source = importSourceFor(ImportFormat::Json, 'jobs.*');
    $records = readRecords($source, 'orange-career/provisional-example.json');

    expect($records)->toHaveCount(2)
        ->and($records[0]->get('company.name'))->toBe('Orange Career')
        ->and($records[0]->get('detailed_location.city'))->toBe('Eindhoven')
        ->and($records[0]->get('tags.*'))->toBe(['SaaS', 'B2B']);
});

test('readers report malformed sources and missing record paths clearly', function () {
    $reader = app(ImportReaderResolver::class)->for(ImportFormat::Json);

    expect(fn () => iterator_to_array($reader->records(importSourceFor(ImportFormat::Json), SourcePayload::fromContents('{bad json}'))))->toThrow('malformed')
        ->and(fn () => iterator_to_array($reader->records(importSourceFor(ImportFormat::Json, 'missing.*'), SourcePayload::fromContents('{"jobs": []}'))))->toThrow('record path');
});

test('the XML fixtures expose repeated records and preserve nested raw values', function () {
    $vnom = readRecords(importSourceFor(ImportFormat::Xml, 'job'), 'vnom/jobs.xml', 3);
    $michaelPage = readRecords(importSourceFor(ImportFormat::Xml, 'job'), 'michael-page/jobs.xml', 3);

    expect($vnom)->toHaveCount(3)
        ->and($vnom[0]->get('identifier'))->not->toBe($vnom[0]->get('referencenumber'))
        ->and($vnom[0]->get('description'))->toContain('<p>')
        ->and($vnom[0]->get('salary'))->toBeString()
        ->and($michaelPage)->toHaveCount(3)
        ->and($michaelPage[0]->get('salary.min'))->not->toBeNull()
        ->and($michaelPage[0]->get('description.role'))->toContain('<')
        ->and($michaelPage[0]->get('location.text'))->not->toBeNull();
});

test('CSV and XLSX records expose the same table field paths and preserve empty values', function () {
    $csv = readRecords(importSourceFor(ImportFormat::Csv), 'csv/jobs.csv');
    $xlsx = readRecords(importSourceFor(ImportFormat::Xlsx), 'xlsx/jobs.xlsx');

    expect(array_keys($csv[0]->data))->toBe(array_keys($xlsx[0]->data))
        ->and($csv[0]->get('title'))->toBe($xlsx[0]->get('title'))
        ->and($csv[0]->get('salary_min'))->toBe('3500')
        ->and($xlsx[0]->get('salary_min'))->toBe(3500);
});

test('selection rules are format-neutral and support legacy Sales and Marketing inclusion', function () {
    $selector = app(RecordSelector::class);
    $vnom = readRecords(importSourceFor(ImportFormat::Xml, 'job'), 'vnom/jobs.xml', 20);
    $michaelPage = readRecords(importSourceFor(ImportFormat::Xml, 'job'), 'michael-page/jobs.xml', 20);

    $rules = fn (string $field): array => ['logic' => 'or', 'rules' => [
        ['field' => $field, 'operator' => 'contains', 'value' => 'Sales'],
        ['field' => $field, 'operator' => 'contains', 'value' => 'Marketing'],
    ]];

    expect(iterator_to_array($selector->filter($vnom, $rules('function'))))->not->toBeEmpty()
        ->and(iterator_to_array($selector->filter($michaelPage, $rules('sector.term'))))->not->toBeEmpty()
        ->and($selector->matches($vnom[0], null))->toBeTrue()
        ->and(fn () => $selector->matches($vnom[0], ['logic' => 'xor', 'rules' => []]))->toThrow('and/or');
});

test('field discovery reports nested and repeated paths with bounded samples', function () {
    $records = readRecords(importSourceFor(ImportFormat::Xml, 'job'), 'michael-page/jobs.xml', 3);
    $fields = collect(app(FieldDiscovery::class)->discover($records, 1))->keyBy('path');

    expect($fields)->toHaveKeys(['salary.min', 'description.role', 'description.bulletPoints.*', 'location.text', 'sector.term', 'industry.term'])
        ->and($fields['salary.min']->present)->toBe(3)
        ->and($fields['salary.min']->samples)->toHaveCount(1);
});

test('remote source validation rejects unsafe addresses and never includes credentials in errors', function () {
    $fetcher = app(SourceFetcher::class);

    foreach (['file:///etc/passwd', 'http://localhost/feed.xml', 'http://127.0.0.1/feed.xml', 'http://10.0.0.1/feed.xml', 'http://169.254.169.254/latest', 'http://[::1]/feed.xml', 'https://user:secret@example.com/feed.xml'] as $url) {
        try {
            $fetcher->assertSafeUrl($url);
            $this->fail("Expected [{$url}] to be rejected.");
        } catch (Throwable $exception) {
            expect($exception->getMessage())->not->toContain('secret');
        }
    }
});

test('private source loading rejects traversal and missing files', function () {
    $loader = app(LocalSourceLoader::class);

    expect(fn () => $loader->fromPrivatePath('../.env'))->toThrow('invalid')
        ->and(fn () => $loader->fromPrivatePath('imports/missing.xml'))->toThrow('missing or unreadable');
});

<?php

use App\Enums\ImportFormat;
use App\Enums\ImportTransport;
use App\Filament\Resources\ImportMappings\ImportMappingResource;
use App\Filament\Resources\ImportMappings\Pages\PreviewImportMapping;
use App\Imports\Exceptions\UnsafeRemoteSourceException;
use App\Imports\Preview\ImportPreview;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);
test('a bounded preview normalizes multiple records without persistence', function () {
    $source = ImportSource::factory()->create(['transport' => ImportTransport::Upload, 'format' => ImportFormat::Json, 'record_path' => 'jobs.*', 'selection_rules' => null, 'configuration' => ['sample_path' => base_path('tests/Fixtures/Imports/orange-career/provisional-example.json')]]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ([['source_reference', ['title']], ['vacancy.title', ['title']]] as $i => [$destination, $paths]) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => $destination, 'source_paths' => $paths, 'position' => $i]);
    }
    $preview = app(ImportPreview::class)->build($source, $mapping->fresh('fields'), 1);
    expect($preview['counts']['previewed'])->toBe(1)->and($preview['records'][0]->status())->toBe('Klaar voor import');
});

function previewSource(ImportFormat $format, string $fixture, string $path): array
{
    $source = ImportSource::factory()->create(['transport' => ImportTransport::Upload, 'format' => $format, 'record_path' => $path, 'selection_rules' => null, 'configuration' => ['sample_path' => base_path("tests/Fixtures/Imports/{$fixture}")]]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ([['source_reference', ['title']], ['vacancy.title', ['title']]] as $index => [$destination, $paths]) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => $destination, 'source_paths' => $paths, 'position' => $index]);
    }

    return [$source, $mapping->fresh('fields')];
}

test('JSON XML CSV and XLSX fixtures reach the shared preview layer', function (ImportFormat $format, string $fixture, string $path) {
    [$source, $mapping] = previewSource($format, $fixture, $path);
    $preview = app(ImportPreview::class)->build($source, $mapping, 1);
    expect($preview['counts']['previewed'])->toBe(1);
})->with([
    [ImportFormat::Json, 'orange-career/provisional-example.json', 'jobs.*'],
    [ImportFormat::Json, 'orange-career/final_sanitized_jobs_reordered.json', '*'],
    [ImportFormat::Xml, 'vnom/jobs.xml', 'job'],
    [ImportFormat::Xml, 'michael-page/jobs.xml', 'job'],
    [ImportFormat::Csv, 'csv/jobs.csv', ''],
    [ImportFormat::Xlsx, 'xlsx/jobs.xlsx', ''],
]);

test('preview does not persist operational or domain records', function () {
    [$source, $mapping] = previewSource(ImportFormat::Json, 'orange-career/provisional-example.json', 'jobs.*');
    $tables = ['vacancies', 'companies', 'categories', 'tags', 'media', 'imports', 'import_logs', 'applications'];
    $before = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()]);
    app(ImportPreview::class)->build($source, $mapping, 2);
    foreach ($before as $table => $count) {
        expect(DB::table($table)->count())->toBe($count);
    }
});

test('remote preview uses the safe fetch boundary and remains bounded', function () {
    Http::fake(['https://93.184.216.34/feed.json' => Http::response('{"jobs":[{"title":"Een"},{"title":"Twee"}]}')]);
    $source = ImportSource::factory()->create(['transport' => ImportTransport::Http, 'format' => ImportFormat::Json, 'endpoint_url' => 'https://93.184.216.34/feed.json', 'record_path' => 'jobs.*', 'selection_rules' => null]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ([['source_reference', ['title']], ['vacancy.title', ['title']]] as $index => [$destination, $paths]) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => $destination, 'source_paths' => $paths, 'position' => $index]);
    }
    expect(app(ImportPreview::class)->build($source, $mapping->fresh('fields'), 1)['counts']['previewed'])->toBe(1);
});

test('preview retains salary and rate together and only normalizes annual salary when configured', function () {
    Http::fake(['https://93.184.216.34/compensation.json' => Http::response('{"jobs":[{"id":"pay-1","title":"Consultant","annual":60000,"hourly":125,"salary_period":"year","rate_period":"hour"}]}')]);
    $source = ImportSource::factory()->create(['transport' => ImportTransport::Http, 'format' => ImportFormat::Json, 'endpoint_url' => 'https://93.184.216.34/compensation.json', 'record_path' => 'jobs.*', 'selection_rules' => null]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    $fields = [
        ['source_reference', ['id'], 'direct', []], ['vacancy.title', ['title'], 'direct', []],
        ['vacancy.salary_min', ['annual'], 'transform', ['transform' => 'annual_salary_to_monthly']], ['vacancy.salary_period', [], 'default', ['value' => 'month']],
        ['vacancy.rate_min', ['hourly'], 'direct', []], ['vacancy.rate_period', ['rate_period'], 'direct', []],
    ];
    foreach ($fields as $position => [$destination, $paths, $operation, $configuration]) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => $destination, 'source_paths' => $paths, 'operation' => $operation, 'configuration' => $configuration, 'position' => $position]);
    }

    $data = app(ImportPreview::class)->build($source, $mapping->fresh('fields'), 1)['records'][0]->result->data;
    expect($data->get('vacancy.salary_min'))->toBe(5000)
        ->and($data->get('vacancy.salary_period'))->toBe('month')
        ->and($data->get('vacancy.rate_min'))->toBe(125)
        ->and($data->get('vacancy.rate_period'))->toBe('hour');
});

test('null salary and rate values remain absent without breaking preview', function () {
    Http::fake(['https://93.184.216.34/null-compensation.json' => Http::response('{"jobs":[{"id":"pay-2","title":"Adviseur","salary":null,"rate":null}]}')]);
    $source = ImportSource::factory()->create(['transport' => ImportTransport::Http, 'format' => ImportFormat::Json, 'endpoint_url' => 'https://93.184.216.34/null-compensation.json', 'record_path' => 'jobs.*', 'selection_rules' => null]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ([['source_reference', ['id']], ['vacancy.title', ['title']], ['vacancy.salary_min', ['salary']], ['vacancy.rate_min', ['rate']]] as $position => [$destination, $paths]) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => $destination, 'source_paths' => $paths, 'position' => $position]);
    }

    $data = app(ImportPreview::class)->build($source, $mapping->fresh('fields'), 1)['records'][0]->result->data;
    expect($data->get('vacancy.salary_min'))->toBeNull()->and($data->get('vacancy.rate_min'))->toBeNull();
});

test('preview displays taxonomy and tags without attaching or creating them', function () {
    Http::fake(['https://93.184.216.34/taxonomy.json' => Http::response('{"jobs":[{"id":"tax-1","title":"Marketeer","function":"Marketing","tags":["B2B","SaaS"]}]}')]);
    $source = ImportSource::factory()->create(['transport' => ImportTransport::Http, 'format' => ImportFormat::Json, 'endpoint_url' => 'https://93.184.216.34/taxonomy.json', 'record_path' => 'jobs.*', 'selection_rules' => null]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ([['source_reference', ['id']], ['vacancy.title', ['title']], ['taxonomy.function_area', ['function']], ['tags', ['tags']]] as $position => [$destination, $paths]) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => $destination, 'source_paths' => $paths, 'position' => $position]);
    }
    $before = ['categories' => DB::table('categories')->count(), 'tags' => DB::table('tags')->count(), 'taggables' => DB::table('taggables')->count()];
    $data = app(ImportPreview::class)->build($source, $mapping->fresh('fields'), 1)['records'][0]->result->data;

    expect($data->get('taxonomy.function_area'))->toBe('Marketing')->and($data->get('tags'))->toBe(['B2B', 'SaaS']);
    foreach ($before as $table => $count) {
        expect(DB::table($table)->count())->toBe($count);
    }
});

test('unsafe remote preview targets are rejected before HTTP and never expose URL secrets', function () {
    Http::fake();
    $source = ImportSource::factory()->create(['transport' => ImportTransport::Http, 'format' => ImportFormat::Json, 'endpoint_url' => 'https://127.0.0.1/feed.json?token=secret', 'record_path' => 'jobs.*', 'selection_rules' => null]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ([['source_reference', ['id']], ['vacancy.title', ['title']]] as $position => [$destination, $paths]) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => $destination, 'source_paths' => $paths, 'position' => $position]);
    }

    expect(fn () => app(ImportPreview::class)->build($source, $mapping->fresh('fields')))->toThrow(UnsafeRemoteSourceException::class);
    Http::assertNothingSent();
});

test('invalid preview configuration renders a safe Dutch error state', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $source = ImportSource::factory()->create(['transport' => ImportTransport::Upload, 'configuration' => ['sample_path' => base_path('tests/Fixtures/Imports/missing.json')]]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ([['source_reference', ['id']], ['vacancy.title', ['title']]] as $position => [$destination, $paths]) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => $destination, 'source_paths' => $paths, 'position' => $position]);
    }

    $this->actingAs($admin)->get(ImportMappingResource::getUrl('preview', ['record' => $mapping]))
        ->assertSuccessful()
        ->assertSee('De preview kon niet veilig worden gelezen.')
        ->assertDontSee('missing.json');
});

test('preview configuration failures render the same safe Dutch error state', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Http::fake(['https://93.184.216.34/malformed.json' => Http::response('{malformed}')]);
    $cases = [
        'malformed source' => ['transport' => ImportTransport::Http, 'endpoint_url' => 'https://93.184.216.34/malformed.json', 'record_path' => 'jobs.*', 'selection_rules' => null, 'configuration' => []],
        'invalid record path' => ['transport' => ImportTransport::Upload, 'record_path' => 'missing.*', 'selection_rules' => null, 'configuration' => ['sample_path' => base_path('tests/Fixtures/Imports/orange-career/provisional-example.json')]],
        'invalid selection rules' => ['transport' => ImportTransport::Upload, 'record_path' => 'jobs.*', 'selection_rules' => ['logic' => 'xor', 'rules' => []], 'configuration' => ['sample_path' => base_path('tests/Fixtures/Imports/orange-career/provisional-example.json')]],
        'incomplete mapping' => ['transport' => ImportTransport::Upload, 'record_path' => 'jobs.*', 'selection_rules' => null, 'configuration' => ['sample_path' => base_path('tests/Fixtures/Imports/orange-career/provisional-example.json')]],
        'mapper error' => ['transport' => ImportTransport::Upload, 'record_path' => 'jobs.*', 'selection_rules' => null, 'configuration' => ['sample_path' => base_path('tests/Fixtures/Imports/orange-career/provisional-example.json')]],
    ];
    foreach ($cases as $case => $attributes) {
        $source = ImportSource::factory()->create(array_merge(['format' => ImportFormat::Json], $attributes));
        $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => 'source_reference', 'source_paths' => ['id']]);
        if ($case !== 'incomplete mapping') {
            ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => 'vacancy.title', 'source_paths' => ['title'], 'operation' => $case === 'mapper error' ? 'transform' : 'direct', 'configuration' => $case === 'mapper error' ? ['transform' => 'unknown'] : []]);
        }

        $this->actingAs($admin)->get(ImportMappingResource::getUrl('preview', ['record' => $mapping]))
            ->assertSuccessful()
            ->assertSee('De preview kon niet veilig worden gelezen.')
            ->assertDontSee('malformed')
            ->assertDontSee('Unknown import transform');
    }
});

test('preview filters only alter the rendered state and not source or domain data', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    [$source, $mapping] = previewSource(ImportFormat::Json, 'orange-career/provisional-example.json', 'jobs.*');
    $tables = ['vacancies', 'companies', 'categories', 'tags', 'media', 'imports', 'import_logs', 'applications'];
    $before = collect($tables)->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()]);

    foreach (['Alles', 'Klaar voor import', 'Waarschuwing', 'Actie vereist', 'Fout'] as $filter) {
        Livewire::actingAs($admin)->test(PreviewImportMapping::class, ['record' => $mapping->id])->set('filter', $filter)->assertSet('filter', $filter);
    }
    foreach ($before as $table => $count) {
        expect(DB::table($table)->count())->toBe($count);
    }
});

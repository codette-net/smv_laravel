<?php

use App\Enums\ApplicationMode;
use App\Enums\CategoryType;
use App\Enums\ImportFormat;
use App\Enums\ImportTransport;
use App\Filament\Resources\ImportMappings\Pages\PreviewImportMapping;
use App\Imports\Mapping\NormalizedVacancyData;
use App\Imports\Preview\ImportPreview;
use App\Imports\Validation\ImportRecordValidator;
use App\Models\Category;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\ImportTaxonomyMapping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('validator reports required fields destinations and compensation coherently', function () {
    $source = ImportSource::factory()->create();
    $outcome = app(ImportRecordValidator::class)->validate(new NormalizedVacancyData(['source_reference' => 'ref-1', 'vacancy' => ['title' => 'Adviseur', 'application_mode' => ApplicationMode::External->value, 'application_url' => 'https://example.test/apply', 'salary_min' => 4000, 'salary_max' => 3000, 'rate_min' => 100, 'rate_max' => 120, 'rate_period' => 'hour']]), $source);
    expect($outcome->status())->toBe('error')->and($outcome->errors[0]['code'])->toBe('salary_invalid');
});

test('taxonomy mappings are source and type scoped and never create categories', function () {
    $source = ImportSource::factory()->create();
    $other = ImportSource::factory()->create();
    $sales = Category::factory()->create(['name' => 'Sales', 'type' => CategoryType::function_area]);
    ImportTaxonomyMapping::create(['import_source_id' => $source->id, 'category_type' => CategoryType::function_area, 'source_key' => 'sales support', 'source_value' => 'Sales Support', 'category_id' => $sales->id]);
    $validator = app(ImportRecordValidator::class);
    $data = new NormalizedVacancyData(['source_reference' => 'a', 'vacancy' => ['title' => 'Titel'], 'taxonomy' => ['function_area' => ['Sales Support']]]);
    expect($validator->validate($data, $source)->status())->toBe('ready')->and($validator->validate($data, $source)->resolved[0]['category'])->toBe('Sales')->and($validator->validate($data, $other)->status())->toBe('needs_resolution');
});

function validated(array $values, ?ImportSource $source = null)
{
    return app(ImportRecordValidator::class)->validate(new NormalizedVacancyData($values), $source ?? ImportSource::factory()->create());
}

test('required fields application modes and optional dates have the expected domain outcomes', function () {
    $source = ImportSource::factory()->create();
    expect(validated(['source_reference' => 12, 'vacancy' => ['title' => 'Titel']], $source)->status())->toBe('ready')
        ->and(validated(['source_reference' => 'x', 'vacancy' => ['title' => '   ']], $source)->status())->toBe('error')
        ->and(validated(['vacancy' => ['title' => 'Titel']], $source)->status())->toBe('error')
        ->and(validated(['source_reference' => 'x', 'vacancy' => ['title' => 'Titel', 'application_mode' => 'external', 'application_url' => 'not-url']], $source)->status())->toBe('error')
        ->and(validated(['source_reference' => 'x', 'vacancy' => ['title' => 'Titel', 'application_mode' => 'email', 'application_email' => 'goed@example.test']], $source)->status())->toBe('ready')
        ->and(validated(['source_reference' => 'x', 'vacancy' => ['title' => 'Titel', 'application_mode' => 'internal']], $source)->status())->toBe('ready');
});

test('compensation warnings tags and validation remain non-persistent', function () {
    $source = ImportSource::factory()->create();
    $before = collect(['vacancies', 'companies', 'categories', 'tags', 'taggables', 'media', 'applications', 'imports', 'import_logs'])->mapWithKeys(fn($table) => [$table => DB::table($table)->count()]);
    $outcome = validated(['source_reference' => 'x', 'vacancy' => ['title' => 'Titel', 'salary_min' => 1000, 'salary_max' => 2000, 'salary_period' => 'month', 'rate_min' => 75, 'rate_max' => 100, 'rate_period' => 'hour'], 'tags' => ['', 'SaaS', 'saas', 'B2B']], $source);
    expect($outcome->status())->toBe('ready')->and($outcome->data->get('tags'))->toBe(['SaaS', 'B2B']);
    foreach ($before as $table => $count) {
        expect(DB::table($table)->count())->toBe($count);
    }
});

test('cross type and fuzzy taxonomy mappings are rejected or unresolved', function () {
    $source = ImportSource::factory()->create();
    $fulltime = Category::factory()->create(['name' => 'Fulltime', 'type' => CategoryType::employment_type]);
    expect(validated(['source_reference' => 'x', 'vacancy' => ['title' => 'Titel'], 'taxonomy' => ['employment_type' => ['Loondienst']]], $source)->status())->toBe('needs_resolution')
        ->and(validated(['source_reference' => 'x', 'vacancy' => ['title' => 'Titel'], 'taxonomy' => ['function_area' => ['Sales Support']]], $source)->status())->toBe('needs_resolution');
    expect(fn() => ImportTaxonomyMapping::create(['import_source_id' => $source->id, 'category_type' => CategoryType::function_area, 'source_value' => 'Loondienst', 'source_key' => 'loondienst', 'category_id' => $fulltime->id]))->toThrow(InvalidArgumentException::class);
});

function taxonomyPreviewSetup(): array
{
    $source = ImportSource::factory()->create(['transport' => ImportTransport::Http, 'format' => ImportFormat::Json, 'endpoint_url' => 'https://93.184.216.34/taxonomy-values.json', 'record_path' => 'jobs.*', 'selection_rules' => null]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ([['source_reference', ['id']], ['vacancy.title', ['title']], ['taxonomy.function_area', ['function']]] as $position => [$destination, $paths]) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => $destination, 'source_paths' => $paths, 'position' => $position]);
    }

    return [$source, $mapping->fresh('fields')];
}

test('a saved source alias refreshes only matching taxonomy values across preview records', function () {
    Http::fake(['https://93.184.216.34/taxonomy-values.json' => Http::response('{"jobs":[{"id":"1","title":"A","function":"Sales Support"},{"id":"2","title":"B","function":"Marketing"},{"id":"3","title":"C","function":"Sales Support"}]}')]);
    [$source, $mapping] = taxonomyPreviewSetup();
    $sales = Category::factory()->create(['name' => 'Sales', 'type' => CategoryType::function_area]);
    $before = app(ImportPreview::class)->build($source, $mapping, 3);
    expect($before['counts']['needs_resolution'])->toBe(3);
    ImportTaxonomyMapping::create(['import_source_id' => $source->id, 'category_type' => CategoryType::function_area, 'source_value' => 'Sales Support', 'source_key' => 'sales support', 'category_id' => $sales->id]);
    $after = app(ImportPreview::class)->build($source, $mapping, 3);
    expect($after['records'][0]->outcome->resolved[0]['category'])->toBe('Sales')
        ->and($after['records'][2]->outcome->resolved[0]['category'])->toBe('Sales')
        ->and($after['records'][1]->outcome->status())->toBe('needs_resolution')
        ->and(DB::table('vacancies')->count())->toBe(0);
});

test('an authorized user can save and update one reusable taxonomy mapping', function () {
    Http::fake([
        'https://93.184.216.34/taxonomy-values.json' => Http::response(
            '{"jobs":[{"id":"1","title":"A","function":"Sales Support"}]}'
        ),
    ]);

    [$source, $mapping] = taxonomyPreviewSetup();

    $sales = Category::factory()->create([
        'name' => 'Sales',
        'type' => CategoryType::function_area,
    ]);

    Role::firstOrCreate([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    Livewire::test(PreviewImportMapping::class, [
        'record' => $mapping->id,
    ])
        ->set('resolutionType', CategoryType::function_area->value)
        ->set('resolutionValue', 'Sales Support')
        ->set('resolutionCategoryId', $sales->id)
        ->call('saveTaxonomyMapping')
        ->assertHasNoErrors();

    expect(
        ImportTaxonomyMapping::query()
            ->where('import_source_id', $source->id)
            ->where('category_type', CategoryType::function_area->value)
            ->where('source_key', 'sales support')
            ->count()
    )->toBe(1);

    // Start a fresh Livewire lifecycle for the second save.
    Livewire::test(PreviewImportMapping::class, [
        'record' => $mapping->id,
    ])
        ->set('resolutionType', CategoryType::function_area->value)
        ->set('resolutionValue', 'Sales Support')
        ->set('resolutionCategoryId', $sales->id)
        ->call('saveTaxonomyMapping')
        ->assertHasNoErrors();

    expect(
        ImportTaxonomyMapping::query()
            ->where('import_source_id', $source->id)
            ->where('category_type', CategoryType::function_area->value)
            ->where('source_key', 'sales support')
            ->count()
    )->toBe(1);
});

test('taxonomy resolution rejects a category from another taxonomy type', function () {
    Http::fake([
        'https://93.184.216.34/taxonomy-values.json' => Http::response(
            '{"jobs":[{"id":"1","title":"A","function":"Sales Support"}]}'
        ),
    ]);

    [, $mapping] = taxonomyPreviewSetup();

    $wrong = Category::factory()->create([
        'name' => 'Hybride',
        'type' => CategoryType::workplace,
    ]);

    Role::firstOrCreate([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    Livewire::test(PreviewImportMapping::class, [
        'record' => $mapping->id,
    ])
        ->set('resolutionType', CategoryType::function_area->value)
        ->set('resolutionValue', 'Sales Support')
        ->set('resolutionCategoryId', $wrong->id)
        ->call('saveTaxonomyMapping')
        ->assertHasErrors(['resolutionCategoryId']);
});

test('taxonomy resolution follows import mapping authorization', function () {
    [, $mapping] = taxonomyPreviewSetup();

    foreach (['super-admin', 'admin', 'editor', 'employer', 'candidate'] as $roleName) {
        Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);
    }

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $employer = User::factory()->create();
    $employer->assignRole('employer');

    $candidate = User::factory()->create();
    $candidate->assignRole('candidate');

    expect($superAdmin->can('update', $mapping))->toBeTrue()
        ->and($admin->can('update', $mapping))->toBeTrue()
        ->and($editor->can('update', $mapping))->toBeTrue()
        ->and($employer->can('update', $mapping))->toBeFalse()
        ->and($candidate->can('update', $mapping))->toBeFalse();
});

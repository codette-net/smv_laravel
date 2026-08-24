<?php

use App\Enums\ImportFormat;
use App\Enums\ImportTransport;
use App\Filament\Resources\ImportSources\ImportSourceResource;
use App\Filament\Resources\ImportSources\Pages\CreateImportSource;
use App\Filament\Resources\ImportSources\Pages\EditImportSource;
use App\Imports\ImportReaderResolver;
use App\Imports\LocalSourceLoader;
use App\Models\Company;
use App\Models\ImportMapping;
use App\Models\ImportSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function uploadSourceUser(string $role): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function importFixtureUpload(string $fixture, string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, file_get_contents(base_path("tests/Fixtures/Imports/{$fixture}")));
}

test('the import source form is transport aware', function () {
    $admin = uploadSourceUser('admin');

    Livewire::actingAs($admin)->test(CreateImportSource::class)
        ->set('data.transport', ImportTransport::Upload->value)
        ->assertSchemaComponentVisible('uploaded_source_path')
        ->assertSchemaComponentHidden('endpoint_url')
        ->assertSchemaComponentHidden('credentials')
        ->assertSchemaComponentHidden('approved_for_automatic_run')
        ->set('data.transport', ImportTransport::Http->value)
        ->assertSchemaComponentHidden('uploaded_source_path')
        ->assertSchemaComponentVisible('endpoint_url')
        ->assertSchemaComponentVisible('credentials')
        ->assertSchemaComponentVisible('approved_for_automatic_run')
        ->set('data.transport', ImportTransport::Api->value)
        ->assertSchemaComponentHidden('uploaded_source_path')
        ->assertSchemaComponentVisible('endpoint_url')
        ->assertSchemaComponentVisible('credentials')
        ->assertSchemaComponentVisible('approved_for_automatic_run');
});

test('supported uploaded feed files are stored privately and enter the existing reader pipeline', function (ImportFormat $format, string $fixture, string $filename, ?string $recordPath) {
    Storage::fake('local');
    Storage::fake('public');
    $admin = uploadSourceUser('admin');

    Livewire::actingAs($admin)->test(CreateImportSource::class)
        ->fillForm([
            'name' => "Upload {$format->value}",
            'company_id' => Company::factory()->create()->id,
            'transport' => ImportTransport::Upload->value,
            'format' => $format->value,
            'uploaded_source_path' => importFixtureUpload($fixture, $filename),
            'record_path' => $recordPath,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $source = ImportSource::query()->where('name', "Upload {$format->value}")->firstOrFail();
    $path = data_get($source->configuration, 'source_path');

    expect($path)->toBeString()->toStartWith('imports/sources/')
        ->and($path)->not->toContain(base_path())
        ->and(iterator_to_array(app(ImportReaderResolver::class)->for($source)->records($source, app(LocalSourceLoader::class)->forSource($source))))->not->toBeEmpty();
    Storage::disk('local')->assertExists($path);
    Storage::disk('public')->assertMissing($path);
})->with([
    'JSON' => [ImportFormat::Json, 'orange-career/provisional-example.json', 'vacatures.json', 'jobs.*'],
    'XML' => [ImportFormat::Xml, 'vnom/jobs_for_test.xml', 'vacatures.xml', 'job'],
    'CSV' => [ImportFormat::Csv, 'csv/jobs.csv', 'vacatures.csv', null],
    'XLSX' => [ImportFormat::Xlsx, 'xlsx/jobs.xlsx', 'vacatures.xlsx', null],
]);

test('an unsupported upload type is rejected', function () {
    Storage::fake('local');
    $admin = uploadSourceUser('admin');

    Livewire::actingAs($admin)->test(CreateImportSource::class)
        ->fillForm([
            'name' => 'Ongeldige upload',
            'company_id' => Company::factory()->create()->id,
            'transport' => ImportTransport::Upload->value,
            'format' => ImportFormat::Json->value,
            'uploaded_source_path' => UploadedFile::fake()->create('feed.exe', 10, 'application/x-msdownload'),
        ])
        ->call('create')
        ->assertHasFormErrors(['uploaded_source_path']);

    expect(ImportSource::query()->where('name', 'Ongeldige upload')->exists())->toBeFalse();
});

test('editing metadata preserves the private file and mapping while deliberate replacement updates the source reference', function () {
    Storage::fake('local');
    $admin = uploadSourceUser('admin');
    $company = Company::factory()->create();
    $oldPath = Storage::disk('local')->putFile('imports/sources', importFixtureUpload('orange-career/provisional-example.json', 'oud.json'));
    $source = ImportSource::factory()->create([
        'company_id' => $company->id,
        'transport' => ImportTransport::Upload,
        'format' => ImportFormat::Json,
        'endpoint_url' => null,
        'record_path' => 'jobs.*',
        'configuration' => ['source_path' => $oldPath, 'source_name' => 'oud.json', 'delimiter' => ';'],
    ]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);

    Livewire::actingAs($admin)->test(EditImportSource::class, ['record' => $source->getRouteKey()])
        ->fillForm(['name' => 'Nieuwe bronnaam'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(data_get($source->fresh()->configuration, 'source_path'))->toBe($oldPath)
        ->and(data_get($source->fresh()->configuration, 'delimiter'))->toBe(';')
        ->and($mapping->fresh()->import_source_id)->toBe($source->id);
    Storage::disk('local')->assertExists($oldPath);

    Livewire::actingAs($admin)->test(EditImportSource::class, ['record' => $source->getRouteKey()])
        ->fillForm(['uploaded_source_path' => null])
        ->fillForm(['uploaded_source_path' => [importFixtureUpload('orange-career/final_sanitized_jobs_reordered.json', 'nieuw.json')]])
        ->call('save')
        ->assertHasNoFormErrors();

    $newPath = data_get($source->fresh()->configuration, 'source_path');
    expect($newPath)->not->toBe($oldPath)
        ->and($mapping->fresh()->import_source_id)->toBe($source->id);
    Storage::disk('local')->assertExists($newPath);
});

test('credentials remain redacted and import source authorization is unchanged', function () {
    $source = ImportSource::factory()->create(['credentials' => ['token' => 'upload-ui-secret']]);

    foreach (['admin', 'editor'] as $role) {
        $user = uploadSourceUser($role);
        $this->actingAs($user)->get(ImportSourceResource::getUrl('create'))->assertSuccessful()->assertDontSee('upload-ui-secret');
        $this->actingAs($user)->get(ImportSourceResource::getUrl('edit', ['record' => $source]))->assertSuccessful()->assertDontSee('upload-ui-secret');
    }

    foreach (['employer', 'candidate'] as $role) {
        $user = uploadSourceUser($role);
        $this->actingAs($user)->get(ImportSourceResource::getUrl('create'))->assertForbidden();
        $this->actingAs($user)->get(ImportSourceResource::getUrl('edit', ['record' => $source]))->assertForbidden();
    }
});

test('remote approval remains administrator only and is not applicable to uploads', function () {
    $admin = uploadSourceUser('admin');
    $editor = uploadSourceUser('editor');

    Livewire::actingAs($admin)->test(CreateImportSource::class)
        ->set('data.transport', ImportTransport::Http->value)
        ->assertSchemaComponentVisible('approved_for_automatic_run')
        ->set('data.transport', ImportTransport::Upload->value)
        ->assertSchemaComponentHidden('approved_for_automatic_run');

    Livewire::actingAs($editor)->test(CreateImportSource::class)
        ->set('data.transport', ImportTransport::Http->value)
        ->assertSchemaComponentHidden('approved_for_automatic_run');
});

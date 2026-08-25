<?php

use App\Enums\ImportFormat;
use App\Enums\ImportStatus;
use App\Enums\ImportTransport;
use App\Enums\ImportType;
use App\Filament\Resources\ImportSources\ImportSourceResource;
use App\Models\Company;
use App\Models\Import;
use App\Models\ImportSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function importSourceUser(string $role): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('an import source belongs to its owning company and retains import runs', function () {
    $source = ImportSource::factory()->create();
    $run = Import::create(['import_source_id' => $source->id, 'type' => ImportType::xml, 'status' => ImportStatus::Pending]);

    expect($source->company)->toBeInstanceOf(Company::class)
        ->and($source->company->importSources->sole()->is($source))->toBeTrue()
        ->and($source->imports->sole()->is($run))->toBeTrue();
});

test('import source casts source configuration and encrypts credentials', function () {
    $source = ImportSource::factory()->create([
        'transport' => ImportTransport::Api,
        'format' => ImportFormat::Json,
        'credentials' => ['token' => 'top-secret-token'],
        'configuration' => ['headers' => ['Accept' => 'application/json']],
        'selection_rules' => [['path' => 'sector.term', 'operator' => 'contains', 'values' => ['Sales']]],
    ]);

    expect($source->transport)->toBe(ImportTransport::Api)
        ->and($source->format)->toBe(ImportFormat::Json)
        ->and($source->configuration['headers']['Accept'])->toBe('application/json')
        ->and($source->selection_rules[0]['path'])->toBe('sector.term');
    expect((string) DB::table('import_sources')->whereKey($source->id)->value('credentials'))->not->toContain('top-secret-token');
});

test('only active approved sources are eligible for automatic runs', function () {
    $approver = importSourceUser('admin');
    $eligible = ImportSource::factory()->create();
    $eligible->approve($approver);
    $inactive = ImportSource::factory()->create(['is_active' => false]);
    $inactive->approve($approver);
    $unapproved = ImportSource::factory()->create();

    expect($eligible->fresh()->isApprovedForAutomaticRun())->toBeTrue()
        ->and($inactive->fresh()->isApprovedForAutomaticRun())->toBeFalse()
        ->and($unapproved->isApprovedForAutomaticRun())->toBeFalse()
        ->and(ImportSource::query()->approvedForAutomaticRun()->pluck('id')->all())->toBe([$eligible->id]);
});

test('remote sources reject unsafe schemes and approval requires HTTPS', function () {
    expect(fn () => ImportSource::factory()->create(['transport' => ImportTransport::Http, 'endpoint_url' => 'file:///etc/passwd']))->toThrow(InvalidArgumentException::class);

    $source = ImportSource::factory()->create(['transport' => ImportTransport::Http, 'endpoint_url' => 'http://example.test/feed.xml']);
    expect(fn () => $source->approve(importSourceUser('admin')))->toThrow(InvalidArgumentException::class);
});

test('import source access permits editors to manage metadata but only administrators can approve', function () {
    $source = ImportSource::factory()->create(['credentials' => ['token' => 'not-for-display']]);
    $admin = importSourceUser('admin');
    $editor = importSourceUser('editor');
    $employer = importSourceUser('employer');
    $candidate = importSourceUser('candidate');

    $this->actingAs($admin)->get(ImportSourceResource::getUrl())->assertSuccessful();
    $this->actingAs($admin)->get(ImportSourceResource::getUrl('view', ['record' => $source]))->assertSuccessful()->assertDontSee('not-for-display');
    $this->actingAs($editor)->get(ImportSourceResource::getUrl())->assertSuccessful();
    $this->actingAs($employer)->get(ImportSourceResource::getUrl())->assertForbidden();
    $this->actingAs($candidate)->get(ImportSourceResource::getUrl())->assertForbidden();

    expect($admin->can('approve', $source))->toBeTrue()
        ->and($editor->can('approve', $source))->toBeFalse();
});

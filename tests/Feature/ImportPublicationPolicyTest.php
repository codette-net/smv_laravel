<?php

use App\Enums\ApplicationMode;
use App\Enums\CompanyStatus;
use App\Enums\ImportFormat;
use App\Enums\ImportMissingVacancyPolicy;
use App\Enums\ImportPublicationPolicy;
use App\Enums\ImportStatus;
use App\Enums\ImportTransport;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Filament\Resources\ImportSources\ImportSourceResource;
use App\Filament\Resources\Vacancies\VacancyResource;
use App\Imports\VacancyImportRunner;
use App\Models\Company;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(function (): void {
    foreach (glob(storage_path('framework/publication-policy-*')) ?: [] as $path) {
        unlink($path);
    }
});

function publicationPolicyMapping(array $jobs, array $sourceAttributes = []): array
{
    $path = storage_path('framework/publication-policy-'.uniqid().'.json');
    file_put_contents($path, json_encode(['jobs' => $jobs], JSON_THROW_ON_ERROR));
    $configuration = $sourceAttributes['configuration'] ?? ['sample_path' => $path];
    $configuration['sample_path'] = $path;
    $sourceAttributes['configuration'] = $configuration;

    $source = ImportSource::factory()->create(array_merge([
        'transport' => ImportTransport::Upload,
        'format' => ImportFormat::Json,
        'record_path' => 'jobs',
        'selection_rules' => null,
    ], $sourceAttributes));
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ([
        ['source_reference', ['id'], 'direct', []],
        ['vacancy.title', ['title'], 'direct', []],
        ['vacancy.application_mode', [], 'default', ['value' => ApplicationMode::Internal->value]],
    ] as $position => [$destination, $paths, $operation, $fieldConfiguration]) {
        ImportMappingField::factory()->create([
            'import_mapping_id' => $mapping->id,
            'destination_key' => $destination,
            'source_paths' => $paths,
            'operation' => $operation,
            'configuration' => $fieldConfiguration,
            'position' => $position,
        ]);
    }

    return [$source->fresh(), $mapping->fresh('fields'), $path];
}

function replacePublicationPolicyFeed(string $path, array $jobs): void
{
    file_put_contents($path, json_encode(['jobs' => $jobs], JSON_THROW_ON_ERROR));
}

function publiclyAvailableImportedVacancies(): Builder
{
    return Vacancy::query()
        ->publiclyVisible()
        ->whereHas('company', fn (Builder $query): Builder => $query->publiclyVisible());
}

function publicationPolicyAdmin(): User
{
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

test('new import sources default to conservative publication and missing policies', function () {
    $source = ImportSource::factory()->create()->fresh();

    expect($source->publication_policy)->toBe(ImportPublicationPolicy::ReviewRequired)
        ->and($source->missing_vacancy_policy)->toBe(ImportMissingVacancyPolicy::ReviewRequired)
        ->and($source->publishesNewVacanciesAutomatically())->toBeFalse()
        ->and($source->takesMissingVacanciesOffline())->toBeFalse();
});

test('review required imports remain pending while auto publish imports use the existing public lifecycle', function () {
    [, $reviewMapping] = publicationPolicyMapping([['id' => 'review', 'title' => 'Eerst beoordelen']]);
    [, $autoMapping] = publicationPolicyMapping([['id' => 'auto', 'title' => 'Direct publiceren']], [
        'publication_policy' => ImportPublicationPolicy::AutoPublish,
    ]);

    app(VacancyImportRunner::class)->run($reviewMapping);
    app(VacancyImportRunner::class)->run($autoMapping);

    $review = Vacancy::where('source_reference', 'review')->sole();
    $automatic = Vacancy::where('source_reference', 'auto')->sole();
    expect($review->status)->toBe(VacancyStatus::Pending)
        ->and($review->published_at)->toBeNull()
        ->and(publiclyAvailableImportedVacancies()->whereKey($review)->exists())->toBeFalse()
        ->and($automatic->status)->toBe(VacancyStatus::Active)
        ->and($automatic->published_at)->not->toBeNull()
        ->and(publiclyAvailableImportedVacancies()->whereKey($automatic)->exists())->toBeTrue();
});

test('an auto publish source cannot make a vacancy for a private company publicly visible', function () {
    $company = Company::factory()->create(['status' => CompanyStatus::Pending]);
    [, $mapping] = publicationPolicyMapping([['id' => 'private', 'title' => 'Privé bedrijf']], [
        'company_id' => $company->id,
        'publication_policy' => ImportPublicationPolicy::AutoPublish,
    ]);

    app(VacancyImportRunner::class)->run($mapping);

    $vacancy = Vacancy::sole();
    expect($vacancy->status)->toBe(VacancyStatus::Active)
        ->and(publiclyAvailableImportedVacancies()->whereKey($vacancy)->exists())->toBeFalse();
});

test('auto publish falls back to review when no valid application destination is mapped', function () {
    [, $mapping] = publicationPolicyMapping([['id' => 'no-application', 'title' => 'Geen sollicitatiebestemming']], [
        'publication_policy' => ImportPublicationPolicy::AutoPublish,
    ]);
    $mapping->fields()->where('destination_key', 'vacancy.application_mode')->delete();

    app(VacancyImportRunner::class)->run($mapping->fresh('fields'));

    $vacancy = Vacancy::sole();
    expect($vacancy->status)->toBe(VacancyStatus::Pending)
        ->and($vacancy->published_at)->toBeNull()
        ->and(publiclyAvailableImportedVacancies()->whereKey($vacancy)->exists())->toBeFalse();
});

test('feed owned mapped fields refresh without resetting unrelated editorial and operational fields', function () {
    [, $mapping, $path] = publicationPolicyMapping([['id' => 'owned', 'title' => 'Titel uit feed']]);
    app(VacancyImportRunner::class)->run($mapping);
    $vacancy = Vacancy::sole();
    $vacancy->update([
        'status' => VacancyStatus::Archived,
        'is_featured' => true,
        'is_filled' => true,
        'reference' => 'SMV-REDACTIE-1',
    ]);
    replacePublicationPolicyFeed($path, [['id' => 'owned', 'title' => 'Vernieuwde feedtitel']]);

    $run = app(VacancyImportRunner::class)->run($mapping);

    expect($run->updated_rows)->toBe(1)
        ->and($vacancy->fresh()->title)->toBe('Vernieuwde feedtitel')
        ->and($vacancy->fresh()->status)->toBe(VacancyStatus::Archived)
        ->and($vacancy->fresh()->is_featured)->toBeTrue()
        ->and($vacancy->fresh()->is_filled)->toBeTrue()
        ->and($vacancy->fresh()->reference)->toBe('SMV-REDACTIE-1');
});

test('verified missing policies are source scoped and never affect manual vacancies', function () {
    [, $reviewMapping, $reviewPath] = publicationPolicyMapping([
        ['id' => 'review-a', 'title' => 'Review A'],
        ['id' => 'review-b', 'title' => 'Review B'],
    ], [
        'publication_policy' => ImportPublicationPolicy::AutoPublish,
        'missing_vacancy_policy' => ImportMissingVacancyPolicy::ReviewRequired,
        'configuration' => ['snapshot_reconciliation' => ['enabled' => true]],
    ]);
    [$offlineSource, $offlineMapping, $offlinePath] = publicationPolicyMapping([
        ['id' => 'offline-a', 'title' => 'Offline A'],
        ['id' => 'offline-b', 'title' => 'Offline B'],
    ], [
        'publication_policy' => ImportPublicationPolicy::AutoPublish,
        'missing_vacancy_policy' => ImportMissingVacancyPolicy::AutoOffline,
        'configuration' => ['snapshot_reconciliation' => ['enabled' => true]],
    ]);
    app(VacancyImportRunner::class)->run($reviewMapping);
    app(VacancyImportRunner::class)->run($offlineMapping);
    $manual = Vacancy::factory()->for(Company::factory())->create([
        'import_source_id' => null,
        'source' => VacancySource::Manual,
        'status' => VacancyStatus::Active,
        'is_filled' => false,
    ]);
    $historicalClick = Vacancy::where('source_reference', 'offline-b')->sole()->outboundApplyClicks()->create();
    replacePublicationPolicyFeed($reviewPath, [['id' => 'review-a', 'title' => 'Review A']]);
    replacePublicationPolicyFeed($offlinePath, [['id' => 'offline-a', 'title' => 'Offline A']]);

    app(VacancyImportRunner::class)->run($reviewMapping);
    $offlineRun = app(VacancyImportRunner::class)->run($offlineMapping);
    $reviewMissing = Vacancy::where('source_reference', 'review-b')->sole();
    $offlineMissing = Vacancy::where('source_reference', 'offline-b')->sole();

    expect($reviewMissing->missing_since)->not->toBeNull()
        ->and($reviewMissing->status)->toBe(VacancyStatus::Active)
        ->and($reviewMissing->source_offlined_at)->toBeNull()
        ->and($offlineRun->status)->toBe(ImportStatus::Completed)
        ->and(data_get($offlineRun->mapping, 'policies.publication'))->toBe(ImportPublicationPolicy::AutoPublish->value)
        ->and(data_get($offlineRun->mapping, 'policies.missing_vacancy'))->toBe(ImportMissingVacancyPolicy::AutoOffline->value)
        ->and($offlineMissing->missing_since)->not->toBeNull()
        ->and($offlineMissing->status)->toBe(VacancyStatus::Archived)
        ->and($offlineMissing->source_offlined_at)->not->toBeNull()
        ->and($offlineMissing->import_source_id)->toBe($offlineSource->id)
        ->and($offlineMissing->outboundApplyClicks()->whereKey($historicalClick)->exists())->toBeTrue()
        ->and($manual->fresh()->status)->toBe(VacancyStatus::Active)
        ->and($manual->fresh()->missing_since)->toBeNull()
        ->and(Vacancy::count())->toBe(5);
});

test('unsafe snapshots never invoke automatic offline behavior', function () {
    [, $mapping, $path] = publicationPolicyMapping([
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'B', 'title' => 'Vacature B'],
    ], [
        'publication_policy' => ImportPublicationPolicy::AutoPublish,
        'missing_vacancy_policy' => ImportMissingVacancyPolicy::AutoOffline,
        'configuration' => ['snapshot_reconciliation' => ['enabled' => true]],
    ]);
    app(VacancyImportRunner::class)->run($mapping);
    replacePublicationPolicyFeed($path, [
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'C', 'title' => ''],
    ]);

    $run = app(VacancyImportRunner::class)->run($mapping);
    $protected = Vacancy::where('source_reference', 'B')->sole();

    expect($run->status)->toBe(ImportStatus::Incomplete)
        ->and($protected->missing_since)->toBeNull()
        ->and($protected->source_offlined_at)->toBeNull()
        ->and($protected->status)->toBe(VacancyStatus::Active);
});

test('reappearance restores only a source-offlined eligible vacancy and preserves manual suppression', function () {
    [, $mapping, $path] = publicationPolicyMapping([
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'B', 'title' => 'Handmatig gearchiveerd'],
        ['id' => 'C', 'title' => 'Bron offline'],
    ], [
        'publication_policy' => ImportPublicationPolicy::AutoPublish,
        'missing_vacancy_policy' => ImportMissingVacancyPolicy::AutoOffline,
        'configuration' => ['snapshot_reconciliation' => ['enabled' => true]],
    ]);
    app(VacancyImportRunner::class)->run($mapping);
    $manualSuppression = Vacancy::where('source_reference', 'B')->sole();
    $manualSuppression->update(['status' => VacancyStatus::Archived]);
    $sourceManaged = Vacancy::where('source_reference', 'C')->sole();
    $sourceManagedId = $sourceManaged->id;
    replacePublicationPolicyFeed($path, [['id' => 'A', 'title' => 'Vacature A']]);
    app(VacancyImportRunner::class)->run($mapping);

    expect($manualSuppression->fresh()->source_offlined_at)->toBeNull()
        ->and($sourceManaged->fresh()->source_offlined_at)->not->toBeNull();

    replacePublicationPolicyFeed($path, [
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'B', 'title' => 'Handmatig gearchiveerd terug'],
        ['id' => 'C', 'title' => 'Bron terug'],
    ]);
    $restored = app(VacancyImportRunner::class)->run($mapping);

    expect($restored->restored_rows)->toBe(2)
        ->and($manualSuppression->fresh()->missing_since)->toBeNull()
        ->and($manualSuppression->fresh()->status)->toBe(VacancyStatus::Archived)
        ->and($sourceManaged->fresh()->missing_since)->toBeNull()
        ->and($sourceManaged->fresh()->source_offlined_at)->toBeNull()
        ->and($sourceManaged->fresh()->status)->toBe(VacancyStatus::Active)
        ->and($sourceManaged->fresh()->id)->toBe($sourceManagedId)
        ->and(Vacancy::where('source_reference', 'C')->count())->toBe(1);
});

test('reappearance does not republish a source-offlined vacancy whose lifecycle has expired', function () {
    [, $mapping, $path] = publicationPolicyMapping([
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'B', 'title' => 'Vacature B'],
    ], [
        'publication_policy' => ImportPublicationPolicy::AutoPublish,
        'missing_vacancy_policy' => ImportMissingVacancyPolicy::AutoOffline,
        'configuration' => ['snapshot_reconciliation' => ['enabled' => true]],
    ]);
    app(VacancyImportRunner::class)->run($mapping);
    replacePublicationPolicyFeed($path, [['id' => 'A', 'title' => 'Vacature A']]);
    app(VacancyImportRunner::class)->run($mapping);
    $expired = Vacancy::where('source_reference', 'B')->sole();
    $expired->update(['expires_at' => now()->subMinute()]);
    replacePublicationPolicyFeed($path, [
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'B', 'title' => 'Vacature B terug'],
    ]);

    app(VacancyImportRunner::class)->run($mapping);

    expect($expired->fresh()->missing_since)->toBeNull()
        ->and($expired->fresh()->source_offlined_at)->toBeNull()
        ->and($expired->fresh()->status)->toBe(VacancyStatus::Archived)
        ->and(publiclyAvailableImportedVacancies()->whereKey($expired)->exists())->toBeFalse();
});

test('Filament explains per-source policies and feed ownership in Dutch', function () {
    $admin = publicationPolicyAdmin();
    $source = ImportSource::factory()->create();
    $vacancy = Vacancy::factory()->for($source->company)->create([
        'import_source_id' => $source->id,
        'source' => VacancySource::Import,
    ]);

    $this->actingAs($admin)
        ->get(ImportSourceResource::getUrl('edit', ['record' => $source]))
        ->assertSuccessful()
        ->assertSee('Nieuwe geïmporteerde vacatures')
        ->assertSee('Ontbrekende advertenties')
        ->assertSee('volledig geverifieerde snapshot');
    $this->actingAs($admin)
        ->get(VacancyResource::getUrl('edit', ['record' => $vacancy]))
        ->assertSuccessful()
        ->assertSee('wordt beheerd door een externe feed')
        ->assertSee('kunnen bij een volgende import worden overschreven');
});

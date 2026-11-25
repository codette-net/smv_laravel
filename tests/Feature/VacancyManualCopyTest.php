<?php

use App\Actions\Vacancies\CreateManualVacancyCopy;
use App\Enums\ApplicationMode;
use App\Enums\CategoryType;
use App\Enums\CompensationPeriod;
use App\Enums\ImportFormat;
use App\Enums\ImportMissingVacancyPolicy;
use App\Enums\ImportPublicationPolicy;
use App\Enums\ImportTransport;
use App\Enums\SalaryBasis;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Filament\Resources\Vacancies\Pages\EditVacancy;
use App\Filament\Resources\Vacancies\VacancyResource;
use App\Imports\VacancyImportRunner;
use App\Models\Category;
use App\Models\Company;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    collect(['super-admin', 'admin', 'editor', 'employer', 'candidate'])
        ->each(fn (string $role) => Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
});

afterEach(function (): void {
    foreach (glob(storage_path('framework/manual-copy-*')) ?: [] as $path) {
        unlink($path);
    }
});

function manualCopyUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function importedVacancyForManualCopy(array $attributes = []): Vacancy
{
    $source = ImportSource::factory()->create();

    return Vacancy::factory()->for($source->company)->create([
        'import_source_id' => $source->id,
        'source_reference' => 'advertisement-123',
        'source_requisition_id' => 'requisition-456',
        'source' => VacancySource::Import,
        ...$attributes,
    ]);
}

test('the manual-copy action is available only for imported vacancies', function (): void {
    $administrator = manualCopyUser('admin');
    $imported = importedVacancyForManualCopy();
    $manual = Vacancy::factory()->for(Company::factory())->create([
        'import_source_id' => null,
        'source_reference' => null,
        'source' => VacancySource::Manual,
    ]);

    Livewire::actingAs($administrator)
        ->test(EditVacancy::class, ['record' => $imported->getRouteKey()])
        ->assertActionVisible('makeManualCopy');

    Livewire::actingAs($administrator)
        ->test(EditVacancy::class, ['record' => $manual->getRouteKey()])
        ->assertActionHidden('makeManualCopy');
});

test('only vacancy staff can duplicate an imported vacancy', function (): void {
    $imported = importedVacancyForManualCopy();

    expect(manualCopyUser('super-admin')->can('duplicate', $imported))->toBeTrue()
        ->and(manualCopyUser('admin')->can('duplicate', $imported))->toBeTrue()
        ->and(manualCopyUser('editor')->can('duplicate', $imported))->toBeTrue()
        ->and(manualCopyUser('employer')->can('duplicate', $imported))->toBeFalse()
        ->and(manualCopyUser('candidate')->can('duplicate', $imported))->toBeFalse();
});

test('the action creates an independent draft with deliberate content and relation copies', function (): void {
    $administrator = manualCopyUser('admin');
    $category = Category::create([
        'name' => 'Sales',
        'slug' => 'sales',
        'type' => CategoryType::function_area,
    ]);
    $imported = importedVacancyForManualCopy([
        'title' => 'Senior accountmanager',
        'description' => '<h2>De functie</h2><p>Een zelfstandige commerciële rol.</p>',
        'location' => 'Antwerpen',
        'advertised_state' => 'Antwerpen',
        'advertised_country' => 'BE',
        'advertised_postal_code' => '2000',
        'workplace_city' => 'Mechelen',
        'workplace_country' => 'BE',
        'salary_min' => 4000,
        'salary_max' => 5000,
        'salary_currency' => 'EUR',
        'salary_period' => CompensationPeriod::Month,
        'salary_basis' => SalaryBasis::GrossFullTimeEquivalent,
        'rate_min' => 90,
        'rate_max' => 110,
        'rate_currency' => 'EUR',
        'rate_period' => CompensationPeriod::Hour,
        'application_mode' => ApplicationMode::External,
        'application_url' => 'https://jobs.example.test/apply?utm_source=smv&utm_campaign=partner',
        'reference' => 'provider-reference',
        'source_salary_text' => '€4.000 - €5.000 bruto per maand',
        'source_language' => 'nl-BE',
        'source_product_code' => 'SUPERIOR',
        'last_seen_at' => now()->subHour(),
        'last_seen_import_id' => null,
        'missing_since' => now()->subDay(),
        'source_offlined_at' => now()->subHour(),
        'published_at' => now()->subWeek(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
        'is_featured' => true,
        'commercial_priority_adjustment' => 25,
        'is_filled' => true,
        'status' => VacancyStatus::Archived,
    ]);
    $imported->categories()->attach($category);
    $imported->syncTags(['B2B', 'SaaS']);
    $original = $imported->fresh()->getAttributes();

    $component = Livewire::actingAs($administrator)
        ->test(EditVacancy::class, ['record' => $imported->getRouteKey()])
        ->callAction('makeManualCopy');

    $copy = Vacancy::query()->whereKeyNot($imported->id)->sole();
    $component->assertRedirect(VacancyResource::getUrl('edit', ['record' => $copy]));

    expect($copy->id)->not->toBe($imported->id)
        ->and($copy->slug)->not->toBe($imported->slug)
        ->and($copy->company_id)->toBe($imported->company_id)
        ->and($copy->title)->toBe($imported->title)
        ->and($copy->description)->toBe($imported->description)
        ->and($copy->location)->toBe('Antwerpen')
        ->and($copy->workplace_city)->toBe('Mechelen')
        ->and($copy->salary_min)->toBe(4000)
        ->and($copy->salary_basis)->toBe(SalaryBasis::GrossFullTimeEquivalent)
        ->and($copy->rate_max)->toBe(110)
        ->and($copy->application_mode)->toBe(ApplicationMode::External)
        ->and($copy->application_url)->toBe($imported->application_url)
        ->and($copy->categories->modelKeys())->toBe([$category->id])
        ->and($copy->tags->pluck('name')->sort()->values()->all())->toBe(['B2B', 'SaaS'])
        ->and($copy->import_source_id)->toBeNull()
        ->and($copy->reference)->toBeNull()
        ->and($copy->source_reference)->toBeNull()
        ->and($copy->source_requisition_id)->toBeNull()
        ->and($copy->source_salary_text)->toBeNull()
        ->and($copy->source_language)->toBeNull()
        ->and($copy->source_product_code)->toBeNull()
        ->and($copy->last_seen_at)->toBeNull()
        ->and($copy->last_seen_import_id)->toBeNull()
        ->and($copy->missing_since)->toBeNull()
        ->and($copy->source_offlined_at)->toBeNull()
        ->and($copy->published_at)->toBeNull()
        ->and($copy->deadline_at)->toBeNull()
        ->and($copy->expires_at)->toBeNull()
        ->and($copy->is_featured)->toBeFalse()
        ->and($copy->commercial_priority_adjustment)->toBe(0)
        ->and($copy->is_filled)->toBeFalse()
        ->and($copy->status)->toBe(VacancyStatus::Draft)
        ->and($copy->source)->toBe(VacancySource::Manual)
        ->and($imported->fresh()->getAttributes())->toBe($original);
});

test('unsafe imported application destinations fall back to internal review', function (): void {
    $imported = importedVacancyForManualCopy([
        'application_mode' => ApplicationMode::External,
        'application_url' => 'https://user:secret@example.test/apply',
        'application_email' => 'ignored@example.test',
    ]);

    $copy = app(CreateManualVacancyCopy::class)->handle($imported);

    expect($copy->application_mode)->toBe(ApplicationMode::Internal)
        ->and($copy->application_url)->toBeNull()
        ->and($copy->application_email)->toBeNull();
});

test('later feed updates and missing reconciliation affect the original but not its manual copy', function (): void {
    $path = storage_path('framework/manual-copy-'.uniqid().'.json');
    file_put_contents($path, json_encode(['jobs' => [['id' => 'A', 'title' => 'Originele titel']]], JSON_THROW_ON_ERROR));
    $source = ImportSource::factory()->create([
        'transport' => ImportTransport::Upload,
        'format' => ImportFormat::Json,
        'record_path' => 'jobs',
        'selection_rules' => null,
        'publication_policy' => ImportPublicationPolicy::AutoPublish,
        'missing_vacancy_policy' => ImportMissingVacancyPolicy::AutoOffline,
        'configuration' => [
            'sample_path' => $path,
            'snapshot_reconciliation' => ['enabled' => true, 'allow_empty' => true],
        ],
    ]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    ImportMappingField::factory()->create([
        'import_mapping_id' => $mapping->id,
        'destination_key' => 'source_reference',
        'source_paths' => ['id'],
        'position' => 0,
    ]);
    ImportMappingField::factory()->create([
        'import_mapping_id' => $mapping->id,
        'destination_key' => 'vacancy.title',
        'source_paths' => ['title'],
        'position' => 1,
    ]);
    ImportMappingField::factory()->create([
        'import_mapping_id' => $mapping->id,
        'destination_key' => 'vacancy.application_mode',
        'operation' => 'default',
        'source_paths' => [],
        'configuration' => ['value' => ApplicationMode::Internal->value],
        'position' => 2,
    ]);

    app(VacancyImportRunner::class)->run($mapping->fresh('fields'));
    $original = Vacancy::query()->where('source_reference', 'A')->sole();
    $copy = app(CreateManualVacancyCopy::class)->handle($original);
    file_put_contents($path, json_encode(['jobs' => [['id' => 'A', 'title' => 'Titel uit volgende feed']]], JSON_THROW_ON_ERROR));

    app(VacancyImportRunner::class)->run($mapping->fresh('fields'));

    expect($original->fresh()->title)->toBe('Titel uit volgende feed')
        ->and($copy->fresh()->title)->toBe('Originele titel')
        ->and($copy->missing_since)->toBeNull()
        ->and($copy->source_offlined_at)->toBeNull()
        ->and($copy->status)->toBe(VacancyStatus::Draft);

    file_put_contents($path, json_encode(['jobs' => []], JSON_THROW_ON_ERROR));
    app(VacancyImportRunner::class)->run($mapping->fresh('fields'));
    $original->refresh();
    $copy->refresh();

    expect($original->missing_since)->not->toBeNull()
        ->and($original->status)->toBe(VacancyStatus::Archived)
        ->and($original->source_offlined_at)->not->toBeNull()
        ->and($copy->missing_since)->toBeNull()
        ->and($copy->source_offlined_at)->toBeNull()
        ->and($copy->status)->toBe(VacancyStatus::Draft);

    Livewire::actingAs(manualCopyUser('admin'))
        ->test(EditVacancy::class, ['record' => $copy->getRouteKey()])
        ->fillForm([
            'description' => 'Na redactionele controle heeft deze handmatige vacature voldoende inhoud om zelfstandig te publiceren.',
            'status' => VacancyStatus::Active->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $copy->refresh();
    expect($copy->status)->toBe(VacancyStatus::Active)
        ->and($copy->published_at)->not->toBeNull();
});

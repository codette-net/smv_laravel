<?php

use App\Enums\ImportLogLevel;
use App\Enums\ImportStatus;
use App\Filament\Resources\ImportMappings\ImportMappingResource;
use App\Filament\Resources\ImportMappings\Pages\EditImportMapping;
use App\Filament\Resources\Imports\ImportResource;
use App\Filament\Resources\ImportSources\ImportSourceResource;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\ImportMapping;
use App\Models\ImportSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function importHistoryUser(string $role): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function recordedImportRun(): Import
{
    $source = ImportSource::factory()->create(['name' => 'Veilige bron']);
    $source->company->update(['name' => 'Historisch bedrijf']);
    $run = Import::create([
        'import_source_id' => $source->id,
        'status' => ImportStatus::Completed,
        'total_rows' => 5,
        'imported_rows' => 2,
        'updated_rows' => 1,
        'skipped_rows' => 1,
        'failed_rows' => 1,
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
    ]);
    ImportLog::create([
        'import_id' => $run->id,
        'level' => ImportLogLevel::Warning,
        'message' => 'Record veilig overgeslagen.',
        'context' => ['position' => 3, 'source_reference' => 'safe-3', 'code' => 'validation_or_resolution', 'authorization' => 'Bearer history-secret', 'raw_payload' => ['private' => true], 'trace' => 'internal stack'],
    ]);

    return $run->fresh(['importSource.company', 'importLogs']);
}

test('execution authorization is enforced by policy and the Filament action', function () {
    $mapping = ImportMapping::factory()->create();
    $users = collect(['super-admin', 'admin', 'editor', 'employer', 'candidate'])
        ->mapWithKeys(fn (string $role): array => [$role => importHistoryUser($role)]);

    expect(Gate::forUser($users['super-admin'])->allows('execute', $mapping))->toBeTrue()
        ->and(Gate::forUser($users['admin'])->allows('execute', $mapping))->toBeTrue()
        ->and(Gate::forUser($users['editor'])->allows('execute', $mapping))->toBeFalse()
        ->and(Gate::forUser($users['employer'])->allows('execute', $mapping))->toBeFalse()
        ->and(Gate::forUser($users['candidate'])->allows('execute', $mapping))->toBeFalse();

    foreach (['super-admin', 'admin'] as $role) {
        Livewire::actingAs($users[$role])->test(EditImportMapping::class, ['record' => $mapping->id])->assertActionVisible('execute');
    }

    Livewire::actingAs($users['editor'])->test(EditImportMapping::class, ['record' => $mapping->id])->assertActionHidden('execute');
});

test('only administrators can list and view safe read-only import history', function () {
    $run = recordedImportRun();
    $users = collect(['super-admin', 'admin', 'editor', 'employer', 'candidate'])
        ->mapWithKeys(fn (string $role): array => [$role => importHistoryUser($role)]);

    foreach (['super-admin', 'admin'] as $role) {
        $this->actingAs($users[$role])->get(ImportResource::getUrl())
            ->assertSuccessful()
            ->assertSee('Veilige bron')
            ->assertSee('Historisch bedrijf')
            ->assertSee('Overgeslagen')
            ->assertSee('Mislukt');

        $this->actingAs($users[$role])->get(ImportResource::getUrl('view', ['record' => $run]))
            ->assertSuccessful()
            ->assertSee('Record veilig overgeslagen.')
            ->assertSee('safe-3')
            ->assertSee('validation_or_resolution')
            ->assertDontSee('Bearer')
            ->assertDontSee('authorization')
            ->assertDontSee('history-secret')
            ->assertDontSee('raw_payload')
            ->assertDontSee('internal stack');
    }

    foreach (['editor', 'employer', 'candidate'] as $role) {
        $this->actingAs($users[$role])->get(ImportResource::getUrl())->assertForbidden();
        $this->actingAs($users[$role])->get(ImportResource::getUrl('view', ['record' => $run]))->assertForbidden();
    }

    $this->actingAs($users['admin']);
    expect(ImportResource::canCreate())->toBeFalse()
        ->and(ImportResource::canEdit($run))->toBeFalse()
        ->and(ImportResource::canDelete($run))->toBeFalse()
        ->and(array_keys(ImportResource::getPages()))->toBe(['index', 'view']);
});

test('import navigation has one ordered group with sources mappings and history', function () {
    expect(ImportSourceResource::getNavigationGroup())->toBe('Imports')
        ->and(ImportSourceResource::getNavigationSort())->toBe(1)
        ->and(ImportSourceResource::getNavigationLabel())->toBe('Importbronnen')
        ->and(ImportMappingResource::getNavigationGroup())->toBe('Imports')
        ->and(ImportMappingResource::getNavigationSort())->toBe(2)
        ->and(ImportMappingResource::getNavigationLabel())->toBe('Importmappings')
        ->and(ImportResource::getNavigationGroup())->toBe('Imports')
        ->and(ImportResource::getNavigationSort())->toBe(3)
        ->and(ImportResource::getNavigationLabel())->toBe('Importhistorie');
});

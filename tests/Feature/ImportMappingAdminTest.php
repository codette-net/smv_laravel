<?php

use App\Enums\ImportFormat;
use App\Filament\Resources\ImportMappings\ImportMappingResource;
use App\Filament\Resources\ImportSources\ImportSourceResource;
use App\Imports\Mapping\ImportMapper;
use App\Imports\Mapping\MappingCompletion;
use App\Imports\Mapping\SourceFieldOptions;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function mappingAdmin(string $role): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('administrators can open mapping UI while non-admin panel roles cannot', function () {
    $admin = mappingAdmin('admin');
    $employer = mappingAdmin('employer');
    $this->actingAs($admin)->get(ImportMappingResource::getUrl())->assertSuccessful();
    $this->actingAs($employer)->get(ImportMappingResource::getUrl())->assertForbidden();
});

test('source field discovery exposes nested and repeated fixture paths without rendering raw source HTML', function () {
    $source = ImportSource::factory()->create(['configuration' => ['sample_path' => base_path('tests/Fixtures/Imports/michael-page/jobs.xml')], 'record_path' => 'job']);
    $options = app(SourceFieldOptions::class)->for($source);
    expect($options)->toHaveKeys(['salary.min', 'description.bulletPoints.*', 'location.text', 'sector.term']);
});

test('the import source mapping action points to mapping configuration', function () {
    $source = ImportSource::factory()->create();
    $admin = mappingAdmin('admin');
    $this->actingAs($admin)->get(ImportSourceResource::getUrl())->assertSuccessful()->assertSee('Mapping configureren');
    expect(ImportMappingResource::getUrl('create', ['import_source_id' => $source->id]))->toContain('import_source_id='.$source->id);
});

test('mapping completion state derives from the required registry destinations', function () {
    $mapping = ImportMapping::factory()->create();
    expect(app(MappingCompletion::class)->for($mapping))->toBe('Niet geconfigureerd');
    ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => 'vacancy.title']);
    expect(app(MappingCompletion::class)->for($mapping))->toBe('Onvolledig');
    ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => 'source_reference']);
    expect(app(MappingCompletion::class)->for($mapping))->toBe('Klaar voor preview');
});

test('a bounded configured sample can be normalized without domain persistence', function () {
    $source = ImportSource::factory()->create(['format' => ImportFormat::Json, 'configuration' => ['sample_path' => base_path('tests/Fixtures/Imports/orange-career/provisional-example.json')], 'record_path' => 'jobs.*']);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => 'source_reference', 'source_paths' => ['id']]);
    ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => 'vacancy.title', 'source_paths' => ['title']]);
    $result = app(ImportMapper::class)->map(app(SourceFieldOptions::class)->firstRecordFor($source), $mapping->fresh('fields'), $source);
    expect($result->data->get('vacancy.title'))->not->toBeNull();
});

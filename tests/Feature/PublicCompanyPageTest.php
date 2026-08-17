<?php

use App\Enums\CompanyStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Company;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function publicCompany(array $attributes = []): Company
{
    return Company::factory()->create([
        'status' => CompanyStatus::Active,
        ...$attributes,
    ]);
}

function companyVacancy(Company $company, array $attributes = []): Vacancy
{
    return Vacancy::factory()->create([
        'company_id' => $company->id,
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
        'is_filled' => false,
        'expires_at' => now()->addWeek(),
        ...$attributes,
    ]);
}

test('an active company resolves by its slug and renders its profile', function () {
    $company = publicCompany([
        'name' => 'Voorbeeld Bedrijf',
        'tagline' => 'De beste plek voor commerciële professionals.',
        'description' => 'Een uitgebreid bedrijfsprofiel.',
    ]);

    $this->get(route('bedrijven.show', $company))
        ->assertOk()
        ->assertSee('Voorbeeld Bedrijf')
        ->assertSee('De beste plek voor commerciële professionals.')
        ->assertSee('Een uitgebreid bedrijfsprofiel.');
});

test('unknown, soft-deleted, and non-public companies are not exposed', function () {
    $softDeleted = publicCompany();
    $softDeleted->delete();
    $draft = publicCompany(['status' => CompanyStatus::Draft]);

    $this->get('/bedrijven/onbekend-bedrijf')->assertNotFound();
    $this->get(route('bedrijven.show', $softDeleted))->assertNotFound();
    $this->get(route('bedrijven.show', $draft))->assertNotFound();
});

test('the public company page uses Media Library media and handles missing media', function () {
    Storage::fake('public');
    $withMedia = publicCompany(['name' => 'Bedrijf met logo']);
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    $withMedia->addMedia(UploadedFile::fake()->createWithContent('logo.png', $png))->toMediaCollection('logo');
    $withMedia->addMedia(UploadedFile::fake()->createWithContent('cover.png', $png))->toMediaCollection('cover');
    $withoutMedia = publicCompany(['name' => 'Bedrijf zonder media']);

    $this->get(route('bedrijven.show', $withMedia))
        ->assertOk()
        ->assertSee('Logo van Bedrijf met logo');
    $this->get(route('bedrijven.show', $withoutMedia))
        ->assertOk()
        ->assertSee('Bedrijf zonder media');
});

test('only current published vacancies are shown for a company', function () {
    $company = publicCompany();
    companyVacancy($company, ['title' => 'Zichtbare vacature']);
    companyVacancy($company, ['title' => 'Concept vacature', 'status' => VacancyStatus::Draft]);
    companyVacancy($company, ['title' => 'Ingevulde vacature', 'is_filled' => true]);
    companyVacancy($company, ['title' => 'Gesloten vacature', 'deadline_at' => now()->subDay()]);
    companyVacancy($company, ['title' => 'Verlopen vacature', 'expires_at' => now()->subDay()]);
    companyVacancy($company, ['title' => 'Geplande vacature', 'published_at' => now()->addDay()]);

    $this->get(route('bedrijven.show', $company))
        ->assertOk()
        ->assertSee('Zichtbare vacature')
        ->assertDontSee('Concept vacature')
        ->assertDontSee('Ingevulde vacature')
        ->assertDontSee('Gesloten vacature')
        ->assertDontSee('Verlopen vacature')
        ->assertDontSee('Geplande vacature');
});

test('a company without public vacancies renders an empty state', function () {
    $company = publicCompany();

    $this->get(route('bedrijven.show', $company))
        ->assertOk()
        ->assertSee('Momenteel geen openstaande vacatures.');
});

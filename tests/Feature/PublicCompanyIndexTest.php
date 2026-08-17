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

function listedCompany(array $attributes = []): Company
{
    return Company::factory()->create([
        'status' => CompanyStatus::Active,
        ...$attributes,
    ]);
}

function listedVacancy(Company $company, array $attributes = []): Vacancy
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

test('the company index lists active companies and links to their slug detail pages', function () {
    $company = listedCompany(['name' => 'Zichtbaar Bedrijf']);
    $draft = listedCompany(['name' => 'Concept Bedrijf', 'status' => CompanyStatus::Draft]);
    $softDeleted = listedCompany(['name' => 'Verwijderd Bedrijf']);
    $softDeleted->delete();

    $this->get(route('companies.index'))
        ->assertOk()
        ->assertSee('Zichtbaar Bedrijf')
        ->assertDontSee($draft->name)
        ->assertDontSee($softDeleted->name)
        ->assertSee(route('bedrijven.show', $company), false);
});

test('the company index renders Media Library logos and handles missing media', function () {
    Storage::fake('public');
    $withMedia = listedCompany(['name' => 'Bedrijf met logo']);
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    $withMedia->addMedia(UploadedFile::fake()->createWithContent('logo.png', $png))->toMediaCollection('logo');
    $withoutMedia = listedCompany(['name' => 'Bedrijf zonder media']);

    $this->get(route('companies.index'))
        ->assertOk()
        ->assertSee('Logo van Bedrijf met logo')
        ->assertSee($withoutMedia->name);
});

test('company vacancy counts follow the current public vacancy rule', function () {
    $company = listedCompany();
    listedVacancy($company, ['title' => 'Open vacature']);
    listedVacancy($company, ['title' => 'Concept vacature', 'status' => VacancyStatus::Draft]);
    listedVacancy($company, ['title' => 'Ingevulde vacature', 'is_filled' => true]);
    listedVacancy($company, ['title' => 'Verlopen vacature', 'expires_at' => now()->subDay()]);

    $this->get(route('companies.index'))
        ->assertOk()
        ->assertSee('1 vacature');
});

test('the company index paginates results', function () {
    $companies = collect(range(1, 13))
        ->map(fn (int $number) => listedCompany([
            'is_featured' => false,
            'name' => sprintf('Bedrijf %02d', $number),
        ]));

    $this->get(route('companies.index'))
        ->assertOk()
        ->assertSee($companies->first()->name)
        ->assertDontSee($companies->last()->name);

    $this->get(route('companies.index', ['page' => 2]))
        ->assertOk()
        ->assertSee($companies->last()->name);
});

test('the company index renders an empty state', function () {
    $this->get(route('companies.index'))
        ->assertOk()
        ->assertSee('Geen bedrijven gevonden.');
});

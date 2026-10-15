<?php

use App\Enums\CategoryType;
use App\Enums\CompanyStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Category;
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

test('a featured company card renders its Media Library cover image', function () {
    Storage::fake('public');
    $company = listedCompany([
        'is_featured' => true,
        'name' => 'Uitgelichte Werkgever',
    ]);
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    $company->addMedia(UploadedFile::fake()->createWithContent('cover.png', $png))->toMediaCollection('cover');

    $this->get(route('companies.index'))
        ->assertOk()
        ->assertSee('Uitgelichte Werkgever')
        ->assertSee('Uitgelicht')
        ->assertSee($company->fresh()->publicCoverUrl(), false);
});

test('company vacancy counts follow the current public vacancy rule', function () {
    $company = listedCompany();
    listedVacancy($company, ['title' => 'Open vacature']);
    listedVacancy($company, ['title' => 'Concept vacature', 'status' => VacancyStatus::Draft]);
    listedVacancy($company, ['title' => 'Ingevulde vacature', 'is_filled' => true]);
    listedVacancy($company, ['title' => 'Gesloten vacature', 'deadline_at' => now()->subDay()]);
    listedVacancy($company, ['title' => 'Verlopen vacature', 'expires_at' => now()->subDay()]);
    listedVacancy($company, ['title' => 'Geplande vacature', 'published_at' => now()->addDay()]);

    $this->get(route('companies.index'))
        ->assertOk()
        ->assertSee('Open vacature')
        ->assertDontSee('Concept vacature')
        ->assertDontSee('Ingevulde vacature')
        ->assertDontSee('Gesloten vacature')
        ->assertDontSee('Verlopen vacature')
        ->assertDontSee('Geplande vacature');
});

test('company cards show two public vacancy titles and a remaining count', function () {
    $company = listedCompany(['name' => 'Werkgever met aanbod']);

    foreach (range(1, 4) as $number) {
        listedVacancy($company, [
            'title' => "Publieke vacature {$number}",
            'published_at' => now()->subMinutes($number),
        ]);
    }

    $this->get(route('companies.index'))
        ->assertOk()
        ->assertSee('Publieke vacature 1')
        ->assertSee('Publieke vacature 2')
        ->assertDontSee('Publieke vacature 3')
        ->assertDontSee('Publieke vacature 4')
        ->assertSee('aria-label="Nog 2 vacatures bij Werkgever met aanbod"', false)
        ->assertSee('+2')
        ->assertSee(route('vacancies.show', Vacancy::query()->where('title', 'Publieke vacature 1')->firstOrFail()), false);
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

test('the company index searches names and relevant secondary fields', function () {
    listedCompany(['name' => 'Helder Salesbureau', 'location' => 'Utrecht']);
    listedCompany(['name' => 'Groei Partners', 'tagline' => 'Voor B2B softwareteams']);
    listedCompany(['name' => 'Andere Werkgever', 'location' => 'Rotterdam']);
    listedCompany(['name' => 'Verborgen Utrecht', 'location' => 'Utrecht', 'status' => CompanyStatus::Draft]);

    $this->get(route('companies.index', ['q' => 'Helder']))
        ->assertOk()
        ->assertSee('Helder Salesbureau')
        ->assertDontSee('Groei Partners')
        ->assertDontSee('Andere Werkgever');

    $this->get(route('companies.index', ['q' => 'softwareteams']))
        ->assertOk()
        ->assertSee('Groei Partners')
        ->assertDontSee('Helder Salesbureau');

    $this->get(route('companies.index', ['q' => 'Utrecht']))
        ->assertOk()
        ->assertSee('Helder Salesbureau')
        ->assertDontSee('Verborgen Utrecht');

    $this->get(route('companies.index', ['q' => 'bestaat niet']))
        ->assertOk()
        ->assertSee('Geen bedrijven gevonden.');
});

test('one typed company category filters results and combines with search', function () {
    $agency = Category::factory()->create([
        'name' => 'Marketingbureau',
        'slug' => 'marketingbureau',
        'type' => CategoryType::company_category,
    ]);
    $saas = Category::factory()->create([
        'name' => 'SaaS',
        'slug' => 'saas',
        'type' => CategoryType::company_category,
    ]);
    Category::factory()->create([
        'name' => 'Vacaturecategorie met dezelfde slug',
        'slug' => 'marketingbureau',
        'type' => CategoryType::sector,
    ]);

    $matching = listedCompany(['name' => 'Helder Marketing']);
    $alsoMatching = listedCompany(['name' => 'Andere Agency']);
    $nonMatching = listedCompany(['name' => 'SaaS Verkoper']);
    $hidden = listedCompany(['name' => 'Verborgen Marketing', 'status' => CompanyStatus::Draft]);
    $matching->categories()->attach($agency);
    $alsoMatching->categories()->attach($agency);
    $nonMatching->categories()->attach($saas);
    $hidden->categories()->attach($agency);

    $this->get(route('companies.index', ['category' => 'marketingbureau']))
        ->assertOk()
        ->assertSee('Helder Marketing')
        ->assertSee('Andere Agency')
        ->assertDontSee('SaaS Verkoper')
        ->assertDontSee('Verborgen Marketing')
        ->assertSee('Marketingbureau')
        ->assertSee('(2)');

    $this->get(route('companies.index', ['q' => 'Helder', 'category' => 'marketingbureau']))
        ->assertOk()
        ->assertSee('Helder Marketing')
        ->assertDontSee('Andere Agency');

    $this->get(route('companies.index', ['category' => 'saas']))
        ->assertOk()
        ->assertSee('SaaS Verkoper')
        ->assertDontSee('Helder Marketing');
});

test('invalid or non scalar category input is handled safely', function () {
    listedCompany(['name' => 'Publieke Werkgever']);

    $this->get(route('companies.index', ['category' => 'onbekend']))
        ->assertOk()
        ->assertDontSee('Publieke Werkgever')
        ->assertSee('Geen bedrijven gevonden.');

    $this->get(route('companies.index').'?category[]=een&category[]=twee')
        ->assertOk()
        ->assertSee('Publieke Werkgever');
});

test('company pagination preserves search and category query state', function () {
    $category = Category::factory()->create([
        'name' => 'Recruitment',
        'slug' => 'recruitment',
        'type' => CategoryType::company_category,
    ]);

    foreach (range(1, 13) as $number) {
        $company = listedCompany([
            'is_featured' => false,
            'name' => sprintf('Zoekbaar bedrijf %02d', $number),
        ]);
        $company->categories()->attach($category);
    }

    $this->get(route('companies.index', ['q' => 'Zoekbaar', 'category' => 'recruitment']))
        ->assertOk()
        ->assertSee('q=Zoekbaar', false)
        ->assertSee('category=recruitment', false)
        ->assertSee('<link rel="canonical" href="'.route('companies.index').'">', false);

    $this->get(route('companies.index', ['q' => 'Zoekbaar', 'category' => 'recruitment', 'page' => 2]))
        ->assertOk()
        ->assertSee('Zoekbaar bedrijf 13')
        ->assertSee('<link rel="canonical" href="'.route('companies.index', ['page' => 2]).'">', false)
        ->assertDontSee('<link rel="canonical" href="'.route('companies.index', ['q' => 'Zoekbaar', 'category' => 'recruitment', 'page' => 2]).'">', false);
});

test('company cards use a contained logo and a safe plain text introduction fallback', function () {
    Storage::fake('public');
    $company = listedCompany([
        'name' => 'Veilige Werkgever',
        'tagline' => null,
        'description' => '<p>Een <strong>duidelijke</strong> introductie.</p>',
    ]);
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    $company->addMedia(UploadedFile::fake()->createWithContent('logo.png', $png))->toMediaCollection('logo');

    $this->get(route('companies.index'))
        ->assertOk()
        ->assertSee('Een duidelijke introductie.')
        ->assertDontSee('<strong>duidelijke</strong>', false)
        ->assertSee('object-contain', false)
        ->assertViewHas('companies', fn ($companies) => $companies->every(
            fn (Company $company): bool => $company->relationLoaded('media')
                && $company->relationLoaded('categories')
                && $company->relationLoaded('publicVacanciesPreview')
                && array_key_exists('public_vacancies_count', $company->getAttributes())
        ));
});

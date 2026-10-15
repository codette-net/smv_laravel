<?php

use App\Enums\CategoryType;
use App\Enums\CompanyStatus;
use App\Enums\CompensationPeriod;
use App\Enums\SalaryBasis;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\Vacancy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-08-17 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function publicListingCompany(array $attributes = []): Company
{
    return Company::factory()->create([
        'status' => CompanyStatus::Active,
        ...$attributes,
    ]);
}

function publicListingVacancy(Company $company, array $attributes = []): Vacancy
{
    return Vacancy::factory()->create([
        'company_id' => $company->id,
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
        'is_filled' => false,
        'published_at' => now(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
        ...$attributes,
    ]);
}

test('the vacancy index only renders publicly visible vacancies', function () {
    $company = publicListingCompany();
    publicListingVacancy($company, ['title' => 'Zichtbare vacature']);
    publicListingVacancy($company, ['title' => 'Concept vacature', 'status' => VacancyStatus::Draft]);
    publicListingVacancy($company, ['title' => 'Vervulde vacature', 'is_filled' => true]);
    publicListingVacancy($company, ['title' => 'Verlopen vacature', 'expires_at' => now()->subSecond()]);
    publicListingVacancy($company, ['title' => 'Geplande vacature', 'published_at' => now()->addSecond()]);

    $this->get(route('vacancies.index'))
        ->assertOk()
        ->assertSee('Zichtbare vacature')
        ->assertSee('Bewaar vacature')
        ->assertDontSee('Concept vacature')
        ->assertDontSee('Vervulde vacature')
        ->assertDontSee('Verlopen vacature')
        ->assertDontSee('Geplande vacature');
});

test('the vacancy index searches titles and company names', function () {
    $salesCompany = publicListingCompany(['name' => 'Commercieel Collectief']);
    $otherCompany = publicListingCompany(['name' => 'Ander Bedrijf']);
    publicListingVacancy($salesCompany, ['title' => 'Accountmanager buitendienst']);
    publicListingVacancy($otherCompany, ['title' => 'Marketing specialist']);

    $this->get(route('vacancies.index', ['zoek' => 'accountmanager']))
        ->assertOk()
        ->assertSee('Accountmanager buitendienst')
        ->assertDontSee('Marketing specialist');

    $this->get(route('vacancies.index', ['zoek' => 'collectief']))
        ->assertOk()
        ->assertSee('Accountmanager buitendienst')
        ->assertDontSee('Marketing specialist');
});

test('city filtering works independently and together with search', function () {
    $company = publicListingCompany();
    publicListingVacancy($company, ['title' => 'Accountmanager Utrecht', 'location' => 'Utrecht']);
    publicListingVacancy($company, ['title' => 'Marketeer Utrecht', 'location' => 'Utrecht']);
    publicListingVacancy($company, ['title' => 'Accountmanager Amsterdam', 'location' => 'Amsterdam']);

    $this->get(route('vacancies.index', ['locatie' => 'Utrecht']))
        ->assertOk()
        ->assertSee('Accountmanager Utrecht')
        ->assertSee('Marketeer Utrecht')
        ->assertDontSee('Accountmanager Amsterdam');

    $this->get(route('vacancies.index', ['zoek' => 'Accountmanager', 'locatie' => 'Utrecht']))
        ->assertOk()
        ->assertSee('Accountmanager Utrecht')
        ->assertDontSee('Marketeer Utrecht')
        ->assertDontSee('Accountmanager Amsterdam');
});

test('location category and company filters combine through the query string', function () {
    $sales = Category::create([
        'name' => 'Sales',
        'slug' => 'sales',
        'type' => CategoryType::vacancy_category,
    ]);
    $marketing = Category::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
        'type' => CategoryType::vacancy_category,
    ]);
    $targetCompany = publicListingCompany(['name' => 'Doelbedrijf']);
    $otherCompany = publicListingCompany(['name' => 'Ander bedrijf']);
    $match = publicListingVacancy($targetCompany, ['title' => 'Salesconsultant', 'location' => 'Utrecht']);
    $match->categories()->attach($sales);
    $otherCategory = publicListingVacancy($targetCompany, ['title' => 'Marketeer', 'location' => 'Utrecht']);
    $otherCategory->categories()->attach($marketing);
    publicListingVacancy($otherCompany, ['title' => 'Salesmanager', 'location' => 'Utrecht'])->categories()->attach($sales);

    $this->get(route('vacancies.index', [
        'locatie' => 'Utrecht',
        'categorie' => 'sales',
        'bedrijf' => $targetCompany->slug,
    ]))
        ->assertOk()
        ->assertSee('Salesconsultant')
        ->assertDontSee('Marketeer')
        ->assertDontSee('Salesmanager')
        ->assertSee('Locatie: Utrecht')
        ->assertSee('Categorie: Sales')
        ->assertSee('Bedrijf: '.$targetCompany->slug);
});

test('the index filters structured vacancy taxonomies through stable slugs', function () {
    $fulltime = Category::factory()->create(['name' => 'Fulltime', 'type' => CategoryType::employment_type]);
    $hybrid = Category::factory()->create(['name' => 'Hybride', 'type' => CategoryType::workplace]);
    $it = Category::factory()->create(['name' => 'IT', 'type' => CategoryType::sector]);
    $sales = Category::factory()->create(['name' => 'Sales', 'type' => CategoryType::function_area]);
    $medior = Category::factory()->create(['name' => 'Medior', 'type' => CategoryType::experience]);
    $company = publicListingCompany();
    $match = publicListingVacancy($company, ['title' => 'Passende vacature']);
    $match->categories()->attach([$fulltime->id, $hybrid->id, $it->id, $sales->id, $medior->id]);
    publicListingVacancy($company, ['title' => 'Andere vacature']);

    $this->get(route('vacancies.index', [
        'dienstverband' => $fulltime->slug,
        'werklocatie' => $hybrid->slug,
        'sector' => $it->slug,
        'functiegebied' => $sales->slug,
        'ervaring' => $medior->slug,
    ]))
        ->assertOk()
        ->assertSee('Passende vacature')
        ->assertDontSee('Andere vacature')
        ->assertSee('Dienstverband: Fulltime')
        ->assertSee('Ervaring: Medior');
});

test('each structured taxonomy filter and their combined selection use only matching categories', function () {
    $categories = [
        'dienstverband' => Category::factory()->create(['name' => 'Fulltime', 'type' => CategoryType::employment_type]),
        'werklocatie' => Category::factory()->create(['name' => 'Hybride', 'type' => CategoryType::workplace]),
        'sector' => Category::factory()->create(['name' => 'IT', 'type' => CategoryType::sector]),
        'functiegebied' => Category::factory()->create(['name' => 'Sales', 'type' => CategoryType::function_area]),
        'ervaring' => Category::factory()->create(['name' => 'Senior', 'type' => CategoryType::experience]),
    ];
    $company = publicListingCompany();
    $match = publicListingVacancy($company, ['title' => 'Volledig getaxeerde vacature']);
    $match->categories()->attach(collect($categories)->pluck('id')->all());
    publicListingVacancy($company, ['title' => 'Vacature zonder taxonomie']);

    foreach ($categories as $parameter => $category) {
        $this->get(route('vacancies.index', [$parameter => $category->slug]))
            ->assertOk()
            ->assertSee('Volledig getaxeerde vacature')
            ->assertDontSee('Vacature zonder taxonomie');
    }

    $this->get(route('vacancies.index', collect($categories)->map(fn (Category $category): string => $category->slug)->all()))
        ->assertOk()
        ->assertSee('Volledig getaxeerde vacature')
        ->assertDontSee('Vacature zonder taxonomie');
});

test('taxonomy options only expose relevant categories of their matching type and chips use names', function () {
    $it = Category::factory()->create(['name' => 'IT', 'type' => CategoryType::sector]);
    $legacy = Category::factory()->create(['name' => 'Verborgen oude categorie', 'type' => CategoryType::vacancy_category]);
    $company = publicListingCompany();
    $vacancy = publicListingVacancy($company, ['title' => 'IT vacature']);
    $vacancy->categories()->attach($it);
    publicListingVacancy($company, ['title' => 'Andere vacature'])->categories()->attach($legacy);

    $this->get(route('vacancies.index', ['sector' => 'it']))
        ->assertOk()
        ->assertSee('Sector: IT')
        ->assertSee('>IT<', false)
        ->assertDontSee('Verborgen oude categorie');
});

test('the index supports safe newest deadline and alphabetical sorting', function () {
    $company = publicListingCompany();
    publicListingVacancy($company, ['title' => 'Zebra', 'published_at' => now()->subDay(), 'deadline_at' => now()->addDays(5)]);
    publicListingVacancy($company, ['title' => 'Alfa', 'published_at' => now(), 'deadline_at' => now()->addDays(2)]);
    publicListingVacancy($company, ['title' => 'Geen deadline', 'published_at' => now()->subHours(2), 'deadline_at' => null]);

    $newest = $this->get(route('vacancies.index', ['sort' => 'nieuwste']))->assertOk()->getContent();
    expect(strpos($newest, 'Alfa'))->toBeLessThan(strpos($newest, 'Geen deadline'));

    $deadline = $this->get(route('vacancies.index', ['sort' => 'deadline']))->assertOk()->getContent();
    expect(strpos($deadline, 'Alfa'))->toBeLessThan(strpos($deadline, 'Zebra'))
        ->and(strpos($deadline, 'Zebra'))->toBeLessThan(strpos($deadline, 'Geen deadline'));

    $alphabetical = $this->get(route('vacancies.index', ['sort' => 'az']))->assertOk()->getContent();
    expect(strpos($alphabetical, 'Alfa'))->toBeLessThan(strpos($alphabetical, 'Zebra'));

    $this->get(route('vacancies.index', ['sort' => 'onveilig']))
        ->assertOk()
        ->assertSee('Alfa');
});

test('the index paginates while preserving filter query parameters', function () {
    $company = publicListingCompany();

    foreach (range(1, 25) as $number) {
        publicListingVacancy($company, [
            'title' => sprintf('Pagina vacature %02d', $number),
            'published_at' => now()->subMinutes($number),
        ]);
    }

    $this->get(route('vacancies.index', ['zoek' => 'Pagina vacature']))
        ->assertOk()
        ->assertSee('25 vacatures gevonden')
        ->assertSee('Pagina vacature 01')
        ->assertSee('Pagina vacature 12')
        ->assertDontSee('Pagina vacature 13')
        ->assertSee('zoek=Pagina%20vacature', false);

    $this->get(route('vacancies.index', ['zoek' => 'Pagina vacature', 'page' => 2]))
        ->assertOk()
        ->assertSee('Pagina vacature 13')
        ->assertSee('Pagina vacature 24')
        ->assertDontSee('Pagina vacature 25');

    $this->get(route('vacancies.index', ['zoek' => 'Pagina vacature', 'page' => 3]))
        ->assertOk()
        ->assertSee('Pagina vacature 25')
        ->assertDontSee('Pagina vacature 24');
});

test('the index has stable pagination boundaries when sort values are equal', function () {
    $company = publicListingCompany();

    $newest = collect(range(1, 13))->map(fn (int $number) => publicListingVacancy($company, [
        'title' => sprintf('Gelijke nieuwste %02d', $number),
        'published_at' => now()->subDay(),
        'created_at' => now()->subDay(),
    ]));

    $this->get(route('vacancies.index', ['zoek' => 'Gelijke nieuwste', 'sort' => 'nieuwste']))
        ->assertOk()
        ->assertSee($newest->last()->title)
        ->assertDontSee($newest->first()->title);

    $this->get(route('vacancies.index', ['zoek' => 'Gelijke nieuwste', 'sort' => 'nieuwste', 'page' => 2]))
        ->assertOk()
        ->assertSee($newest->first()->title);

    $deadlines = collect(range(1, 13))->map(fn (int $number) => publicListingVacancy($company, [
        'title' => sprintf('Gelijke deadline %02d', $number),
        'deadline_at' => now()->addWeek(),
        'published_at' => now()->subDay(),
    ]));

    $this->get(route('vacancies.index', ['zoek' => 'Gelijke deadline', 'sort' => 'deadline']))
        ->assertOk()
        ->assertSee($deadlines->last()->title)
        ->assertDontSee($deadlines->first()->title);

    $this->get(route('vacancies.index', ['zoek' => 'Gelijke deadline', 'sort' => 'deadline', 'page' => 2]))
        ->assertOk()
        ->assertSee($deadlines->first()->title);

    $alphabetical = collect(range(1, 13))->map(fn () => publicListingVacancy($company, [
        'title' => 'Gelijke alfabetische vacature',
        'published_at' => now()->subDay(),
    ]));

    $this->get(route('vacancies.index', ['zoek' => 'Gelijke alfabetische vacature', 'sort' => 'az']))
        ->assertOk()
        ->assertSee('href="'.route('vacancies.show', $alphabetical->last()).'"', false)
        ->assertDontSee('href="'.route('vacancies.show', $alphabetical->first()).'"', false);

    $this->get(route('vacancies.index', ['zoek' => 'Gelijke alfabetische vacature', 'sort' => 'az', 'page' => 2]))
        ->assertOk()
        ->assertSee('href="'.route('vacancies.show', $alphabetical->first()).'"', false);
});

test('the index renders a Dutch empty state and handles company logos', function () {
    config(['filesystems.disks.company-logo-test' => [
        'driver' => 'local',
        'root' => storage_path('framework/testing/disks/company-logo-test'),
    ]]);
    Storage::fake('company-logo-test');
    $company = publicListingCompany(['name' => 'Bedrijf met logo']);
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    $company->addMedia(UploadedFile::fake()->createWithContent('logo.png', $png))->toMediaCollection('logo', 'company-logo-test');
    publicListingVacancy($company, ['title' => 'Vacature met logo']);

    $this->get(route('vacancies.index'))
        ->assertOk()
        ->assertSee('1 vacature gevonden')
        ->assertSee('Logo van Bedrijf met logo');

    $this->get(route('vacancies.index', ['zoek' => 'onvindbaar']))
        ->assertOk()
        ->assertSee('Geen vacatures gevonden');
});

test('monthly salary filtering uses inclusive overlap and only comparable FTE ranges', function () {
    $company = publicListingCompany();
    publicListingVacancy($company, [
        'title' => 'Passend maandsalaris',
        'salary_min' => 3500,
        'salary_max' => 4500,
        'salary_currency' => 'EUR',
        'salary_period' => CompensationPeriod::Month,
        'salary_basis' => SalaryBasis::GrossFullTimeEquivalent,
    ]);
    publicListingVacancy($company, [
        'title' => 'Vaste grenswaarde',
        'salary_min' => 5000,
        'salary_max' => null,
        'salary_currency' => 'EUR',
        'salary_period' => CompensationPeriod::Month,
        'salary_basis' => SalaryBasis::GrossFullTimeEquivalent,
    ]);
    publicListingVacancy($company, [
        'title' => 'Onbekende salarisbasis',
        'salary_min' => 4000,
        'salary_max' => 5000,
        'salary_currency' => 'EUR',
        'salary_period' => CompensationPeriod::Month,
        'salary_basis' => null,
    ]);
    publicListingVacancy($company, [
        'title' => 'Alleen uurtarief',
        'rate_min' => 80,
        'rate_max' => 100,
        'rate_currency' => 'EUR',
        'rate_period' => CompensationPeriod::Hour,
    ]);

    $this->get(route('vacancies.index'))
        ->assertOk()
        ->assertSee('Onbekende salarisbasis')
        ->assertSee('Alleen uurtarief');

    $this->get(route('vacancies.index', ['vergoeding' => 'maand', 'bedrag_van' => 4500, 'bedrag_tot' => 5000]))
        ->assertOk()
        ->assertSee('Passend maandsalaris')
        ->assertSee('Vaste grenswaarde')
        ->assertDontSee('Onbekende salarisbasis')
        ->assertDontSee('Alleen uurtarief')
        ->assertSee('Vergoeding: € 4500 – € 5000 bruto per maand (FTE)');
});

test('hourly rate filtering supports one-sided ranges without salary conversion', function () {
    $company = publicListingCompany();
    publicListingVacancy($company, [
        'title' => 'Passend uurtarief',
        'rate_min' => null,
        'rate_max' => 95,
        'rate_currency' => 'EUR',
        'rate_period' => CompensationPeriod::Hour,
    ]);
    publicListingVacancy($company, [
        'title' => 'Te laag uurtarief',
        'rate_min' => 60,
        'rate_max' => 70,
        'rate_currency' => 'EUR',
        'rate_period' => CompensationPeriod::Hour,
    ]);
    publicListingVacancy($company, [
        'title' => 'Maandsalaris is geen tarief',
        'salary_min' => 5000,
        'salary_currency' => 'EUR',
        'salary_period' => CompensationPeriod::Month,
        'salary_basis' => SalaryBasis::GrossFullTimeEquivalent,
    ]);

    $this->get(route('vacancies.index', ['vergoeding' => 'uur', 'bedrag_van' => 80]))
        ->assertOk()
        ->assertSee('Passend uurtarief')
        ->assertDontSee('Te laag uurtarief')
        ->assertDontSee('Maandsalaris is geen tarief');
});

test('invalid compensation filters show Dutch feedback and are not partially applied', function (array $parameters, string $message) {
    $company = publicListingCompany();
    publicListingVacancy($company, ['title' => 'Blijft zichtbaar bij ongeldige invoer']);

    $this->get(route('vacancies.index', $parameters))
        ->assertOk()
        ->assertSee($message)
        ->assertSee('Blijft zichtbaar bij ongeldige invoer');
})->with([
    'missing mode' => [['bedrag_van' => '3000'], 'Kies of je op bruto maandsalaris (FTE) of uurtarief wilt filteren.'],
    'negative amount' => [['vergoeding' => 'maand', 'bedrag_van' => '-1'], 'Het minimumbedrag moet een positief heel bedrag zijn.'],
    'non numeric amount' => [['vergoeding' => 'maand', 'bedrag_tot' => 'veel'], 'Het maximumbedrag moet een positief heel bedrag zijn.'],
    'array amount' => [['vergoeding' => 'maand', 'bedrag_van' => ['3000']], 'Het minimumbedrag moet een positief heel bedrag zijn.'],
    'reversed range' => [['vergoeding' => 'maand', 'bedrag_van' => '5000', 'bedrag_tot' => '4000'], 'Het maximumbedrag moet gelijk zijn aan of hoger zijn dan het minimumbedrag.'],
]);

test('education and parent taxonomy filters combine without duplicate vacancies', function () {
    $company = publicListingCompany();
    $parent = Category::factory()->create(['name' => 'Technologie', 'type' => CategoryType::sector]);
    $child = Category::factory()->create(['name' => 'SaaS', 'type' => CategoryType::sector, 'parent_id' => $parent->id]);
    $hbo = Category::factory()->create(['name' => 'HBO', 'type' => CategoryType::qualification]);
    $match = publicListingVacancy($company, ['title' => 'SaaS accountmanager']);
    $match->categories()->attach([$parent->id, $child->id, $hbo->id]);
    publicListingVacancy($company, ['title' => 'Andere opleiding'])->categories()->attach($child);

    $response = $this->get(route('vacancies.index', ['sector' => $parent->slug, 'opleiding' => $hbo->slug]))
        ->assertOk()
        ->assertSee('SaaS accountmanager')
        ->assertDontSee('Andere opleiding')
        ->assertSee('Opleidingsniveau: HBO');

    expect(substr_count($response->getContent(), 'SaaS accountmanager'))->toBe(1);
});

test('canonical employment experience education and workplace values stay type scoped', function () {
    $company = publicListingCompany();
    $stage = Category::factory()->create(['name' => 'Stage', 'type' => CategoryType::employment_type]);
    $starter = Category::factory()->create(['name' => 'Starter', 'type' => CategoryType::experience]);
    $noRequirement = Category::factory()->create(['name' => 'Geen specifieke opleiding vereist', 'type' => CategoryType::qualification]);
    $remote = Category::factory()->create(['name' => 'Remote', 'type' => CategoryType::workplace]);
    $match = publicListingVacancy($company, ['title' => 'Remote salesstage']);
    $match->categories()->attach([$stage->id, $starter->id, $noRequirement->id, $remote->id]);
    publicListingVacancy($company, ['title' => 'Vacature met onbekende opleiding']);

    $this->get(route('vacancies.index', [
        'dienstverband' => $stage->slug,
        'ervaring' => $starter->slug,
        'opleiding' => $noRequirement->slug,
        'werklocatie' => $remote->slug,
    ]))
        ->assertOk()
        ->assertSee('Remote salesstage')
        ->assertDontSee('Vacature met onbekende opleiding')
        ->assertSee('Opleidingsniveau: Geen specifieke opleiding vereist');
});

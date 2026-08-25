<?php

use App\Enums\CompanyStatus;
use App\Enums\CompensationPeriod;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Company;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seoCompany(array $attributes = []): Company
{
    return Company::factory()->create([
        'status' => CompanyStatus::Active,
        ...$attributes,
    ]);
}

function seoVacancy(Company $company, array $attributes = []): Vacancy
{
    return Vacancy::factory()->create([
        'company_id' => $company->id,
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
        'is_filled' => false,
        'published_at' => now()->subDay(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
        ...$attributes,
    ]);
}

test('public detail pages expose their slug canonicals titles and safe descriptions', function () {
    config(['app.env' => 'production']);
    $company = seoCompany([
        'name' => 'Acme & Partners',
        'tagline' => '<strong>Commercieel groeien</strong>',
        'description' => null,
    ]);
    $vacancy = seoVacancy($company, [
        'title' => 'Accountmanager',
        'location' => 'Utrecht',
        'description' => '<p>Bouw aan <strong>duurzame relaties</strong>.</p>',
        'application_url' => 'https://ats.example.test/jobs/secret-reference',
        'source_reference' => 'secret-reference',
    ]);

    $this->get(route('vacancies.show', $vacancy))
        ->assertOk()
        ->assertSee('<title>Accountmanager vacature in Utrecht | Sales en Marketing Vacatures</title>', false)
        ->assertSee('<meta name="description" content="Bouw aan duurzame relaties.">', false)
        ->assertSee('<link rel="canonical" href="'.route('vacancies.show', $vacancy).'">', false)
        ->assertDontSee('<link rel="canonical" href="https://ats.example.test', false);

    $this->get(route('bedrijven.show', $company))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('bedrijven.show', $company).'">', false)
        ->assertSee('<meta name="description" content="Commercieel groeien">', false)
        ->assertSee('"@context":"https://schema.org","@type":"Organization"', false);
});

test('vacancy filters are noindex while clean pagination remains self canonical', function () {
    config(['app.env' => 'production']);

    $this->get(route('vacancies.index', ['zoek' => 'sales', 'sort' => 'az']))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<link rel="canonical" href="'.route('vacancies.index').'">', false);

    $this->get(route('vacancies.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<link rel="canonical" href="'.route('vacancies.index', ['page' => 2]).'">', false);
});

test('vacancy detail emits bounded JobPosting data without inventing unsupported fields', function () {
    $company = seoCompany(['name' => 'Veilige Werkgever']);
    $vacancy = seoVacancy($company, [
        'title' => 'Sales consultant',
        'description' => '<p>Werk met klanten.</p><script>alert("x")</script>',
        'salary_min' => null,
        'salary_max' => null,
        'salary_currency' => null,
        'salary_period' => null,
    ]);

    $response = $this->get(route('vacancies.show', $vacancy))->assertOk();
    $response->assertSee('"@type":"JobPosting"', false)
        ->assertSee('"url":"'.route('vacancies.show', $vacancy).'"', false)
        ->assertDontSee('alert(\\"x\\")', false)
        ->assertDontSee('"employmentType"', false)
        ->assertDontSee('"baseSalary"', false)
        ->assertDontSee('</script><script>', false);
});

test('supported salary data is represented without using independent rate data as salary', function () {
    $company = seoCompany();
    $vacancy = seoVacancy($company, [
        'salary_min' => 3500,
        'salary_max' => 4500,
        'salary_currency' => 'EUR',
        'salary_period' => CompensationPeriod::Month,
        'rate_min' => 80,
        'rate_max' => 100,
        'rate_currency' => 'EUR',
        'rate_period' => CompensationPeriod::Hour,
    ]);

    $this->get(route('vacancies.show', $vacancy))
        ->assertOk()
        ->assertSee('"baseSalary"', false)
        ->assertSee('"minValue":3500', false)
        ->assertSee('"maxValue":4500', false)
        ->assertSee('"unitText":"MONTH"', false)
        ->assertDontSee('"minValue":80', false);
});

test('sitemap contains only canonical publicly visible entities', function () {
    $publicCompany = seoCompany(['name' => 'Publiek bedrijf']);
    $draftCompany = seoCompany(['name' => 'Concept bedrijf', 'status' => CompanyStatus::Draft]);
    $publicVacancy = seoVacancy($publicCompany, ['title' => 'Publieke vacature']);
    $expiredVacancy = seoVacancy($publicCompany, ['title' => 'Verlopen vacature', 'expires_at' => now()->subMinute()]);
    $hiddenCompanyVacancy = seoVacancy($draftCompany, ['title' => 'Vacature verborgen bedrijf']);

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('content-type', 'text/xml; charset=UTF-8')
        ->assertSee(route('home'), false)
        ->assertSee(route('vacancies.index'), false)
        ->assertSee(route('companies.index'), false)
        ->assertSee(route('vacancies.show', $publicVacancy), false)
        ->assertSee(route('bedrijven.show', $publicCompany), false)
        ->assertDontSee(route('vacancies.show', $expiredVacancy), false)
        ->assertDontSee(route('vacancies.show', $hiddenCompanyVacancy), false)
        ->assertDontSee(route('bedrijven.show', $draftCompany), false);
});

test('non production responses and robots prevent indexing while production robots advertises sitemap', function () {
    config(['app.env' => 'staging']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    $this->get(route('robots'))
        ->assertOk()
        ->assertSee('Disallow: /');

    config(['app.env' => 'production']);

    $this->get(route('robots'))
        ->assertOk()
        ->assertSee('Allow: /')
        ->assertSee('Disallow: /admin')
        ->assertSee('Sitemap: '.route('sitemap'));
});

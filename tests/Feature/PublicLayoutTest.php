<?php

use App\Enums\CompanyStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\BlogPost;
use App\Models\Company;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function homepageSearchVacancy(string $title, array $attributes = []): Vacancy
{
    $company = Company::factory()->create(['status' => CompanyStatus::Active]);

    return Vacancy::factory()->create([
        'company_id' => $company->id,
        'title' => $title,
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
        'is_filled' => false,
        'published_at' => now()->subHour(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addWeek(),
        ...$attributes,
    ]);
}

test('the homepage renders the shared public navigation footer and vacancy search form', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertViewIs('home')
        ->assertSee('Vind jouw volgende commerciële uitdaging')
        ->assertSee('action="'.route('home').'"', false)
        ->assertSee('name="zoek"', false)
        ->assertSee('name="locatie"', false)
        ->assertSee('name="dienstverband"', false)
        ->assertSee('name="functiegebied"', false)
        ->assertSee('href="'.route('home').'"', false)
        ->assertSee('href="'.route('vacancies.index').'"', false)
        ->assertSee('href="'.route('companies.index').'"', false)
        ->assertSee('href="'.route('blog.index').'"', false)
        ->assertSee('href="'.route('filament.dashboard.auth.login').'"', false)
        ->assertSee('Footer navigatie')
        ->assertSee('© '.now()->year.' Sales en Marketing Vacatures');
});

test('the homepage applies submitted vacancy filters and keeps its debounced search on the homepage', function () {
    homepageSearchVacancy('Accountmanager Utrecht', ['location' => 'Utrecht']);
    homepageSearchVacancy('Marketing specialist Amsterdam', ['location' => 'Amsterdam']);
    homepageSearchVacancy('Verborgen conceptvacature', ['status' => VacancyStatus::Draft]);

    $response = $this->get(route('home', ['zoek' => 'accountmanager', 'locatie' => 'Utrecht']));

    $response->assertOk()
        ->assertSee('Accountmanager Utrecht')
        ->assertDontSee('Marketing specialist Amsterdam')
        ->assertDontSee('Verborgen conceptvacature')
        ->assertSee('value="accountmanager"', false)
        ->assertSee('href="'.route('home').'">Wis filters</a>', false)
        ->assertSee('action="'.route('home').'"', false)
        ->assertSee('x-on:input.debounce.500ms=', false)
        ->assertSee('requestSubmit()', false)
        ->assertDontSee('action="'.route('vacancies.index').'"', false);
});

test('the homepage keeps additional filters collapsible and opens them only for active additional filters', function () {
    homepageSearchVacancy('Accountmanager Utrecht', ['location' => 'Utrecht']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('id="homepage-aanvullende-filters"', false)
        ->assertSee('x-show="filtersOpen"', false)
        ->assertSee('x-data="{ filtersOpen: false, loading: false }"', false)
        ->assertSee('type="button" aria-controls="homepage-aanvullende-filters" :aria-expanded="filtersOpen"', false);

    $this->get(route('home', ['zoek' => 'Accountmanager']))
        ->assertOk()
        ->assertSee('x-data="{ filtersOpen: false, loading: false }"', false);

    $this->get(route('home', ['locatie' => 'Utrecht']))
        ->assertOk()
        ->assertSee('x-data="{ filtersOpen: true, loading: false }"', false)
        ->assertSee('href="'.route('home').'">Wis filters</a>', false)
        ->assertSeeInOrder(['href="'.route('home').'">Wis filters</a>', 'id="homepage-aanvullende-filters"'], false)
        ->assertSee('x-on:change="$el.form.requestSubmit()"', false);
});

test('homepage result pagination preserves submitted query parameters', function () {
    foreach (range(1, 7) as $number) {
        homepageSearchVacancy(sprintf('Homepage pagina %02d', $number), ['published_at' => now()->subMinutes($number)]);
    }

    $this->get(route('home', ['zoek' => 'Homepage pagina']))
        ->assertOk()
        ->assertSee('Homepage pagina 01')
        ->assertSee('Homepage pagina 06')
        ->assertDontSee('Homepage pagina 07')
        ->assertSee('zoek=Homepage%20pagina', false);

    $this->get(route('home', ['zoek' => 'Homepage pagina', 'page' => 2]))
        ->assertOk()
        ->assertSee('Homepage pagina 07')
        ->assertDontSee('Homepage pagina 06');
});

test('the shared public shell is rendered on public pages', function () {
    BlogPost::factory()->published()->create(['title' => 'Publiek artikel']);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Hoofdnavigatie')
        ->assertSee('Footer navigatie')
        ->assertSee('href="'.route('filament.dashboard.auth.login').'"', false);
});

test('the vacancy index retains its auto-submit filter interaction', function () {
    $this->get(route('vacancies.index'))
        ->assertOk()
        ->assertSee('action="'.route('vacancies.index').'"', false)
        ->assertSee('requestSubmit()', false);
});

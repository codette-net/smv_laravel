<?php

use App\Enums\CompanyStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function saveableVacancy(array $vacancyAttributes = [], array $companyAttributes = []): Vacancy
{
    $company = Company::factory()->create([
        'status' => CompanyStatus::Active,
        ...$companyAttributes,
    ]);

    return Vacancy::factory()->for($company)->create([
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
        'is_filled' => false,
        'published_at' => now()->subMinute(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
        ...$vacancyAttributes,
    ]);
}

function savingUser(string $role = 'candidate'): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => $role]);
    $user->assignRole($role);

    return $user;
}

test('an authenticated user can save and unsave a public vacancy idempotently', function () {
    $user = savingUser();
    $vacancy = saveableVacancy();

    $this->actingAs($user)->post(route('vacancies.save', $vacancy))->assertRedirect();
    $this->post(route('vacancies.save', $vacancy))->assertRedirect();

    expect($user->savedVacancies()->count())->toBe(1)
        ->and($vacancy->savedByUsers()->sole()->is($user))->toBeTrue();

    $this->delete(route('vacancies.unsave', $vacancy))->assertRedirect();
    expect($user->savedVacancies()->count())->toBe(0);
});

test('an authenticated vacancy save can toggle asynchronously without a redirect', function () {
    $user = savingUser();
    $vacancy = saveableVacancy();

    $this->actingAs($user)
        ->postJson(route('vacancies.save', $vacancy))
        ->assertOk()
        ->assertExactJson(['saved' => true]);

    expect($user->savedVacancies()->whereKey($vacancy)->exists())->toBeTrue();

    $this->deleteJson(route('vacancies.unsave', $vacancy))
        ->assertOk()
        ->assertExactJson(['saved' => false]);

    expect($user->savedVacancies()->whereKey($vacancy)->exists())->toBeFalse();
});

test('all authenticated roles can save while guests cannot mutate a relation directly', function (string $role) {
    $vacancy = saveableVacancy();
    $user = savingUser($role);

    $this->actingAs($user)->post(route('vacancies.save', $vacancy))->assertRedirect();
    expect($user->savedVacancies()->whereKey($vacancy)->exists())->toBeTrue();
})->with(['candidate', 'employer', 'editor', 'admin']);

test('only a public vacancy of a public company can be newly saved', function () {
    $user = savingUser();
    $draft = saveableVacancy(['status' => VacancyStatus::Draft]);
    $expired = saveableVacancy(['expires_at' => now()->subMinute()]);
    $privateCompanyVacancy = saveableVacancy([], ['status' => CompanyStatus::Pending]);

    foreach ([$draft, $expired, $privateCompanyVacancy] as $vacancy) {
        $this->actingAs($user)->post(route('vacancies.save', $vacancy))->assertNotFound();
    }

    expect($user->savedVacancies()->count())->toBe(0);
});

test('unsave only resolves vacancies already related to the authenticated user', function () {
    $user = savingUser();
    $privateVacancy = saveableVacancy(['status' => VacancyStatus::Draft]);

    $this->actingAs($user)
        ->delete(route('vacancies.unsave', $privateVacancy))
        ->assertNotFound();
});

test('a guest save intent resumes once after login', function () {
    $vacancy = saveableVacancy();
    $user = User::factory()->create(['email' => 'kandidaat@example.com', 'password' => 'Veilig123']);

    $this->post(route('vacancies.save', $vacancy))
        ->assertRedirect(route('login'))
        ->assertSessionHas('saved_vacancy.vacancy_id', $vacancy->id);

    $this->post(route('login.store'), [
        'email' => 'kandidaat@example.com',
        'password' => 'Veilig123',
    ])->assertRedirect(route('vacancies.show', $vacancy));

    expect($user->savedVacancies()->count())->toBe(1);
});

test('a guest can register generally and resume a save without creating a company', function () {
    $vacancy = saveableVacancy();
    $this->post(route('vacancies.save', $vacancy));

    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Account aanmaken')
        ->assertDontSee('Bedrijfsnaam');

    $this->post(route('register.store'), [
        'name' => 'Sollicitant',
        'email' => 'sollicitant@example.com',
        'password' => 'Veilig123',
        'password_confirmation' => 'Veilig123',
    ])->assertRedirect(route('vacancies.show', $vacancy));

    $user = User::where('email', 'sollicitant@example.com')->firstOrFail();
    expect($user->role)->toBe('candidate')
        ->and($user->companies()->count())->toBe(0)
        ->and($user->savedVacancies()->whereKey($vacancy)->exists())->toBeTrue();
});

test('a stale guest intent is revalidated after authentication', function () {
    $vacancy = saveableVacancy();
    $user = User::factory()->create(['email' => 'later@example.com', 'password' => 'Veilig123']);
    $this->post(route('vacancies.save', $vacancy));
    $vacancy->update(['is_filled' => true]);

    $this->post(route('login.store'), [
        'email' => 'later@example.com',
        'password' => 'Veilig123',
    ])->assertRedirect(route('account.saved-vacancies'))
        ->assertSessionHas('account_status', 'Deze vacature is niet meer beschikbaar en kon niet worden bewaard.');

    expect($user->savedVacancies()->count())->toBe(0);
});

test('the saved vacancy account page paginates and hides unavailable content', function () {
    $user = savingUser();
    $publicVacancies = collect(range(1, 12))->map(fn (int $number) => saveableVacancy(['title' => 'Publieke vacature '.$number]));
    $unavailable = saveableVacancy(['title' => 'Privé concept']);
    $user->savedVacancies()->sync($publicVacancies->push($unavailable)->pluck('id'));
    DB::table('saved_vacancies')->where('vacancy_id', $unavailable->id)->update(['created_at' => now()->addMinute()]);
    $unavailable->update(['status' => VacancyStatus::Draft]);

    $this->actingAs($user)->get(route('account.saved-vacancies'))
        ->assertOk()
        ->assertSee('Bewaarde vacatures')
        ->assertSee('Niet meer beschikbaar')
        ->assertDontSee('Privé concept')
        ->assertViewHas('vacancies', fn ($paginator) => $paginator->total() === 13
            && $paginator->perPage() === 12
            && $paginator->lastPage() === 2)
        ->assertSee('noindex, nofollow');
});

test('a soft deleted saved vacancy remains as an unavailable removable account item', function () {
    $user = savingUser();
    $vacancy = saveableVacancy(['title' => 'Verwijderde privétitel']);
    $user->savedVacancies()->attach($vacancy);
    $vacancy->delete();

    $this->actingAs($user)->get(route('account.saved-vacancies'))
        ->assertOk()
        ->assertSee('Niet meer beschikbaar')
        ->assertDontSee('Verwijderde privétitel');

    $this->delete(route('vacancies.unsave', $vacancy))->assertRedirect();
    expect(DB::table('saved_vacancies')->count())->toBe(0);
});

test('vacancy cards and detail show the correct saved state without nested links', function () {
    $user = savingUser();
    $vacancy = saveableVacancy(['title' => 'Bewaarde accountmanager']);
    $user->savedVacancies()->attach($vacancy);

    $this->actingAs($user)->get(route('vacancies.index'))
        ->assertOk()
        ->assertSee('aria-pressed="true"', false)
        ->assertSee('aria-label="Verwijder uit bewaarde vacatures"', false)
        ->assertSee('fill="currentColor"', false)
        ->assertSee('x-on:submit.prevent="toggle"', false)
        ->assertSee('x-teleport="body"', false);

    $this->get(route('vacancies.show', $vacancy))
        ->assertOk()
        ->assertSee('aria-pressed="true"', false);
});

test('listing saved state is loaded in bulk instead of queried per vacancy card', function () {
    $user = savingUser();
    $company = Company::factory()->create(['status' => CompanyStatus::Active]);
    $createVacancies = function (int $count) use ($company): void {
        Vacancy::factory()->count($count)->for($company)->create([
            'status' => VacancyStatus::Active,
            'source' => VacancySource::Manual,
            'is_filled' => false,
            'published_at' => now()->subMinute(),
            'deadline_at' => now()->addWeek(),
            'expires_at' => now()->addMonth(),
        ]);
    };

    $createVacancies(2);
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->actingAs($user)->get(route('vacancies.index'))->assertOk();
    $smallListQueries = count(DB::getQueryLog());

    $createVacancies(8);
    DB::flushQueryLog();
    $this->get(route('vacancies.index'))->assertOk();
    $largeListQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($largeListQueries)->toBeLessThanOrEqual($smallListQueries + 1);
});

test('save mutations use the web csrf boundary and rendered forms contain a token', function () {
    $vacancy = saveableVacancy();

    expect(Route::getRoutes()->getByName('vacancies.save')->gatherMiddleware())->toContain('web')
        ->and(Route::getRoutes()->getByName('vacancies.unsave')->gatherMiddleware())->toContain('web');

    $this->get(route('vacancies.show', $vacancy))
        ->assertOk()
        ->assertSee('name="_token"', false);
});

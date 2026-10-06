<?php

use App\Enums\CompanyStatus;
use App\Filament\Widgets\SavedContentStatisticsWidget;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\Analytics\SavedContentStatistics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function statisticsUser(string $role = 'candidate'): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => $role]);
    $user->assignRole($role);

    return $user;
}

test('current save statistics aggregate unique pivot state and react to unsaves', function () {
    $statistics = app(SavedContentStatistics::class);
    $userA = statisticsUser();
    $userB = statisticsUser();
    $company = Company::factory()->create();
    $vacancy = Vacancy::factory()->for($company)->create();

    expect($statistics->currentVacancySaves())->toBe(0)
        ->and($statistics->currentCompanySaves())->toBe(0);

    $userA->savedVacancies()->syncWithoutDetaching([$vacancy->id]);
    $userA->savedVacancies()->syncWithoutDetaching([$vacancy->id]);
    $userB->savedVacancies()->syncWithoutDetaching([$vacancy->id]);
    $userA->savedCompanies()->syncWithoutDetaching([$company->id]);
    $userA->savedCompanies()->syncWithoutDetaching([$company->id]);
    $userB->savedCompanies()->syncWithoutDetaching([$company->id]);

    expect($statistics->currentVacancySaves())->toBe(2)
        ->and($statistics->currentCompanySaves())->toBe(2)
        ->and($statistics->topVacancies()->sole()->saved_by_users_count)->toBe(2)
        ->and($statistics->topCompanies()->sole()->saved_by_users_count)->toBe(2);

    $userA->savedVacancies()->detach($vacancy);
    $userA->savedCompanies()->detach($company);

    expect($statistics->currentVacancySaves())->toBe(1)
        ->and($statistics->currentCompanySaves())->toBe(1);
});

test('top saved rankings are database aggregates with deterministic order', function () {
    $users = collect(range(1, 3))->map(fn () => statisticsUser());
    $companyLow = Company::factory()->create(['name' => 'Minder bewaard']);
    $companyHigh = Company::factory()->create(['name' => 'Meest bewaard']);
    $vacancyLow = Vacancy::factory()->for($companyLow)->create(['title' => 'Minder bewaarde vacature']);
    $vacancyHigh = Vacancy::factory()->for($companyHigh)->create(['title' => 'Meest bewaarde vacature']);

    $users[0]->savedVacancies()->attach([$vacancyLow->id, $vacancyHigh->id]);
    $users[1]->savedVacancies()->attach($vacancyHigh);
    $users[2]->savedVacancies()->attach($vacancyHigh);
    $users[0]->savedCompanies()->attach([$companyLow->id, $companyHigh->id]);
    $users[1]->savedCompanies()->attach($companyHigh);

    $statistics = app(SavedContentStatistics::class);

    expect($statistics->topVacancies()->first()->is($vacancyHigh))->toBeTrue()
        ->and($statistics->topVacancies()->first()->saved_by_users_count)->toBe(3)
        ->and($statistics->topCompanies()->first()->is($companyHigh))->toBeTrue()
        ->and($statistics->topCompanies()->first()->saved_by_users_count)->toBe(2);
});

test('soft deleted content is excluded from current internal save statistics', function () {
    $user = statisticsUser();
    $company = Company::factory()->create();
    $vacancy = Vacancy::factory()->for($company)->create();
    $user->savedVacancies()->attach($vacancy);
    $user->savedCompanies()->attach($company);
    $vacancy->delete();
    $company->delete();

    $statistics = app(SavedContentStatistics::class);

    expect($statistics->currentVacancySaves())->toBe(0)
        ->and($statistics->currentCompanySaves())->toBe(0)
        ->and($statistics->topVacancies())->toBeEmpty()
        ->and($statistics->topCompanies())->toBeEmpty();
});

test('the aggregate widget is restricted to existing Filament staff roles', function (string $role, bool $allowed) {
    $this->actingAs(statisticsUser($role));

    expect(SavedContentStatisticsWidget::canView())->toBe($allowed);
})->with([
    'super admin' => ['super-admin', true],
    'admin' => ['admin', true],
    'editor' => ['editor', true],
    'employer' => ['employer', false],
    'candidate' => ['candidate', false],
]);

test('the staff widget renders aggregate rankings without saver identities', function () {
    $admin = statisticsUser('admin');
    $saver = statisticsUser();
    $company = Company::factory()->create(['name' => 'Bewaarde organisatie', 'status' => CompanyStatus::Active]);
    $vacancy = Vacancy::factory()->for($company)->create(['title' => 'Bewaarde functie']);
    $saver->savedVacancies()->attach($vacancy);
    $saver->savedCompanies()->attach($company);

    $this->actingAs($admin);
    Livewire::test(SavedContentStatisticsWidget::class)
        ->assertSee('Actuele bewaarstatistieken')
        ->assertSee('Bewaarde functie')
        ->assertSee('Bewaarde organisatie')
        ->assertDontSee($saver->name)
        ->assertDontSee($saver->email);
});

test('save counts and saver identities are not added to public cards', function () {
    $saver = statisticsUser();
    $company = Company::factory()->create(['status' => CompanyStatus::Active]);
    $vacancy = Vacancy::factory()->for($company)->create();
    $saver->savedVacancies()->attach($vacancy);
    $saver->savedCompanies()->attach($company);

    $this->get(route('vacancies.index'))
        ->assertOk()
        ->assertDontSee('Actuele bewaarstatistieken')
        ->assertDontSee($saver->email);

    $this->get(route('companies.index'))
        ->assertOk()
        ->assertDontSee('Actuele bewaarstatistieken')
        ->assertDontSee($saver->email);
});

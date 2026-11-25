<?php

use App\Actions\Orders\CreateOrder;
use App\Enums\AdvertisingPackage;
use App\Enums\ApplicationMode;
use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\VacancyStatus;
use App\Filament\Pages\VacancyRanking;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Models\Company;
use App\Models\Package;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\Vacancies\VacancyFilterOptions;
use App\Support\Vacancies\VacancySearch;
use Database\Seeders\PackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    collect(['super-admin', 'admin', 'editor', 'employer', 'candidate'])
        ->each(fn (string $role) => Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
});

function rankingUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function rankingVacancy(Company $company, array $attributes = []): Vacancy
{
    return Vacancy::factory()->for($company)->create([
        'title' => 'Ranking vacature '.fake()->unique()->numerify('####'),
        'status' => VacancyStatus::Active,
        'application_mode' => ApplicationMode::Internal,
        'published_at' => now()->subDay(),
        'deadline_at' => now()->addMonth(),
        'expires_at' => now()->addMonths(2),
        'is_filled' => false,
        'commercial_priority_adjustment' => 0,
        ...$attributes,
    ]);
}

test('commercial priority is additive while package priority fails closed without entitlement', function (): void {
    $this->seed(PackageSeeder::class);
    $company = Company::factory()->create(['commercial_priority' => 7]);
    $vacancy = rankingVacancy($company, [
        'placement_package' => AdvertisingPackage::Superior,
        'commercial_priority_adjustment' => -3,
    ]);
    $superior = Package::query()->where('slug', AdvertisingPackage::Superior->value)->firstOrFail();

    app(CreateOrder::class)->execute(
        $company->user,
        $company,
        [['package' => $superior, 'vacancy' => $vacancy]],
        OrderStatus::Paid,
    );

    $ranked = Vacancy::query()->withCommercialPriority()->findOrFail($vacancy->id);

    expect($superior->commercial_priority)->toBe(20)
        ->and($ranked->commercialPriorityComponents())->toBe([
            'package' => 0,
            'company' => 7,
            'vacancy' => -3,
            'effective' => 4,
        ])
        ->and($ranked->placement_package)->toBe(AdvertisingPackage::Superior);
});

test('default public ordering applies manual priority after eligibility and explicit sorts remain authoritative', function (): void {
    $company = Company::factory()->create([
        'status' => CompanyStatus::Active,
        'commercial_priority' => 5,
    ]);
    $high = rankingVacancy($company, [
        'title' => 'Ranking match Zebra hoog',
        'published_at' => now()->subWeek(),
        'commercial_priority_adjustment' => 15,
        'is_featured' => false,
    ]);
    $newer = rankingVacancy($company, [
        'title' => 'Ranking match Alfa nieuw',
        'published_at' => now(),
        'commercial_priority_adjustment' => -5,
        'is_featured' => true,
    ]);
    $hidden = rankingVacancy($company, [
        'title' => 'Ranking match Verborgen',
        'status' => VacancyStatus::Draft,
        'commercial_priority_adjustment' => 1000,
    ]);
    rankingVacancy($company, [
        'title' => 'Volstrekt andere zoekterm',
        'commercial_priority_adjustment' => 1000,
    ]);
    $publishedAt = $high->published_at->copy();

    $this->get(route('vacancies.index', ['zoek' => 'Ranking match']))
        ->assertOk()
        ->assertSeeInOrder([$high->title, $newer->title])
        ->assertDontSee($hidden->title)
        ->assertDontSee('Volstrekt andere zoekterm');

    $this->get(route('vacancies.index', ['zoek' => 'Ranking match', 'sort' => 'nieuwste']))
        ->assertOk()
        ->assertSeeInOrder([$newer->title, $high->title]);

    $this->get(route('vacancies.index', ['zoek' => 'Ranking match', 'sort' => 'az']))
        ->assertOk()
        ->assertSeeInOrder([$newer->title, $high->title]);

    expect($high->fresh()->published_at?->equalTo($publishedAt))->toBeTrue()
        ->and($high->fresh()->is_featured)->toBeFalse()
        ->and($newer->fresh()->is_featured)->toBeTrue();
});

test('filters run before ranking and pagination remains deterministic', function (): void {
    $company = Company::factory()->create(['commercial_priority' => 0]);
    $utrecht = rankingVacancy($company, [
        'title' => 'Gefilterde ranking Utrecht',
        'location' => 'Utrecht',
        'commercial_priority_adjustment' => -10,
    ]);
    $amsterdam = rankingVacancy($company, [
        'title' => 'Gefilterde ranking Amsterdam',
        'location' => 'Amsterdam',
        'commercial_priority_adjustment' => 100,
    ]);

    $this->get(route('vacancies.index', ['zoek' => 'Gefilterde ranking', 'locatie' => 'Utrecht']))
        ->assertOk()
        ->assertSee($utrecht->title)
        ->assertDontSee($amsterdam->title);

    $sameTimestamp = now()->subDays(2);
    $vacancies = collect(range(1, 14))->map(fn (int $number): Vacancy => rankingVacancy($company, [
        'title' => sprintf('Deterministische ranking %02d', $number),
        'published_at' => $sameTimestamp,
        'commercial_priority_adjustment' => 0,
    ]));
    $filters = app(VacancyFilterOptions::class)->emptyFilters();
    $filters['zoek'] = 'Deterministische ranking';
    $query = app(VacancySearch::class)->query($filters, 'nieuwste');
    $firstPage = (clone $query)->forPage(1, 6)->pluck('vacancies.id');
    $secondPage = (clone $query)->forPage(2, 6)->pluck('vacancies.id');

    expect($firstPage->intersect($secondPage))->toBeEmpty()
        ->and($firstPage->all())->toBe($vacancies->sortByDesc('id')->take(6)->pluck('id')->all())
        ->and($secondPage->all())->toBe($vacancies->sortByDesc('id')->slice(6, 6)->pluck('id')->all());
});

test('the homepage uses commercial priority for its general vacancy result section', function (): void {
    $company = Company::factory()->create(['commercial_priority' => 0]);
    $high = rankingVacancy($company, [
        'title' => 'Homepage ranking hoog',
        'published_at' => now()->subWeek(),
        'commercial_priority_adjustment' => 20,
    ]);
    $newer = rankingVacancy($company, [
        'title' => 'Homepage ranking nieuwste',
        'published_at' => now(),
        'commercial_priority_adjustment' => 0,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder([$high->title, $newer->title]);
});

test('authorized staff can edit and bulk manage vacancy ranking from the paginated Filament page', function (): void {
    $admin = rankingUser('admin');
    $company = Company::factory()->create(['commercial_priority' => 3]);
    $first = rankingVacancy($company, ['commercial_priority_adjustment' => 1]);
    $second = rankingVacancy($company, ['commercial_priority_adjustment' => -2]);
    $publishedAt = $first->published_at->copy();
    $featured = $first->is_featured;

    $component = Livewire::actingAs($admin)->test(VacancyRanking::class)
        ->assertCanSeeTableRecords([$first, $second])
        ->assertTableColumnStateSet('effective_priority', 4, $first)
        ->call('updateTableColumnState', 'commercial_priority_adjustment', (string) $first->id, 8);

    expect($first->fresh()->commercial_priority_adjustment)->toBe(8);

    $component->callTableBulkAction('setPriority', [$first, $second], ['value' => 5]);
    expect($first->fresh()->commercial_priority_adjustment)->toBe(5)
        ->and($second->fresh()->commercial_priority_adjustment)->toBe(5);

    $component->callTableBulkAction('addPriority', [$first, $second], ['value' => -2]);
    expect($first->fresh()->commercial_priority_adjustment)->toBe(3)
        ->and($second->fresh()->commercial_priority_adjustment)->toBe(3);

    $component->callTableBulkAction('resetPriority', [$first, $second]);
    expect($first->fresh()->commercial_priority_adjustment)->toBe(0)
        ->and($second->fresh()->commercial_priority_adjustment)->toBe(0)
        ->and($first->fresh()->published_at?->equalTo($publishedAt))->toBeTrue()
        ->and($first->fresh()->is_featured)->toBe($featured);
});

test('the ranking page supports search filters effective sorting and large pagination options', function (): void {
    $admin = rankingUser('admin');
    $firstCompany = Company::factory()->create(['name' => 'Rankingbedrijf A', 'commercial_priority' => 2]);
    $secondCompany = Company::factory()->create(['name' => 'Rankingbedrijf B', 'commercial_priority' => -2]);
    $high = rankingVacancy($firstCompany, [
        'title' => 'Zoekbare rankingreferentie',
        'source_reference' => 'RANK-SEARCH-001',
        'placement_package' => AdvertisingPackage::Superior,
        'commercial_priority_adjustment' => 8,
    ]);
    $low = rankingVacancy($secondCompany, [
        'title' => 'Andere rankingvacature',
        'placement_package' => AdvertisingPackage::Standard,
        'commercial_priority_adjustment' => 0,
    ]);

    Livewire::actingAs($admin)->test(VacancyRanking::class)
        ->assertCanSeeTableRecords([$high, $low])
        ->sortTable('effective_priority', 'desc')
        ->assertCanSeeTableRecords([$high, $low], inOrder: true)
        ->searchTable('RANK-SEARCH-001')
        ->assertCanSeeTableRecords([$high])
        ->assertCanNotSeeTableRecords([$low])
        ->searchTable()
        ->filterTable('company', $firstCompany->id)
        ->assertCanSeeTableRecords([$high])
        ->assertCanNotSeeTableRecords([$low])
        ->assertSet('tableRecordsPerPage', 50);
});

test('Company priority is inline editable and ranking authorization excludes employers and candidates', function (): void {
    $admin = rankingUser('admin');
    $editor = rankingUser('editor');
    $employer = rankingUser('employer');
    $candidate = rankingUser('candidate');
    $company = Company::factory()->create(['commercial_priority' => 0]);
    $vacancy = rankingVacancy($company);

    expect(Gate::forUser($admin)->allows('manageRanking', $vacancy))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('manageRanking', $vacancy))->toBeTrue()
        ->and(Gate::forUser($employer)->allows('manageRanking', $vacancy))->toBeFalse()
        ->and(Gate::forUser($candidate)->allows('manageRanking', $vacancy))->toBeFalse()
        ->and(Gate::forUser($employer)->allows('manageRanking', $company))->toBeFalse();

    Livewire::actingAs($admin)
        ->test(ListCompanies::class)
        ->call('updateTableColumnState', 'commercial_priority', (string) $company->id, -12);

    expect($company->fresh()->commercial_priority)->toBe(-12)
        ->and(Vacancy::query()->withCommercialPriority()->findOrFail($vacancy->id)->commercialPriorityComponents()['effective'])->toBe(-12);

    $this->actingAs($employer)
        ->get(VacancyRanking::getUrl())
        ->assertForbidden();
});

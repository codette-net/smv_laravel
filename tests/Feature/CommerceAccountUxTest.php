<?php

use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\PackageType;
use App\Enums\PaymentStatus;
use App\Enums\VacancyStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Application;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PlacementCredit;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\Commerce\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    collect(['admin', 'candidate', 'employer'])
        ->each(fn (string $role) => Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
});

function commerceAccountUser(string $role = 'candidate'): User
{
    $user = User::factory()->create(['role' => $role]);
    $user->assignRole($role);

    return $user;
}

function commerceAccountOrder(User $owner, Company $company, array $attributes = []): Order
{
    return Order::factory()->for($owner)->for($company)->create([
        'order_number' => 'SMV-UX-'.strtoupper(fake()->unique()->bothify('####??')),
        'status' => OrderStatus::Paid,
        'subtotal_cents' => 18_900,
        'vat_cents' => 3_969,
        'total_cents' => 22_869,
        'currency' => 'EUR',
        'vat_rate_basis_points' => 2100,
        ...$attributes,
    ]);
}

function commerceAccountCredit(
    User $owner,
    Company $company,
    ?Vacancy $vacancy = null,
    array $attributes = [],
): PlacementCredit {
    $order = commerceAccountOrder($owner, $company);
    $item = OrderItem::factory()->for($order)->create([
        'vacancy_id' => $vacancy?->id,
        'title_snapshot' => 'Standaard',
        'total_cents' => $order->total_cents,
    ]);

    return PlacementCredit::query()->create([
        'company_id' => $company->id,
        'order_id' => $order->id,
        'order_item_id' => $item->id,
        'package_id' => null,
        'vacancy_id' => $vacancy?->id,
        'grant_sequence' => 1,
        'product_code' => 'standard',
        'package_name_snapshot' => 'Standaard',
        'package_type_snapshot' => PackageType::Placement->value,
        'duration_days_snapshot' => 60,
        'commercial_priority_snapshot' => 0,
        'benefits_snapshot' => [],
        'granted_at' => now()->subDay(),
        'allocated_at' => $vacancy === null ? null : now()->subHours(12),
        ...$attributes,
    ]);
}

test('candidate accounts lead with candidate activity and do not pretend to have an employer context', function (): void {
    $candidate = commerceAccountUser();
    $publicCompany = Company::factory()->create(['status' => CompanyStatus::Active]);
    $vacancy = Vacancy::factory()->for($publicCompany)->create(['status' => VacancyStatus::Active]);
    Application::factory()->for($vacancy)->create(['candidate_id' => $candidate->id]);
    $candidate->savedVacancies()->attach($vacancy);
    $candidate->savedCompanies()->attach($publicCompany);

    $this->actingAs($candidate)->get(route('account.index'))
        ->assertOk()
        ->assertSeeInOrder(['Werkzoekende', 'Mijn overzicht', 'Mijn sollicitaties', 'Bewaarde vacatures', 'Bewaarde bedrijven'])
        ->assertSee('Wilt u namens een bedrijf een vacature plaatsen?')
        ->assertDontSee('Werkgeversoverzicht');
});

test('company ownership makes the account employer first while preserving candidate activity', function (): void {
    $user = commerceAccountUser();
    $company = Company::factory()->for($user)->create([
        'name' => 'Dubbel Context Bedrijf',
        'status' => CompanyStatus::Pending,
    ]);
    $pendingVacancy = Vacancy::factory()->for($company)->create([
        'title' => 'Vacature in beoordeling',
        'status' => VacancyStatus::Pending,
    ]);
    Vacancy::factory()->for($company)->create([
        'title' => 'Concept met actie',
        'status' => VacancyStatus::Draft,
    ]);
    $applicationCompany = Company::factory()->create(['status' => CompanyStatus::Active]);
    $applicationVacancy = Vacancy::factory()->for($applicationCompany)->create(['status' => VacancyStatus::Active]);
    Application::factory()->for($applicationVacancy)->create(['candidate_id' => $user->id]);
    $order = commerceAccountOrder($user, $company, [
        'order_number' => 'SMV-UX-DASHBOARD',
        'status' => OrderStatus::Cancelled,
    ]);
    OrderItem::factory()->for($order)->create([
        'vacancy_id' => $pendingVacancy->id,
        'title_snapshot' => 'Standaard',
    ]);

    $this->actingAs($user)->get(route('account.index'))
        ->assertOk()
        ->assertSeeInOrder(['Werkgeversoverzicht', 'Mijn bedrijven', 'Recente vacatures', 'Plaatsingsrechten', 'Recente bestellingen', 'Ook in dit account'])
        ->assertSee('Dubbel Context Bedrijf')
        ->assertSee('Uw bedrijf wacht op goedkeuring')
        ->assertSee('Deze vacature wacht op goedkeuring door SMV')
        ->assertSee('SMV-UX-DASHBOARD')
        ->assertSee('Pakket: Standaard')
        ->assertSee('Vacature: Vacature in beoordeling')
        ->assertSee('Bestelling: Geannuleerd')
        ->assertSee('€ 228,69')
        ->assertSee('Mijn sollicitaties')
        ->assertViewHas('employerStats', fn (array $stats): bool => $stats['awaitingApproval'] === 1
            && $stats['attention'] >= 2);
});

test('placement overview counts lifecycle states and explains real activation blockers', function (): void {
    $owner = commerceAccountUser('employer');
    $pendingCompany = Company::factory()->for($owner)->create(['status' => CompanyStatus::Pending]);
    $activeCompany = Company::factory()->for($owner)->create(['status' => CompanyStatus::Active]);
    $pendingVacancy = Vacancy::factory()->for($pendingCompany)->create(['status' => VacancyStatus::Pending]);
    $activeVacancy = Vacancy::factory()->for($activeCompany)->create(['status' => VacancyStatus::Active]);
    $scheduledVacancy = Vacancy::factory()->for($activeCompany)->create([
        'status' => VacancyStatus::Active,
        'published_at' => now()->addDays(2),
    ]);
    $reviewVacancy = Vacancy::factory()->for($activeCompany)->create(['status' => VacancyStatus::Active]);

    commerceAccountCredit($owner, $activeCompany);
    commerceAccountCredit($owner, $pendingCompany, $pendingVacancy);
    commerceAccountCredit($owner, $activeCompany, $activeVacancy, [
        'activated_at' => now()->subDay(),
        'expires_at' => now()->addDays(59),
    ]);
    commerceAccountCredit($owner, $activeCompany, $scheduledVacancy, [
        'activated_at' => now()->addDays(2),
        'expires_at' => now()->addDays(62),
    ]);
    commerceAccountCredit($owner, $activeCompany, $reviewVacancy, [
        'activated_at' => now()->subDay(),
        'expires_at' => now()->addDays(59),
        'review_required_at' => now(),
        'review_reason' => 'Terugbetaling bevestigd; plaatsing vereist controle.',
    ]);

    $this->actingAs($owner)->get(route('account.placements'))
        ->assertOk()
        ->assertViewHas('creditCounts', [
            'available' => 1,
            'assigned' => 2,
            'active' => 1,
            'review' => 1,
        ])
        ->assertSee('Van betaling naar publicatie')
        ->assertSee('Het bedrijf wacht op goedkeuring')
        ->assertSee('De publicatie staat gepland voor')
        ->assertSee('De betaling is terugbetaald')
        ->assertSee('publicatie wijzigt niet automatisch');
});

test('order history shows package vacancy Dutch money and separate financial and placement states', function (): void {
    $owner = commerceAccountUser('employer');
    $company = Company::factory()->for($owner)->create();
    $vacancy = Vacancy::factory()->for($company)->create(['title' => 'Accountmanager SaaS']);
    $refunded = commerceAccountOrder($owner, $company, [
        'order_number' => 'SMV-UX-REFUNDED',
        'status' => OrderStatus::Refunded,
    ]);
    $item = OrderItem::factory()->for($refunded)->create([
        'vacancy_id' => $vacancy->id,
        'title_snapshot' => 'Superior',
    ]);
    Payment::factory()->for($refunded)->create([
        'status' => PaymentStatus::Refunded,
        'amount_cents' => $refunded->total_cents,
    ]);
    PlacementCredit::query()->create([
        'company_id' => $company->id,
        'order_id' => $refunded->id,
        'order_item_id' => $item->id,
        'vacancy_id' => $vacancy->id,
        'grant_sequence' => 1,
        'product_code' => 'superior',
        'package_name_snapshot' => 'Superior',
        'package_type_snapshot' => PackageType::Placement->value,
        'duration_days_snapshot' => 60,
        'commercial_priority_snapshot' => 20,
        'granted_at' => now()->subWeek(),
        'allocated_at' => now()->subWeek(),
        'activated_at' => now()->subDays(6),
        'expires_at' => now()->addDays(54),
        'review_required_at' => now(),
        'review_reason' => 'Financieel terugbetaald; operationele controle open.',
    ]);
    commerceAccountOrder($owner, $company, [
        'order_number' => 'SMV-UX-CANCELLED',
        'status' => OrderStatus::Cancelled,
    ])->orderItems()->save(OrderItem::factory()->make(['title_snapshot' => 'Standaard']));
    $foreignOwner = commerceAccountUser('employer');
    $foreignCompany = Company::factory()->for($foreignOwner)->create();
    commerceAccountOrder($foreignOwner, $foreignCompany, ['order_number' => 'SMV-UX-FOREIGN']);

    $this->actingAs($owner)->get(route('account.orders.index'))
        ->assertOk()
        ->assertSee('SMV-UX-REFUNDED')
        ->assertSee('Superior')
        ->assertSee('Accountmanager SaaS')
        ->assertSee('€ 228,69')
        ->assertSee('Bestelling: Terugbetaald')
        ->assertSee('Betaling: Terugbetaald')
        ->assertSee('Plaatsing: controle nodig')
        ->assertSee('De financiële terugbetaling is voltooid')
        ->assertSee('SMV-UX-CANCELLED')
        ->assertSee('Bestelling: Geannuleerd')
        ->assertDontSee('SMV-UX-FOREIGN');
});

test('staff order pages expose the existing actions and the unresolved operational review', function (): void {
    $admin = commerceAccountUser('admin');
    $owner = commerceAccountUser('employer');
    $company = Company::factory()->for($owner)->create();
    $vacancy = Vacancy::factory()->for($company)->create();
    $credit = commerceAccountCredit($owner, $company, $vacancy, [
        'activated_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
        'review_required_at' => now(),
        'review_reason' => 'Handmatige plaatsingsbeslissing nodig.',
    ]);
    $credit->order->update(['status' => OrderStatus::Refunded]);

    $this->actingAs($admin)->get(OrderResource::getUrl('index'))
        ->assertOk()
        ->assertSee('Bekijken / acties');

    $this->get(OrderResource::getUrl('view', ['record' => $credit->order]))
        ->assertOk()
        ->assertSee('Operationele plaatsingscontrole')
        ->assertSee('De financiële terugbetaling is bevestigd')
        ->assertSee('herstel of verleng niets automatisch');
});

test('commerce labels and Dutch EUR formatting have one stable presentation contract', function (): void {
    expect(Money::format(22_869, 'eur'))->toBe('€ 228,69')
        ->and(Money::format(22_869, 'USD'))->toBe('USD 228,69')
        ->and(OrderStatus::PendingPayment->getLabel())->toBe('Wacht op betaling')
        ->and(OrderStatus::Cancelled->badgeVariant())->toBe('danger')
        ->and(PaymentStatus::Refunded->getLabel())->toBe('Terugbetaald')
        ->and(PaymentStatus::Expired->badgeVariant())->toBe('neutral');
});

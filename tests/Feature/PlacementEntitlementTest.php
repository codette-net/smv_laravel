<?php

use App\Actions\Orders\CreateOrder;
use App\Actions\Payments\ConfirmPayment;
use App\Actions\Placements\AllocatePlacementCredit;
use App\Actions\Placements\GrantPaidOrderPlacementCredits;
use App\Enums\AdvertisingPackage;
use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PlacementCreditEventType;
use App\Enums\PlacementCreditStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Filament\Resources\PlacementCredits\PlacementCreditResource;
use App\Models\Company;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PlacementCredit;
use App\Models\PlacementCreditEvent;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\PackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    collect(['super-admin', 'admin', 'editor', 'employer'])
        ->each(fn (string $role) => Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
    $this->seed(PackageSeeder::class);
});

function entitlementEmployer(string $role = 'employer'): User
{
    $user = User::factory()->create(['role' => $role]);
    $user->assignRole($role);

    return $user;
}

function entitlementCompany(User $owner, array $attributes = []): Company
{
    return Company::factory()->for($owner)->create([
        'status' => CompanyStatus::Active,
        'commercial_priority' => 0,
        ...$attributes,
    ]);
}

function entitlementVacancy(Company $company, AdvertisingPackage $package, array $attributes = []): Vacancy
{
    return Vacancy::factory()->for($company)->create([
        'status' => VacancyStatus::Draft,
        'source' => VacancySource::Manual,
        'source_reference' => null,
        'import_source_id' => null,
        'is_filled' => false,
        'published_at' => null,
        'deadline_at' => now()->addMonth(),
        'expires_at' => null,
        'placement_package' => $package,
        ...$attributes,
    ]);
}

function entitlementOrder(
    User $owner,
    Company $company,
    AdvertisingPackage $package,
    ?Vacancy $vacancy = null,
    int $quantity = 1,
): Order {
    return app(CreateOrder::class)->execute($owner, $company, [[
        'package' => $package,
        'vacancy' => $vacancy,
        'quantity' => $quantity,
    ]], OrderStatus::PendingPayment);
}

function confirmEntitlementOrder(Order $order, string $key): Payment
{
    $payment = Payment::factory()->for($order)->create([
        'idempotency_key' => $key,
        'status' => PaymentStatus::Pending,
        'amount_cents' => $order->total_cents,
        'currency' => $order->currency,
    ]);

    return app(ConfirmPayment::class)->execute($payment, providerAuthoritative: true);
}

test('paid confirmation grants every purchased unit once from immutable entitlement snapshots', function (): void {
    $owner = entitlementEmployer();
    $company = entitlementCompany($owner);
    $superior = Package::query()->where('slug', AdvertisingPackage::Superior->value)->firstOrFail();
    $superior->update([
        'placement_quantity' => 2,
        'vacancy_duration_days' => 45,
        'commercial_priority' => 27,
        'features' => ['Historisch voordeel'],
    ]);
    $order = app(CreateOrder::class)->execute($owner, $company, [[
        'package' => $superior,
        'quantity' => 2,
    ]], OrderStatus::PendingPayment);
    $superior->update([
        'placement_quantity' => 1,
        'vacancy_duration_days' => 10,
        'commercial_priority' => 99,
        'features' => ['Gewijzigd voordeel'],
    ]);

    $payment = confirmEntitlementOrder($order, 'entitlement-grant-once');
    app(ConfirmPayment::class)->execute($payment->fresh(), providerAuthoritative: true);

    $item = $order->fresh()->orderItems->sole();
    $credits = PlacementCredit::query()->where('order_id', $order->id)->orderBy('grant_sequence')->get();

    expect($item->placement_quantity_snapshot)->toBe(2)
        ->and($item->vacancy_duration_days_snapshot)->toBe(45)
        ->and($item->commercial_priority_snapshot)->toBe(27)
        ->and($item->entitlement_snapshot['features'])->toBe(['Historisch voordeel'])
        ->and($credits)->toHaveCount(4)
        ->and($credits->pluck('grant_sequence')->all())->toBe([1, 2, 3, 4])
        ->and($credits->every(fn (PlacementCredit $credit): bool => $credit->duration_days_snapshot === 45))->toBeTrue()
        ->and($credits->every(fn (PlacementCredit $credit): bool => $credit->commercial_priority_snapshot === 27))->toBeTrue()
        ->and(PlacementCreditEvent::query()->where('event_type', PlacementCreditEventType::Granted->value)->count())->toBe(4);
});

test('unpaid orders grant nothing and paid reconciliation repairs missing grants idempotently', function (): void {
    $owner = entitlementEmployer();
    $company = entitlementCompany($owner);
    $order = entitlementOrder($owner, $company, AdvertisingPackage::Standard);
    Payment::factory()->for($order)->create([
        'status' => PaymentStatus::Failed,
        'amount_cents' => $order->total_cents,
    ]);

    expect(PlacementCredit::count())->toBe(0);

    $order->update(['status' => OrderStatus::Paid]);
    Artisan::call('placements:reconcile-paid-orders', ['--order' => $order->id]);
    Artisan::call('placements:reconcile-paid-orders', ['--order' => $order->id]);

    expect(PlacementCredit::query()->where('order_id', $order->id)->count())->toBe(1)
        ->and(PlacementCreditEvent::query()->where('event_type', PlacementCreditEventType::Granted->value)->count())->toBe(1);
});

test('a fulfilment failure rolls back the payment and Order paid transition', function (): void {
    $owner = entitlementEmployer();
    $company = entitlementCompany($owner);
    $order = entitlementOrder($owner, $company, AdvertisingPackage::Standard);
    $payment = Payment::factory()->for($order)->create([
        'idempotency_key' => 'failing-fulfilment',
        'status' => PaymentStatus::Pending,
        'amount_cents' => $order->total_cents,
    ]);
    $grant = Mockery::mock(GrantPaidOrderPlacementCredits::class);
    $grant->shouldReceive('execute')->once()->andThrow(new RuntimeException('fulfilment failed'));
    app()->instance(GrantPaidOrderPlacementCredits::class, $grant);

    expect(fn () => app(ConfirmPayment::class)->execute($payment, providerAuthoritative: true))
        ->toThrow(RuntimeException::class, 'fulfilment failed');

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($order->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and(PlacementCredit::count())->toBe(0);
});

test('an incomplete eligible snapshot cannot silently confirm without a credit', function (): void {
    $owner = entitlementEmployer();
    $company = entitlementCompany($owner);
    $order = entitlementOrder($owner, $company, AdvertisingPackage::Standard);
    $order->orderItems()->update(['vacancy_duration_days_snapshot' => null]);
    $payment = Payment::factory()->for($order)->create([
        'idempotency_key' => 'incomplete-confirmation',
        'status' => PaymentStatus::Pending,
        'amount_cents' => $order->total_cents,
    ]);

    expect(fn () => app(ConfirmPayment::class)->execute($payment, providerAuthoritative: true))
        ->toThrow(RuntimeException::class, 'lacks an immutable placement entitlement snapshot');

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($order->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and(PlacementCredit::count())->toBe(0);
});

test('payment allocates a linked pending vacancy without publishing or activating it', function (): void {
    $owner = entitlementEmployer();
    $company = entitlementCompany($owner);
    $vacancy = entitlementVacancy($company, AdvertisingPackage::Superior, ['status' => VacancyStatus::Pending]);
    $order = entitlementOrder($owner, $company, AdvertisingPackage::Superior, $vacancy);

    confirmEntitlementOrder($order, 'linked-pending-placement');
    $credit = PlacementCredit::query()->where('order_id', $order->id)->sole();

    expect($credit->vacancy_id)->toBe($vacancy->id)
        ->and($credit->allocated_at)->not->toBeNull()
        ->and($credit->activated_at)->toBeNull()
        ->and($credit->status())->toBe(PlacementCreditStatus::Allocated)
        ->and($vacancy->fresh()->status)->toBe(VacancyStatus::Pending)
        ->and($vacancy->fresh()->published_at)->toBeNull()
        ->and(Vacancy::publiclyVisible()->whereKey($vacancy)->exists())->toBeFalse();
});

test('Company ownership package and vacancy state protect redemption and duplicate consumption', function (): void {
    $owner = entitlementEmployer();
    $outsider = entitlementEmployer();
    $company = entitlementCompany($owner);
    $vacancy = entitlementVacancy($company, AdvertisingPackage::Standard);
    $order = entitlementOrder($owner, $company, AdvertisingPackage::Standard);
    confirmEntitlementOrder($order, 'available-credit');
    $credit = PlacementCredit::query()->where('order_id', $order->id)->sole();

    expect(fn () => app(AllocatePlacementCredit::class)->execute($outsider, $credit, $vacancy))
        ->toThrow(InvalidArgumentException::class);

    $allocated = app(AllocatePlacementCredit::class)->execute($owner, $credit, $vacancy);
    $same = app(AllocatePlacementCredit::class)->execute($owner, $allocated, $vacancy);

    expect($same->id)->toBe($credit->id)
        ->and(PlacementCredit::query()->where('vacancy_id', $vacancy->id)->count())->toBe(1)
        ->and(PlacementCreditEvent::query()->where('event_type', PlacementCreditEventType::Allocated->value)->count())->toBe(1);

    $secondOrder = entitlementOrder($owner, $company, AdvertisingPackage::Standard);
    confirmEntitlementOrder($secondOrder, 'second-available-credit');
    $secondCredit = PlacementCredit::query()->where('order_id', $secondOrder->id)->sole();
    expect(fn () => app(AllocatePlacementCredit::class)->execute($owner, $secondCredit, $vacancy))
        ->toThrow(InvalidArgumentException::class);

    $wrongPackageVacancy = entitlementVacancy($company, AdvertisingPackage::Superior);
    expect(fn () => app(AllocatePlacementCredit::class)->execute($owner, $secondCredit, $wrongPackageVacancy))
        ->toThrow(InvalidArgumentException::class);

    $archived = entitlementVacancy($company, AdvertisingPackage::Standard, ['status' => VacancyStatus::Archived]);
    expect(fn () => app(AllocatePlacementCredit::class)->execute($owner, $secondCredit, $archived))
        ->toThrow(InvalidArgumentException::class);
});

test('publication activates the paid term once and ordinary edits or reactivation never extend it', function (): void {
    $this->travelTo(now()->startOfDay());
    $owner = entitlementEmployer();
    $company = entitlementCompany($owner);
    $vacancy = entitlementVacancy($company, AdvertisingPackage::Standard, ['status' => VacancyStatus::Pending]);
    $order = entitlementOrder($owner, $company, AdvertisingPackage::Standard, $vacancy);
    confirmEntitlementOrder($order, 'activation-duration');

    $publishedAt = now()->addDays(3);
    $vacancy->update(['status' => VacancyStatus::Active, 'published_at' => $publishedAt]);
    $credit = PlacementCredit::query()->where('vacancy_id', $vacancy->id)->sole();
    $expectedExpiry = $publishedAt->copy()->addDays(60);

    expect($credit->activated_at?->equalTo($publishedAt))->toBeTrue()
        ->and($credit->expires_at?->equalTo($expectedExpiry))->toBeTrue()
        ->and($vacancy->fresh()->expires_at?->equalTo($expectedExpiry))->toBeTrue();

    $vacancy->update(['title' => 'Gewone wijziging']);
    $vacancy->update(['status' => VacancyStatus::Archived]);
    $this->travel(10)->days();
    $vacancy->update(['status' => VacancyStatus::Active]);

    expect($credit->fresh()->activated_at?->equalTo($publishedAt))->toBeTrue()
        ->and($credit->fresh()->expires_at?->equalTo($expectedExpiry))->toBeTrue()
        ->and(PlacementCreditEvent::query()->where('event_type', PlacementCreditEventType::Activated->value)->count())->toBe(1);
});

test('active Superior entitlement supplies SQL package ranking and expiry removes only that component', function (): void {
    $owner = entitlementEmployer();
    $company = entitlementCompany($owner, ['commercial_priority' => 5]);
    $vacancy = entitlementVacancy($company, AdvertisingPackage::Superior, [
        'status' => VacancyStatus::Pending,
        'commercial_priority_adjustment' => -2,
    ]);
    $order = entitlementOrder($owner, $company, AdvertisingPackage::Superior, $vacancy);
    confirmEntitlementOrder($order, 'ranking-entitlement');
    $vacancy->update(['status' => VacancyStatus::Active, 'published_at' => now()]);
    $credit = PlacementCredit::query()->where('vacancy_id', $vacancy->id)->sole();

    expect(Vacancy::query()->withCommercialPriority()->findOrFail($vacancy->id)->commercialPriorityComponents())->toBe([
        'package' => 20,
        'company' => 5,
        'vacancy' => -2,
        'effective' => 23,
    ]);

    $this->travelTo($credit->expires_at->copy()->addSecond());
    expect(Vacancy::query()->withCommercialPriority()->findOrFail($vacancy->id)->commercialPriorityComponents())->toBe([
        'package' => 0,
        'company' => 5,
        'vacancy' => -2,
        'effective' => 3,
    ]);
});

test('manual and imported publication stay compatible without paid credits', function (): void {
    $company = entitlementCompany(entitlementEmployer(), ['commercial_priority' => 3]);
    $manual = entitlementVacancy($company, AdvertisingPackage::Superior, [
        'status' => VacancyStatus::Active,
        'published_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
        'commercial_priority_adjustment' => 2,
    ]);
    $imported = entitlementVacancy($company, AdvertisingPackage::Superior, [
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Import,
        'published_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
        'commercial_priority_adjustment' => 1,
    ]);

    expect(Vacancy::publiclyVisible()->whereKey($manual)->exists())->toBeTrue()
        ->and(Vacancy::publiclyVisible()->whereKey($imported)->exists())->toBeTrue()
        ->and(Vacancy::query()->withCommercialPriority()->findOrFail($manual->id)->commercialPriorityComponents()['package'])->toBe(0)
        ->and(Vacancy::query()->withCommercialPriority()->findOrFail($imported->id)->commercialPriorityComponents()['package'])->toBe(0);
});

test('employer overview is Company scoped and can redeem an available credit for an owned draft', function (): void {
    $owner = entitlementEmployer();
    $other = entitlementEmployer();
    $company = entitlementCompany($owner);
    $otherCompany = entitlementCompany($other);
    $vacancy = entitlementVacancy($company, AdvertisingPackage::Standard, ['title' => 'Eigen concept voor credit']);
    $order = entitlementOrder($owner, $company, AdvertisingPackage::Standard);
    $foreignOrder = entitlementOrder($other, $otherCompany, AdvertisingPackage::Superior);
    confirmEntitlementOrder($order, 'overview-own-credit');
    confirmEntitlementOrder($foreignOrder, 'overview-foreign-credit');
    $credit = PlacementCredit::query()->where('order_id', $order->id)->sole();

    $this->actingAs($owner)->get(route('account.placements'))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Eigen concept voor credit')
        ->assertDontSee($foreignOrder->order_number);

    $this->post(route('account.placements.redeem', $credit), ['vacancy_id' => $vacancy->id])
        ->assertRedirect(route('account.placements'));

    expect($credit->fresh()->vacancy_id)->toBe($vacancy->id)
        ->and($vacancy->fresh()->status)->toBe(VacancyStatus::Draft);
});

test('placement preview can consume an existing credit without creating a duplicate Order', function (): void {
    $owner = entitlementEmployer();
    $company = entitlementCompany($owner);
    $creditOrder = entitlementOrder($owner, $company, AdvertisingPackage::Standard);
    confirmEntitlementOrder($creditOrder, 'preview-existing-credit');
    $credit = PlacementCredit::query()->where('order_id', $creditOrder->id)->sole();
    $vacancy = entitlementVacancy($company, AdvertisingPackage::Standard, ['title' => 'Concept zonder nieuwe bestelling']);

    $this->actingAs($owner)->get(route('vacancy-placement.preview', $vacancy))
        ->assertOk()
        ->assertSee('Beschikbaar plaatsingsrecht')
        ->assertSee('Standaard · 60 dagen');

    $this->post(route('vacancy-placement.submit', $vacancy), [
        'placement_credit_id' => $credit->id,
    ])->assertRedirect(route('vacancy-placement.success', $vacancy));

    expect(Order::count())->toBe(1)
        ->and($credit->fresh()->vacancy_id)->toBe($vacancy->id)
        ->and($vacancy->fresh()->status)->toBe(VacancyStatus::Pending)
        ->and($vacancy->fresh()->published_at)->toBeNull();
});

test('a credit waits for public Company eligibility before activation', function (): void {
    $owner = entitlementEmployer();
    $company = entitlementCompany($owner, ['status' => CompanyStatus::Pending]);
    $vacancy = entitlementVacancy($company, AdvertisingPackage::Standard, ['status' => VacancyStatus::Pending]);
    $order = entitlementOrder($owner, $company, AdvertisingPackage::Standard, $vacancy);
    confirmEntitlementOrder($order, 'company-eligibility');
    $vacancy->update(['status' => VacancyStatus::Active, 'published_at' => now()]);

    expect(PlacementCredit::query()->where('vacancy_id', $vacancy->id)->sole()->activated_at)->toBeNull();

    $company->update(['status' => CompanyStatus::Active]);

    expect(PlacementCredit::query()->where('vacancy_id', $vacancy->id)->sole()->activated_at)->not->toBeNull();
});

test('staff can inspect placement credit audit history while employers cannot enter Filament', function (): void {
    $owner = entitlementEmployer();
    $admin = entitlementEmployer('admin');
    $company = entitlementCompany($owner);
    $order = entitlementOrder($owner, $company, AdvertisingPackage::Standard);
    confirmEntitlementOrder($order, 'filament-credit');
    $credit = PlacementCredit::query()->sole();

    $this->actingAs($admin)->get(PlacementCreditResource::getUrl())->assertOk();
    $this->get(PlacementCreditResource::getUrl('view', ['record' => $credit]))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Toegekend');
    $this->actingAs($owner)->get(PlacementCreditResource::getUrl())->assertForbidden();

    expect(PlacementCreditResource::canCreate())->toBeFalse();
});

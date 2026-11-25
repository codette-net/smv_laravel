<?php

use App\Actions\Orders\CreateOrder;
use App\Enums\AdvertisingPackage;
use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Company;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function orderEmployer(): User
{
    Role::firstOrCreate(['name' => 'employer', 'guard_name' => 'web']);

    $user = User::factory()->create(['role' => 'employer']);
    $user->assignRole('employer');

    return $user;
}

function orderCompany(User $user): Company
{
    return Company::factory()->for($user)->create(['status' => CompanyStatus::Active]);
}

test('order creation snapshots the approved package and placement intent without creating payment', function () {
    $user = orderEmployer();
    $company = orderCompany($user);
    $vacancy = Vacancy::factory()->create([
        'company_id' => $company->id,
        'source' => VacancySource::Manual,
        'status' => VacancyStatus::Draft,
        'published_at' => null,
        'placement_package' => AdvertisingPackage::Standard,
    ]);

    $order = app(CreateOrder::class)->execute($user, $company, [[
        'package' => AdvertisingPackage::Standard,
        'vacancy' => $vacancy,
        'vat_rate_basis_points' => 2100,
    ]], OrderStatus::PendingPayment);

    expect($order->order_number)->toStartWith('SMV-')
        ->and($order->status)->toBe(OrderStatus::PendingPayment)
        ->and($order->user->is($user))->toBeTrue()
        ->and($order->company->is($company))->toBeTrue()
        ->and($order->vacancies->sole()->is($vacancy))->toBeTrue()
        ->and($order->payments)->toBeEmpty()
        ->and($order->subtotal_cents)->toBe(18_900)
        ->and($order->vat_cents)->toBe(3_969)
        ->and($order->total_cents)->toBe(22_869);

    $item = $order->orderItems->sole();
    expect($item->product_code)->toBe('standard')
        ->and($item->title_snapshot)->toBe('Standaard')
        ->and($item->price_cents)->toBe(18_900)
        ->and($item->subtotal_cents)->toBe(18_900)
        ->and($item->vat_cents)->toBe(3_969)
        ->and($item->total_cents)->toBe(22_869)
        ->and($item->currency)->toBe('EUR');

    expect($vacancy->refresh()->placement_package)->toBe(AdvertisingPackage::Standard)
        ->and(Vacancy::publiclyVisible()->whereKey($vacancy)->exists())->toBeFalse();
});

test('package rows are snapshotted and later price changes do not change historical totals', function () {
    $user = orderEmployer();
    $company = orderCompany($user);
    $package = Package::factory()->create([
        'name' => 'Historisch pakket',
        'slug' => 'historisch-pakket',
        'price_cents' => 12_345,
        'currency' => 'EUR',
        'is_active' => true,
    ]);

    $order = app(CreateOrder::class)->execute($user, $company, [['package' => $package]]);
    $package->update(['price_cents' => 99_999, 'name' => 'Nieuw pakketlabel']);

    expect($order->fresh()->subtotal_cents)->toBe(12_345)
        ->and($order->fresh()->vat_cents)->toBe(2_592)
        ->and($order->fresh()->total_cents)->toBe(14_937)
        ->and($order->fresh()->orderItems->sole()->title_snapshot)->toBe('Historisch pakket')
        ->and($order->fresh()->orderItems->sole()->price_cents)->toBe(12_345);
});

test('order money uses integer minor units for quantity and half-up VAT rounding', function () {
    $user = orderEmployer();
    $company = orderCompany($user);

    $order = app(CreateOrder::class)->execute($user, $company, [[
        'package' => AdvertisingPackage::Standard,
        'quantity' => 3,
        'vat_rate_basis_points' => 3333,
    ]]);

    expect($order->subtotal_cents)->toBe(56_700)
        ->and($order->vat_cents)->toBe(18_898)
        ->and($order->total_cents)->toBe(75_598)
        ->and($order->orderItems->sole()->quantity)->toBe(3);

    expect(fn () => app(CreateOrder::class)->execute($user, $company, [[
        'package' => AdvertisingPackage::Standard,
        'quantity' => 0,
    ]]))->toThrow(InvalidArgumentException::class);
});

test('order creation rejects an unavailable product, foreign vacancy and foreign company', function () {
    $user = orderEmployer();
    $company = orderCompany($user);
    $otherUser = orderEmployer();
    $otherCompany = orderCompany($otherUser);
    $foreignVacancy = Vacancy::factory()->create(['company_id' => $otherCompany->id]);

    expect(fn () => app(CreateOrder::class)->execute($user, $company, [['package' => AdvertisingPackage::Custom]]))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => app(CreateOrder::class)->execute($user, $company, [['package' => AdvertisingPackage::Standard, 'vacancy' => $foreignVacancy]]))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => app(CreateOrder::class)->execute($user, $otherCompany, [['package' => AdvertisingPackage::Standard]]))
        ->toThrow(InvalidArgumentException::class);

    expect(Order::count())->toBe(0);
});

test('order creation is atomic when a later line is invalid', function () {
    $user = orderEmployer();
    $company = orderCompany($user);

    expect(fn () => app(CreateOrder::class)->execute($user, $company, [
        ['package' => AdvertisingPackage::Standard],
        ['package' => 'not-a-package'],
    ]))->toThrow(InvalidArgumentException::class);

    expect(Order::count())->toBe(0);
});

test('orders are read-only staff administration and never customer-visible through Filament', function () {
    $admin = User::factory()->create();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin->assignRole('admin');
    $candidate = User::factory()->create();
    $owner = orderEmployer();
    $company = orderCompany($owner);
    $order = app(CreateOrder::class)->execute($owner, $company, [['package' => AdvertisingPackage::Standard]]);

    $this->actingAs($admin)->get(OrderResource::getUrl())->assertSuccessful();
    $this->actingAs($admin)->get(OrderResource::getUrl('view', ['record' => $order]))
        ->assertSuccessful()
        ->assertSee($order->order_number);
    $this->actingAs($candidate)->get(OrderResource::getUrl())->assertForbidden();

    expect(OrderResource::canCreate())->toBeFalse();
});

<?php

use App\Actions\Orders\CreateOrder;
use App\Enums\AdvertisingPackage;
use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\PackageType;
use App\Enums\VacancyStatus;
use App\Filament\Resources\Packages\PackageResource;
use App\Filament\Resources\Packages\Pages\EditPackage;
use App\Models\Company;
use App\Models\Package;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\Commerce\PackageCatalogue;
use Database\Seeders\PackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function seedApprovedPackageCatalogue(): void
{
    app(PackageSeeder::class)->run();
}

test('approved package catalogue stores structured prices durations benefits and validity', function () {
    seedApprovedPackageCatalogue();

    $standard = Package::where('slug', 'standard')->firstOrFail();
    $superior = Package::where('slug', 'superior')->firstOrFail();
    $custom = Package::where('slug', 'custom')->firstOrFail();

    expect(Package::count())->toBe(3)
        ->and($standard->price_cents)->toBe(18_900)
        ->and($standard->currency)->toBe('EUR')
        ->and($standard->vacancy_duration_days)->toBe(60)
        ->and($standard->placement_quantity)->toBe(1)
        ->and($standard->vat_rate_basis_points)->toBe(2100)
        ->and($standard->checkout_eligible)->toBeTrue()
        ->and($standard->valid_until->toDateString())->toBe('2026-12-31')
        ->and($superior->price_cents)->toBe(39_800)
        ->and($superior->package_type)->toBe(PackageType::Placement)
        ->and($superior->commercial_priority)->toBe(20)
        ->and($superior->features)->toContain('Persoonlijke begeleiding en hulp bij de vacaturepresentatie')
        ->and($superior->operational_notes)->toContain('SMV-116')
        ->and($custom->price_cents)->toBe(95_000)
        ->and($custom->package_type)->toBe(PackageType::Custom)
        ->and($custom->is_quote_only)->toBeTrue()
        ->and($custom->checkout_eligible)->toBeFalse()
        ->and($custom->vacancy_duration_days)->toBeNull()
        ->and($custom->presentation()['price'])->toBe('Vanaf € 950');
});

test('package benefits and configurable superior duration drive public presentation', function () {
    seedApprovedPackageCatalogue();

    $superior = Package::where('slug', 'superior')->firstOrFail();
    $superior->update([
        'name' => 'Superior Plus',
        'vacancy_duration_days' => 75,
        'features' => ['Handmatige extra zichtbaarheid', 'Persoonlijke redactiebegeleiding'],
    ]);

    $plan = collect(app(PackageCatalogue::class)->presentation())->firstWhere('value', 'superior');

    expect($plan['name'])->toBe('Superior Plus')
        ->and($plan['features'])->toBe(['Handmatige extra zichtbaarheid', 'Persoonlijke redactiebegeleiding'])
        ->and($superior->fresh()->vacancy_duration_days)->toBe(75);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertSee('Superior Plus')
        ->assertSee('Persoonlijke redactiebegeleiding');
});

test('expired or tax-incomplete packages cannot enter checkout', function () {
    seedApprovedPackageCatalogue();
    config(['commercial.allow_expired_prices_in_non_production' => false]);

    $standard = Package::where('slug', 'standard')->firstOrFail();
    $standard->update(['valid_until' => '2026-11-23']);

    expect($standard->fresh()->hasApprovedPrice())->toBeFalse()
        ->and($standard->fresh()->isAvailableForCheckout())->toBeFalse()
        ->and($standard->fresh()->presentation()['price'])->toBe('Prijs op aanvraag');

    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create(['status' => CompanyStatus::Active]);

    expect(fn () => app(CreateOrder::class)->execute($user, $company, [['package' => $standard->fresh()]]))
        ->toThrow(InvalidArgumentException::class);

    $standard->update(['valid_until' => '2026-12-31', 'vat_rate_basis_points' => null]);

    expect($standard->fresh()->isAvailableForCheckout())->toBeFalse();
    expect(fn () => app(CreateOrder::class)->execute($user, $company, [['package' => $standard->fresh()]]))
        ->toThrow(InvalidArgumentException::class);
});

test('package order snapshots apply configured vat and remain immutable', function () {
    seedApprovedPackageCatalogue();

    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create(['status' => CompanyStatus::Active]);
    $vacancy = Vacancy::factory()->for($company)->create([
        'status' => VacancyStatus::Draft,
        'placement_package' => AdvertisingPackage::Standard,
        'published_at' => null,
    ]);
    $standard = Package::where('slug', 'standard')->firstOrFail();

    $order = app(CreateOrder::class)->execute($user, $company, [[
        'package' => $standard,
        'vacancy' => $vacancy,
    ]], OrderStatus::PendingPayment);

    expect($order->subtotal_cents)->toBe(18_900)
        ->and($order->vat_rate_basis_points)->toBe(2100)
        ->and($order->vat_cents)->toBe(3_969)
        ->and($order->total_cents)->toBe(22_869)
        ->and($order->orderItems->sole()->package_id)->toBe($standard->id)
        ->and($order->payments)->toBeEmpty()
        ->and($vacancy->fresh()->status)->toBe(VacancyStatus::Draft)
        ->and($vacancy->fresh()->published_at)->toBeNull();

    $standard->update(['price_cents' => 25_000, 'vat_rate_basis_points' => 900]);

    expect($order->fresh()->total_cents)->toBe(22_869)
        ->and($order->fresh()->orderItems->sole()->price_cents)->toBe(18_900)
        ->and($order->fresh()->orderItems->sole()->vat_rate_basis_points)->toBe(2100);
});

test('package seeding is idempotent and preserves later staff edits', function () {
    seedApprovedPackageCatalogue();
    Package::where('slug', 'standard')->update(['name' => 'Standaard aangepast']);

    seedApprovedPackageCatalogue();

    expect(Package::count())->toBe(3)
        ->and(Package::where('slug', 'standard')->value('name'))->toBe('Standaard aangepast');
});

test('package administration is readable by editors and editable only by administrators', function () {
    seedApprovedPackageCatalogue();
    collect(['admin', 'editor', 'employer'])->each(
        fn (string $role) => Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']),
    );

    $package = Package::where('slug', 'standard')->firstOrFail();
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $editor = User::factory()->create();
    $editor->assignRole('editor');
    $employer = User::factory()->create();
    $employer->assignRole('employer');

    expect(Gate::forUser($admin)->allows('update', $package))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $package))->toBeFalse()
        ->and(Gate::forUser($editor)->allows('view', $package))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('update', $package))->toBeFalse()
        ->and(Gate::forUser($employer)->allows('viewAny', Package::class))->toBeFalse();

    $this->actingAs($admin)
        ->get(PackageResource::getUrl('edit', ['record' => $package]))
        ->assertSuccessful();

    Livewire::test(EditPackage::class, ['record' => $package->getRouteKey()])
        ->fillForm([
            'name' => 'Standaard beheerbaar',
            'features' => ['Bewerkbaar voordeel'],
            'price_cents' => 19_900,
            'vacancy_duration_days' => 65,
            'commercial_priority' => 12,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($package->fresh()->name)->toBe('Standaard beheerbaar')
        ->and($package->fresh()->features)->toBe(['Bewerkbaar voordeel'])
        ->and($package->fresh()->price_cents)->toBe(19_900)
        ->and($package->fresh()->vacancy_duration_days)->toBe(65)
        ->and($package->fresh()->commercial_priority)->toBe(12);

    $this->actingAs($editor)
        ->get(PackageResource::getUrl('view', ['record' => $package]))
        ->assertSuccessful();
    $this->actingAs($editor)
        ->get(PackageResource::getUrl('edit', ['record' => $package]))
        ->assertForbidden();
});

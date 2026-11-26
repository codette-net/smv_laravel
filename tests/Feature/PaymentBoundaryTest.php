<?php

use App\Actions\Orders\CreateOrder;
use App\Actions\Payments\ConfirmPayment;
use App\Actions\Payments\StartPayment;
use App\Enums\AdvertisingPackage;
use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Payments\FakePaymentGateway;
use App\Payments\FakePaymentOutcome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function paymentEmployer(): User
{
    Role::firstOrCreate(['name' => 'employer', 'guard_name' => 'web']);

    $user = User::factory()->create(['role' => 'employer']);
    $user->assignRole('employer');

    return $user;
}

function paymentOrder(?User $user = null): Order
{
    $user ??= paymentEmployer();
    $company = Company::factory()->for($user)->create(['status' => CompanyStatus::Active]);

    return app(CreateOrder::class)
        ->execute($user, $company, [['package' => AdvertisingPackage::Standard]], OrderStatus::PendingPayment)
        ->fresh();
}

function fakeGateway(FakePaymentOutcome $outcome): void
{
    app()->instance(FakePaymentGateway::class, new FakePaymentGateway($outcome));
}

test('a pending order starts a fake payment using its immutable amount and currency', function () {
    $user = paymentEmployer();
    $order = paymentOrder($user);
    fakeGateway(FakePaymentOutcome::Pending);

    $payment = app(StartPayment::class)->execute($user, $order, 'attempt-pending');

    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->provider)->toBe('fake')
        ->and($payment->provider_payment_id)->toBe('fake-attempt-pending')
        ->and($payment->provider_reference)->toBe('fake-attempt-pending')
        ->and($payment->amount_cents)->toBe($order->total_cents)
        ->and($payment->currency)->toBe($order->currency)
        ->and($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

test('fake success marks the payment and order paid exactly once', function () {
    $user = paymentEmployer();
    $order = paymentOrder($user);
    fakeGateway(FakePaymentOutcome::Success);

    $payment = app(StartPayment::class)->execute($user, $order, 'attempt-success');
    $confirmed = app(ConfirmPayment::class)->execute($payment);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($confirmed->id)->toBe($payment->id)
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->payments()->where('status', PaymentStatus::Paid->value)->count())->toBe(1);

    app(ConfirmPayment::class)->execute($payment->fresh());

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->payments()->count())->toBe(1);
});

test('failed and cancelled attempts remain auditable and can be retried', function () {
    $user = paymentEmployer();
    $order = paymentOrder($user);

    fakeGateway(FakePaymentOutcome::Failed);
    $failed = app(StartPayment::class)->execute($user, $order, 'attempt-failed');

    fakeGateway(FakePaymentOutcome::Cancelled);
    $cancelled = app(StartPayment::class)->execute($user, $order, 'attempt-cancelled');

    expect($failed->status)->toBe(PaymentStatus::Failed)
        ->and($cancelled->status)->toBe(PaymentStatus::Cancelled)
        ->and($order->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and($order->fresh()->payments)->toHaveCount(2);

    fakeGateway(FakePaymentOutcome::Success);
    $successful = app(StartPayment::class)->execute($user, $order, 'attempt-retry');

    expect($successful->status)->toBe(PaymentStatus::Paid)
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->payments()->where('status', PaymentStatus::Paid->value)->count())->toBe(1);
});

test('idempotency returns the existing attempt and prevents payment of another order', function () {
    $user = paymentEmployer();
    $order = paymentOrder($user);
    fakeGateway(FakePaymentOutcome::Pending);

    $first = app(StartPayment::class)->execute($user, $order, 'same-attempt');
    $second = app(StartPayment::class)->execute($user, $order, 'same-attempt');

    expect($second->is($first))->toBeTrue()
        ->and($order->fresh()->payments)->toHaveCount(1);

    $otherOrder = paymentOrder($user);
    expect(fn () => app(StartPayment::class)->execute($user, $otherOrder, 'same-attempt'))
        ->toThrow(InvalidArgumentException::class);
});

test('ownership and order state are enforced and already paid orders cannot start another attempt', function () {
    $owner = paymentEmployer();
    $other = paymentEmployer();
    $order = paymentOrder($owner);
    fakeGateway(FakePaymentOutcome::Pending);

    expect(fn () => app(StartPayment::class)->execute($other, $order, 'not-owner'))
        ->toThrow(InvalidArgumentException::class);

    fakeGateway(FakePaymentOutcome::Success);
    app(StartPayment::class)->execute($owner, $order, 'paid-attempt');

    expect(fn () => app(StartPayment::class)->execute($owner, $order->fresh(), 'second-attempt'))
        ->toThrow(InvalidArgumentException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

test('payment uses the order snapshot after package price changes', function () {
    $user = paymentEmployer();
    $company = Company::factory()->for($user)->create(['status' => CompanyStatus::Active]);
    $package = Package::factory()->create(['price_cents' => 12_345, 'currency' => 'EUR', 'is_active' => true]);
    $order = app(CreateOrder::class)->execute($user, $company, [['package' => $package]], OrderStatus::PendingPayment);
    $package->update(['price_cents' => 99_999]);
    fakeGateway(FakePaymentOutcome::Pending);

    $payment = app(StartPayment::class)->execute($user, $order, 'snapshot-attempt');

    expect($payment->amount_cents)->toBe(14_937)
        ->and($payment->currency)->toBe('EUR');
});

test('an incomplete legacy placement snapshot fails before starting provider checkout', function () {
    $user = paymentEmployer();
    $order = paymentOrder($user);
    $order->orderItems()->update([
        'placement_quantity_snapshot' => null,
        'vacancy_duration_days_snapshot' => null,
        'commercial_priority_snapshot' => null,
    ]);
    fakeGateway(FakePaymentOutcome::Pending);

    expect(fn () => app(StartPayment::class)->execute($user, $order, 'incomplete-entitlement'))
        ->toThrow(InvalidArgumentException::class, 'immutable placement entitlement snapshot');

    expect(Payment::count())->toBe(0)
        ->and($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

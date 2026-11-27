<?php

use App\Actions\Orders\CreateOrder;
use App\Actions\Payments\StartPayment;
use App\Enums\AdvertisingPackage;
use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Payments\PaymentGatewayException;
use App\Payments\Stripe\StripeCheckoutClient;
use App\Payments\Stripe\StripeCheckoutSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(function (): void {
    config(['payments.provider' => null]);
    app()->forgetInstance(StripeCheckoutClient::class);
});

final class RecordingStripeCheckoutClient implements StripeCheckoutClient
{
    public int $calls = 0;

    /** @var array<string, mixed> */
    public array $parameters = [];

    public ?string $idempotencyKey = null;

    public function createSession(array $parameters, string $idempotencyKey): StripeCheckoutSession
    {
        $this->calls++;
        $this->parameters = $parameters;
        $this->idempotencyKey = $idempotencyKey;

        return new StripeCheckoutSession(
            id: 'cs_test_smv_'.$this->calls,
            url: 'https://checkout.stripe.com/c/pay/cs_test_smv_'.$this->calls,
            livemode: false,
        );
    }
}

final class FailingStripeCheckoutClient implements StripeCheckoutClient
{
    public function createSession(array $parameters, string $idempotencyKey): StripeCheckoutSession
    {
        throw new PaymentGatewayException('Stripe Checkout is tijdelijk niet bereikbaar.');
    }
}

function stripeCheckoutOrder(?User $owner = null, AdvertisingPackage $package = AdvertisingPackage::Standard): Order
{
    $owner ??= User::factory()->create();
    $company = Company::factory()->for($owner)->create(['status' => CompanyStatus::Active]);

    return app(CreateOrder::class)->execute(
        $owner,
        $company,
        [['package' => $package]],
        OrderStatus::PendingPayment,
    );
}

function configureStripeCheckout(RecordingStripeCheckoutClient $client): void
{
    config([
        'payments.provider' => 'stripe',
        'payments.stripe.livemode' => false,
        'payments.stripe.secret_key' => 'sk_test_smv',
    ]);
    app()->instance(StripeCheckoutClient::class, $client);
}

test('Stripe Checkout uses immutable Order snapshots and exact net VAT and total amounts', function (): void {
    $owner = User::factory()->create(['email' => 'checkout-owner@example.test']);
    $order = stripeCheckoutOrder($owner);
    $client = new RecordingStripeCheckoutClient;
    configureStripeCheckout($client);

    $payment = app(StartPayment::class)->execute($owner, $order);

    expect($payment->provider)->toBe('stripe')
        ->and($payment->provider_reference)->toBe('cs_test_smv_1')
        ->and($payment->provider_payment_id)->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->amount_cents)->toBe(22_869)
        ->and($payment->currency)->toBe('EUR')
        ->and($payment->redirectUrl())->toStartWith('https://checkout.stripe.com/')
        ->and($client->idempotencyKey)->toBe($payment->idempotency_key)
        ->and($client->parameters['mode'])->toBe('payment')
        ->and($client->parameters['customer_email'])->toBe('checkout-owner@example.test')
        ->and($client->parameters['client_reference_id'])->toBe((string) $payment->id)
        ->and($client->parameters['metadata']['smv_payment_id'])->toBe((string) $payment->id)
        ->and($client->parameters['success_url'])->toBe(route('account.orders.success', $order))
        ->and($client->parameters['cancel_url'])->toBe(route('account.orders.cancelled', $order));

    $lineItems = collect($client->parameters['line_items']);
    expect($lineItems)->toHaveCount(2)
        ->and($lineItems[0]['price_data']['unit_amount'])->toBe(18_900)
        ->and($lineItems[0]['quantity'])->toBe(1)
        ->and($lineItems[1]['price_data']['unit_amount'])->toBe(3_969)
        ->and($lineItems->sum(fn (array $item): int => $item['price_data']['unit_amount'] * $item['quantity']))->toBe($order->total_cents);
});

test('repeated checkout requests reuse one pending Payment and one Stripe Session', function (): void {
    $owner = User::factory()->create();
    $order = stripeCheckoutOrder($owner);
    $client = new RecordingStripeCheckoutClient;
    configureStripeCheckout($client);

    $first = app(StartPayment::class)->execute($owner, $order);
    $second = app(StartPayment::class)->execute($owner, $order);

    expect($second->is($first))->toBeTrue()
        ->and($client->calls)->toBe(1)
        ->and($order->payments()->count())->toBe(1);
});

test('an indeterminate Stripe failure can safely retry the same Payment and idempotency key', function (): void {
    $owner = User::factory()->create();
    $order = stripeCheckoutOrder($owner);
    config([
        'payments.provider' => 'stripe',
        'payments.stripe.livemode' => false,
        'payments.stripe.secret_key' => 'sk_test_smv',
    ]);
    app()->instance(StripeCheckoutClient::class, new FailingStripeCheckoutClient);

    expect(fn () => app(StartPayment::class)->execute($owner, $order))
        ->toThrow(PaymentGatewayException::class);

    $pendingPayment = Payment::sole();
    expect($pendingPayment->status)->toBe(PaymentStatus::Pending)
        ->and($pendingPayment->provider_reference)->toBeNull();

    $client = new RecordingStripeCheckoutClient;
    app()->instance(StripeCheckoutClient::class, $client);
    $retriedPayment = app(StartPayment::class)->execute($owner, $order);

    expect($retriedPayment->is($pendingPayment))->toBeTrue()
        ->and($retriedPayment->provider_reference)->toBe('cs_test_smv_1')
        ->and($client->idempotencyKey)->toBe($pendingPayment->idempotency_key)
        ->and($order->payments()->count())->toBe(1);
});

test('checkout requires Stripe configuration and leaves no fabricated remote reference', function (): void {
    $owner = User::factory()->create();
    $order = stripeCheckoutOrder($owner);
    config([
        'payments.provider' => 'stripe',
        'payments.stripe.secret_key' => null,
        'payments.stripe.livemode' => false,
    ]);

    expect(fn () => app(StartPayment::class)->execute($owner, $order))
        ->toThrow(PaymentGatewayException::class);

    expect(Payment::count())->toBe(0);

    config(['payments.stripe.secret_key' => 'sk_live_wrong_environment']);
    expect(fn () => app(StartPayment::class)->execute($owner, $order))
        ->toThrow(PaymentGatewayException::class);

    expect(Payment::count())->toBe(0);
});

test('browser success and cancellation returns never confirm or cancel payment state', function (): void {
    $owner = User::factory()->create();
    $order = stripeCheckoutOrder($owner);
    $client = new RecordingStripeCheckoutClient;
    configureStripeCheckout($client);
    $payment = app(StartPayment::class)->execute($owner, $order);

    $this->actingAs($owner)
        ->get(route('account.orders.success', $order))
        ->assertOk()
        ->assertSee('wachten op de betaalbevestiging');
    $this->get(route('account.orders.cancelled', $order))
        ->assertOk()
        ->assertSee('niet automatisch geannuleerd');

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

test('customer Order history and checkout are owner scoped', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $admin = User::factory()->create();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin->assignRole('admin');
    $order = stripeCheckoutOrder($owner);
    $client = new RecordingStripeCheckoutClient;
    configureStripeCheckout($client);

    $this->actingAs($owner)
        ->get(route('account.orders.index'))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Standaard')
        ->assertSee('Betaling: Nog niet gestart');
    $this->get(route('account.orders.show', $order))->assertOk();
    $this->post(route('account.orders.pay', $order))->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_smv_1');

    $this->actingAs($other)->get(route('account.orders.show', $order))->assertForbidden();
    $this->post(route('account.orders.pay', $order))->assertForbidden();
    $this->get(route('account.orders.index'))->assertOk()->assertDontSee($order->order_number);
    $this->actingAs($admin)->get(route('account.orders.show', $order))->assertForbidden();

    expect($client->calls)->toBe(1);
});

test('Maatwerk remains quote-only and cannot enter Checkout', function (): void {
    $this->post(route('vacancy-placement.package'), ['package' => AdvertisingPackage::Custom->value])
        ->assertRedirect(route('contact', ['reason' => 'advertising', 'package' => 'custom']));

    expect(Order::count())->toBe(0)
        ->and(Payment::count())->toBe(0);
});

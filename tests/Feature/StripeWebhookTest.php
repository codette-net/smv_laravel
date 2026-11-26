<?php

use App\Actions\Orders\CreateOrder;
use App\Actions\Payments\StartPayment;
use App\Enums\AdvertisingPackage;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\User;
use App\Payments\Stripe\StripeCheckoutClient;
use App\Payments\Stripe\StripeCheckoutSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/** @return array{0: Order, 1: Payment} */
function stripeWebhookAttempt(string $sessionId = 'cs_test_webhook_1'): array
{
    $owner = User::factory()->create();
    $company = Company::factory()->for($owner)->create();
    $order = app(CreateOrder::class)->execute(
        $owner,
        $company,
        [['package' => AdvertisingPackage::Standard]],
        OrderStatus::PendingPayment,
    );
    $payment = Payment::factory()->for($order)->create([
        'provider' => 'stripe',
        'provider_payment_id' => null,
        'provider_reference' => $sessionId,
        'status' => PaymentStatus::Pending,
        'amount_cents' => $order->total_cents,
        'currency' => $order->currency,
    ]);

    return [$order, $payment];
}

/** @param array<string, mixed> $sessionOverrides */
function stripeWebhookPayload(
    string $eventId,
    string $eventType,
    Payment $payment,
    array $sessionOverrides = [],
): string {
    $session = [
        'id' => $payment->provider_reference,
        'object' => 'checkout.session',
        'mode' => 'payment',
        'livemode' => false,
        'amount_total' => $payment->amount_cents,
        'currency' => strtolower($payment->currency),
        'client_reference_id' => (string) $payment->id,
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_smv_1',
        'metadata' => [
            'smv_payment_id' => (string) $payment->id,
            'smv_order_reference' => $payment->order->order_number,
        ],
        ...$sessionOverrides,
    ];

    return (string) json_encode([
        'id' => $eventId,
        'object' => 'event',
        'type' => $eventType,
        'data' => ['object' => $session],
    ], JSON_THROW_ON_ERROR);
}

function stripeWebhookSignature(string $payload, string $secret, ?int $timestamp = null): string
{
    $timestamp ??= time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

    return 't='.$timestamp.',v1='.$signature;
}

function sendStripeWebhook(object $test, string $payload, ?string $signature): TestResponse
{
    $server = ['CONTENT_TYPE' => 'application/json'];
    if ($signature !== null) {
        $server['HTTP_STRIPE_SIGNATURE'] = $signature;
    }

    return $test->call('POST', '/stripe/webhook', [], [], [], $server, $payload);
}

beforeEach(function (): void {
    config([
        'payments.stripe.webhook_secret' => 'whsec_smv_test',
        'payments.stripe.livemode' => false,
    ]);
});

afterEach(function (): void {
    config(['payments.provider' => null]);
    app()->forgetInstance(StripeCheckoutClient::class);
});

test('a verified paid Checkout event confirms the Payment and Order exactly once', function (): void {
    [$order, $payment] = stripeWebhookAttempt();
    $payload = stripeWebhookPayload('evt_checkout_paid', 'checkout.session.completed', $payment);
    $signature = stripeWebhookSignature($payload, 'whsec_smv_test');

    sendStripeWebhook($this, $payload, $signature)->assertOk();
    sendStripeWebhook($this, $payload, $signature)->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->provider_payment_id)->toBe('pi_test_smv_1')
        ->and($payment->fresh()->paid_at)->not->toBeNull()
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and(PaymentWebhookEvent::count())->toBe(1);
});

test('Checkout completion remains pending until actual payment status succeeds asynchronously', function (): void {
    [$order, $payment] = stripeWebhookAttempt();
    $pending = stripeWebhookPayload('evt_checkout_pending', 'checkout.session.completed', $payment, [
        'payment_status' => 'unpaid',
        'payment_intent' => 'pi_test_delayed',
    ]);

    sendStripeWebhook($this, $pending, stripeWebhookSignature($pending, 'whsec_smv_test'))->assertOk();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($order->fresh()->status)->toBe(OrderStatus::PendingPayment);

    $success = stripeWebhookPayload('evt_checkout_async_success', 'checkout.session.async_payment_succeeded', $payment, [
        'payment_intent' => 'pi_test_delayed',
    ]);
    sendStripeWebhook($this, $success, stripeWebhookSignature($success, 'whsec_smv_test'))->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid);
});

test('failed and expired Checkout events remain auditable and allow a new attempt', function (string $eventType, PaymentStatus $expected): void {
    [$order, $payment] = stripeWebhookAttempt();
    $payload = stripeWebhookPayload('evt_'.str_replace('.', '_', $eventType), $eventType, $payment, [
        'payment_status' => 'unpaid',
        'payment_intent' => null,
    ]);

    sendStripeWebhook($this, $payload, stripeWebhookSignature($payload, 'whsec_smv_test'))->assertOk();

    expect($payment->fresh()->status)->toBe($expected)
        ->and($order->fresh()->status)->toBe(OrderStatus::PendingPayment);

    config(['payments.provider' => 'stripe', 'payments.stripe.secret_key' => 'sk_test_smv']);
    app()->instance(StripeCheckoutClient::class, new class implements StripeCheckoutClient
    {
        public function createSession(array $parameters, string $idempotencyKey): StripeCheckoutSession
        {
            return new StripeCheckoutSession('cs_test_retry', 'https://checkout.stripe.com/c/pay/cs_test_retry', false);
        }
    });

    $retry = app(StartPayment::class)->execute($order->user, $order->fresh());
    expect($retry->id)->not->toBe($payment->id)
        ->and($retry->status)->toBe(PaymentStatus::Pending)
        ->and($order->payments()->count())->toBe(2);
})->with([
    'async failure' => ['checkout.session.async_payment_failed', PaymentStatus::Failed],
    'session expiry' => ['checkout.session.expired', PaymentStatus::Expired],
]);

test('out of order failure cannot regress paid state and authoritative late success can recover failure', function (): void {
    [$order, $payment] = stripeWebhookAttempt();
    $success = stripeWebhookPayload('evt_out_of_order_success', 'checkout.session.async_payment_succeeded', $payment);
    sendStripeWebhook($this, $success, stripeWebhookSignature($success, 'whsec_smv_test'))->assertOk();

    $failure = stripeWebhookPayload('evt_out_of_order_failure', 'checkout.session.async_payment_failed', $payment, [
        'payment_status' => 'unpaid',
    ]);
    sendStripeWebhook($this, $failure, stripeWebhookSignature($failure, 'whsec_smv_test'))->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid);

    [$lateOrder, $latePayment] = stripeWebhookAttempt('cs_test_webhook_late');
    $failedFirst = stripeWebhookPayload('evt_late_failure', 'checkout.session.async_payment_failed', $latePayment, [
        'payment_status' => 'unpaid',
    ]);
    sendStripeWebhook($this, $failedFirst, stripeWebhookSignature($failedFirst, 'whsec_smv_test'))->assertOk();
    $lateSuccess = stripeWebhookPayload('evt_late_success', 'checkout.session.async_payment_succeeded', $latePayment);
    sendStripeWebhook($this, $lateSuccess, stripeWebhookSignature($lateSuccess, 'whsec_smv_test'))->assertOk();

    expect($latePayment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($lateOrder->fresh()->status)->toBe(OrderStatus::Paid);
});

test('a verified late payment is recorded without regressing an Order already paid by another attempt', function (): void {
    [$order, $payment] = stripeWebhookAttempt();
    Payment::factory()->for($order)->create([
        'status' => PaymentStatus::Paid,
        'amount_cents' => $order->total_cents,
        'paid_at' => now(),
    ]);
    $order->update(['status' => OrderStatus::Paid]);
    $payload = stripeWebhookPayload('evt_late_duplicate_charge', 'checkout.session.async_payment_succeeded', $payment);

    sendStripeWebhook($this, $payload, stripeWebhookSignature($payload, 'whsec_smv_test'))->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->payments()->where('status', PaymentStatus::Paid->value)->count())->toBe(2);
});

test('webhook rejects missing invalid or unavailable signature verification', function (): void {
    [, $payment] = stripeWebhookAttempt();
    $payload = stripeWebhookPayload('evt_invalid_signature', 'checkout.session.completed', $payment);

    sendStripeWebhook($this, $payload, null)->assertStatus(400);
    sendStripeWebhook($this, $payload, 't=123,v1=invalid')->assertStatus(400);
    $malformed = '{not-json';
    sendStripeWebhook($this, $malformed, stripeWebhookSignature($malformed, 'whsec_smv_test'))->assertStatus(400);

    config(['payments.stripe.webhook_secret' => null]);
    sendStripeWebhook($this, $payload, stripeWebhookSignature($payload, 'whsec_smv_test'))->assertStatus(503);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and(PaymentWebhookEvent::count())->toBe(0);
});

test('webhook rejects amount currency metadata and livemode mismatches', function (array $overrides): void {
    [, $payment] = stripeWebhookAttempt();
    $payload = stripeWebhookPayload('evt_mismatch_'.md5(json_encode($overrides)), 'checkout.session.completed', $payment, $overrides);

    sendStripeWebhook($this, $payload, stripeWebhookSignature($payload, 'whsec_smv_test'))->assertStatus(422);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and(PaymentWebhookEvent::count())->toBe(0);
})->with([
    'amount' => [['amount_total' => 1]],
    'currency' => [['currency' => 'usd']],
    'metadata' => [['metadata' => ['smv_payment_id' => '999', 'smv_order_reference' => 'wrong']]],
    'livemode' => [['livemode' => true]],
]);

<?php

use App\Actions\Orders\CancelUnpaidOrder;
use App\Actions\Orders\CreateOrder;
use App\Actions\Orders\RequestFullRefund;
use App\Actions\Payments\ConfirmPayment;
use App\Actions\Payments\StartPayment;
use App\Actions\Placements\GrantPaidOrderPlacementCredits;
use App\Enums\AdvertisingPackage;
use App\Enums\CompanyStatus;
use App\Enums\OrderOperationStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PlacementCreditStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderOperation;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\PlacementCredit;
use App\Models\PlacementCreditEvent;
use App\Models\User;
use App\Models\Vacancy;
use App\Payments\FakePaymentGateway;
use App\Payments\FakePaymentOutcome;
use App\Payments\FakeRefundOutcome;
use App\Payments\Stripe\StripeCheckoutClient;
use App\Payments\Stripe\StripeCheckoutSession;
use App\Payments\Stripe\StripeCheckoutState;
use App\Payments\Stripe\StripeOrderOperationsClient;
use App\Payments\Stripe\StripePaymentGateway;
use App\Payments\Stripe\StripeRefundState;
use App\Support\Commerce\EntitledVacancyPackagePriority;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

final class RecordingStripeOrderOperationsClient implements StripeOrderOperationsClient
{
    /** @var array<string, mixed> */
    public array $refundParameters = [];

    public string $refundStatus = 'pending';

    public StripeCheckoutState $checkoutState;

    public function __construct()
    {
        $this->checkoutState = new StripeCheckoutState('cs_test_cancel', 'open', 'unpaid', null, false);
    }

    public function retrieveCheckoutSession(string $sessionId): StripeCheckoutState
    {
        return $this->checkoutState;
    }

    public function expireCheckoutSession(string $sessionId): StripeCheckoutState
    {
        return new StripeCheckoutState($sessionId, 'expired', 'unpaid', null, false);
    }

    public function createRefund(array $parameters, string $idempotencyKey): StripeRefundState
    {
        $this->refundParameters = $parameters + ['idempotency_key' => $idempotencyKey];

        return new StripeRefundState(
            're_test_smv_117',
            $this->refundStatus,
            (int) $parameters['amount'],
            'EUR',
            (string) $parameters['payment_intent'],
            $this->refundStatus === 'failed' ? 'declined' : null,
            $this->refundStatus === 'pending' ? 'processing' : null,
        );
    }
}

function reversalStaff(string $role = 'admin'): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => $role]);
    $user->assignRole($role);

    return $user;
}

/** @return array{0: User, 1: Company, 2: Order} */
function reversalOrder(?Vacancy $vacancy = null): array
{
    $employer = User::factory()->create();
    $company = $vacancy?->company ?? Company::factory()->for($employer)->create(['status' => CompanyStatus::Active]);
    if ($vacancy !== null) {
        $employer = $company->user;
    }

    $order = app(CreateOrder::class)->execute(
        $employer,
        $company,
        [['package' => AdvertisingPackage::Superior, 'vacancy' => $vacancy]],
        OrderStatus::PendingPayment,
    );

    return [$employer, $company, $order];
}

/** @return array{0: Order, 1: Payment} */
function paidReversalOrder(?Vacancy $vacancy = null, string $provider = 'fake'): array
{
    [$employer, , $order] = reversalOrder($vacancy);

    if ($provider === 'fake') {
        app()->instance(FakePaymentGateway::class, new FakePaymentGateway(FakePaymentOutcome::Success));
        $payment = app(StartPayment::class)->execute($employer, $order, 'paid-reversal-'.$order->id);
    } else {
        $payment = Payment::factory()->for($order)->create([
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_refund_'.$order->id,
            'provider_reference' => 'cs_test_refund_'.$order->id,
            'status' => PaymentStatus::Paid,
            'amount_cents' => $order->total_cents,
            'currency' => $order->currency,
            'paid_at' => now(),
            'completed_at' => now(),
        ]);
        $order->update(['status' => OrderStatus::Paid]);
        app(GrantPaidOrderPlacementCredits::class)->execute($order->fresh());
    }

    return [$order->fresh(), $payment->fresh()];
}

function bindStripeOperations(RecordingStripeOrderOperationsClient $operations): void
{
    $checkout = new class implements StripeCheckoutClient
    {
        public function createSession(array $parameters, string $idempotencyKey): StripeCheckoutSession
        {
            return new StripeCheckoutSession('cs_unused', 'https://checkout.stripe.com/c/pay/cs_unused', false);
        }
    };
    app()->instance(StripePaymentGateway::class, new StripePaymentGateway($checkout, $operations));
}

function reversalWebhookSignature(string $payload, string $secret): string
{
    $timestamp = time();

    return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
}

function sendReversalWebhook(object $test, array $event): TestResponse
{
    $payload = json_encode($event, JSON_THROW_ON_ERROR);

    return $test->call('POST', '/stripe/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => reversalWebhookSignature($payload, 'whsec_refund_test'),
    ], $payload);
}

beforeEach(function (): void {
    config([
        'payments.provider' => 'fake',
        'payments.stripe.livemode' => false,
        'payments.stripe.webhook_secret' => 'whsec_refund_test',
    ]);
});

test('authorized staff cancels an unpaid Order once with a mandatory audit reason', function (): void {
    [, , $order] = reversalOrder();
    $admin = reversalStaff();

    expect(fn () => app(CancelUnpaidOrder::class)->execute($admin, $order, 'kort'))
        ->toThrow(InvalidArgumentException::class);

    $operation = app(CancelUnpaidOrder::class)->execute($admin, $order, 'Klant heeft de aanvraag vóór betaling ingetrokken.');
    $duplicate = app(CancelUnpaidOrder::class)->execute($admin, $order->fresh(), 'Tweede klik wordt genegeerd.');

    expect($operation->status)->toBe(OrderOperationStatus::Succeeded)
        ->and($operation->operator_user_id)->toBe($admin->id)
        ->and($operation->reason)->toContain('ingetrokken')
        ->and($operation->metadata['previous_order_status'])->toBe(OrderStatus::PendingPayment->value)
        ->and($operation->metadata['resulting_order_status'])->toBe(OrderStatus::Cancelled->value)
        ->and($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($duplicate->id)->toBe($operation->id)
        ->and(OrderOperation::count())->toBe(1)
        ->and(PlacementCredit::count())->toBe(0);
});

test('employers cannot cancel or refund Orders through the staff actions', function (): void {
    [$employer, , $order] = reversalOrder();
    expect(fn () => app(CancelUnpaidOrder::class)->execute($employer, $order, 'Niet bevoegd voor annulering.'))
        ->toThrow(AuthorizationException::class);

    [$paidOrder] = paidReversalOrder();
    expect(fn () => app(RequestFullRefund::class)->execute($employer, $paidOrder, 'Niet bevoegd voor terugbetaling.'))
        ->toThrow(AuthorizationException::class);
});

test('cancellation expires an open attempt and permanently blocks customer Checkout', function (): void {
    [$employer, , $order] = reversalOrder();
    app()->instance(FakePaymentGateway::class, new FakePaymentGateway(FakePaymentOutcome::Pending));
    $payment = app(StartPayment::class)->execute($employer, $order, 'cancel-open-attempt');

    $operation = app(CancelUnpaidOrder::class)->execute(reversalStaff(), $order, 'Open betaling veilig beëindigen.');

    expect($operation->status)->toBe(OrderOperationStatus::Succeeded)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Expired)
        ->and($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and(fn () => app(StartPayment::class)->execute($employer, $order->fresh()))
        ->toThrow(InvalidArgumentException::class);
});

test('cancellation refuses a Stripe session when payment may already have succeeded', function (): void {
    [, , $order] = reversalOrder();
    $payment = Payment::factory()->for($order)->create([
        'provider' => 'stripe',
        'provider_reference' => 'cs_test_cancel',
        'provider_payment_id' => null,
        'status' => PaymentStatus::Pending,
        'amount_cents' => $order->total_cents,
    ]);
    $operations = new RecordingStripeOrderOperationsClient;
    $operations->checkoutState = new StripeCheckoutState('cs_test_cancel', 'complete', 'paid', 'pi_race', false);
    bindStripeOperations($operations);

    $operation = app(CancelUnpaidOrder::class)->execute(reversalStaff(), $order, 'Controleer de gelijktijdige Stripe-betaling.');

    expect($operation->status)->toBe(OrderOperationStatus::RequiresReview)
        ->and($operation->failure_code)->toBe('payment_may_have_succeeded')
        ->and($order->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

test('a full refund uses the immutable total and successful PaymentIntent and revokes unused credits once', function (): void {
    [$order, $payment] = paidReversalOrder();
    $admin = reversalStaff();
    app()->instance(FakePaymentGateway::class, new FakePaymentGateway(FakePaymentOutcome::Pending, FakeRefundOutcome::Success));

    $operation = app(RequestFullRefund::class)->execute($admin, $order, 'Volledige terugbetaling inclusief btw akkoord.');
    $duplicate = app(RequestFullRefund::class)->execute($admin, $order->fresh(), 'Dubbele klik.');
    $credit = PlacementCredit::query()->where('order_id', $order->id)->firstOrFail();

    expect($operation->amount_cents)->toBe($order->total_cents)
        ->and($operation->payment_id)->toBe($payment->id)
        ->and($operation->status)->toBe(OrderOperationStatus::Succeeded)
        ->and($operation->provider_status)->toBe('succeeded')
        ->and($operation->metadata['previous_payment_status'])->toBe(PaymentStatus::Paid->value)
        ->and($operation->metadata['resulting_payment_status'])->toBe(PaymentStatus::Refunded->value)
        ->and($duplicate->id)->toBe($operation->id)
        ->and($order->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and($credit->fresh()->status())->toBe(PlacementCreditStatus::Reversed)
        ->and(PlacementCreditEvent::query()->where('event_type', 'reversed')->count())->toBe(1);
});

test('Stripe refund request sends the full amount and actual successful PaymentIntent', function (): void {
    [$order, $payment] = paidReversalOrder(provider: 'stripe');
    $operations = new RecordingStripeOrderOperationsClient;
    bindStripeOperations($operations);

    $operation = app(RequestFullRefund::class)->execute(reversalStaff(), $order, 'Stripe-terugbetaling voor volledige bestelling.');

    expect($operation->status)->toBe(OrderOperationStatus::Pending)
        ->and($operations->refundParameters['amount'])->toBe($order->total_cents)
        ->and($operations->refundParameters['payment_intent'])->toBe($payment->provider_payment_id)
        ->and($operations->refundParameters['metadata']['smv_order_operation_id'])->toBe((string) $operation->id)
        ->and($operations->refundParameters['idempotency_key'])->toBe($operation->idempotency_key);
});

test('an indeterminate refund reuses one durable operation and idempotency key on safe retry', function (): void {
    [$order] = paidReversalOrder();
    $gateway = new FakePaymentGateway(FakePaymentOutcome::Pending, FakeRefundOutcome::Indeterminate);
    app()->instance(FakePaymentGateway::class, $gateway);
    $admin = reversalStaff();

    $uncertain = app(RequestFullRefund::class)->execute($admin, $order, 'Netwerkuitkomst moet veilig herbruikbaar zijn.');
    $key = $uncertain->idempotency_key;
    $gateway->refundWith(FakeRefundOutcome::Success);
    $recovered = app(RequestFullRefund::class)->execute($admin, $order->fresh(), 'Herhaal met dezelfde provider-idempotentie.');

    expect($uncertain->status)->toBe(OrderOperationStatus::RequiresReview)
        ->and($uncertain->failure_code)->toBe('provider_outcome_unknown')
        ->and($recovered->id)->toBe($uncertain->id)
        ->and($recovered->idempotency_key)->toBe($key)
        ->and($recovered->status)->toBe(OrderOperationStatus::Succeeded)
        ->and(OrderOperation::count())->toBe(1);
});

test('pending and failed provider outcomes never mark the Order refunded', function (FakeRefundOutcome $outcome, OrderOperationStatus $expected): void {
    [$order, $payment] = paidReversalOrder();
    app()->instance(FakePaymentGateway::class, new FakePaymentGateway(FakePaymentOutcome::Pending, $outcome));

    $operation = app(RequestFullRefund::class)->execute(reversalStaff(), $order, 'Provideruitkomst blijft leidend voor de financiële status.');

    expect($operation->status)->toBe($expected)
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and(PlacementCredit::query()->where('order_id', $order->id)->firstOrFail()->reversed_at)->toBeNull();
})->with([
    'pending' => [FakeRefundOutcome::Pending, OrderOperationStatus::Pending],
    'failed' => [FakeRefundOutcome::Failed, OrderOperationStatus::Failed],
]);

test('confirmed refund keeps an allocated unpublished credit attached and requires review', function (): void {
    $employer = User::factory()->create();
    $company = Company::factory()->for($employer)->create(['status' => CompanyStatus::Active]);
    $vacancy = Vacancy::factory()->for($company)->create([
        'source' => VacancySource::Manual,
        'status' => VacancyStatus::Pending,
        'is_filled' => false,
        'placement_package' => AdvertisingPackage::Superior,
    ]);
    [$order] = paidReversalOrder($vacancy);
    $credit = PlacementCredit::query()->where('order_id', $order->id)->firstOrFail();
    app()->instance(FakePaymentGateway::class, new FakePaymentGateway(FakePaymentOutcome::Pending, FakeRefundOutcome::Success));

    $operation = app(RequestFullRefund::class)->execute(reversalStaff(), $order, 'Toegewezen conceptplaatsing vereist controle.');

    expect($operation->status)->toBe(OrderOperationStatus::RequiresReview)
        ->and($credit->fresh()->vacancy_id)->toBe($vacancy->id)
        ->and($credit->fresh()->reversed_at)->toBeNull()
        ->and($credit->fresh()->review_required_at)->not->toBeNull()
        ->and($vacancy->fresh()->status)->toBe(VacancyStatus::Pending);
});

test('confirmed refund freezes allocated and active credits for review without changing publication or manual priority', function (): void {
    $employer = User::factory()->create();
    $company = Company::factory()->for($employer)->create(['status' => CompanyStatus::Active, 'commercial_priority' => 11]);
    $vacancy = Vacancy::factory()->for($company)->create([
        'source' => VacancySource::Manual,
        'status' => VacancyStatus::Active,
        'is_filled' => false,
        'published_at' => now()->subDay(),
        'placement_package' => AdvertisingPackage::Superior,
        'commercial_priority_adjustment' => 7,
    ]);
    [$order] = paidReversalOrder($vacancy);
    $credit = PlacementCredit::query()->where('order_id', $order->id)->firstOrFail();
    expect($credit->status())->toBe(PlacementCreditStatus::Active)
        ->and(app(EntitledVacancyPackagePriority::class)->priorityFor($vacancy))->toBeGreaterThan(0);

    app()->instance(FakePaymentGateway::class, new FakePaymentGateway(FakePaymentOutcome::Pending, FakeRefundOutcome::Success));
    $operation = app(RequestFullRefund::class)->execute(reversalStaff(), $order, 'Actieve plaatsing vereist commerciële controle.');

    expect($operation->provider_status)->toBe('succeeded')
        ->and($operation->status)->toBe(OrderOperationStatus::RequiresReview)
        ->and($credit->fresh()->status())->toBe(PlacementCreditStatus::RequiresReview)
        ->and($credit->fresh()->vacancy_id)->toBe($vacancy->id)
        ->and($vacancy->fresh()->status)->toBe(VacancyStatus::Active)
        ->and($vacancy->fresh()->published_at)->not->toBeNull()
        ->and($vacancy->fresh()->commercial_priority_adjustment)->toBe(7)
        ->and($company->fresh()->commercial_priority)->toBe(11)
        ->and(app(EntitledVacancyPackagePriority::class)->priorityFor($vacancy->fresh()))->toBe(0);
});

test('verified refund webhooks complete pending refunds once and stale failures cannot regress success', function (): void {
    [$order, $payment] = paidReversalOrder(provider: 'stripe');
    $operations = new RecordingStripeOrderOperationsClient;
    bindStripeOperations($operations);
    $operation = app(RequestFullRefund::class)->execute(reversalStaff(), $order, 'Webhook bevestigt uiteindelijke Stripe-status.');

    $object = [
        'id' => 're_test_smv_117',
        'object' => 'refund',
        'amount' => $order->total_cents,
        'currency' => 'eur',
        'payment_intent' => $payment->provider_payment_id,
        'status' => 'succeeded',
        'metadata' => ['smv_order_operation_id' => (string) $operation->id],
    ];
    $success = ['id' => 'evt_refund_success', 'object' => 'event', 'type' => 'refund.updated', 'data' => ['object' => $object]];
    sendReversalWebhook($this, $success)->assertOk();
    sendReversalWebhook($this, $success)->assertOk();

    $failed = [
        'id' => 'evt_refund_stale_failure',
        'object' => 'event',
        'type' => 'refund.failed',
        'data' => ['object' => [...$object, 'status' => 'failed', 'failure_reason' => 'declined']],
    ];
    sendReversalWebhook($this, $failed)->assertOk();

    expect($operation->fresh()->provider_status)->toBe('succeeded')
        ->and($operation->fresh()->status)->toBe(OrderOperationStatus::Succeeded)
        ->and($order->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and(PaymentWebhookEvent::count())->toBe(2)
        ->and(PaymentWebhookEvent::query()->where('order_operation_id', $operation->id)->count())->toBe(2);
});

test('late authoritative payment after cancellation is retained and flags the cancellation for review', function (): void {
    [, , $order] = reversalOrder();
    $payment = Payment::factory()->for($order)->create([
        'status' => PaymentStatus::Pending,
        'amount_cents' => $order->total_cents,
        'currency' => $order->currency,
    ]);
    $operation = app(CancelUnpaidOrder::class)->execute(reversalStaff(), $order, 'Annulering vóór late bevestiging.');

    app(ConfirmPayment::class)->execute($payment->fresh(), providerAuthoritative: true);

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($operation->fresh()->status)->toBe(OrderOperationStatus::RequiresReview)
        ->and($operation->fresh()->failure_code)->toBe('late_payment_after_cancellation')
        ->and(PlacementCredit::query()->where('order_id', $order->id)->count())->toBe(1);
});

test('a stale payment success callback cannot regress an already confirmed refund', function (): void {
    [$order, $payment] = paidReversalOrder();
    app()->instance(FakePaymentGateway::class, new FakePaymentGateway(FakePaymentOutcome::Pending, FakeRefundOutcome::Success));
    app(RequestFullRefund::class)->execute(reversalStaff(), $order, 'Terugbetaling is al definitief bevestigd.');

    $confirmed = app(ConfirmPayment::class)->execute($payment->fresh(), providerAuthoritative: true);

    expect($confirmed->status)->toBe(PaymentStatus::Refunded)
        ->and($order->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and(PlacementCredit::query()->where('order_id', $order->id)->firstOrFail()->status())->toBe(PlacementCreditStatus::Reversed);
});

test('staff roles are authorized and employer and candidate roles remain denied', function (): void {
    [, , $order] = reversalOrder();

    expect(reversalStaff('super-admin')->can('cancel', $order))->toBeTrue()
        ->and(reversalStaff('admin')->can('refund', $order))->toBeTrue()
        ->and(reversalStaff('editor')->can('refund', $order))->toBeTrue()
        ->and(reversalStaff('employer')->can('cancel', $order))->toBeFalse()
        ->and(reversalStaff('candidate')->can('refund', $order))->toBeFalse();
});

test('Filament exposes only the state-appropriate authorized commercial action', function (): void {
    [, , $pendingOrder] = reversalOrder();
    [$paidOrder] = paidReversalOrder();
    $editor = reversalStaff('editor');

    Livewire::actingAs($editor)
        ->test(ViewOrder::class, ['record' => $pendingOrder->id])
        ->assertActionVisible('cancelUnpaid')
        ->assertActionHidden('refundPaid');

    Livewire::actingAs($editor)
        ->test(ViewOrder::class, ['record' => $paidOrder->id])
        ->assertActionHidden('cancelUnpaid')
        ->assertActionVisible('refundPaid');
});

# Payments and Stripe operations

SMV uses the provider-neutral Payment boundary from SMV-111. Automated tests default to the
deterministic Fake gateway and never contact Stripe. Production checkout uses hosted Stripe
Checkout; Stripe's verified webhook is the only authority that confirms payment.

## Configuration

Set these deployment variables without committing their values:

```dotenv
PAYMENT_GATEWAY=stripe
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_LIVE_MODE=false
STRIPE_TIMEOUT_SECONDS=20
STRIPE_CONNECT_TIMEOUT_SECONDS=10
STRIPE_MAX_NETWORK_RETRIES=2
```

Use matching `sk_test_`/`pk_test_` credentials with `STRIPE_LIVE_MODE=false` on local and
staging environments. Production requires matching live credentials and
`STRIPE_LIVE_MODE=true`. Missing credentials, an unspecified mode, or a mode/key mismatch fails
closed. The publishable key is reserved for possible client-side Stripe use; hosted Checkout is
currently created server-side with the secret key.

`PAYMENT_GATEWAY=fake` is the test/local deterministic option. Application production mode
always rejects the Fake gateway.

## Webhook setup

Configure one Stripe webhook endpoint per environment:

```text
POST https://<environment-host>/stripe/webhook
```

Subscribe to:

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `checkout.session.async_payment_failed`
- `checkout.session.expired`
- `refund.created`
- `refund.updated`
- `refund.failed`

Copy that endpoint's signing secret into `STRIPE_WEBHOOK_SECRET`. The endpoint must remain
outside browser authentication and CSRF middleware; it authenticates the raw request body with
Stripe's `Stripe-Signature` header. Missing/invalid signatures are rejected, and full payloads,
credentials and signing secrets are never logged.

For local testing with Stripe CLI:

```powershell
stripe listen --forward-to http://smv.test/stripe/webhook
```

Use the `whsec_...` printed by the CLI only for that local listener. A browser redirect to a
success page is not proof of payment and does not change Order or Payment state.

Suggested local test sequence:

1. Create or reveal Stripe test-mode API keys in the Stripe Dashboard and place only the test
   secret/publishable values in the local `.env`.
2. Set `PAYMENT_GATEWAY=stripe`, `STRIPE_LIVE_MODE=false`, clear cached configuration when
   needed, and run Laravel on the host used in `APP_URL`.
3. Start the Stripe CLI listener above, copy its generated signing secret to
   `STRIPE_WEBHOOK_SECRET`, and restart/clear configuration if it was cached.
4. Submit a Standaard or Superior Vacancy, inspect the immutable Order summary, and enter hosted
   Checkout. Use Stripe's documented test card `4242 4242 4242 4242` with any future expiry/CVC
   for a success path; use Stripe's documented decline test cards for a failure path.
5. Confirm the browser return initially remains pending when its webhook has not arrived, then
   confirm the verified webhook transitions the Payment and Order to paid.
6. Use `stripe events resend <event-id>` to repeat a delivered event and verify the state and
   event ledger remain unchanged. Interrupt/expire a Checkout and verify retry creates or reuses
   only the eligible attempt.
7. Inspect the Order in Filament **Bestellingen** for status, amount, Checkout Session and
   PaymentIntent references. No staff control may mark a payment paid.

The exact CLI syntax and available test cards can change; follow the current official Stripe
CLI and testing documentation when performing release acceptance.

## Staging and production activation

Create a separate Stripe test-mode webhook destination for the staging HTTPS host, pointing to
`https://<staging-host>/stripe/webhook`. Give staging its own test API keys and its own endpoint
`whsec_...`; never reuse the local listener or production signing secret. Run successful,
declined, cancelled, delayed (where enabled), duplicate-delivery and amount/state inspection
checks before promotion.

Production activation requires a public HTTPS endpoint, matching live API keys,
`STRIPE_LIVE_MODE=true`, the production endpoint's distinct signing secret, and a successful
end-to-end payment/reconciliation rehearsal. This ticket does not register or deploy any Stripe
endpoint.

## Checkout and reconciliation behavior

Checkout derives all descriptions, quantity, net price, VAT and currency from immutable
OrderItem snapshots. Net and VAT are separate Stripe line items so the provider total exactly
matches `orders.total_cents`. Current Package prices are never re-read.

Each payment attempt has one application idempotency key. If session creation has an
indeterminate network failure, retrying reuses the same pending Payment and Stripe key. A stored
Checkout Session is reused instead of creating another session. Failed or expired attempts
remain auditable and may be followed by a new attempt.

Webhook reconciliation maps the stored Checkout Session reference to one Payment, then validates
test/live mode, internal Payment/Order references, amount and currency. Provider event IDs are
stored uniquely, so duplicate delivery is harmless. Out-of-order failure or expiry cannot
regress a paid Payment or Order; an authoritative delayed success can recover a failed attempt.

`ConfirmPayment` is also the authoritative fulfilment boundary for eligible placement products.
It reconciles Company-owned credits from the immutable OrderItem package type, placement
quantity, duration, package-priority and benefit snapshots in the same database transaction as
the paid transition. Repeated callbacks and confirmations reuse the unique OrderItem/grant
sequence rows. `php artisan placements:reconcile-paid-orders` performs the same idempotent
operation for operational recovery without replaying a Stripe webhook; legacy OrderItems that
lack the required entitlement snapshots fail closed and require review.

## Customer and staff surfaces

Authenticated purchasers can inspect only their own Orders at `/account/bestellingen`, open an
Order summary and retry eligible pending payment. Success and cancellation pages report the
persisted state. Cancellation of a Checkout page does not cancel the Order.

The role-aware `/account` overview is employer-first when the User owns a Company and remains
candidate-first otherwise. Order cards show reference, immutable package snapshot, linked Vacancy,
Dutch-formatted amount/currency, date and separate Order/Payment badges. Cancelled and refunded
states remain visible. A financially completed refund with an allocated or active credit adds a
separate placement-review warning rather than obscuring the successful refund.

Staff retain the existing Filament Order resource. The list action is labelled **Bekijken / acties**
so cancellation/refund controls are discoverable on the existing detail. It shows Payment status, provider, amount,
currency, Checkout Session reference, PaymentIntent reference and timestamps. It provides no
manual paid-state button.

## Cancellation and full refunds

Authorized super-administrators, administrators and editors can cancel an unpaid Order or
request a complete refund from the Filament Order view. Both actions require an internal
reason. The reason and safe provider diagnostics remain staff-only; customers see cancelled,
refund pending, refunded, failed or review wording in their private Order history.

Cancellation first records the operation and then checks the historical payment provider. An
open unpaid Stripe Checkout Session is expired before the Order becomes cancelled. A completed,
paid, processing or ambiguous Session leaves the Order payable state unchanged and flags review.
A later signed payment event can still mark a cancelled Order paid and grant its entitlement;
the cancellation record then requires staff reconciliation.

A full refund always uses the successful Payment's immutable amount/currency and PaymentIntent.
The durable operation idempotency key is reused after timeout, so retry cannot issue a second
refund. A Stripe response with `pending` remains pending; only an authoritative `succeeded`
Refund object from the API or signed webhook marks Payment and Order refunded. `refund.created`,
`refund.updated` and `refund.failed` share the existing webhook signature and event-deduplication
boundary. Out-of-order failure cannot regress succeeded.

Confirmed refunds reverse unused credits. Allocated or active credits become unavailable and
stop ranking but stay linked and require staff review; Vacancy publication and manual priority
are unchanged. Partial refunds, self-service, disputes, credit notes and automatic publication
changes are outside MVP.

### Manual placement review after a refund

There is no approved universal commercial outcome for an already allocated or active placement.
The review therefore remains open rather than offering a misleading one-click resolution:

1. Open Filament **Bestellingen** through **Bekijken / acties** and verify that the financial
   status is provider-confirmed **Terugbetaald**.
2. Review the operation ledger (operator, mandatory reason, provider reference/outcome) and the
   linked **Plaatsingsrecht** audit history.
3. Inspect the linked Vacancy and Company. Agree the commercial outcome with the responsible
   staff member before using existing authorized Vacancy administration for any manual lifecycle
   change.
4. Do not restore or renew the credit, republish the Vacancy, extend its dates, alter manual
   priority or change Payment state as an automatic consequence of the refund.

The placement credit deliberately remains **Controle vereist** because the current event model has
no approved resolved-review transition. A later ticket may add a reasoned, audited resolution once
the permitted outcomes and authorizations are approved.

For staging, add the three refund events to the test-mode webhook destination. Exercise an
unpaid cancellation, successful/pending/failed full refund, duplicate event delivery, retry
after an indeterminate response, unused-credit reversal and active-placement review. Compare
Order, Payment, operation and credit history before enabling live mode.

## Deliberate boundary

An eligible paid Order grants placement credits but never publishes, features or approves a
Vacancy. A safely linked Vacancy receives one credit and remains in its existing draft/pending
review state; otherwise the credit remains available to its Company. The paid placement term
starts only when the Vacancy and Company first become publicly eligible. Refund/cancellation
outcomes follow the SMV-117 operation ledger and are never inferred from browser redirects or
arbitrary local Payment edits.

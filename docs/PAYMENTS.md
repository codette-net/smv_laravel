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

## Customer and staff surfaces

Authenticated purchasers can inspect only their own Orders at `/account/bestellingen`, open an
Order summary and retry eligible pending payment. Success and cancellation pages report the
persisted state. Cancellation of a Checkout page does not cancel the Order.

Staff retain the existing Filament Order resource. It shows Payment status, provider, amount,
currency, Checkout Session reference, PaymentIntent reference and timestamps. It provides no
manual paid-state button.

## Deliberate boundary

A paid Order does not publish or feature a Vacancy and does not grant placement entitlement.
SMV-116 owns idempotent Company placement-credit creation and consumption. Refunds and customer
Order cancellation belong to SMV-117.

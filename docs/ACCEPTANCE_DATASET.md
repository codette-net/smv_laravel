# Acceptance dataset

`AcceptanceDemoSeeder` creates a disposable, deterministic SMV walkthrough dataset. It is
separate from `DatabaseSeeder` and is never run automatically by migrations or deployment.

## Seed command and safety

Run this only against a local, testing or explicitly isolated staging database:

```text
php artisan db:seed --class=Database\Seeders\AcceptanceDemoSeeder
```

The seeder refuses `production` and every other environment. It uses no network calls,
partner credentials or copied personal data. The default password is the development-only
value `acceptance-demo-password`; override it with `ACCEPTANCE_DEMO_PASSWORD` when an
isolated staging environment requires a different value. Never reuse this value in production.

For a disposable in-memory verification, use the migration command with the acceptance
seeder explicitly selected. Do not use `migrate:fresh` against a shared or staging database:

```powershell
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
php artisan migrate:fresh --seeder=Database\Seeders\AcceptanceDemoSeeder
```

The normal `DatabaseSeeder` and the existing factory/test setup remain unchanged. Existing
Blog content is reused only when the database has no Blog posts; it is not duplicated on reruns.

## Fixture accounts

| Role | E-mail | Use |
| --- | --- | --- |
| Candidate | `candidate@smv-acceptance.test` | Login, saved Vacancies/Companies and internal Application history |
| Employer | `employer@smv-acceptance.test` | Owned Company and employer Vacancy context |
| Editor | `editor@smv-acceptance.test` | Conservative Filament editorial access |
| Admin | `admin@smv-acceptance.test` | Filament administration, imports and monitoring |
| Super-admin | `super_admin@smv-acceptance.test` | Full staff acceptance access |

All addresses use the reserved `.test` domain and all passwords are acceptance-only fixtures.

## Scenario guide

### Companies

- `acceptance-company-complete`: complete public profile with logo fallback and several public
  Vacancy relationships; owned by the acceptance employer; Company priority `+5`.
- `acceptance-company-minimal`: deliberately incomplete public profile for missing-field states;
  Company priority `-5`.
- `acceptance-company-imported`: source-scoped imported-employer target Company; priority `0`.
- `acceptance-company-empty`: active Company without a current public Vacancy; priority `0`.

### Manual Vacancies

The stable references are `acceptance-manual-internal`, `acceptance-manual-external`,
`acceptance-manual-email`, `acceptance-manual-draft`, `acceptance-manual-archived`,
`acceptance-manual-expired` and `acceptance-manual-filled`. Together they cover internal,
external and e-mail destinations, draft/pending/published/archived/expired/filled lifecycle
states, Dutch/Belgian locations, taxonomies, tags, salary and hourly-rate filters.

Use `acceptance-manual-external` for outbound apply-click tracking and
`acceptance-manual-internal` for the public internal Application flow.

For ranking acceptance, `acceptance-manual-internal` has Superior package intent,
Company `+5` and Vacancy `+5`, for an effective score of `10`; the package component remains
`0` because the related Order is unpaid. `acceptance-manual-external` has an active paid
Standard placement, Company `+5` and Vacancy `0` (effective `5`, because Standard starts at
package score `0`). `acceptance-manual-email` combines Company `-5`
with Vacancy `+10`, and `acceptance-manual-draft` has Vacancy `-10` while remaining non-public.
`acceptance-import-active` has an SMV-managed Vacancy adjustment of `+6` to exercise imported
record administration. `acceptance-placement-superior-active` provides the active paid Superior
`+20` scenario, `acceptance-placement-pending` proves allocation does not bypass review and
`acceptance-placement-expired` proves expired package priority is removed.

### Imported Vacancies and sources

| Source | Policy/scenario | Useful records |
| --- | --- | --- |
| `acceptance-source-trusted` | Approved, scheduled, source-scoped, auto-publish and auto-offline; HTTPS `.invalid` fixture endpoint | `acceptance-import-active`, `acceptance-import-offline`, `acceptance-import-restored`, `acceptance-import-copy` |
| `acceptance-source-review` | Upload/manual, review-required publication and missing policy | `acceptance-import-review`, `acceptance-import-missing` |
| `acceptance-source-manual` | Manual-only JSON source with no endpoint and no run | Mapping administration and never-run health |
| `acceptance-source-monitoring` | Safe `.invalid` fixture endpoint with scheduled history | Completed, failed, incomplete and skipped/lock-contention runs |

All imported records use source-scoped provider IDs, stable source references, and synthetic
metadata. `acceptance-import-copy` is the imported record for **Maak handmatige kopie**.
The import history contains a verified count drop, review/incomplete state, failure and
concurrency skip without pretending that a live feed was contacted.

### Account and reporting history

The candidate has two saved Vacancies, two saved Companies and one reviewed internal
Application. Five deterministic outbound click events span the manual and imported Vacancies
and several recent dates, so the SMV-097C date/source/Company filters visibly change the report.
No external click is represented as an Application.

## Packages

The acceptance seeder initializes the same editable Standaard, Superior and Maatwerk
catalogue as a fresh installation. The rows contain structured placement/quote rules,
benefits, staff fulfilment notes, configured 21% VAT and validity through 2026-12-31.
Standaard starts at package priority `0`, Superior at `+20` and Maatwerk at `0`; the configured
score is snapshotted into a purchased credit and affects ranking only while its allocation is
active and the Vacancy remains publicly eligible.
Rerunning the seeder creates missing catalogue rows without overwriting later staff edits.

## Orders

The seeder includes three deterministic employer-owned Order examples linked to placement
Vacancies: `SMV-ACCEPTANCE-DRAFT`, `SMV-ACCEPTANCE-PENDING` and `SMV-ACCEPTANCE-PAID`.
Their OrderItems retain the approved product/price/currency/21%-VAT snapshots. The pending
Order has failed and cancelled Fake attempts; the paid Order has one successful Fake attempt.
Additional paid fixture Orders supply available Standard and Superior credits, an allocated
pending Superior placement, an active Superior placement and an expired placement. Their
OrderItems also retain package type, placement quantity, duration, priority and benefits. All
references are clearly synthetic and no Stripe transaction is fabricated. Re-running the
seeder reconciles each paid fixture through the production grant action without multiplying
credits or audit events; granted snapshots remain stable once credits exist.

SMV-117 adds `SMV-ACCEPTANCE-CANCELLABLE` as an unpaid staff-cancellation scenario,
`SMV-ACCEPTANCE-REFUNDED` as a confirmed Fake full refund with an automatically reversed unused
credit, and `SMV-ACCEPTANCE-REFUND-REVIEW` as a financially refunded active placement that
remains published and attached while its package entitlement is blocked for review. The latter
uses Vacancy `acceptance-placement-refund-review`. The operations have stable idempotency and
provider references, a fictitious internal reason and the acceptance administrator as operator;
no Stripe request or webhook is fabricated.

## Reruns and future Payments

Users, Companies, Vacancies, ImportSources, mappings, import runs/logs, Applications, saved
pivots and click events use stable fixture keys or timestamps. Rerunning the seeder updates the
intended fixtures and does not multiply them. Acceptance data is deliberately separate from
factories and should not be made a prerequisite for isolated feature tests.

The Fake gateway remains the automated-test provider after the Stripe integration. The seeded
Orders are suitable for customer/staff history inspection, but they deliberately fabricate no
Stripe transaction. Hosted Checkout and verified webhooks are covered by isolated tests with an
in-memory Stripe client boundary. Payment-to-credit and activation behavior use the same
idempotent application actions as production without contacting Stripe.

This dataset is the shared manual/browser prerequisite for SMV-072 and the disposable
production-like data prerequisite for the SMV-073 migration/dry-run rehearsal.

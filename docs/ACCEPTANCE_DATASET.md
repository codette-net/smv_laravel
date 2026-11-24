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
  Vacancy relationships; owned by the acceptance employer.
- `acceptance-company-minimal`: deliberately incomplete public profile for missing-field states.
- `acceptance-company-imported`: source-scoped imported-employer target Company.
- `acceptance-company-empty`: active Company without a current public Vacancy.

### Manual Vacancies

The stable references are `acceptance-manual-internal`, `acceptance-manual-external`,
`acceptance-manual-email`, `acceptance-manual-draft`, `acceptance-manual-archived`,
`acceptance-manual-expired` and `acceptance-manual-filled`. Together they cover internal,
external and e-mail destinations, draft/pending/published/archived/expired/filled lifecycle
states, Dutch/Belgian locations, taxonomies, tags, salary and hourly-rate filters.

Use `acceptance-manual-external` for outbound apply-click tracking and
`acceptance-manual-internal` for the public internal Application flow.

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

## Orders

The seeder includes three deterministic employer-owned Order examples linked to placement
Vacancies: `SMV-ACCEPTANCE-DRAFT`, `SMV-ACCEPTANCE-PENDING` and `SMV-ACCEPTANCE-PAID`.
The pending Order has failed and cancelled Fake attempts; the paid Order has one successful
Fake attempt. All references are clearly synthetic and no Stripe transaction is fabricated.
Re-running the seeder updates these rows, attempts and OrderItems without multiplying records.

## Reruns and future Payments

Users, Companies, Vacancies, ImportSources, mappings, import runs/logs, Applications, saved
pivots and click events use stable fixture keys or timestamps. Rerunning the seeder updates the
intended fixtures and does not multiply them. Acceptance data is deliberately separate from
factories and should not be made a prerequisite for isolated feature tests.

The Fake gateway remains the automated-test provider after the confirmed Stripe integration in
SMV-112. Customer checkout, webhook verification and entitlement activation remain future
work.

This dataset is the shared manual/browser prerequisite for SMV-072 and the disposable
production-like data prerequisite for the SMV-073 migration/dry-run rehearsal.

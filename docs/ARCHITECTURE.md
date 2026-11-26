# SMV Architecture

## Status

The repository audit and foundation stabilization are complete. The application now has
working public recruitment flows, Filament administration, a format-independent import
pipeline, a native Blog and the technical SEO foundation. The remaining work is commercial
Order/payment completion, content/brand approval, legacy URL migration and release
hardening; generic feed operations are substantially mature but still need partner activation
and production acceptance.

## Guiding principles

- Laravel conventions first.
- Existing working code first.
- Company is a first-class domain entity.
- Imports are a reusable subsystem.
- Public frontend reuses the existing Tailwind design/component system.
- Filament is the primary admin interface.
- SEO requirements influence route/content decisions from the start.
- Avoid infrastructure or abstraction that the MVP does not need.

## Current foundation and required modules

```text
Users

Companies
├── public company page
├── public search and one-category discovery
├── vacancies
├── content relations where useful
└── package/commercial relations where current model requires them

Vacancies
├── public discovery
├── detail
├── taxonomies
├── company
├── application destination
├── owner-scoped public draft/preview/pending hand-off
└── import provenance

Applications

Contact
├── public GET form plus rate-limited POST endpoint
├── ContactRequest validation and honeypot normalization
├── configurable internal Laravel Mail delivery
└── no ContactMessage database persistence

Packages / Orders / Payments
├── editable Package catalogue, price validity and structured placement rules (SMV-050)
├── provider-independent Order/OrderItem boundary (SMV-110)
├── provider-neutral payment gateway plus fake gateway (SMV-111)
├── confirmed Stripe checkout integration (SMV-112)
├── Company placement-credit and entitlement boundary (SMV-116)
└── cancellation/refund audit workflow (SMV-117)

Imports
├── upload, HTTP and API source configuration
├── JSON/XML/CSV/XLSX readers and bounded discovery
├── reusable mappings, transforms and normalized preview
├── validation plus source-scoped taxonomy resolution
├── provider-scoped Vacancy upsert
├── safe run history/logging and missing-record reporting
├── disabled-by-default scheduled database-queue execution
└── derived health, safe run history and locked fresh-run recovery

Blog
├── posts and Media Library cover images
├── typed categories and tags
├── manual Vacancy/Company relations
├── public index/detail/archive routes
└── metadata, BlogPosting JSON-LD and sitemap integration

SEO / migration
├── metadata, Open Graph and environment-aware robots
├── JobPosting, Organization and BlogPosting structured data
├── dynamic sitemap
├── clean and paginated canonicals
└── legacy inventory/redirects (deferred pending exports)
```

Manual import execution runs synchronously through `VacancyImportRunner`; explicitly
enabled remote sources can also dispatch bounded queued runs through the scheduler.
Completed verified runs can record missing source references in `missing_since`. The
ImportSource policy then either leaves publication for staff review or archives a
published missing Vacancy without deleting it. Unsafe runs never apply that policy. The Phase 2 feed
audit and proposed Joobs path are documented in `IMPORT_ARCHITECTURE_AUDIT.md`,
`JOOBS_FEED_MAPPING.md` and `IMPORT_MVP_PLAN.md`.

## Request/application layering

Use normal Laravel request flow and keep business logic out of views and bloated controllers.

Example only:

```text
Route
  -> Controller
     -> FormRequest (where needed)
     -> Action/Service (where useful)
     -> Eloquent
```

Do not create Actions/Services purely to satisfy this diagram.

The general Contact flow follows `Route → ContactController → ContactRequest →
ContactRequestMail`. `CONTACT_MAIL_TO` selects the environment-specific internal
recipient; Laravel's normal mail configuration controls sender and transport. Local
development defaults to the `log` mailer. Production must configure its actual mailer,
sender and recipient. The named `contact` limiter and a honeypot provide lightweight
abuse protection without external services. Mail errors are reported through Laravel's
exception handler but never shown verbatim to visitors, and no submitted Contact record
is written to the database.

## Admin architecture

Filament should manage the operational data needed by the business, including at minimum the domains that exist in the final MVP.

Import mapping is explicitly an admin UX problem as well as a backend problem.

Current Filament panel access is limited to `super-admin`, `admin` and `editor`.
Employer and candidate roles do not have unrestricted panel access. Editor permissions
are conservative pending later editorial refinement. The public `/account` area is
separate from the Filament `/dashboard` panel.

## Public employer placement

The first SMV-078 slice uses the existing Laravel `web` guard rather than a second auth
system. Public `/inloggen` and `/registreren` routes serve the shared public layout;
Filament keeps its own panel login and remains restricted by `User::canAccessPanel()`.
Employer-intent registration assigns the Spatie `employer` role and creates one pending
Company.

Company ownership is the nullable `companies.user_id` foreign key. `User::companies()`
is a one-to-many relation, so a User can manage zero, one or multiple Companies. There
is no Company membership/team pivot. Vacancy draft authorization therefore follows the
owning Company and never trusts a submitted Company outside that User's relation.

The placement flow stores only bounded session state before persistence: an
`AdvertisingPackage` selection and Laravel's normal intended URL. Once submitted, the
Vacancy records `placement_package` as commercial intent, not as a paid entitlement. SMV-110
provides auditable Orders with immutable product/price/currency/VAT snapshots, and SMV-111
makes payment outcomes testable without a real provider. Historical Orders must not
recalculate from mutable Package rows. SMV-112 implements hosted Stripe Checkout and verified
webhooks; the Fake gateway from SMV-111 remains the automated-test boundary and must not be
replaced by live Stripe calls in tests.
New public employer Vacancies remain `draft` through data entry/preview and become
`pending` on hand-off. They are never published or featured by this flow. SMV-050 now
provides the definitive configurable catalogue; SMV-110/111 own the provider-neutral
implementation boundary and SMV-112 connects it to Stripe. Environment credentials and webhook
registration remain deployment responsibilities.

`PackageCatalogue` resolves persisted Package rows by the stable `AdvertisingPackage` slug.
Persisted rows are the editable source of truth for public names/descriptions/benefits and
structured net price, currency, duration, placement quantity, quote/checkout flags, VAT and
validity dates. Configuration provides only the approved bootstrap values for fresh and test
installations. In production an inactive, expired, quote-only or VAT-incomplete Package fails
closed for checkout. Local/testing may explicitly retain expired fixtures without presenting
them as newly approved production prices.

The existing `Vacancy.is_featured` flag remains a presentation concern. SMV-050B provides a
separate commercial ranking layer only after `Vacancy::publiclyVisible()` and the requested
search/filter constraints have established the eligible set. Effective commercial score is
the sum of a configurable package score (initially Standaard 0 and Superior +20), Company
priority and Vacancy override; overrides may be negative. The package component applies only
for a valid active paid placement entitlement. `VacancyPackagePriorityProvider` is bound to
`EntitledVacancyPackagePriority`, whose correlated SQL reads the snapshotted package score only
inside the entitlement and public-eligibility window. Company priority and Vacancy adjustment are independently
usable. The default general Vacancy result order is effective priority, publication date,
creation date and ID. A valid explicit user sort bypasses commercial ordering and keeps the
existing deadline/title/date contract. Neither ranking nor deprioritization mutates publication
timestamps or overloads `is_featured`.

SMV-050B administration uses the dedicated policy-protected Filament **Vacatureranking** page
rather than the public Vacancy form. A paginated server-side table exposes component/effective
scores, inline Vacancy adjustments, Company/status/package filters, reference search and
transactional bulk set/add/reset. Company and Package resources expose their respective single
source fields. Values are signed small integers constrained to -1000 through 1000 in staff UI.
The application has no active model Activitylog convention, so this ticket does not introduce a
parallel audit mechanism; normal timestamps remain. Temporary boost expiry, HubSpot,
advertisement-channel integration and budget accounting remain outside the boundary.

`App\Actions\Orders\CreateOrder` is the single order-creation boundary. It validates
purchaser, Company and optional Vacancy ownership, resolves the approved package catalog,
snapshots all commercial values using integer minor units, and creates Order plus OrderItems in
one transaction. Order status is provider-neutral (`draft`, `pending_payment`, `paid`, with
legacy statuses retained for historical rows); Payment attempts remain a separate model and
are not created by this boundary. A placement package is intent, not entitlement: publication
still follows moderation and verified payment fulfilment. The configured
21% supported-MVP VAT is read from Package configuration rather than browser input; explicit
tax overrides remain possible only at the application boundary for supported situations.

`StartPayment` is the provider-neutral payment-attempt boundary. It resolves the configured
gateway, creates a pending Payment from the immutable Order total/currency, and applies the
gateway result. The Stripe implementation creates hosted Checkout Sessions from OrderItem net
and VAT snapshots, uses the Payment idempotency key for safe session retries, and never reads
mutable Package prices during checkout. Browser success/cancel routes are display-only.

`POST /stripe/webhook` verifies Stripe's signature against the raw request body before the
event reaches `ProcessStripeWebhook`. The processor maps the stored Checkout Session reference
back to the Payment, validates live/test mode, amount, currency and signed internal references,
and records each provider event once in `payment_webhook_events`. Paid provider events call
`ConfirmPayment` authoritatively; delayed failures or expiry cannot regress an already-paid
Order. `ConfirmPayment` and webhook handling are idempotent. The deterministic Fake gateway
remains the automated-test default and never performs network calls. Payment confirmation calls
the separate SMV-116 fulfilment action in the same transaction; it still never publishes a
Vacancy.

SMV-116 stores one Company-owned `PlacementCredit` per purchased unit, uniquely keyed by
OrderItem and grant sequence. OrderItems snapshot placement quantity, duration, package priority
and relevant benefits in addition to money. Grants, Company-scoped allocation and activation use
row locks, unique constraints and deduplicated `PlacementCreditEvent` history. A safely linked
manual Vacancy may receive one credit at payment, but remains pending; an unused compatible
credit can instead be selected for an owned draft without creating another Order. Duration
starts at first public eligibility, is never restarted by ordinary edits/reactivation, and
clamps rather than extends Vacancy expiry. The manual reconciliation command reuses the same
grant action for recovery. This supports several separately consumed grants from one OrderItem
without becoming a general wallet or financial ledger.

SMV-117 reuses the gateway boundary for authorized commercial reversals. An unpaid Order may
be cancelled locally under the Order state rules; a paid Order requires a provider-authoritative
refund, initially for the complete amount. The audit record must retain operator, reason,
amount, timestamps, Order and provider outcome. A consumed credit or active Vacancy creates an
explicit fail-closed resolution case—neither entitlement nor publication may be silently
restored, revoked or reversed. Partial refunds are deferred.

SMV-078B adds a deliberately small public account foundation. Authenticated users enter
through `/account`; employers can update only presentation/contact fields on Companies
whose `user_id` they own and can list Vacancies belonging to those Companies. Status,
owner, slug, featured state, import provenance and publication remain protected. Logo
and cover replacement reuse the existing single-file Media Library collections.

SMV-079 extends that same `web`-guard account with User-owned saved Vacancies and Company
profiles through separate unique `saved_vacancies` and `saved_companies` pivots. New
saves require the applicable canonical public-visibility rules. A guest continuation
stores only the content identifier in the session, revalidates it after authentication
and attaches idempotently. SMV-080B makes registration intent explicit: `/registreren`
offers `Werkzoekende` and `Werkgever`, while one shared `/inloggen` route and one `users`
table remain authoritative. A validated work-seeker intent creates a candidate-classified
User without Company records; an employer intent creates the pending owned Company used
by onboarding. The intent controls fields and continuation, not permanent product access.
Saved-content intents lead to work-seeker registration and Vacancy placement leads to
employer registration through one bounded resolver over the existing session intents.
Authorization remains permission- and ownership-based: an employer can save/apply, and
an existing work seeker can later create an owned Company in the placement flow without
a second User or auth guard.
Unavailable saved records retain their editorial relationship but render only a private
minimal unavailable state under `/account/bewaarde-vacatures` or
`/account/bewaarde-bedrijven`.

SMV-080 uses the existing nullable `applications.candidate_id` as the sole candidate
ownership boundary. An authenticated internal submission sets it from Laravel's session;
clients cannot submit another User id. Guest Applications remain supported with a null
candidate, and historical rows are never claimed by matching e-mail addresses. The
private `/account/sollicitaties` query always starts from `User::applications()` and has
no candidate detail route. It shows a mapped candidate status and only public Vacancy
context. If the Vacancy or Company is no longer public, the relationship remains but
protected content is replaced by a generic unavailable state.

Application status remains one internal enum. Filament presents the existing workflow
values to administrators, while the enum maps those values to intentionally coarser
candidate labels. External and e-mail application modes do not enter the Application
domain and are not represented as completed submissions.

Internal engagement reporting uses database-side counts over the existing unique save
pivots. A staff-only Filament widget shows current totals and deterministic rankings for
existing, non-soft-deleted Vacancies and Companies. It never loads saver lists or exposes
counts publicly. Spatie Activitylog is not used for save/unsave history in this phase;
historical trend reporting is explicitly deferred.

Vacancy publication is normalized server-side when a record transitions from a
non-published status to `published`: a missing `published_at` becomes `now()`. An
explicit future timestamp remains scheduled and an existing published record retains
its original timestamp on unrelated edits. Public eligibility remains query-driven, so
scheduled records become visible without a scheduler after the timestamp passes.
`deadline_at` is the candidate application cutoff and `expires_at` is the listing's
active-until boundary. Under the current MVP lifecycle both elapsed boundaries remove a
Vacancy from public surfaces; their business meanings and administration labels remain
separate.

Vacancy and Company descriptions use explicit domain semantic-HTML boundaries.
`VacancyDescription` and `CompanyDescription` share the narrowly scoped
`LimitedRichText` sanitizer policy, while keeping persistence and rendering decisions
in their own domains. The policy permits only paragraphs, line breaks, `h2`/`h3`,
strong/emphasis, ordered/unordered lists and links with approved schemes. Both models
normalize changed descriptions before persistence; Vacancy imports use the Vacancy
boundary. Public detail pages sanitize again defensively for pre-existing database
rows without rewriting them. Metadata and structured-data descriptions derive plain
text from the corresponding domain service.

## Import employer identity and Vacancy source metadata

ImportSource remains the provider/feed namespace. Existing single-employer sources use
their configured `company_id`. A source explicitly configured for source-scoped employer
resolution instead maps each record through `ImportSourceEmployer`: either an exact stable
provider ID or an administrator-reviewed exact source-name/source-URL composite. The
mapping is unique within source and identity type, may point multiple external identities
or sources to one Company, and never creates or edits Company profiles. An unresolved
identity skips the record without falling back to the source Company and makes missing
detection unsafe for that run.

Vacancy keeps its advertised/search city in `location`. Nullable provider-neutral fields
store source requisition, advertised state/country/postcode, actual workplace city/country,
raw salary text, language and external product code. Public search continues to use
`location`; detail pages distinguish a differing workplace, and JobPosting prefers valid
actual workplace data with legacy advertised-location fallback.

An ImportSource may opt into a named whole-payload contract. Preview and execution apply
it before record mapping; `joobs-1.0` delegates to `JoobsFeedContract` and then continues
through the generic XML/mapping/validation/persistence pipeline. Inclusive date-only
expiry uses the generic `date_end_of_day` transform, requires an explicit IANA timezone,
and stores the resulting end-of-day instant in UTC.

## Verified import snapshots and locking

An ImportSource must explicitly opt into snapshot reconciliation. The runner holds a
shared cache/database lock scoped to that source; a concurrent run is recorded as skipped
and different sources are not blocked. It fetches/reads, selects, maps and validates the
entire snapshot before applying any record. Duplicate or missing references, mapping or
resolution failures, partial-source signals, parse/read failures and unexpected empty
selection make the run unsafe and prohibit reconciliation. An empty snapshot is eligible
only when the source explicitly permits it.

After every record applies successfully, a verified snapshot may set `missing_since` on
source-owned Vacancies absent from its references. `missing_vacancy_policy=review_required`
keeps the existing editorial status. `auto_offline` changes a published missing Vacancy to
`archived`, records `source_offlined_at`, and preserves the row, import identity and history.
Manual Vacancies are outside reconciliation. Unsafe, partial or failed snapshots never
invoke either missing policy.

New imported records use the source's `publication_policy`: the conservative default
`review_required` creates `pending` Vacancies; explicitly trusted sources may use
`auto_publish`, which enters the existing `published` lifecycle without bypassing Company,
publication-date, deadline, expiry, filled-state or application validation boundaries.
Existing imported records keep their editorial status on ordinary feed updates. Mapped
import fields are feed-owned and may be overwritten; unrelated SMV operational fields are
not part of the mapper allowlist.

A reappearing reference always clears `missing_since` and retains the same Vacancy identity.
Only a Vacancy demonstrably archived by the source (`source_offlined_at`) may be restored to
`published`, and only when both source policies allow it and the record is not filled or
past its deadline/expiry. A manually archived record has no source-offline marker and stays
archived. The marker is cleared after reappearance. Permanent editorial ownership requires
a separate manual Vacancy. SMV-108 adds the staff-only Filament action **Maak handmatige
kopie** for source-owned Vacancies. It creates a new manual `draft` with a new slug, retains
the existing Company plus useful editorial content/taxonomies/tags and a currently safe
application destination, and clears provider identity/metadata, snapshot markers,
publication dates, featured/filled state and placement-package intent. The original is not
changed. Because the copy has no `import_source_id` or `source_reference`, feed upserts,
missing reconciliation and source-driven restoration cannot select it.

Publication and missing behavior remain independent per-source policies. Commercial
ranking remains a separate future feature, and raw outbound apply clicks retain their
approved 24-month maximum retention.

## Scheduled import execution

Automatic execution is an explicit, disabled-by-default ImportSource capability for
approved active HTTP/API sources. Each source selects an hourly, six-hourly or daily
cadence and records its last dispatch time. The minute-level scheduler calls a lightweight
dispatcher; it never reads or imports a feed inline. Uploaded sources remain manual-only.

Each due source dispatches a unique `RunScheduledImport` database-queue job to the
`imports` queue. The job reloads eligibility, selects the active default mapping and calls
`VacancyImportRunner`. Queue uniqueness prevents duplicate queued/in-flight jobs, while
the runner's cache/database lock protects manual and queued execution together. Different
sources do not block one another.

Execution is bounded to two attempts and 600 seconds. The existing source lock remains
900 seconds and database `retry_after` is 960 seconds. This invariant prevents a valid job from losing its
lock and prevents a timed-out reservation from retrying before a stale lock expires. A
scheduled worker drains only the import queue and exits when empty or after ten minutes;
no persistent daemon is assumed.

Runner failures remain Import records before the job requests a retry. Retries do not
bypass source-scoped upserts, contract validation or snapshot safety. This infrastructure
does not activate Joobs or resolve partner-specific activation gates.

## Import monitoring and operator recovery

ImportSource health is a provider-neutral projection over recent Import records rather
than a second persisted monitoring history. It separates dispatch time, latest execution
attempt, completion, success and verified completion. Automatic sources use their own
configured cadence plus a bounded tolerance for overdue detection; `last_scheduled_at`
never proves that a worker consumed the job. Two consecutive failed/incomplete runs form
the MVP repeated-failure state, while lock contention retains the existing skipped run.

Verified snapshot counts support a visibility-only substantial-drop warning. A warning
does not invalidate a snapshot or change reconciliation. Run provenance is a nullable
manual/scheduled enum so old rows remain compatible. Administrators can recover only by
starting a fresh run through the default mapping and normal runner; locks, identity,
validation and snapshot safety remain authoritative. There is no resume or delete path.

Filament exposes safe endpoint labels, statuses, timestamps, counts, snapshot outcomes
and allowlisted log context. Import notification delivery is deferred until a dedicated
operations recipient and owner are approved; the public contact recipient is not reused.
Deployment diagnostics and recovery commands are maintained in `IMPORT_OPERATIONS.md`.

## Public frontend

Blade + Tailwind remains the preferred frontend stack.
Alpine.js is suitable for lightweight behavior.

Blade `x-*` components are a preferred implementation pattern. Existing Mosaic/Tailwind
components, markup and assets are the reusable design base. Current demo routes, route
names, layout wiring and prototype page architecture are not authoritative.
`x-app-layout` may be adapted where useful, but broken demo architecture need not be
preserved. Useful components and template markup should be composed into the production
public flow.

The public interface and SEO-facing copy are Dutch; internal identifiers and developer
documentation may remain English.

The production public surface uses `layouts.public`, `HomeController`, the dedicated
public controllers and Blade components under `components/ui`, `components/vacancy`,
`components/company`, `components/blog` and `components/home`. The older
`pages/component`, `pages/job`, `vacatures` prototype views and dashboard-style layout
are not routed. The retained catalogue is compileable through `<x-app-layout>` and
obsolete duplicates were removed in SMV-075, so `artisan view:cache` remains a release
validation command rather than a known blocker.

Company discovery always starts from `Company::publiclyVisible()`. The index applies a
bounded text query and at most one `company_category` slug, eager-loads card media and
typed categories, and aggregates `Vacancy::publiclyVisible()` counts. Category-browser
counts use one constrained aggregate query rather than per-category queries. Saved state
continues to use the authenticated bulk `withExists` query. The homepage employer banner
uses a separate deterministic, bounded public Company query with eager-loaded media.

Homepage and `/vacatures` compose the same `VacancySearch` and `VacancyFilterOptions`
services. Public visibility is applied before keyword/place, typed Category, Company and
compensation constraints. Comparable compensation delegates to the Vacancy scopes from the
SMV-082 contract; the search layer only applies validated inclusive interval overlap. Blade
receives prepared options/errors and does not query. GET state keeps both surfaces shareable,
while Alpine only enhances native form/disclosure behavior.

Do not add Vue/React/another design system without explicit approval.

## Background processing

Imports may become long-running. If current import size/hosting supports queues, isolate the execution so moving work to queued jobs is straightforward.

Do not require queue infrastructure for simple preview/parsing work when synchronous execution is safe and faster to ship.

## Partner reporting and result ordering boundaries

Partner reporting has separate event meanings. A listing impression, public Vacancy
detail view, outbound apply click, partner-confirmed click, completed Application and
contract-defined billable click are not interchangeable. The first SMV reporting slice
should aggregate minimized detail-view and outbound-click events by Vacancy, import
source and date. Partner-confirmed/billable events require an explicit reconciliation
contract and must not be inferred from raw internal clicks. Retention, bot/duplicate
rules and staff-only access are part of the domain boundary, not presentation details.

SMV-097B implements outbound clicks as append-only `outbound_apply_clicks` rows containing
only the Vacancy foreign key and `clicked_at`. ImportSource and Company attribution are
derived through Vacancy rather than copied into the event. The public endpoint accepts no
destination parameter, requires the Vacancy and Company to be publicly eligible, permits
only a credential-free stored HTTP(S) URL, records the event, and redirects to that stored
string unchanged. Every successful request is a raw click; there is no cookie, fingerprint,
IP/user-agent storage, bot classification or unique-human claim. Internal/e-mail application
modes and completed `Application` records remain separate.

SMV-097C exposes these events only inside the Filament panel to `super-admin` and `admin`
roles. A shared reporting query applies inclusive UTC date, Vacancy, Company and
ImportSource filters to raw events, then derives per-Vacancy counts and Company/source
aggregates. The default period is the latest 30 UTC calendar days. CSV export uses the
same filters and emits one aggregate row per Vacancy; it never exports raw event rows or
personal/request metadata. Raw events remain the source of truth, and the standalone
`clicked_at` index supports date-first reporting alongside the existing
`vacancy_id, clicked_at` index.

SMV-097D enforces the approved maximum raw-event retention of 24 months. The daily UTC
`analytics:prune-outbound-clicks` schedule deletes only rows with `clicked_at` strictly
before a cutoff calculated once for that run. Deletion walks a fixed eligible ID range in
bounded batches; a dry-run counts without mutation. Command output and structured Laravel
logs record the run time, cutoff and eligible/deleted counts without event-level data.
Reporting remains query-derived, so periods outside the retained raw window return only
whatever data is still available (normally zero); no historical values are reconstructed.

SMV-097A extends this model with a separate minimal raw Vacancy-detail-view event for each
successful public detail response. It excludes previews, non-public responses and irrelevant
internal routes, and stores no visitor fingerprint, cookie, IP address or identity solely for
analytics. A raw view must never be labelled a unique visitor. Raw view rows follow the same
24-month maximum retention and bounded pruning policy as raw outbound clicks. Staff reporting
may join the two aggregate series by the same date/Vacancy/Company/ImportSource filters and
show **raw outbound-apply CTR = raw outbound clicks / raw detail views**; a zero denominator
produces no CTR value. Existing outbound reporting and privacy constraints remain unchanged.

Result ordering also separates three concerns: data completeness/search relevance,
organic SEO visibility, and explicit commercial promotion or deprioritization. Current
publication timestamps describe lifecycle and must not be edited to simulate commercial
priority. SMV-050B is the approved focused commercial mechanism: explicit score components,
optional expiry for a bounded manual boost, audit evidence and deterministic ordering. It
does not alter search eligibility or organic SEO claims and stays independent from the
optional SMV-118 banner presentation.

## Deployment

Open decision: the Hostinger/staging deployment workflow still requires confirmation
from repository/server operations information that is not currently available.

The build must support a staging environment before production cutover.

## Production-like acceptance data

SMV-109 is a disposable, repeatable acceptance dataset rather than another factory layer. It
will exercise representative Users/roles, Companies, manual and imported Vacancy lifecycle
states, application modes, saves, Applications, ImportSources, missing/reappearing import
history, outbound-click reporting and later Orders. It must never contain production
credentials, partner secrets or copied personal data. The same dataset is a dependency for
SMV-072 browser smoke and SMV-073 migration/deployment rehearsal.

# SMV Architecture

## Status

The repository audit and foundation stabilization are complete. The application now has
working public recruitment flows, Filament administration, a format-independent import
pipeline, a native Blog and the technical SEO foundation. The remaining work is mostly
commercial-scope confirmation, content/brand approval, legacy URL migration and release
hardening.

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
Completed runs can record missing source references in `missing_since`; this does not
unpublish or delete a Vacancy. Any future absence-driven depublication must wait for a
verified complete-feed boundary and an approved manual-override rule. The Phase 2 feed
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
Vacancy records `placement_package` as commercial intent, not as a paid entitlement.
New public employer Vacancies remain `draft` through data entry/preview and become
`pending` on hand-off. They are never published or featured by this flow. SMV-050 owns
the definitive package/entitlement domain and SMV-051 owns Orders and Payments.

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
source-owned Vacancies absent from its references. This is reporting metadata, not an
unpublish/delete operation. A reappearing reference clears that marker while preserving
the Vacancy's existing editorial status and expiry lifecycle.

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

Result ordering also separates three concerns: data completeness/search relevance,
organic SEO visibility, and explicit commercial promotion or deprioritization. Current
publication timestamps describe lifecycle and must not be edited to simulate commercial
priority. Any later sponsored/deprioritized mechanism needs explicit effective dates,
reason/audit data, deterministic ordering and disclosure. It is a version II feature
unless stakeholders approve it as a release requirement.

## Deployment

Open decision: the Hostinger/staging deployment workflow still requires confirmation
from repository/server operations information that is not currently available.

The build must support a staging environment before production cutover.

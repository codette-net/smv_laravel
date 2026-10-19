# External Vacancy Feed Import Architecture Audit

**Audit date:** 19 October 2026  
**Scope:** read-only audit using the actual Joobs v1 partner files and repository code.
This document proposes follow-up work; it does not approve application changes.

## Evidence

The authoritative files are `docs/integrations/joobs/joobs-job-feed-v1.md` and
`docs/integrations/joobs/joobs-job-feed-v1-example.xml`. The full v1 field matrix is in
[JOOBS_FEED_MAPPING.md](JOOBS_FEED_MAPPING.md).

The working tree was clean at the start of this phase. Verification performed:

```text
php artisan test --compact tests/Feature/ImportMissingLifecycleTest.php \
  tests/Feature/ImportEndToEndTest.php tests/Feature/ImportReadingTest.php \
  tests/Feature/ImportValidationTest.php tests/Feature/VacancyLifecycleTest.php
139 passed (870 assertions)
```

The tests cover import, lifecycle, Vacancy/Company public pages, Blog, SEO, Filament
authorization/admin and internal application flows. A read-only PHP DOM check confirmed
the supplied XML is well formed, declares `joobs-1.0`, contains 2 jobs, has 2 distinct
references, and shares 1 requisition ID. No existing automated test consumes this Joobs
fixture. Browser and staging behavior remain unverified.

## Audit summary

| Capability | Status | Evidence / limitation |
| --- | --- | --- |
| Import source, mapping, run, log, and taxonomy mapping persistence | Implemented and tested | `app/Models/{ImportSource,ImportMapping,ImportMappingField,Import,ImportLog,ImportTaxonomyMapping}.php`; import migrations in `database/migrations/`; `tests/Feature/ImportSourceTest.php`, `ImportMappingTest.php`, `ImportHistoryTest.php`. A model/table alone is not execution support. |
| XML, JSON, CSV, XLSX reading | Implemented and tested generically | `app/Imports/Readers/{XmlReader,JsonReader,CsvReader,SpreadsheetReader}.php`, `ImportReaderResolver.php`; `tests/Feature/ImportReadingTest.php`. XML reads `job` nodes and CDATA as text, but Joobs root/version semantics have no test. |
| Upload and guarded HTTP/API source loading | Implemented and tested | `app/Imports/{LocalSourceLoader,SourceFetcher}.php`; `tests/Feature/ImportSourceUploadTest.php`, `ImportSourceTest.php`. Remote fetch has timeouts, a size limit, redirect limit, URL/IP checks, and HTTPS for approved automatic sources. |
| Record path, selection, bounded field discovery | Implemented and tested | `app/Imports/{RecordSelector,FieldDiscovery}.php`; `tests/Feature/ImportReadingTest.php`, `ImportMappingTest.php`. XML record path defaults to `job`. |
| Reusable field mapping, transforms, normalized preview | Implemented and tested | `app/Imports/Mapping/{ImportMapper,DestinationRegistry,NormalizedVacancyData,CompensationTextParser}.php`, `app/Imports/Preview/ImportPreview.php`; `tests/Feature/ImportMapperArrayTest.php`, `ImportPreviewTest.php`, `ImportEndToEndTest.php`. Transform operations are a fixed code registry, not a general expression system. |
| Single-company source association | Implemented and tested | `ImportSource.company_id` is required by `ImportSourceForm`; runner assigns it to every Vacancy. Company fields are preview destinations only; runner does not create, match or update Companies. `tests/Feature/ImportEndToEndTest.php`, `VnomProductionConfigurationTest.php`. |
| Joobs multi-employer Company resolution | Missing | Joobs requires `job/company` as hiring employer, and partner feeds can contain ads from multiple customers. No stable employer ID is specified. The fixed source Company cannot represent this safely. |
| Taxonomy/category mapping | Implemented and tested | `TaxonomyResolver` uses source-scoped explicit aliases then unique exact name/slug matches; unresolved values block the row, and Categories are not created. `tests/Feature/ImportValidationTest.php`, `ImportMappingAdminTest.php`, `VacancyTaxonomyTest.php`. |
| Provider-scoped identity and update | Implemented and tested | DB uniqueness and runner lookup use `import_source_id + source_reference`; reruns update mapped fields on the same row. `tests/Feature/ImportEndToEndTest.php`, `VacancyImportRunnerTest.php`. |
| Per-record validation, sanitization, failures and logs | Implemented and tested | `ImportRecordValidator`, `VacancyDescription`, `VacancyImportRunner`; row-level failures increment counters/log a safe code, and unresolved taxonomy skips the row. `tests/Feature/ImportValidationTest.php`, `ImportHistoryTest.php`, `VacancyDescriptionTest.php`. |
| Missing/restored source reporting | Implemented and tested, not equivalent to Joobs removal behavior | Runner records `missing_since`, `last_seen_import_id`, and restored count; does not unpublish/delete. `tests/Feature/ImportMissingLifecycleTest.php`. |
| Empty and filtered-empty feed protection | Partially supported; critical gap | XML with no matching `job` throws. But if records exist and selection rules select none, runner can reconcile with an empty reference list and mark all source Vacancies missing. There is no minimum record count/snapshot confirmation. |
| Safe feed completeness for reconciliation | Partially supported; unsafe for automated removal | Runner writes each record before the whole document is verified. XML parser completion errors are not explicitly checked. Duplicate-reference preflight, verified-snapshot boundary, and per-source concurrency lock are absent. Malformed/truncated-tail behavior is untested. |
| Expiration/public visibility | Implemented and tested without scheduled status mutation | `Vacancy::publiclyVisible()` excludes elapsed `deadline_at` or `expires_at` at query time. No expiration command/job changes `status` to `expired`. `tests/Feature/VacancyLifecycleTest.php`, `PublicVacancyIndexTest.php`, `PublicVacancyShowTest.php`. |
| Filament import configuration, preview, history and manual execution | Implemented and tested, synchronous | Filament provides source setup, mapping, preview, taxonomy resolution, a confirmed execute action and run history. No retry/recovery control. `tests/Feature/ImportMappingAdminTest.php`, `ImportHistoryTest.php`. |
| Scheduler and import command | Missing | `routes/console.php` only defines `inspire`; `bootstrap/app.php` has no schedule. No source-selection command exists. |
| Queue primitives | Configured, unused by imports | `config/queue.php` defaults to database and failed-job storage; jobs/cache migrations exist. No application `ShouldQueue` import job. Deployed queue environment/worker support is unknown. |
| Vacancy view/click analytics | Missing | No public detail-view event, outbound redirect endpoint, impression/click event table, or conversion attribution exists. `Application` records only SMV's internal application submissions; external applications are not completion events. |

### Import records, identity, Company and taxonomy

`ImportSource` is the provider/configuration root. It stores transport, format,
endpoint, encrypted credentials, configuration, record path, selection rules, active
state, approval, one required owner Company, and last-import metadata. `Import` represents a run
with status, counters and timestamps; `ImportLog` stores safe summaries/context. The
physical legacy `imports.source` field is not the provider identity. Mappings and
field rows are reusable per source; taxonomy aliases are separate source-scoped rows.

The runner always uses the configured source Company. Joobs requires `job/company` to
name the hiring employer and says a partner feed includes ads from multiple customers;
the source Company likely differs from the advertiser. A per-source explicit company
map is the smallest safe proposal. Request a stable partner employer ID. The spec only
calls `joobs_companyurl` an employer page, so do not assume it is a durable identifier.
Without an ID, require an admin-reviewed exact company name + URL mapping; never use
fuzzy/name-only auto-match or fall back to the source Company. Unmatched/ambiguous rows
should be quarantined/skipped. A reviewed imported Company should default to pending.
Vacancy imports may attach to manual Companies but must never modify their profile,
owner, status or media.

Categories resolve by matching `Category.type` and either an explicit source alias or
a unique case-insensitive exact name/slug. Aliases are editable from mapping preview.
There is no fuzzy match, automatic category creation, or source-specific category
guessing. Tags remain flexible descriptive labels.

### Lifecycle and reconciliation findings

Current canonical public eligibility is `status=published`, not filled, publication
time reached, and both `deadline_at` and `expires_at` null or not elapsed. The list and
detail queries apply this rule. Expired items are hidden by queries without changing
their status.

Imported new rows start `pending`; an update preserves the existing vacancy status.
The runner maps source `published_at`, deadlines and expiry only when configured. It
does not publish a newly imported row automatically. Manual status changes are not
represented by an ownership/override field: updates preserve status, while mapped
content fields may be overwritten. A missing feed record only gains a `missing_since`
marker; a manually published imported row remains publicly visible unless its existing
date/status rules exclude it.

An individual record validation/processing failure increments row counters and disables
missing reconciliation, but the overall Import is still finalized as `completed`; only
source-level exceptions produce `failed`. Previously processed rows may already have
been updated before a later row fails. This distinction should be visible in future run
monitoring and must not be treated as a fully successful snapshot.

The missing detector runs after the row loop when no row processing exception occurred.
HTTP/read failure and row-level invalidity disable reconciliation. But a valid document
where selection rules match zero records leaves the reliability flag true and passes an
empty reference list to missing detection; the runner then marks every source Vacancy
missing. Empty XML with no `<job>` throws, but filtered-empty is a different dangerous
path. The XML reader does not inspect libxml errors after iteration, and records are
persisted before the full document is verified. Before absence-based unpublishing,
validate the full envelope and all records first; reject empty snapshots and duplicate
references; stage/map/validate before persistence; and reconcile only after all writes
complete. Invalid-row tolerance must be explicit and should fail the snapshot closed.

### Scheduling, queues and hosting

There is no Laravel task schedule, import CLI command, import queue job, or expiry
command in the current repository. `config/queue.php` defaults to database and configures
failed-job storage, but imports do not dispatch jobs. The exact Hostinger plan, CLI PHP
path, cron frequency, runtime limits, SSH access and queue worker capability are unknown
from the repository. Hostinger's public guidance says hPanel cron schedules use UTC and
provides an Artisan `schedule:run` example; verify actual account plan/settings before
relying on it: [Cron setup](https://support.hostinger.com/en/articles/1583465-how-to-set-up-a-cron-job),
[Laravel guidance](https://support.hostinger.com/en/articles/1583301-which-laravel-versions-are-supported-at-hostinger).

Smallest proposed operating model:

1. Add a command selecting explicitly active, approved, schedule-due sources and
   recording each run; never print private URLs/keys. Cadence belongs to each source;
   Joobs is hourly, other providers configurable.
2. For short bounded runs, a single hPanel cron entry can call `php artisan schedule:run`
   every minute, with hourly events inside Laravel. Hostinger cron is UTC; verify the
   actual plan supports this cadence and measure command duration.
3. If runs exceed safe synchronous runtime, use database queue jobs only if cron/CLI
   can frequently start bounded `queue:work --stop-when-empty` processes. No daemon,
   Redis or Supervisor should be assumed. Otherwise run the bounded importer on a worker
   host/VPS or keep low-volume synchronous execution.
4. Use scheduler overlap prevention and a per-source lock. A job owns one run ID and
   must be idempotent. Retrying creates an auditable run; never reconcile absence on a
   failed/incomplete snapshot. Monitor stale processing, consecutive failures and
   record-count anomalies; recovery is inspect source -> retry -> verify results.
5. Filament manual execution is confirmed and synchronous. Keep for bounded test/import
   jobs; queue it only if actual feed size/runtime warrants it.
6. Expiration already hides records query-side; a daily status-changing expiry command
   is optional admin maintenance, not a public visibility requirement.

### Tracking and analytics readiness

| Event | Existing support | What is needed |
| --- | --- | --- |
| Impressions | Missing | Define whether this means a rendered listing card or a visible card impression; record with a privacy-conscious event/counter model and deduplication window. |
| Vacancy detail-page views | Missing | Count successful public detail responses, define bot/internal traffic exclusions and retention, and associate to vacancy/provider. |
| SMV outbound clicks | Missing | Add a same-site redirect route, validate the destination against the stored application URL, write a click event, then redirect. Preserve the stored Joobs URL including UTM parameters. |
| Partner-confirmed clicks | Missing | Requires a Joobs reporting callback/export or partner report and an agreed join key; SMV outbound clicks alone do not prove partner receipt. |
| Completed applications | Partial / separate domain | Internal submissions are stored as Applications; external destinations leave SMV and do not prove completion. |
| Billable clicks | Missing | Requires a signed commercial definition, deduplication/fraud policy, partner confirmation source and retention rules. |

CTR must state its denominator. For example, **SMV outbound CTR** = unique eligible
outbound click events / listing-card impressions for the same vacancy and period;
**detail conversion** = unique outbound clicks / detail views. Do not call either
partner-confirmed clicks or completed applications. UTM values provide attribution
parameters at the destination, not independent evidence that an event or conversion
occurred. Avoid IP address, full user agent, candidate identity, query strings beyond
necessary campaign metadata, and raw referrers unless a documented purpose requires
them; define aggregation, retention, access and consent/privacy review before launch.

### Partner/commercial context

The partner information is not evidence of one common billing model. VONQ relationship
exists but integration/terms need inventory; Brockmeyer/Appcast and payment details need
confirmation; VNOM feed exists and CPC is under consideration only; Joobs product
combinations and terms are ongoing; Adver is prospective. Do not assume CPC.
`joobs_product` is per-ad source metadata, not SMV `placement_package`.

### Search relevance and ranking

`VacancySearch` matches keyword against title, description and Company name, location
exactly, and offers type-scoped Category, Company and comparable compensation filters.
Sorts are newest, deadline or title. There is no featured/sponsored boost and tags are
not keyword searched. Internal relevance is SMV matching/ranking; user filters are
explicit constraints; Google organic visibility depends on crawlable useful content,
technical SEO and structured data; featured/sponsored order is a commercial rule.
Google says JobPosting structured data makes a page eligible for a job-search
experience, not that it guarantees ranking; crawlable links and people-first content
remain separate requirements. See [JobPosting guidance](https://developers.google.com/search/docs/appearance/structured-data/job-posting),
[crawlable link guidance](https://developers.google.com/search/docs/crawling-indexing/links-crawlable),
and [helpful content guidance](https://developers.google.com/search/docs/fundamentals/creating-helpful-content).
Map only Joobs' supplied closed category list to reviewed typed Categories. Keep
workplace, employment, qualification and experience meanings in their existing types.
Do not inflate categories or claim extra tags improve Google rankings.

### Staging demo evidence

Automated feature tests passed, but no browser or staging environment was exercised.
Existing feature coverage includes Vacancy search/detail/lifecycle, Company pages, Blog,
SEO, Filament import/admin and internal applications. SMV-072 remains partial because
there is no committed browser critical-path suite. Content approvals and actual hosting
and deployment settings need staging verification.

Checklist: homepage; Vacancy search, filters, sorts and pagination; Vacancy detail and
application modes; Company index/detail; Blog index/detail/archives; Filament login,
source/mapping/preview/manual import/history if in scope; external application URL;
canonical/title/robots/sitemap; mobile and keyboard checks; staging HTTPS, private
storage, mail, non-production data, `noindex`, logging and environment settings. Joobs
automation is not a demo blocker absent a confirmed requirement.

## Recommended state boundaries

Current `Import` states are `pending -> processing -> completed|failed`. The runner
can mark a run `completed` despite row failures; it skips missing reconciliation in that
case, but there is no distinct partial-success state. `Vacancy` status is
`draft|pending|published|expired|archived`. `missing_since` is separate metadata and is
not currently included in `publiclyVisible()`.

Proposed run and vacancy transitions:

```text
Import: pending -> fetching/parsing -> staged -> verified -> applying -> completed
                          |             |              |
                          +-- failure --+--------------+-> failed; no absence action

Source-managed Vacancy availability:
present + published -> absent in verified complete snapshot -> unavailable immediately
unavailable -> appears in later verified snapshot -> present; preserve Vacancy.status
new ad -> pending review unless owner approves automatic publication policy
expirationdate day -> remains public through local end of day -> hidden after UTC cutoff
admin archived/expired -> stays archived/expired on source update or return
```

For Joobs, the spec says absent ads go offline, so recommend suppressing visibility as
soon as a **verified complete** feed omits an ad; do not wait a grace period unless Joobs
or the product owner approves one. Keep the Vacancy row and import history. This could
use source availability metadata (for example, `missing_since`) as an independent public
visibility predicate instead of rewriting editor `status`. On reappearance, clear the
availability marker but preserve `status`; a manually archived/expired row stays hidden,
while a previously published row may return because Joobs now confirms it is active.
Current `missing_since` cannot do this yet because public queries ignore it.

Manual status overrides are respected by not writing `status` from import; current code
already preserves status on updates. However, mapped content fields are overwritten by
the feed, so an editor who changes an imported title/description/location will lose that
change on next run. Before release, either declare mapped fields source-owned/read-only
in admin or add explicit per-field overrides; do not silently promise editable overrides.
Never soft-delete source rows: the unique provider/reference key and soft-delete query
scope make reimport of a soft-deleted key unsafe.

`expirationdate` should remain visible through the final calendar day in
`Europe/Brussels`, then expire at the corresponding UTC instant. It populates
`expires_at`, not `deadline_at`. `Europe/Brussels` is the recommended business zone but
requires partner approval and a decision for non-Belgian business days/DST. New ads
remain `pending` unless a separate automatic-publication policy is approved.

## Relevant implementation and tests

- Source/configuration: `app/Models/ImportSource.php`,
  `app/Filament/Resources/ImportSources/`, `tests/Feature/ImportSourceTest.php`,
  `ImportSourceUploadTest.php`.
- Import records/history: `app/Models/{Import,ImportLog}.php`,
  `app/Filament/Resources/Imports/`, `tests/Feature/ImportHistoryTest.php`.
- Reader/fetch/select/discovery: `app/Imports/{SourceFetcher,ImportReaderResolver,RecordSelector,FieldDiscovery}.php`,
  `app/Imports/Readers/`, `tests/Feature/ImportReadingTest.php`.
- Mapping/preview/validation: `app/Imports/Mapping/`, `app/Imports/Preview/`,
  `app/Imports/Validation/`, `tests/Feature/ImportMappingTest.php`,
  `ImportPreviewTest.php`, `ImportValidationTest.php`.
- Persistence/reconciliation: `app/Imports/VacancyImportRunner.php`,
  `tests/Feature/VacancyImportRunnerTest.php`, `ImportEndToEndTest.php`,
  `ImportMissingLifecycleTest.php`.
- Description and vacancy lifecycle: `app/Support/Vacancies/VacancyDescription.php`,
  `app/Models/Vacancy.php`, `tests/Feature/VacancyDescriptionTest.php`,
  `VacancyLifecycleTest.php`, `PublicVacancyShowTest.php`.
- Scheduler/queue evidence: `bootstrap/app.php`, `routes/console.php`,
  `config/queue.php`; no application job class or schedule definition found.
- Tracking evidence: `routes/web.php`, `app/Http/Controllers/VacancyController.php`,
  `app/Http/Controllers/ApplicationController.php`, and migration/model search; no
  vacancy impression/outbound click implementation found.
- Search evidence: `app/Support/Vacancies/VacancySearch.php`,
  `VacancyFilterOptions.php`, `tests/Feature/PublicVacancyIndexTest.php`,
  `tests/Feature/CompensationContractTest.php`.
- Partner requirements: `docs/integrations/joobs/joobs-job-feed-v1.md` and
  `docs/integrations/joobs/joobs-job-feed-v1-example.xml`.

## Proposed decisions needing approval

1. Is Joobs required for the demo? Is the feed endpoint per partner or can it contain
   multiple employer customers? Joobs does not block the demo absent confirmed scope.
2. Should valid Joobs ads auto-publish or stay pending moderation?
3. Should removal unpublish immediately after one verified snapshot, or use a grace
   period/manual review? How do manual overrides and reappearance work?
4. Approve `Europe/Brussels` for inclusive expiration dates and behavior for FR/DE/LU
   and DST.
5. Do requisition ID, workplace city/country, language and product code need durable
   fields for MVP reporting/search?
6. Request stable employer IDs or confirmation that `joobs_companyurl` is stable;
   obtain category closed list and confirm contract/workplace aliases.
7. Verify the Hostinger plan, cron cadence/timezone, PHP CLI, SSH, process/runtime,
   private storage, DB/cache locks and worker capability.
8. Confirm VONQ, Brockmeyer/Appcast, VNOM, Joobs and Adver commercial products,
   billing models, click reports, attribution keys and retention. No common CPC assumption.
9. Approve analytics definitions, privacy basis, consent, retention and access controls.


# External Vacancy Feed Import Architecture Audit

**Audit date:** 19 October 2026  
**Scope:** read-only application audit and documentation proposal for SMV-083. No
application behavior was changed.

## Evidence and reference limitation

The repository contains no `joobs-job-feed-v1.md` or
`joobs-job-feed-v1-example.xml`. The findings below use the Joobs characteristics
provided in the task. The exhaustive field list and XML example still need review
against those two documents; see [JOOBS_FEED_MAPPING.md](JOOBS_FEED_MAPPING.md).

The working tree was clean before this audit. Relevant existing tests were run:

```text
php artisan test --compact tests/Feature/ImportMissingLifecycleTest.php \
  tests/Feature/ImportEndToEndTest.php tests/Feature/ImportReadingTest.php \
  tests/Feature/ImportValidationTest.php tests/Feature/VacancyLifecycleTest.php
41 passed (259 assertions)
```

These are repository behavior checks, not proof of Joobs compatibility or safe
unattended production operation.

## Audit summary

| Capability | Status | Evidence / limitation |
| --- | --- | --- |
| Import source, mapping, run, log, and taxonomy mapping persistence | Implemented and tested | `app/Models/{ImportSource,ImportMapping,ImportMappingField,Import,ImportLog,ImportTaxonomyMapping}.php`; import migrations in `database/migrations/`; `tests/Feature/ImportSourceTest.php`, `ImportMappingTest.php`, `ImportHistoryTest.php`. A model/table alone is not execution support. |
| XML, JSON, CSV, XLSX reading | Implemented and tested | `app/Imports/Readers/{XmlReader,JsonReader,CsvReader,SpreadsheetReader}.php`, `ImportReaderResolver.php`; `tests/Feature/ImportReadingTest.php`. No Joobs fixture is present. |
| Upload and guarded HTTP/API source loading | Implemented and tested | `app/Imports/{LocalSourceLoader,SourceFetcher}.php`; `tests/Feature/ImportSourceUploadTest.php`, `ImportSourceTest.php`. Remote fetch has timeouts, a size limit, redirect limit, URL/IP checks, and HTTPS for approved automatic sources. |
| Record path, selection, bounded field discovery | Implemented and tested | `app/Imports/{RecordSelector,FieldDiscovery}.php`; `tests/Feature/ImportReadingTest.php`, `ImportMappingTest.php`. XML record path defaults to `job`. |
| Reusable field mapping, transforms, normalized preview | Implemented and tested | `app/Imports/Mapping/{ImportMapper,DestinationRegistry,NormalizedVacancyData,CompensationTextParser}.php`, `app/Imports/Preview/ImportPreview.php`; `tests/Feature/ImportMapperArrayTest.php`, `ImportPreviewTest.php`, `ImportEndToEndTest.php`. Transform operations are a fixed code registry, not a general expression system. |
| Source company ownership and association | Implemented and tested | `ImportSource.company_id` is the authoritative Company; `VacancyImportRunner::persist()` assigns it. Source company fields are destinations for mapping/preview, but do not create, match, or overwrite Companies. `tests/Feature/ImportEndToEndTest.php`, `VnomProductionConfigurationTest.php`. |
| Taxonomy/category mapping | Implemented and tested | `TaxonomyResolver` uses source-scoped explicit aliases then unique exact name/slug matches; unresolved values block the row, and Categories are not created. `tests/Feature/ImportValidationTest.php`, `ImportMappingAdminTest.php`, `VacancyTaxonomyTest.php`. |
| Provider-scoped identity and update | Implemented and tested | DB uniqueness and runner lookup use `import_source_id + source_reference`; reruns update mapped fields on the same row. `tests/Feature/ImportEndToEndTest.php`, `VacancyImportRunnerTest.php`. |
| Per-record validation, sanitization, failures and logs | Implemented and tested | `ImportRecordValidator`, `VacancyDescription`, `VacancyImportRunner`; row-level failures increment counters/log a safe code, and unresolved taxonomy skips the row. `tests/Feature/ImportValidationTest.php`, `ImportHistoryTest.php`, `VacancyDescriptionTest.php`. |
| Missing/restored source reporting | Implemented and tested, with material limitations | Runner records `missing_since`, `last_seen_import_id`, and restored status; it does not unpublish/delete missing vacancies. `tests/Feature/ImportMissingLifecycleTest.php`. Empty XML with no matching record path throws and fails before reconciliation. No explicit zero-record confirmation exists for other formats. |
| Safe feed completeness for reconciliation | Partially supported | Source fetch/read failure and row failure disable reconciliation; however, there is no persisted “complete snapshot verified” boundary. XML parser completion errors after yielding rows are not explicitly checked in `XmlReader`. Treat malformed/partial document behavior as unverified. |
| Expiration/public visibility | Implemented and tested without scheduled status mutation | `Vacancy::publiclyVisible()` excludes elapsed `deadline_at` or `expires_at` at query time. No expiration command/job changes `status` to `expired`. `tests/Feature/VacancyLifecycleTest.php`, `PublicVacancyIndexTest.php`, `PublicVacancyShowTest.php`. |
| Filament import configuration, preview, history | Implemented and tested; execution is synchronous | Resources/pages under `app/Filament/Resources/{ImportSources,ImportMappings,Imports}` provide configuration, mapping, preview/resolution, and run history. No operator retry/recovery or automatic run controls. `tests/Feature/ImportMappingAdminTest.php`, `ImportHistoryTest.php`. |
| Scheduled or queued import execution | Missing | `routes/console.php` only defines `inspire`; `bootstrap/app.php` has no schedule callback; no application import Job or `ShouldQueue` class exists. The default queue connection is configured in `config/queue.php`, but this is not evidence of a worker. |
| Vacancy view/click analytics | Missing | No public detail-view event, outbound redirect endpoint, impression/click event table, or conversion attribution exists. `Application` records only SMV's internal application submissions; external applications are not completion events. |

### Import records, identity, Company and taxonomy

`ImportSource` is the provider/configuration root. It stores transport, format,
endpoint, encrypted credentials, configuration, record path, selection rules, active
state, approval, owner Company, and last-import metadata. `Import` represents a run
with status, counters and timestamps; `ImportLog` stores safe summaries/context. The
physical legacy `imports.source` field is not the provider identity. Mappings and
field rows are reusable per source; taxonomy aliases are separate source-scoped rows.

On persistence, the runner resolves only the configured source Company. It does not
resolve each source company name or create Companies. This prevents accidental
duplicate/incorrect Companies, but means a feed source must be intentionally assigned
to the correct Company before use. Each Joobs advertisement should belong to the
approved Joobs provider/source Company (subject to the agreed product ownership
model); `company`-like feed values, if any, must not silently reassign that relation.

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

The missing detector currently runs after the row loop when every selected row has a
usable reference and no row-processing exception occurred. HTTP/read failure and
row-level invalidity skip reconciliation. It does not have an explicit source-level
snapshot token, fetched-document integrity state, or transaction around complete
snapshot reconciliation. XML `read()` completion is not followed by explicit parser
error inspection. Before a partner feed can unpublish by absence, those boundaries
must be made explicit and tested. Empty XML currently has no matching record and
raises `RecordPathNotFoundException`, so it becomes a failed run and does not reconcile;
keep that fail-closed behavior. Do not generalize that conclusion to all formats until
their empty-input behavior is tested.

### Scheduling, queues and hosting

There is no Laravel task schedule, import CLI command, import queue job, or expiry
command in the current repository. `config/queue.php` supports database, sync and other
drivers and configures failed-job storage, but actual deployment driver, cron access,
worker supervision, log retention, alerting, and hosting limits are **unknown** from
the repository. No deployment/hosting documentation was found that confirms shared
hosting capabilities.

Smallest proposed operating model:

1. Before unattended partner activation, add a console command that selects only
   explicitly active and approved sources, writes a run record, and invokes the
   existing pipeline. Never put a private feed URL or secret in command output.
2. If one hourly run fits the measured web/cron execution limit, invoke it from the
   host's Laravel scheduler with one system cron entry. The task itself can remain
   synchronous initially; use a single-server/overlap lock and per-source lock.
3. If measured duration can exceed the host limit, use the database queue only if the
   host supports frequent worker invocations (for example, a scheduled
   `queue:work --stop-when-empty`); otherwise document that a persistent worker is an
   infrastructure prerequisite. Do not assume Redis or Supervisor.
4. Add retry as an explicit command/action over failed run/source, with bounded
   attempts and a new run record. Avoid rerunning old failed jobs automatically until
   idempotency and reconciliation boundaries are proven.
5. Public visibility already expires query-side at the cutoff. A daily command to
   set status to `expired` is optional administrative state maintenance, not a launch
   prerequisite; confirm the desired admin status semantics before adding it.
6. Manual import can remain the Filament mapping/preview/run action for initial
   onboarding. It is synchronous today and should only be used with bounded feed size.

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

## Recommended state boundaries

The current state is `Import: pending -> processing -> completed|failed`; `Vacancy`
status is `draft|pending|published|expired|archived`, with independent source-presence
metadata. For an eventual safe complete-snapshot sync, proposed boundaries are:

```text
source fetch/parse/validate
  -> failed: retain all existing publication states; no missing reconciliation
  -> completed snapshot with accepted records:
       upsert by (import_source_id, source_reference)
       mark rows seen by this run
       after verified completion, mark absent provider ads as missing
       after approved grace/confirmation policy, set status=expired (unpublish)
```

This is a proposal, not an approved behavior. `referencenumber` should be the ad-level
identity; `requisitionid` should remain a separate underlying-vacancy attribute and
must not be used as the unique key when several city advertisements share it. Existing
schema has no separate requisition identifier. A Joobs-specific missing-ad grace
period, status restoration rule, manual override rule, and whether source updates may
reactivate an editor-expired row need owner approval. Do not delete removed rows.

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

## Proposed decisions needing approval

1. Whether Joobs is required before demo, or only before partner onboarding.
2. Whether disappearance from a complete Joobs snapshot should unpublish immediately,
   after a grace window, or require operator review.
3. How source-driven depublication interacts with a manual admin publication/override
   and whether a returned ad may be republished automatically.
4. Which timezone defines `expirationdate` end-of-day and how to handle date-only values.
5. Whether `requisitionid` needs a new stored field for reporting/grouping.
6. Who owns the Company attached to Joobs advertisements and whether Joobs-provided
   employer identity is informational only.
7. Which host scheduler/worker capabilities are actually available.
8. Analytics definitions, consent/privacy basis, retention, and partner confirmation.


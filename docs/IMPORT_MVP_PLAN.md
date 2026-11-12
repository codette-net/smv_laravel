# Demo Acceptance and Import MVP Plan

**Status:** post-demo release alignment, updated 31 October 2026 from repository evidence
and the actual Joobs v1 spec/example. No application implementation is approved by this
document. The staging demo at `vacatures.marketing` has been published and viewed by
stakeholders; production is targeted for late November 2026. Stakeholder viewing is not
formal acceptance, and Joobs automation does not automatically block the general launch.

## Current evidence

- Generic importer supports manual synchronous XML/JSON/CSV/XLSX imports, preview,
  validation, typed taxonomy resolution, sanitization, provider-scoped Vacancy upsert
  and reversible missing reporting.
- It supports reviewed per-record Company resolution, configured Joobs contract
  validation, allowlisted source metadata and safe full-snapshot verification. It does
  not support automated scheduling, queue processing, removal-driven unpublishing or
  outbound analytics.
- Phase 2 import audit suite passed 139 tests / 870 assertions. In the SMV-090 focused
  rerun, 135 selected public/admin/import/application/SEO tests passed (987 assertions);
  `PublicLayoutTest.php` had 10 pass and one existing homepage order assertion failure.
  Tests use SQLite in memory. SMV-094A/094B now consume the deterministic Joobs fixture
  at parser and persistence boundaries; the historical counts above predate those tests.
- The supplied Joobs XML was verified to have two ads, distinct references and one
  shared requisition ID. Browser/staging verification is still required.

## P0 — Staging demo readiness

| Ticket | Objective / dependencies | Likely files | Acceptance criteria | Estimate (Codex / human) | Demo blocker | Release blocker |
| --- | --- | --- | --- | --- | --- | --- |
| SMV-090 — Demo scope and import safety | Document demo flows, safe fixture/data boundaries, blockers and manual acceptance. Does not require Joobs integration. | `docs/IMPORT_MVP_PLAN.md`, `docs/BACKLOG.md`, existing feature tests | Demo scope and checklist below reviewed; any manual import uses isolated staging data/fixture only; no live partner feed or removal sync. | Audit/documentation complete; owner review 30–60 min | No, unless manual import is included | No |
| SMV-091 — Staging acceptance and smoke | Deploy to staging, verify public/admin critical path, configure safe staging runtime and capture defects. Depends on staging URL/credentials, approved content and hosting access. | `docs/DEVELOPMENT_WORKFLOW.md`, `docs/SEO.md`, `docs/SEO_MIGRATION.md`, `docs/BACKLOG.md` SMV-072/074, routes and main feature tests | Checklist below passes in deployed staging; staging stays noindex; production remains distinct; no critical runtime/browser errors; demo data/privacy state reviewed. | 2–4 h / 3–6 h | Yes | Conditional release blocker; staging smoke is required before production |

### Minimal demo acceptance checklist

| Flow | Automated evidence already present | Still required |
| --- | --- | --- |
| Homepage and vacancy discovery | `PublicVacancyIndexTest.php`, homepage/public layout tests | Browser check layout, card navigation, empty state and responsive breakpoints |
| Search, filters, sort, pagination | `PublicVacancyIndexTest.php`, `CompensationContractTest.php` | Browser check GET state/reset, mobile disclosure and visible validation |
| Vacancy details/lifecycle | `PublicVacancyShowTest.php`, `VacancyLifecycleTest.php` | Browser check content hierarchy, application CTA, valid data and 404 for hidden listing |
| Company index/detail | `PublicCompanyIndexTest.php`, `PublicCompanyPageTest.php` | Browser check representative public profiles/media and vacancy blocks |
| Blog | `BlogTest.php`, `BlogContentSeederTest.php` | Browser check approved content/images, index/detail/archive navigation |
| Filament administration | `AdminAuthorizationTest.php`, domain/admin tests | Staging login/roles; verify vacancy/company/blog administration; verify source/mapping/preview/history and only run isolated manual import if agreed |
| Application links | `ApplicationFlowTest.php`, `CandidateApplicationHistoryTest.php` | Verify external URLs go to the correct destination and retain query strings; verify internal flow only if demo scope includes it |
| Basic SEO | `SeoFoundationTest.php`, Blog and sitemap tests | Crawl staging: title/canonical/robots/schema/sitemap; verify staging noindex and no secrets in rendered pages |
| Responsive/accessibility | No committed browser suite; `SMV-071` partial | Test desktop and mobile widths, keyboard navigation, focus and screen-reader labels on key flows |
| Deployment/runtime | Automated feature tests do not establish deployed config | Confirm HTTPS, APP_ENV/DEBUG, DB, private/public storage, mail transport, backup/restore owner, logs and error visibility |

The stakeholder review confirms that the staging site was reachable and suitable for a
walkthrough. It does not establish which browsers/devices were checked, whether every
critical route passed, whether staging noindex/runtime/privacy settings were inspected,
or whether logged defects were accepted. SMV-091 therefore remains partially done until
the checklist has dated pass/fail evidence and an owner records formal acceptance or
explicit waivers.

The WordPress acceptance test is legacy-only evidence. It does not pass these Laravel
criteria. Automated verification currently has one known failure: the final homepage
banner test in `PublicLayoutTest.php` expects the employer banner between the hero and
vacancy search, while `resources/views/home.blade.php` renders it after the vacancy
listing. The other 135 selected flows passed. Resolve whether the intended demo layout
is the test expectation or the current template, then make code/test consistent before
stakeholder review. No browser or staging checks were performed.

### Demo scope and manual walkthrough

**In scope:** homepage/navigation, vacancy search and filters, vacancy detail and
application destination, Company directory/detail, Blog index/article, staff Filament
administration, manual Vacancy create/edit, and basic SEO output. Manual import is an
optional admin demonstration only if its data can be safely isolated. Public account
registration/employer draft flow may be shown only if it is part of the agreed demo;
payment, package entitlement and employer publication are not implemented as completed
commercial flows.

**Exclude:** Joobs or any live partner endpoint; unattended scheduling/queue execution;
automatic missing-ad unpublishing; partner click/conversion/billing claims; production
data and candidate records. A legacy WordPress import acceptance does not establish
Laravel readiness.

#### Safe demo data and import boundary

1. Use the dedicated staging database and approved, non-personal demonstration content.
   Confirm its database host/name are distinct from production before any import or
   seed operation. Do not copy candidate/application records from production.
2. Use a newly created, inactive, unapproved ImportSource with `Upload` transport,
   non-secret synthetic fixture stored in the demo/test fixture path, and a dedicated
   mapping. Associate it with the intended demo Company and verify `company_id` before
   execution; the importer assigns all rows to that configured Company.
3. Preview first. Confirm the number of rows, source references, Company association,
   URLs and taxonomy resolution. Execute only after the reviewer approves the preview.
4. Repeat the exact import and confirm updates occur on the same source/reference rows
   with no count increase. Check the Import run counters and Vacancy list.
5. Do not remove a fixture record and present the current `missing_since` behavior as
   unpublishing. It is reporting metadata only; no automatic synchronization or removal
   behavior is ready for demonstration.
6. Do not use `DatabaseSeeder` as a staging reset. `DatabaseSeeder` calls
   `DemoSeeder` (creates synthetic users, Companies and Vacancies) and
   `AdminUserSeeder`; the latter falls back to a known development password when
   `ADMIN_PASSWORD` is unset. Provision a unique staging admin through an approved
   process and verify credentials/roles before opening panel access. If the seeder was
   already run with defaults, rotate that credential before the demo.

#### Manual acceptance checklist

Run on the deployed staging URL with an owner/operator present; record pass/fail,
browser/device, timestamp and defect link for each item.

1. Open homepage; verify navigation/footer, search form, current vacancies, employers
   and latest articles; follow links and confirm no broken routes.
2. Search by keyword and location; exercise at least one taxonomy filter, clear filters,
   sort and pagination. Confirm results and URL state agree.
3. Open an active Vacancy; verify company, location, description, compensation only when
   supported by the record, application action and a working destination. Test one
   expired/hidden Vacancy gives the expected unavailable response.
4. Open Company directory and two representative Company pages; verify active Vacancy
   links, profile text, logo/fallback and that draft Companies are not public.
5. Open Blog index, a published article and category/tag archive; verify navigation,
   images, formatting and no draft/future article leaks.
6. Sign in to Filament with the provisioned staff account. Verify role boundaries by
   checking allowed resources and confirming a non-admin/editor account cannot access
   restricted admin operations. Create/edit a staging Vacancy and Company only if that
   action is part of the demo.
7. If manual import is in scope, follow the safe-data steps above: inspect source and
   Company, preview, execute once, rerun, verify idempotency and run history. Do not use
   a live provider feed or claim removal synchronization.
8. Test external application links and query-string preservation. Test internal
   application submission only with synthetic data and confirm no candidate details
   appear publicly.
9. Inspect page title, description, canonical, robots, JobPosting/Organization JSON-LD,
   sitemap and robots.txt. Staging must remain `noindex, nofollow`; do not expose secrets
   in page source or logs.
10. At desktop and mobile widths, check navigation, filters, cards, forms, images,
    keyboard focus and horizontal overflow. Verify HTTPS, no mixed-content or console
    errors, and that refresh/deep links return normally.

#### Staging prerequisites

- A staging hostname and TLS certificate, distinct staging database and a deployable
  release/ref; production and staging `.env`/credentials must be separate.
- `APP_ENV=production`-style hardened runtime on the public host, `APP_DEBUG=false`,
  correct `APP_URL`, application key, database credentials, cache/session settings,
  secure cookies and trusted HTTPS handling. Staging SEO behavior must still be
  non-indexable; verify rendered robots output rather than assuming environment value.
- Apply only reviewed forward migrations on staging after backup/snapshot and owner
  approval; never use `migrate:fresh` or seeders as a staging reset.
- Build frontend assets for the release; verify public and Filament assets load. Ensure
  writable storage/cache, private CV storage is not web-public, and uploaded media has
  correct disk/link/permissions.
- Configure mail to a staging-safe sink or approved test mailbox; prevent test
  application/contact messages from reaching unintended recipients.
- Provision least-privilege staff users, rotate any seeded/default credentials, and
  confirm editor/admin access boundaries. Disable public demo accounts unless agreed.
- Confirm log access/retention, error reporting, backup/restore owner, deployment
  rollback path, database isolation and who can execute manual imports.
- Record staging URL/access owner and expected environment-specific behavior. The
  repository does not establish current host credentials, runtime settings, backup
  policy, or a deployed staging instance; those remain operator verification items.

#### Blockers ranked by severity

| Severity | Blocker / evidence | Required before |
| --- | --- | --- |
| Critical | Verify staging is isolated from production, `APP_DEBUG=false`, HTTPS, safe mail and least-privilege admin access. Seeder fallback admin password must not be used. | Any staging demo access |
| High | Resolve the failing homepage ordering acceptance test against the intended design; currently 1 selected test fails. | Stakeholder walkthrough |
| High | Approved sample Vacancies, Companies, Blog posts/images and application destinations are needed for a credible representative walkthrough; current authored copy still has approval needs in `CONTENT_STATUS.md`. | Content-led demo |
| High | Confirm staging URL, credentials, deployment ref, database, storage/media and log/backup/rollback ownership; none can be verified from local repo. | Staging smoke |
| Medium | Decide whether manual import is in demo scope and prepare isolated upload fixture/source/mapping with expected Company. | Import walkthrough only |
| Medium | Browser/mobile/keyboard and actual staging SEO checks have not run; automated feature tests do not cover rendering/runtime in the deployed host. | Demo sign-off |

### Recommended SMV-091 staging procedure

1. Confirm demo scope, content approvals and an owner for each staging prerequisite.
2. Deploy the reviewed release to isolated staging; verify environment, HTTPS,
   migrations, assets, storage, mail sink, logs and admin provisioning.
3. Run the applicable automated feature subset on the release; resolve the known
   homepage test mismatch and rerun it.
4. Populate only approved synthetic/demo records. Verify no production personal data or
   credentials were copied. If import is included, use the isolated upload workflow.
5. Complete the manual checklist above on desktop and mobile; inspect SEO/noindex and
   admin permissions. Log defects with severity and retest fixes.
6. Demo only after all Critical/High blockers pass or receive an explicit scope waiver.
   Capture evidence and outstanding non-blocking items for release planning.

**Remaining work estimate:** SMV-090 audit/documentation is complete. Allow 30–60
minutes for owner scope/content review. SMV-091 needs approximately 2–4 hours of
Codex-assisted preparation/fixes plus 3–6 hours of human staging/browser/operator
verification, excluding deployment access wait time and any newly discovered code fix.
The current homepage test/layout decision is the only code-level blocker identified in
this pass; no code change is made here.

## P1 — Joobs and partner onboarding

The sequence below reflects dependencies. Keep tickets narrow. Joobs multi-employer
support appears necessary because `job/company` is the hiring employer and one partner
feed may contain multiple customer ads. Confirm endpoint scope with Joobs.

| Ticket | Objective / dependencies | Likely files | Acceptance criteria | Estimate (Codex / human) | Demo blocker | Release blocker |
| --- | --- | --- | --- | --- | --- | --- |
| SMV-092 — Joobs mapping contract | Finalize the mapping/identity design and safe defaults; partner guarantees remain activation inputs. | `docs/JOOBS_FEED_MAPPING.md`, `docs/integrations/joobs/*`, `docs/IMPORTS.md` | DONE: every standard/`joobs_` field classified; advertisement/requisition/employer identity, location, salary/date, snapshot and unresolved-value behavior explicit. | Complete / partner follow-up 2–4 h | No | Design gate complete; open partner values still gate live activation |
| SMV-093 — Verified snapshot lifecycle | DONE: make absence reporting safe after the SMV-092/100 foundations. | `VacancyImportRunner.php`, ImportSource configuration, Import runs/logs, import/lifecycle tests | Explicit source opt-in; read/parse/map/resolve preflight before apply; empty snapshots require explicit capability; duplicate/invalid/partial/failed snapshots do not reconcile; shared per-source lock; verified absence marks `missing_since` only; status/deletion policy remains unchanged. | Complete | No | Partner activation still needs publication/removal policy confirmation |
| SMV-094 — Joobs transformations and fixture acceptance | DONE: configured contract plus generic fixture-to-Vacancy persistence. | `SourceContractValidator`, generic mapper/validator/runner, Joobs fixture and persistence tests | Two-city/shared requisition/distinct ad IDs, idempotency, employer resolution, metadata, optional salary, HTML safety, exact URL/UTM, raw salary, dates, location/SEO and failure paths are automated. Unapproved taxonomy values remain unmapped. | Complete | No | Partner identity/value/timezone/publication inputs still gate live activation |
| SMV-095 — Scheduler and bounded queue execution | Add scheduled source command and per-source cadence, after lifecycle is safe and hosting plan is verified. | `bootstrap/app.php`, `routes/console.php`, `config/queue.php`, `ImportSource`, schedule tests/runbook | Hourly Joobs and configurable provider cadence; only active+approved sources; lock/overlap prevention; synchronous only when bounded, else short database-worker invocation; no Redis/daemon assumption; cron UTC tested. | 6–10 h / 4–8 h ops | No | Yes for unattended scheduled feeds |
| SMV-097 — Basic outbound click tracking | Add same-site redirect for external applications, with minimal provider/ad attribution, after partner/privacy requirements are confirmed. | `routes/web.php`, controller, Vacancy CTA, event storage/migration, tests | Record Vacancy/provider/ad/time with minimized data; redirect only to validated stored URL; preserve UTM exactly; document dedupe/bot rule; reject arbitrary redirect targets. | 5–9 h / 4–7 h | No | Conditional on partner contract requiring SMV clicks |
| SMV-100 — Multi-employer Company mapping | DONE: source-scoped employer mapping and provider-neutral Vacancy metadata. Stable provider ID is preferred; reviewed exact company-name + source-URL is the fallback. | `ImportSourceEmployer`, generic validator/runner, Filament admin, Vacancy metadata migration, public detail/JobPosting | Deterministic source-scoped resolution; unresolved records skip without default-Company fallback or missing detection; no Company creation/profile writes; metadata and location semantics covered. | Complete | No | Foundation complete; partner identity approval still gates live Joobs |
| SMV-101 — Import monitoring and recovery | Add run anomaly/retry controls after SMV-093/095. | Import/ImportLog models, Filament Imports pages, policies, command/job, tests/runbook | Show state/age/duration/counts; detect stale processing, repeated failures and count drops; bounded retry is a new auditable run and cannot reconcile incomplete data; recovery checklist. | 5–8 h / 4–6 h | No | Yes for dependable automated operations |

### Joobs readiness classification

| Capability | Classification | Reason / acceptance boundary |
| --- | --- | --- |
| Mapping contract and Joobs fixture tests (SMV-092/094) | Required before first live Joobs feed | The supplied spec/example must become an approved mapping and automated contract. Preserve the exact UTM-tagged application URL and prove two city ads with one requisition remain distinct. |
| Multi-employer Company resolution (SMV-100) | Implemented foundation; configured mappings are still required before first live Joobs feed | A reseller feed uses explicit stable-ID/approved composite mappings, quarantines unresolved identities and never overwrites manual profiles. |
| Advertised versus actual workplace locations | Required before first live Joobs feed | The example proves these differ. Store both semantics; do not overwrite one with the other or misrepresent workplace location publicly. |
| Complete-snapshot validation and reversible absence reporting (SMV-093) | Implemented foundation; final publication/removal policy still required before live automation | Validate root/version, full parse, non-empty set and unique ad IDs before writes/reconciliation. A verified complete run records `missing_since`; it does not yet unpublish or delete. |
| Inclusive expiration date handling | Required before first live Joobs feed | Joobs defines a date-only final paid day. Use approved business timezone end-of-day and boundary/DST tests so the last day is not lost. |
| Import concurrency protection | Required before first live feed execution | One lock per source is needed even for manual operation to prevent overlapping writes and unsafe absence reconciliation. |
| Scheduled execution (SMV-095) | Required before unattended production sync; safe to defer for a controlled manual pilot | Confirm hosting cron/worker constraints, approved cadence and overlap control. Manual email ordering or operator-triggered import can precede it only with an owner/runbook. |
| Monitoring and recovery (SMV-101) | Required before unattended production sync; minimum manual checks required for a pilot | Operators need run age/status/counts/failures and a bounded recovery path. Automated anomaly alerts may follow after the first safe manual pilot if an owner actively monitors every run. |
| Basic per-vacancy reporting (SMV-097A-C) | Conditional on the Joobs launch promise; otherwise required before that promise is made | Joobs asked whether SMV can report views/clicks. SMV cannot report historic uncollected metrics. Do not block the general SMV launch unless the reseller agreement makes this a launch deliverable. |
| Listing impressions and partner/billable reconciliation (SMV-098/099) | Safe to defer | Impressions need visibility rules; partner-confirmed and billable events need contracts/data exchange. They are not substitutes for detail views or raw outbound clicks. |
| Arbitrary ranking/deprioritization | Safe to defer; version II proposal | VNOM's request is documented in `PARTNER_INVENTORY.md`. Do not manipulate publication dates or search order in the MVP. |

### Post-demo ticket register

| Ticket / current status | Goal | Dependencies | Acceptance criteria | Release relevance | Estimate (Codex / human) | Open decisions |
| --- | --- | --- | --- | --- | --- | --- |
| SMV-091 — PARTIALLY DONE | Convert stakeholder viewing into evidenced staging acceptance. | Deployed release, operator access, content decision, defect owner. | Dated public/admin/SEO/noindex/responsive/runtime/privacy checklist; critical defects closed or explicitly waived; named acceptor. | General production gate. | 1-3 h / 3-6 h | Accepting owner, device/browser matrix, outstanding defects and waivers. |
| SMV-092 — DONE (design contract) | Close the Joobs mapping and identity contract with fail-closed defaults. | Repository/spec/SMV-094A evidence; partner follow-up remains activation input. | Field matrix and identity/employer/location/salary/date/snapshot rules finalized in `JOOBS_FEED_MAPPING.md`. | Design gate complete; not general launch gate. | Complete / 2-4 h partner follow-up | Stable employer ID, closed category/product values, timezone and auto-publication confirmation. |
| SMV-093 — DONE | Make complete-snapshot processing and overlap safe without changing publication policy. | SMV-092/100 foundations. | Explicit opt-in, preflight, safe empty policy, source lock, verified missing/reporting and reappearance are implemented; unsafe runs cannot reconcile. | Foundation complete. | Complete | Expected-count signal, manual field-override mechanism, alert thresholds and partner publication/removal policy. |
| SMV-094 — DONE (fixture persistence contract) | A proves parsing without persistence; B proves configured end-to-end generic persistence. | SMV-100 storage/resolution and SMV-093 lifecycle. | Fixture contract, rerun/update, sanitation, raw salary, configured date-only expiry, employer resolution, metadata, location/SEO and unsafe/persistence failures are tested. Taxonomy is withheld pending approved aliases. | Code gate complete; partner activation gate remains. | Complete | Taxonomy aliases, employer identity guarantee, publication policy, approved timezone and partner value sets. |
| SMV-095 — PROPOSED | Run approved sources unattended at a bounded cadence. | SMV-093/094; hosting cron/worker verification. | Approved active sources only; cadence configurable; no overlap; bounded execution; UTC/operations runbook tested. | Unattended sync gate; deferrable for manual pilot. | 6-10 h / 4-8 h | Hourly cadence, queue versus synchronous bound, worker ownership. |
| SMV-097 — SPLIT PROPOSED | Provide honest basic per-vacancy views/clicks and export. | Privacy/product rules; SMV-097A-C sequence; partner attribution definition. | Detail views and controlled outbound clicks attributed to vacancy/source/date; UTM preserved; date-range totals/CSV; retention/access/bot/dedupe documented. | Conditional production gate if promised to Joobs; otherwise pre-promise gate. | 10-16 h / 7-11 h | Retention, bots/deduplication, timezone, export users and Joobs reconciliation format. |
| SMV-100 — DONE | Reusable source-scoped employer resolution and allowlisted provider-neutral Vacancy metadata. | SMV-092 contract. | Reviewed mappings, deterministic resolution, no unsafe fallback/creation/profile writes, metadata persistence, public search compatibility and actual-workplace JobPosting behavior are implemented and tested. | Foundation complete. | Complete | Partner employer ID is still preferred; pending-Company creation remains out of scope. |
| SMV-101 — PROPOSED | Make scheduled imports observable and recoverable. | SMV-093 and preferably SMV-095. | Run status/age/duration/counts visible; stale/failure/drop detection; retry is new audited run and never reconciles incomplete input; recovery checklist. | Unattended production sync gate; minimal manual checks suffice only for a controlled pilot. | 5-8 h / 4-6 h | Alert channel/owner, count-drop threshold, retry authority and retention. |

### Minimal tracking and reporting split

SMV-097 should be split so the first useful capability stays reviewable:

| Ticket | Goal and acceptance | Estimate (Codex / human) | Dependencies |
| --- | --- | --- | --- |
| SMV-097A — Vacancy detail views | Record a minimized event/count for public detail views with vacancy, import source and UTC date; exclude previews/staff/bots under a documented rule; staff-only access and retention approved. | 3-5 h / 2-3 h | Privacy/product owner decision; event storage design. |
| SMV-097B — Outbound apply clicks | Route external CTAs through a same-site endpoint bound to the Vacancy, record the minimized event, validate the stored destination and redirect while preserving the stored UTM query exactly. Never accept an arbitrary target. | 4-6 h / 3-4 h | SMV-097A storage/policy; application URL tests; partner attribution definition. |
| SMV-097C — Basic report/export | Staff-only date range, per-vacancy totals and import-source grouping, with simple CSV. Label metrics as SMV detail views/outbound clicks, not applications or billable clicks. | 3-5 h / 2-4 h | SMV-097A/B; timezone and access/export decisions. |

Listing impressions remain SMV-098 because meaningful card visibility and deduplication
are more complex. Partner-confirmed clicks/applications and billable clicks remain
SMV-099 and require partner data plus contractual reconciliation. Definitions and the
privacy-conscious minimum are recorded in `PARTNER_INVENTORY.md`.

### Smallest executable post-demo sequence

1. **SMV-094A - Joobs fixture contract, no persistence — IMPLEMENTED.** The supplied
   example is in the test fixture boundary and the existing XML reader now has an isolated
   Joobs contract inspector for root/version, two-record fixture behavior, identity,
   shared requisition, advertised/workplace locations, optional fields, exact raw
   description/salary/product values and application URL/UTM. Negative tests cover
   malformed XML, identifiers, duplicates, version and URL validity. No mapping,
   persistence or import execution is involved.
2. **SMV-092 - mapping and identity contract — COMPLETE.** Source-scoped advertisement,
   requisition and employer identity; location behavior; raw salary/date policy; snapshot
   boundaries and fail-closed defaults are recorded. Partner guarantees remain live-feed
   activation inputs, not hidden assumptions.
3. **SMV-100 plus source/location metadata — COMPLETE.** Explicit source-scoped employer
   mapping, allowlisted source metadata and distinct advertised/actual workplace storage
   are implemented using the SMV-092 contract.
4. **SMV-093 - safe snapshot lifecycle and per-source lock — COMPLETE.** Explicit source
   opt-in, whole-snapshot preflight, safe empty policy, reappearance reporting and
   per-source concurrency protection are implemented. Publication/removal policy remains
   deliberately separate.
5. **SMV-094B - end-to-end transformations/persistence — IMPLEMENTED.** The configured
   contract and mapping exercise generic persistence, source-scoped employer resolution,
   metadata, safe HTML, raw salary, inclusive expiry and verified disappearance/
   reappearance. Taxonomy activation awaits approved partner value lists.
6. **Controlled manual pilot (2-4 h / 3-6 h).** Only after 1-5 pass and agreement/feed
   access are approved; use monitored runs and a recovery checklist. Manual email orders
   can remain a business process until this point.
7. **SMV-095 and SMV-101 (11-18 h / 8-14 h).** Add unattended schedule, overlap control,
   monitoring and recovery after hosting/runtime choices are verified.
8. **SMV-097A-C (10-16 h / 7-11 h).** Schedule before production only if the Joobs
   agreement requires SMV per-vacancy reporting at launch; otherwise deliver before the
   reporting promise becomes active.

In parallel, SMV-091 formal acceptance and SMV-104/105 content/commercial decisions are
general production-release work. They do not depend on Joobs feed code. The production
launch may proceed without a live Joobs feed if partner communication and ordering are
kept accurate and no automated feed/reporting capability is promised prematurely.

## P2 — After onboarding / only on confirmed need

| Ticket | Objective / dependencies | Likely files | Acceptance criteria | Estimate (Codex / human) | Demo blocker | Release blocker |
| --- | --- | --- | --- | --- | --- | --- |
| SMV-096 — Expiration status maintenance (optional) | Decide whether query-side hiding is sufficient or admins need scheduled `published -> expired`. | `Vacancy::publiclyVisible()`, `VacancyStatus`, admin filters, scheduler | If approved, task is idempotent and does not alter drafts/archives; otherwise keep query-driven visibility. | 1–3 h / 1–2 h | No | No; public expiry already works |
| SMV-098 — Engagement analytics | Add defined card impressions/detail views and CTR after SMV-097 and privacy/product review. | listing/detail endpoints, event storage, retention/access policy, tests | Metrics distinguish impressions, views and outbound clicks; dedupe, bot exclusions, privacy basis and retention are approved. | 6–12 h / 1–2 days | No | Conditional |
| SMV-099 — Partner/commercial reporting | Reconcile partner-confirmed clicks/conversions/products only after reporting access and commercial terms. | partner reporting import, product metadata, reports, retention docs | Partner-confirmed clicks and completed applications differ from SMV events; no invoicing solely from internal clicks; no assumed common CPC. | 2–4 days / 2–4 days partner/commercial | No | Only if contracted reporting requires it |
| SMV-102 — Targeted search relevance | Change matching/weighting only with query evidence and approved taxonomy. | `VacancySearch`, typed taxonomy, search tests, `docs/SEO.md` | Documented queries improve without arbitrary boosts; taxonomy stays curated and typed; filters/canonicals remain correct; no claim tags improve Google ranking. | 4–8 h / 4–8 h | No | No |
| SMV-103 — Cross-provider expansion | Add VONQ/Brockmeyer/Appcast/Adver/8vance from confirmed specs; extend existing VNOM only for verified operational/commercial gaps. | import pipeline, provider tests, approved schema/reporting | Each new provider mapping verified; existing VNOM configuration reused; identity/company/product semantics explicit; avoid speculative abstraction/dashboard. | 1–3 days/provider / 1–2 days/provider | No | Conditional on signed feed |

## Joobs acceptance-test matrix

### Automated tests

1. Parse the supplied XML with root/version validation; two ads have distinct
   `referencenumber` values and shared `requisitionid`.
2. Import both city ads as distinct Vacancies; `city/state/country/postalcode` remain
   advertising location, while `joobs_workplacecity/country` remain actual workplace.
3. Repeat the same snapshot without duplicates; update changed source fields by ad
   reference and verify the approved optional-omission clearing/retention behavior.
4. Parse `date` as offset-aware `published_at`. Interpret `expirationdate` as the
   inclusive last day in the approved timezone, convert to UTC and test around midnight
   and DST; expired records are not public and JobPosting dates agree.
5. A missing ad in a complete verified snapshot becomes unavailable without deletion;
   a later verified reappearance restores source availability but preserves admin status.
6. HTTP failure, invalid auth, wrong `feedversion`, malformed XML and truncated XML
   never reconcile absence. No partial snapshot is declared complete.
7. Empty response, empty `<source>`, selection rules matching zero rows, and unexpectedly
   small feeds fail closed without changing existing ad visibility.
8. Duplicate references, missing required fields, invalid country/product, and unmapped
   Company/category values are reported and do not cause false absence.
9. `url` retains original path and UTM query exactly; valid HTTPS passes; malformed,
   unsupported scheme and disallowed URL are rejected.
10. Salary missing/clear/ambiguous/locale-formatted values are covered. Raw text is
    retained; unsafe parses stay non-comparable; monthly never implies `gross_fte`.
11. Exact category/jobtype/education/experience/remotetype/contract aliases resolve to
    correct typed Categories; unknown values do not create Categories.
12. Employer matching covers repeated name/URL, renamed name with stable ID, same name
    different employer, missing URL/ID, manual mapping collisions and no Company profile
    or media overwrite.
13. Import retry creates auditable run history; per-source concurrent run lock blocks
    races; stale-processing recovery never reconciles an incomplete snapshot.
14. HTML CDATA preserves allowed simple elements and removes scripts, event handlers,
    unsafe link schemes, style and unsupported embeds.

### Manual browser acceptance

- Configure a Joobs source and approved Company mapping in Filament; secrets are not
  displayed after save. Confirm source active/approved behavior and cadence controls.
- Preview the actual fixture and inspect both source references, shared requisition ID,
  ad/workplace locations, product code, salary warnings and resolved/unresolved taxonomy.
- Verify missing/ambiguous employer or taxonomy keeps the row out of persistence/public
  pages and offers clear diagnostics.
- Execute only against staging or an isolated database; inspect run state/counters/logs,
  URL destination and company association. Confirm an admin-managed Company profile is
  unchanged after import/update.
- Remove an ad from a test snapshot and verify it disappears only after confirmed full
  synchronization. Simulate failure/empty snapshot and verify it remains unchanged.
- Inspect public Vacancy detail, employer attribution, validThrough, canonical and
  application link. Verify destination UTM values survive.

The demo checklist earlier is separate: no Joobs automation should enter it unless the
demo owner explicitly confirms that dependency.

## Tracking definitions

- **Ad impression:** vacancy card rendered or actually visible, as explicitly selected by
  product; these are not interchangeable denominators.
- **Vacancy detail view:** successful public Vacancy detail response.
- **Outbound click:** SMV accepted redirect event to the stored partner application URL.
- **Partner-confirmed click:** count reported by partner through an agreed report/callback.
- **Completed application:** partner/application system confirms submission; an outbound
  click does not prove completion. Internal Applications are separate.
- **Billable click:** contract-defined, deduplicated, auditable event. Never derive an
  invoice from raw SMV clicks without agreed commercial rules and reconciliation.

Possible metrics: listing CTR = outbound clicks / defined card impressions; detail CTR =
outbound clicks / detail views. Report denominators, time range and deduplication clearly.
UTM parameters are preserved attribution metadata, not evidence that any of these events
occurred. Avoid collecting candidate identity, full IP, full user agent, or raw referrer
unless an approved purpose requires it; define retention/access before launch.

## Hostinger and scheduling recommendation

The repository has no schedule, command or import job. `config/queue.php` defaults to
database queue but imports are synchronous. Hostinger's published hPanel docs describe
cron on web/cloud plans, UTC schedules, cron output inspection and an Artisan scheduler
command, but the account's exact plan and resource limits are unknown. Verify hPanel
access, SSH/CLI and PHP path, cron frequency, allowed execution duration, process/RAM
limits, file/private storage, cache driver and queue support in the actual account.

Recommended minimum: one cron invokes Laravel `schedule:run` each minute, if supported;
Laravel schedules hourly Joobs sync and configured provider cadences. Start synchronous
per-source command only if a full measured run is well under the plan's time/resource
limit. If not, dispatch database queue jobs and use a bounded cron-started
`queue:work --stop-when-empty` only if validated; otherwise host the worker on a VPS.
Use a per-source lock, scheduler overlap guard, bounded timeout/retries, run IDs and
stale-run alert. Cron UTC and `expirationdate` local-day interpretation are separate
timezone concerns. Public expiration already works query-side; no expiry cron is needed
for visibility. A nightly status transition is optional admin maintenance.

Operational failures and recovery:

1. **Network/HTTP/auth failure:** run fails, no source or publication change; alert;
   confirm credential rotation/partner availability, retry a new run.
2. **Malformed/truncated/version mismatch:** mark snapshot failed before writes; inspect
   safe parser diagnostic, obtain a fresh document, retry.
3. **Empty/filtered-empty/abnormally small feed:** reject and require operator confirm;
   do not reconcile absence.
4. **Some rows invalid/unmapped:** preserve exact failure counts; snapshot is incomplete
   and does not unpublish absent records; fix mapping/aliases and rerun.
5. **Timeout/worker crash after persistence begins:** mark/recover stale run; idempotently
   rerun; no absent-record reconciliation until full run completes.
6. **Concurrent runs:** source lock denies or defers the later run; never let older run
   finish reconciliation after newer snapshot.
7. **Unexpected count drop:** alert and hold depublication until source owner confirms
   feed is intentionally smaller.
8. **Accidental suppression:** retain rows and history; restore only source-managed
   availability state after verified reappearance/operator recovery; do not bulk-delete.

References: [Hostinger cron setup](https://support.hostinger.com/en/articles/1583465-how-to-set-up-a-cron-job),
[Hostinger Laravel scheduler guidance](https://support.hostinger.com/en/articles/1583301-which-laravel-versions-are-supported-at-hostinger),
[Hostinger plan limits](https://support.hostinger.com/en/articles/6976044-parameters-and-limits-of-hosting-plans).
These are generic Hostinger docs; actual hPanel plan capabilities must be verified.

## Critical path to demo and November release

**Staging demo:** agree scope and access -> deploy/configure staging -> run SMV-091
browser checklist -> fix only blocking defects -> demo. Do not insert Joobs into this
path unless the owner explicitly confirms a partner demo dependency.

**Production by late November:** approve Joobs mapping and Company identity -> implement
multi-company resolver if feed contains multiple employers -> safe snapshot lifecycle ->
automated fixture and lifecycle acceptance -> verify hosting/command/worker design ->
scheduled operation and monitoring -> staging feed acceptance -> SEO/redirect/backup
and launch gates (SMV-044–046, SMV-073/074). Partner data access and decisions are
calendar dependencies. Estimated active engineering across P1 tickets is about **37–65
Codex-assisted hours** plus **28–51 human review, partner validation and operations
hours**. A six-week window is possible if scope is limited to Joobs, staging and release
gates; it is at risk if stable employer identity, commercial requirements or hosting
changes arrive late.

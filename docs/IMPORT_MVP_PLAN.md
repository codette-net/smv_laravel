# External Vacancy Import MVP Plan

**Status:** proposed after read-only SMV-083 audit; not approved implementation scope.
The Laravel demo is targeted within one week. Existing WordPress acceptance is useful
legacy evidence but does not validate Laravel behavior. Joobs must not be assumed to
block the demo.

## Priority and critical path

### P0 — Demo or launch-critical

| Suggested ticket | Goal / scope | Dependencies and likely files | Acceptance criteria | Risks | Codex active time / human review and test time | Blocks demo? |
| --- | --- | --- | --- | --- | --- | --- |
| SMV-090 — Demo import safety gate | Decide whether demo includes import execution. If it does, use a reviewed non-production fixture/source and document manual preview/run steps; do not configure an unreviewed live provider. Confirm the empty XML/partial XML reconciliation boundaries before any demo run that might affect shared data. | Existing generic importer; `docs/IMPORTS.md`; `app/Imports/VacancyImportRunner.php`; `app/Imports/Readers/XmlReader.php`; `tests/Feature/ImportMissingLifecycleTest.php`, `ImportReadingTest.php`, `ImportEndToEndTest.php`. | Demo checklist names exact fixture/source, expected counts and rollback/recovery owner; no real partner feed is fetched without approval; no empty/failed source can mark existing feed records missing. If not exercising imports in demo, record that boundary and retain existing app behavior. | Live source and shared DB would make demo risky; malformed XML completion and non-XML empty feeds need explicit verification before activation. | 1–2 h Codex / 1–2 h human | Only if importing is in confirmed demo scope; otherwise no. |
| SMV-091 — Demo regression smoke | Run agreed existing public/admin critical path and one importer preview/run only if demo scope requires it; record results. This is a release check, not a replacement for the existing feature suite. | `docs/BACKLOG.md` SMV-072, Filament import pages, existing feature tests, hosting/browser details when available. | Homepage, Vacancy detail/application, admin access, and agreed import demonstration complete in the demo environment with no known blocker; attach concise steps/results. | Browser/staging host may not be available; no production-like environment is documented in repo. | 1–2 h Codex / 1–2 h human | Yes, as a demo readiness check; feature code need not change if demo scope excludes imports. |

### P1 — Early post-demo MVP / Joobs partner onboarding

| Suggested ticket | Goal / scope | Dependencies and likely files | Acceptance criteria | Risks | Codex active time / human review and test time | Blocks demo? |
| --- | --- | --- | --- | --- | --- | --- |
| SMV-092 — Joobs v1 contract and mapping approval | Reconcile full partner spec/example, confirm ad identity, Company ownership, field semantics, product code, URL policy, expiration timezone, salary policy and taxonomy aliases. Prefer the current generic pipeline; add only proven missing destinations/transforms. | Attach reference files; `docs/JOOBS_FEED_MAPPING.md`; `docs/IMPORTS.md`; `app/Imports/Mapping/DestinationRegistry.php`; `app/Imports/Mapping/ImportMapper.php`; `app/Models/Vacancy.php`; `Category` types. | Every spec field classified map/transform/ignore/domain gap; mapping profile reviewed against actual example; unresolved taxonomy and salary rules are explicit; no new schema without owner approval. | Reference files absent; ambiguous semantics or shared provider Company may alter public presentation/data model. | 2–4 h Codex / 2–3 h partner/product review | No. |
| SMV-093 — Verified snapshot boundary and safe depublication | Make import completion mean a complete, validated snapshot. Fail closed for empty/invalid/partial feeds, duplicate ad keys, row failures according to approved policy, and concurrent runs. Add a reversible missing/depublication state rule for Joobs; do not delete. Define precedence for manual status/override and returned ads. | Approved SMV-092 decisions; `VacancyImportRunner.php`; `XmlReader.php`; `Import`/`Vacancy` models; import migrations only if approved; `ImportMissingLifecycleTest.php`, `ImportReadingTest.php`, `VacancyLifecycleTest.php`. | Removed ad is unpublished only after verified full successful feed; HTTP/XML/empty/partial/invalid feed does not change publication; reruns are idempotent; concurrent runs serialize; restored ads follow approved override policy; logs explain run and affected ads. | Existing runner marks `missing_since` but does not unpublish; current absence detection has no complete-snapshot token; status restoration can override editor intent if poorly defined. | 4–8 h Codex / 3–5 h human test/review | No. |
| SMV-094 — Joobs mapping fixture and automated acceptance | Build mapping/profile and automated tests using the supplied real two-city example. Add only small core transformations that generic mapping cannot safely express. | SMV-092/093; `tests/Fixtures/Imports/joobs/`; `ImportEndToEndTest.php`; `ImportMissingLifecycleTest.php`; `ImportValidationTest.php`; `CompensationTextParser.php`. | All automated cases in “Acceptance testing” pass; description is safe; application URL and UTM are preserved; ambiguous salary stays non-comparable with raw value retained; category resolution is type-correct. | A parser tailored to one example could overfit; raw salary retention may need schema/diagnostic decision; timezone expectations require partner agreement. | 4–8 h Codex / 3–5 h human | No. |
| SMV-095 — Manual and scheduled operations | Add a bounded command to run approved sources, one host cron/scheduler integration, overlap/source locks, failed-run retry path and operational runbook. Keep synchronous only if measured execution stays within host limits; otherwise use a queue only where workers are supported. | Hosting capabilities; `bootstrap/app.php`, `routes/console.php`, `config/queue.php`; `ImportSource` approvals; run history UI; deployment docs. | Hourly sync runs only explicitly approved active sources; no secrets in logs; failed run remains visible and can be retried as a distinct run; overlap cannot double reconcile; logs/alert owner and retention are named; shared-host plan is verified. | Host/cron/runtime limits unknown; no workers/scheduler currently; async retries before safe snapshot boundaries can cause incorrect depublication. | 4–8 h Codex / 3–5 h ops/human validation | No. |
| SMV-096 — Daily expiry state maintenance (optional) | Decide whether query-side hiding is sufficient or admins require a scheduled `published -> expired` status transition. | Owner policy; `Vacancy::publiclyVisible()`; `VacancyStatus`; Filament status filters; `bootstrap/app.php` scheduler. | If approved, an idempotent daily task updates only eligible elapsed vacancies and does not change manual archive/draft states; otherwise document query-driven expiry. | Status mutation can conflict with manual status; today public behavior already hides expired records without a scheduler. | 1–3 h Codex / 1–2 h human | No. |

### P2 — Later improvements

| Suggested ticket | Goal / scope | Dependencies and likely files | Acceptance criteria | Risks | Codex active time / human review and test time | Blocks demo? |
| --- | --- | --- | --- | --- | --- | --- |
| SMV-097 — Privacy-conscious engagement events | Define and record listing impressions, detail views and SMV outbound clicks with clear denominator and provider/ad association; preserve destination query parameters. | Product/privacy definitions; `VacancyController.php`, `routes/web.php`, click redirect, new event/counter storage, retention/access policy. | Events distinguish impressions, detail views and outbound clicks; dedupe rules and retention are documented; redirect validates destination and records before redirect; no unnecessary candidate data retained. | “Impression” definition, bots, consent/privacy basis and retention are undecided. | 5–10 h Codex / 4–8 h product/privacy review and testing | No. |
| SMV-098 — Partner and commercial reporting | Add partner-confirmed click reconciliation, conversion reporting and billable-click calculation only after partner data contract and commercial definitions exist. | SMV-097; Joobs/Appcast/VNOM reporting interfaces; contracts and attribution keys. | Partner-confirmed clicks and completed applications are distinct from SMV events; billable metric has approved dedupe/fraud policy and auditable evidence. | Partner reports may lack shared ad/click identifiers; high risk of overstating conversions or billable events. | 8–16 h Codex / 1–2 days human/partner validation | No. |
| SMV-099 — Provider expansion and admin dashboards | Add 8vance JSON, Appcast/VNOM differences, multi-country rules, performance dashboard and generalized operational alerts based on real contracts and feed volume. | Provider specifications and SMV-092/095; existing JSON/XML pipeline and import admin. | Each provider has explicit tested contract/mapping; shared common pipeline remains; country/currency/date differences are covered; dashboard counters reconcile to run logs. | Premature abstraction, source-specific complexity, and dashboard scope can delay partner onboarding without improving safety. | 1–3 days Codex / 1–2 days human review/testing per provider, more for dashboard | No. |

Effort is active working time, not elapsed calendar time, and excludes partner response,
hosting procurement, access provisioning, and waiting for approval. Ranges assume existing
generic code is extended and no major schema redesign is approved.

## Laravel acceptance tests

The legacy WordPress acceptance pass is not evidence for Laravel. Once the actual Joobs
example is attached, use it as a fixture, preserving its relevant XML structure and UTM
query values. Automated tests should cover at minimum:

1. Valid two-city XML import.
2. Two ad references with one shared requisition ID produce distinct vacancies.
3. Repeated import produces no duplicates.
4. Changed title/description/location/URL updates the same ad row.
5. Expiration date is interpreted with the approved final-day timezone rule; an expired
   vacancy is excluded from public listing/detail and JobPosting `validThrough` is correct.
6. An advertisement removed from a verified successful feed follows the approved
   depublication transition without deletion.
7. HTTP error, unsupported redirect, malformed XML and truncated/partial XML do not cause
   depublication.
8. Empty feed behavior fails closed and never marks all advertisements absent.
9. Application URL including UTM parameters is stored unchanged and passes allowed URL
   validation; invalid/non-web URLs are skipped safely.
10. Salary missing/clear/ambiguous strings and taxonomy mappings are tested; unknown
    values stay unresolved/non-comparable and raw salary is preserved under the approved
    design.
11. Retry creates auditable run history, reruns are idempotent, and concurrent executions
    cannot race reconciliation.
12. HTML in CDATA is sanitized, including scripts, event handlers, unsafe links and
    unsupported embeds; source fields cannot inject HTML into other destinations.

### Manual browser acceptance

- In Filament, verify Joobs source approval/active status, owner Company, transport/format,
  record path, mapping fields, taxonomy aliases and the bounded normalized preview.
- Verify a preview with an unresolved taxonomy value is visibly blocked/reported and does
  not create a Category or Vacancy.
- Run the approved fixture/manual import; inspect created/updated/skipped/failed/missing
  counters and safe log context without secrets or raw sensitive payloads.
- Verify removed/expired ads are no longer public only after a confirmed complete run;
  verify a failed run leaves public state unchanged.
- Verify title change does not change the stable Vacancy URL/slug unexpectedly; confirm
  current SEO canonical and structured-data behavior.
- Verify an outbound destination retains Joobs UTM parameters. Do not claim a click or
  conversion metric until SMV tracking exists.

## Demo critical path

1. Confirm whether the demo actually includes external import. If not, keep Joobs out of
   the one-week release path.
2. If it does include import, use a safe, reviewed fixture or isolated demo source and
   confirm the host/database is non-production. Show preview and run history.
3. Do not demonstrate removal-driven unpublishing as an existing feature: current Laravel
   behavior only records `missing_since`; implementation requires SMV-093 and approval.
4. Use the existing public/Filament smoke gate (SMV-091) and record any demo blockers.
5. After the demo, obtain the missing Joobs documents and partner/business decisions,
   then implement SMV-092 through SMV-095 in that order.


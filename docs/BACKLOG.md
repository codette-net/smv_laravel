# SMV Agent Backlog

## How to use this backlog

This status was reconciled with the repository on 1 October 2026. Status means:

- **DONE** — accepted behavior exists in code and tests.
- **PARTIALLY DONE** — useful behavior exists, but the named acceptance boundary is not complete.
- **NOT STARTED** — no coherent implementation exists yet.
- **OBSOLETE** — superseded and should not be implemented as written.
- **NEEDS REVIEW** — a foundation exists, but business scope must be confirmed before implementation.

Every implementation ticket should retain a focused goal, existing context, requirements,
out-of-scope boundary, acceptance criteria and tests. Do not infer completion from this
document when current code proves otherwise.

## Phase A — Agent readiness / audit

| Ticket | Status | Repository result |
| --- | --- | --- |
| SMV-001 Repository audit and foundation stabilization | **DONE** | Schema/model inconsistencies, authorization, slugs, soft deletes, import identity and baseline tests were stabilized. |
| SMV-002 Documentation sync | **DONE** | Foundation documentation was synchronized; this audit updates later delivery status. |

## Phase B — Recruitment core

| Ticket | Status | Repository result |
| --- | --- | --- |
| SMV-010 Company domain audit/completion | **DONE** | Stable slugs, status/public scope, profile fields, taxonomy, Media Library and factories/tests exist. |
| SMV-011 Company Filament admin | **DONE** | Policy-backed Company CRUD, media and taxonomy administration exist. |
| SMV-012 Public Company page | **DONE** | Slug-bound public profile with media, Organization JSON-LD and public Vacancies exists. |
| SMV-013 Public Company index | **DONE** | Public-only paginated listing, Vacancy counts and featured presentation exist. |
| SMV-020 Vacancy lifecycle audit/completion | **DONE** | `Vacancy::publiclyVisible()` is the canonical lifecycle boundary. |
| SMV-021 Vacancy Filament admin | **DONE** | Vacancy CRUD, taxonomy, typed tags, publication and application destination fields exist. |
| SMV-022 Vacancy listing/search/filter | **DONE** | GET-based search, Company/location/taxonomy filters, deterministic sorting and pagination exist. |
| SMV-022A Vacancy listing visual alignment | **DONE** | Production listing/cards use the Tidy/Mosaic-derived component system. |
| SMV-022B Vacancy taxonomy + Spatie Tags | **DONE** | Five controlled Vacancy taxonomies and flexible Spatie tags are separated. |
| SMV-023 Vacancy detail | **DONE** | Public-only detail, related Vacancies, Company links and JobPosting JSON-LD exist. |
| SMV-024 Application destination/internal flow | **DONE** | External, e-mail and internal modes, validated private CV storage, notification and read-only admin review exist. |

## Phase C — Imports

| Ticket | Status | Repository result |
| --- | --- | --- |
| SMV-030 Import subsystem audit/design | **DONE** | Generic architecture and provider/security decisions are documented. |
| SMV-031 Import sources | **DONE** | Upload/HTTP/API configuration, private uploads, approvals and guarded source access exist. |
| SMV-032 Parser/field discovery | **DONE** | JSON/XML/CSV/XLSX readers, selection, bounded discovery and safe remote fetch exist. |
| SMV-033 Mapping model/backend | **DONE** | Reusable mappings, destination registry, transforms and normalized DTO exist. |
| SMV-034 Filament mapping interface | **DONE** | Discovery, mapping completeness, clear/reset and bounded normalized sample feedback exist. |
| SMV-035 Normalized preview | **DONE** | Multi-record, filtered, remote-capable, side-effect-free preview exists. |
| SMV-036 Validation/failure reporting | **DONE** | Structured validation/resolution outcomes and source-scoped taxonomy aliases exist. |
| SMV-037 Import persistence/update/duplicate handling | **DONE** | SMV-037A/B/C provide provider-scoped atomic upsert, run accounting/history and format parity. |
| SMV-038 Import rerun lifecycle | **DONE** | Safe reruns plus reversible missing/still-missing/restored reporting exist without auto-deletion. |
| SMV-039 First production partner/feed configuration | **DONE** | VNOM is validated through generic configuration and tests; automatic production activation still requires provider identity/mapping approval and operations limits. |

Import execution is synchronous. Queues, scheduling, automatic retries and stale-run
recovery are deliberately not represented as completed functionality.

## Phase D — SEO migration foundation

| Ticket | Status | Repository result |
| --- | --- | --- |
| SMV-040 Current route/SEO audit | **DONE** | Canonical public route and indexability policy established. |
| SMV-041 Metadata/canonical foundation | **DONE** | Shared metadata, Open Graph, robots directives and pagination canonicals exist. |
| SMV-042 Structured data | **DONE** | JobPosting, Organization and BlogPosting JSON-LD exist. |
| SMV-043 Sitemap/robots | **DONE** | Dynamic public-only sitemap and environment-aware robots response exist. |
| SMV-044 Legacy URL inventory import | **NOT STARTED** | Blocked on live crawl/Ahrefs/Search Console/WordPress export input. |
| SMV-045 Redirect implementation/testing | **NOT STARTED** | Depends on an approved SMV-044 decision map. |
| SMV-046 Staging SEO crawl/checklist | **NOT STARTED** | Depends on deployable staging plus the redirect/content set. |

## Phase E — Commercial flow

| Ticket | Status | Repository result |
| --- | --- | --- |
| SMV-050 Packages audit/completion | **NEEDS REVIEW** | Package schema/model exist, but public/admin product behavior, pricing and entitlement rules are not agreed. |
| SMV-051 Orders/payments audit/completion | **NEEDS REVIEW** | Historical models/schema exist; no confirmed checkout/provider/reconciliation MVP flow exists. |
| SMV-052 Employer vacancy-posting flow | **NOT STARTED** | No employer dashboard or public posting workflow exists; depends on SMV-050/051 scope decisions. |

## Phase F — Blog / content

| Ticket | Status | Repository result |
| --- | --- | --- |
| SMV-060 Native Laravel/Filament Blog | **DONE** | BlogPost domain, Filament CRUD, Media Library, public index/detail, SEO and sitemap exist. |
| SMV-061 Blog taxonomy and editorial relations | **DONE** | Typed categories/tags, manual Vacancy/Company relations, archives, JSON-LD and sitemap behavior exist. |
| SMV-062 Operational public Contact flow | **NOT STARTED** | The current Contact page is intentionally disabled and stores/sends nothing. |
| SMV-063 Public content and brand integration | **DONE** | The first stakeholder-led content pass, `/adverteren`, real Blog seed content, package presentation and content-status record exist; final approvals remain tracked in `CONTENT_STATUS.md`. |

## Phase G — Polish/release

| Ticket | Status | Exact remainder |
| --- | --- | --- |
| SMV-070 Frontend consistency pass | **PARTIALLY DONE** | Shared public layout, Tidy-derived cards/controls, responsive navigation, homepage filters, Company pages and static pages are aligned. Final client content/visual sign-off remains; premium-card markup has no persisted listing-tier domain yet. |
| SMV-071 Responsive/accessibility pass | **PARTIALLY DONE** | Responsive layouts, labels, focus styles and keyboard hooks exist. A deliberate keyboard/screen-reader/mobile audit is still required, especially for the custom listbox, account menus and disabled/non-functional actions. |
| SMV-072 End-to-end smoke tests | **PARTIALLY DONE** | Broad Pest feature coverage exists, but there is no committed browser-level critical-path/cross-browser smoke suite or release matrix. |
| SMV-073 Migration dry run | **NOT STARTED** | Needs production-like data/export inputs and deployment environment. |
| SMV-074 Production launch checklist | **NOT STARTED** | Depends on content, redirects, staging crawl, migration dry run and operational configuration. |
| SMV-075 Prototype/showcase and release-cache cleanup | **DONE** | The intended showcase layout is available as `<x-app-layout>`, useful Tidy/component references remain, obsolete duplicate/onboarding prototypes and tracked conflict artefacts are removed, and `artisan view:cache` succeeds. |
| SMV-076 Public SEO regression hardening | **DONE** | Blog and archive pagination use clean self-canonicals, public metadata is escaped once at the output boundary, indexable static pages are present in the sitemap, and the environment-aware robots path matches the Filament dashboard. |
| SMV-077 Company discovery and filtering | **NOT STARTED** | Company search/filtering is deliberately separate from SMV-063. |

## Next work queue

### A. Functional / MVP

#### SMV-062 — Operational public Contact flow

- **Status:** NOT STARTED
- **Why now:** the navigation exposes `/contact`, but its fields and submit button are disabled.
- **Scope:** Form Request, CSRF-protected delivery/storage decision, spam protection appropriate to risk, Dutch success/error state and privacy-safe tests.
- **Dependencies:** confirmed recipient/retention/privacy requirements.
- **Acceptance:** a visitor can submit successfully; failures are understandable; no personal data leaks to logs; automated validation/delivery tests pass.

#### SMV-050 — Packages and commercial scope audit

- **Status:** NEEDS REVIEW
- **Why now:** `/tarieven` is public while package/pricing/entitlement behavior is still placeholder-level.
- **Scope:** reconcile Package/Order/Payment foundations with the actual launch offer; decide whether checkout and SMV-051/052 are launch requirements.
- **Dependencies:** approved products, prices, VAT/payment and employer workflow decisions.
- **Acceptance:** documented keep/change/defer decisions, schema gap list and small follow-up tickets; no speculative checkout implementation.

### B. Content / public site

#### SMV-063 — Public content and brand approval pass

- **Status:** DONE (first content round)
- **Result:** the public homepage, employer proposition, package presentation, About,
  Contact, Blog introduction, Company introduction, navigation and footer now use a
  coherent Dutch content layer. Eight supplied articles are seeded idempotently with
  native Blog taxonomy and Media Library covers.
- **Remaining approvals:** final commercial, historical, reach, testimonial and image
  decisions are listed in `CONTENT_STATUS.md`; none are presented publicly as facts.

#### SMV-077 — Company discovery and filtering

- **Status:** NOT STARTED
- **Why later:** SMV-063 improves Company discovery copy and homepage presentation but
  does not expand the existing Company index query experience.
- **Scope:** Company-name search, category/sector filtering, optional location only when
  current data is reliable, browse-by-category, GET query-string state, pagination,
  result count, featured-Company integration and a responsive desktop sidebar/filter UI
  within the current SMV/Tidy design language.
- **Out of scope:** speculative taxonomies, geocoding and a separate frontend stack.
- **Acceptance:** public-only Company results remain deterministic and shareable; filters
  combine correctly and work on desktop/mobile with focused regression coverage.

#### Later content/discovery ideas (not committed MVP scope)

- Vacancy of the day/week/month and employer of the month.
- More prominent stage and traineeship discovery when the data supports it.
- Job alerts/newsletter and function/career landing pages for Accountmanagement, Sales
  and Marketing.
- Additional employer/recruitment content and possible expansion beyond the Netherlands.

#### SMV-044 — Legacy URL inventory import

- **Status:** NOT STARTED
- **Why now:** technical SEO is ready, but launch cannot safely preserve legacy equity without real exports.
- **Scope:** import crawl, Ahrefs, Search Console and WordPress URL data into the documented CSV inventory and assign KEEP/301/410/NOINDEX/MERGE decisions.
- **Dependencies:** external exports and content decisions.
- **Acceptance:** every high-value URL has an approved destination/action; no blanket homepage redirects; sensitive exports stay out of Git.

### C. Release / cleanup

#### SMV-076 — Public SEO regression hardening

- **Status:** DONE
- **Why now:** later Blog pagination and static public pages were added after the completed SEO foundation.
- **Result:** the Blog index now follows the existing page-1-clean/page-2+-self-canonical policy and discards unrelated query parameters. Shared title, description, robots, canonical and Open Graph output is escaped exactly once; structured JSON-LD keeps its dedicated safe encoder. `/over-ons`, `/tarieven` and `/contact` are documented and included as public indexable sitemap entries. Production robots now excludes the actual `/dashboard` panel path; non-production remains fully blocked.
- **Dependencies:** none for this technical correction; final copy and brand approval remain in SMV-063.
- **Acceptance:** page 1/page 2 canonical tests pass, stored titles cannot break head markup, and every indexable static route has a documented sitemap decision.

#### SMV-075 — Prototype/showcase and release-cache cleanup

- **Status:** DONE
- **Why now:** `artisan view:cache` failed on unrouted showcase views using a layout that contained anonymous-component syntax in the wrong directory.
- **Result:** restored that existing layout as the compileable `<x-app-layout>` component; retained `/tidy-html`, `resources/views/pages/component/`, the Tidy job references and supporting components; removed only proven-unreferenced onboarding/duplicate prototypes and two tracked conflict copies. Debug logging and the non-functional Vacancy bookmark were removed, and the account menu now delegates dashboard visibility to `User::canAccessPanel()`.
- **Dependencies:** none.
- **Acceptance:** `view:cache`, the frontend build and full functional test suite pass; no routed production view was removed; every removed view/artefact was proven unreferenced or byte-identical to a retained reference.

#### SMV-071 — Responsive and accessibility completion

- **Status:** PARTIALLY DONE
- **Why now:** custom navigation/listbox/filter interactions now exist on every major public route.
- **Scope:** keyboard, focus, screen-reader names/state, reduced motion, mobile overflow and inactive-action audit; fix only verified issues.
- **Dependencies:** stable SMV-070 markup.
- **Acceptance:** critical public flows are keyboard-usable at mobile/desktop breakpoints and documented accessibility checks pass.

#### SMV-072 — Critical-path end-to-end smoke suite

- **Status:** PARTIALLY DONE
- **Why now:** feature tests are broad, but release confidence also needs browser-level route and interaction coverage.
- **Scope:** homepage search, Vacancy filtering/detail/application, Company/Blog navigation, admin login and one import preview/run/history happy path.
- **Dependencies:** SMV-062 and SMV-075 preferably complete.
- **Acceptance:** repeatable staging/local smoke checklist or automated browser suite with no console/runtime errors.

#### SMV-073 — Production-like migration dry run

- **Status:** NOT STARTED
- **Why now:** verifies migrations, private/public storage, seed policy and import runtime before cutover.
- **Scope:** disposable production-like environment, backup/restore rehearsal, migrations, builds, representative feed run and rollback/incident notes.
- **Dependencies:** hosting/staging details, SMV-044/045 and content dataset.
- **Acceptance:** timed repeatable runbook with verified data/media/import integrity and named rollback owner.

#### SMV-074 — Production launch checklist

- **Status:** NOT STARTED
- **Why now:** final integration gate rather than another feature phase.
- **Scope:** environment, queues if adopted, mail, storage, HTTPS, robots/sitemap, redirects, analytics/Search Console, monitoring and post-launch checks.
- **Dependencies:** SMV-046, SMV-062/063 and SMV-071–073.
- **Acceptance:** signed checklist, production smoke pass, indexability verified and rollback/monitoring responsibilities assigned.

SMV-045 and SMV-046 remain mandatory after the SMV-044 inventory and a staging host are
available, but are not independently actionable before those dependencies exist.

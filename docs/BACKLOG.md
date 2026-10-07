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
| SMV-052 Employer vacancy-posting flow | **OBSOLETE** | Superseded by the more explicit SMV-078 staged advertising and quick-registration flow; do not implement both. |
| SMV-078 Employer vacancy advertising flow & quick registration | **DONE (stabilized account foundation)** | Guests choose a placement before auth; public login/registration, owned Company onboarding/profile editing, protected Vacancy drafts, preview, pending moderation hand-off and owned Vacancy history exist without payment/publication entitlement. |
| SMV-079 Saved Vacancies | **DONE** | User-owned Vacancy and Company-profile saves, guest auth continuation, public card state and private paginated account lists exist. |
| SMV-080 Candidate application account history | **DONE** | Authenticated Users see only session-linked internal Applications and a deliberate candidate status; current save rankings are aggregate and staff-only. |
| SMV-080B Registration intent journeys | **DONE** | One User/login now presents explicit Werkzoekende and Werkgever registration journeys, resumes saved-content or placement intent safely and keeps authorization ownership-based. |
| SMV-081 Safe rich-text Vacancy and Company descriptions | **DONE** | Employer and Filament editors share a reusable limited editor UI; domain-aware Vacancy and Company boundaries sanitize persistence and public rendering. |

## Phase F — Blog / content

| Ticket | Status | Repository result |
| --- | --- | --- |
| SMV-060 Native Laravel/Filament Blog | **DONE** | BlogPost domain, Filament CRUD, Media Library, public index/detail, SEO and sitemap exist. |
| SMV-061 Blog taxonomy and editorial relations | **DONE** | Typed categories/tags, manual Vacancy/Company relations, archives, JSON-LD and sitemap behavior exist. |
| SMV-062 Operational public Contact flow | **DONE** | The public form validates, rate-limits and honeypot-checks submissions, sends a configurable internal Laravel mail and persists no contact record. |
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
| SMV-077 Company discovery and filtering | **DONE** | Public-only Company search, one-category filtering, richer reusable cards and the homepage employer banner exist. |
| SMV-077A Dedicated Company category archives | **NOT STARTED** | A future category directory/archive may extend the current in-page single-category discovery without introducing dead routes now. |

## Phase H — Vacancy discovery extension

The evidence and proposed policy for this phase are documented in
`MVP_DISCOVERY_EXTENSION_AUDIT.md`. These tickets are planned, not implemented.

| Ticket | Status | Exact remainder |
| --- | --- | --- |
| SMV-082 Compensation data and normalization contract | **NOT STARTED** | Confirm comparable monthly salary/hourly rate metadata and close admin/import validation gaps without guessing legacy values. |
| SMV-083 Salary/rate range filtering | **NOT STARTED** | Add shared inclusive overlap filtering to homepage and Vacancy listing after SMV-082. |
| SMV-084 Sector/function-area ownership and administration | **NOT STARTED** | Complete explicit Vacancy/Company assignment and import/admin boundaries using the existing Category architecture. |
| SMV-085 Public sector/function-area archives | **NOT STARTED** | Add type-safe overview/detail routes, counts, filters, pagination and public Company context. |
| SMV-086 Linked public taxonomy labels | **NOT STARTED** | Link deliberate Vacancy/Company taxonomy labels to canonical archives without nested links. |
| SMV-087 Relevant Vacancies for Company and Vacancy detail | **NOT STARTED** | Apply visibility-safe, deduplicated relevance and featured ordering to existing blocks. |
| SMV-088 Blog editorial taxonomy fallback | **NOT STARTED** | Preserve manual Blog relations and add explicit function-area/sector fallback selection. |
| SMV-089 Discovery SEO and regression gate | **NOT STARTED** | Verify archive SEO/sitemap policy, visibility, performance and cross-feature regressions. |

## Next work queue

### A. Functional / MVP

#### SMV-062 — Operational public Contact flow

- **Status:** DONE
- **Result:** `GET /contact` retains the public page and `POST /contact` accepts the short
  general-purpose form through a Form Request. Laravel Mail sends one internal message
  to `CONTACT_MAIL_TO`, using the visitor only as Reply-To. A named limiter and inaccessible
  honeypot provide lightweight protection; success uses POST/redirect/GET and transport
  failures return a neutral message. Contact messages are not stored in the database.
- **Operations:** `.env.example` keeps `MAIL_MAILER=log` for safe local development.
  Production must configure its mail transport, application sender and
  `CONTACT_MAIL_TO`; `CONTACT_RATE_LIMIT_PER_MINUTE` defaults to five.
- **Deferred:** a visitor autoresponder and stronger anti-spam tooling are not required
  unless delivery or abuse data demonstrates a need.

#### SMV-050 — Packages and commercial scope audit

- **Status:** NEEDS REVIEW
- **Why now:** `/tarieven` is public while package/pricing/entitlement behavior is still placeholder-level.
- **Scope:** reconcile Package/Order/Payment foundations with the actual launch offer; decide which package/entitlement behavior SMV-078 needs before checkout hand-off.
- **Dependencies:** approved products, prices, VAT/payment and employer workflow decisions.
- **Acceptance:** documented keep/change/defer decisions, schema gap list and small follow-up tickets; no speculative checkout implementation.

#### SMV-078 — Employer vacancy advertising flow, account foundation and stabilization

- **Status:** DONE (stabilized vertical slice)
- **Result:** `/adverteren` and `/tarieven` lead Standaard/Superior visitors into the
  public `/vacature-plaatsen` flow. Guests choose first, then use the normal Laravel
  `web` guard through `/inloggen` or `/registreren`; the selected enum-backed intent and
  intended destination are retained in the session. Registration creates an employer
  User plus one owned pending Company. A User can own multiple Companies through
  `companies.user_id`; there is no membership/team pivot.
- **Vacancy boundary:** an authenticated owner creates a `draft` manual Vacancy for an
  owned Company, previews it privately, and submits it as `pending`. The flow cannot set
  publication, import identity or featured entitlement. Public visibility continues to
  depend exclusively on `Vacancy::publiclyVisible()`.
- **Packages:** `AdvertisingPackage` centralizes the already-published labels/prices and
  `vacancies.placement_package` records intent only. It is deliberately not an Order,
  payment or entitlement. Maatwerk uses the allowlisted SMV-062 Contact context.
- **Security/SEO:** owner policies and Company-scoped validation protect every private
  Vacancy mutation; auth and wizard pages are `noindex, nofollow`, use clean canonicals
  and are absent from the sitemap. Ordinary employers retain no Filament access.
- **Account/publication:** `/account` is the ordinary authenticated destination, with
  owner-scoped Company profile/media editing and owned Vacancy history. Filament
  publish-now fills a missing `published_at` on transition to `published`; explicit
  future scheduling and historical publication dates are preserved. Deadline is the
  application cutoff and expiry the listing cutoff; both currently end public visibility.
- **Deferred:** SMV-050 still owns definitive package/entitlement rules; SMV-051 owns
  Order/payment/provider handling. Candidate notifications and external/e-mail
  application tracking remain deliberately unavailable.

#### SMV-079 — Saved Vacancies

- **Status:** DONE
- **Result:** authenticated Users of every role can save each public Vacancy once and
  remove it again. Guests continue through the existing login/registration routes and
  the bounded save intent is revalidated before it is completed. General registration
  creates a candidate account without a Company; the Vacancy-placement context retains
  the employer plus pending-Company behavior.
- **Account/lifecycle:** `/account/bewaarde-vacatures` is private, paginated and
  `noindex, nofollow`. Public saves render with the shared cards. A saved record that is
  no longer publicly eligible remains related but exposes only `Niet meer beschikbaar`;
  it can still be removed and does not leak private Vacancy or Company content.
- **Company profiles:** the same role-independent behavior is available on Company
  cards through a separate unique `saved_companies` relationship and the private
  `/account/bewaarde-bedrijven` list. Company and Vacancy saves remain separate domains.

#### SMV-080 — Candidate application account history

- **Status:** DONE
- **Result:** authenticated internal submissions store the session User in the existing
  nullable `applications.candidate_id`. `/account/sollicitaties` lists only that User's
  internal Applications, newest first and paginated. Existing e-mail-only/guest rows
  are not claimed automatically. Workflow states map to deliberately limited candidate
  labels; motivation, contact data, CV paths and internal terminology remain private.
- **Lifecycle:** unavailable, filled, expired, hidden or soft-deleted Vacancy context is
  replaced by a generic unavailable label while the known Application status remains.
  Admins can update only the existing status through Filament; no ATS pipeline was added.

#### SMV-080B — Separate work-seeker and employer registration journeys

- **Status:** DONE
- **Result:** generic `/registreren` first offers `Werkzoekende` and `Werkgever`; both
  use the same `users` table, `web` guard and shared `/inloggen` page. Explicit validated
  context determines form fields, initial classification and continuation. Work-seeker
  registration never creates a Company; employer registration retains pending owned
  Company onboarding and Vacancy-placement continuation.
- **Intent/security:** the bounded registration resolver maps saved Vacancy/Company
  sessions to work-seeker registration and placement sessions to employer registration.
  Context cannot grant Filament access, ownership of an existing Company or arbitrary
  Vacancy management. Roles are not exclusive product identities: employers may save
  and apply, while an existing work seeker may later complete employer onboarding with
  the same User.
- **Engagement:** a staff-only Filament widget aggregates current saves directly from
  the unique Vacancy and Company pivots, including deterministic top-five rankings of
  existing records. No saver identities or counts are exposed publicly. Historical
  save/unsave events and Activitylog integration are deferred until trend reporting is
  an actual requirement.
- **Boundary:** external URLs and e-mail destinations never create fake Applications;
  messaging, timelines, notifications, application claiming and behavioural profiling
  remain outside this ticket.

#### SMV-081 — Safe rich-text Vacancy and Company descriptions

- **Status:** DONE
- **Result:** Vacancy placement and Company profile editing share a progressively
  enhanced limited rich-text editor with a normal textarea fallback and accessible
  Lucide toolbar controls. Filament exposes the same semantic choices. Explicit
  Vacancy and Company domain services use one low-level Symfony HTML Sanitizer policy
  permitting only paragraphs, line breaks, `h2`/`h3`, strong/emphasis,
  ordered/unordered lists and safe links. Model writes and public rendering use their
  corresponding domain boundary; Vacancy imports and preview use the Vacancy policy.
  Legacy rows are defensively sanitized at render time without a destructive rewrite.
- **Boundary:** arbitrary HTML, media, tables, embeds, inline styles/classes and custom
  editor blocks remain prohibited. Metadata and JobPosting descriptions remain plain
  text.

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

- **Status:** DONE
- **Result:** `/bedrijven` supports public-only search across useful Company profile
  fields and exactly one typed Company category through shareable GET state. A compact
  category browser uses one grouped public-Company count query, pagination preserves
  filters and featured priority remains deterministic.
- **Presentation:** reusable Company cards now use a left-aligned contained logo or
  deterministic letter fallback, safe plain-text introduction, public Vacancy aggregate,
  typed category context and the existing asynchronous circular save control. A reusable
  Tidy-derived employer logo strip immediately follows the homepage hero and contains
  only real public Company records.
- **Out of scope:** speculative taxonomies, geocoding and a separate frontend stack.

#### SMV-077A — Dedicated Company category archives

- **Status:** NOT STARTED
- **Scope:** decide whether `/bedrijven/categorieen` and
  `/bedrijven/categorie/{slug}` add sufficient visitor and SEO value beyond the existing
  `/bedrijven?category={slug}` discovery flow. No placeholder or dead public route ships
  before that decision.

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

### D. Vacancy discovery extension

#### SMV-082 — Compensation data and normalization contract

- **Classification/status:** MVP — NOT STARTED.
- **Goal/evidence:** make existing `salary_*` and `rate_*` ranges reliably comparable. All
  eight amount/currency/period fields and `CompensationPeriod` exist, but Filament omits
  metadata and no field distinguishes fulltime-equivalent from offered-hours salary.
- **In scope:** audit live/seed/import values; agree nullable salary-basis metadata; align
  Filament/employer validation; document EUR-month salary and EUR-hour rate comparability;
  correct stale compensation statements in import documentation.
- **Out of scope:** currency conversion, assumed hours, bonus/commission percentages,
  package entitlement and bulk guessing/backfill.
- **Dependencies:** product confirmation of the public salary basis; package confirmation is
  not required.
- **Acceptance:** ambiguous rows remain unknown; minimum/maximum ordering and enum values are
  validated at every write boundary; salary and rate can still coexist; imports preserve
  warnings instead of guessing.
- **Tests/verification:** create/update/import salary-only, rate-only, both, null metadata,
  annual-to-monthly once, reversed/negative bounds and unknown period/basis.
- **Documentation:** `DATA_MODEL.md`, `IMPORTS.md`, `IMPORT_DESIGN.md`, `MVP.md` and this audit.
- **Open decision:** FTE versus offered-hours canonical monthly basis; recommended default is
  explicitly marked gross monthly FTE, EUR only. Zero should mean invalid/unknown.
- **Effort:** medium — schema may be small, but write-boundary and data-quality verification
  spans admin, employer and imports.

#### SMV-083 — Salary and hourly-rate range filtering

- **Classification/status:** MVP — NOT STARTED.
- **Goal/evidence:** extend shared `VacancySearch` so homepage and `/vacatures` support two
  amount inputs plus an explicit monthly/hourly mode. Existing shared GET filters, debounce,
  collapsible homepage panel, reset and pagination are reusable.
- **In scope:** `vergoeding`, `bedrag_van`, `bedrag_tot`; inclusive overlap for one/two/fixed
  bounds; Dutch validation; active chips; combined filters; page reset/query preservation.
- **Out of scope:** slider, currency conversion, salary sorting and ambiguous compensation.
- **Dependencies:** SMV-082 comparability contract.
- **Acceptance:** monthly salary never matches hourly rate; unknown values are excluded only
  with an active amount; equal boundaries match; homepage results stay on `/`; search and
  “Wis filters” remain visible.
- **Tests/verification:** minimum-only, maximum-only, equal endpoints, fixed/one-bound rows,
  negative/non-numeric/reversed input, period/currency/basis mismatch, combined taxonomy and
  Company filters, pagination state and homepage parity.
- **Documentation:** `MVP.md`, `FRONTEND.md`, `DATA_MODEL.md`, `SEO.md`.
- **Open decision:** final public copy for monthly salary basis; no technical package blocker.
- **Effort:** medium — one shared query path, two responsive forms and boundary-heavy tests.

#### SMV-084 — Sector/function-area ownership and administration

- **Classification/status:** MVP — NOT STARTED.
- **Goal/evidence:** complete explicit ownership through existing `Category`/`categoryables`.
  Vacancy admin/import already supports sector/function area; Company relation supports them
  technically but Company admin currently exposes only `company_category`.
- **In scope:** type-scoped Company sector selection; review Vacancy hierarchy selection;
  preserve source-scoped aliases; safe handling of existing assignments and duplicate slugs.
- **Out of scope:** new taxonomy tables, name-based auto-classification, automatic Company →
  Vacancy inheritance and Blog taxonomy changes.
- **Dependencies:** none; precedes public Company-sector presentation.
- **Acceptance:** Vacancy and Company sectors are explicit; function area remains Vacancy-led;
  same slug across types is safe; Blog/vacancy/company categories stay isolated; imports do
  not create or guess unresolved Categories.
- **Tests/verification:** Filament sync/detach, inverse morph relations, same-slug types,
  parent-type validation, import aliases and no implicit propagation.
- **Documentation:** `DATA_MODEL.md`, `ARCHITECTURE.md`, `IMPORTS.md`.
- **Open decision:** whether Company needs function-area ownership; recommended MVP is sector
  only, with Vacancy function areas remaining explicit.
- **Effort:** medium — architecture is reusable, but every query/form must constrain type.

#### SMV-085 — Public sector and function-area overview/archives

- **Classification/status:** MVP — NOT STARTED.
- **Goal/evidence:** turn existing filter-only taxonomies into crawlable discovery pages.
  Blog archives provide proven typed binding/canonical patterns; no Vacancy taxonomy routes
  currently exist.
- **In scope:** `/vacatures/categorieen`, typed sector/function-area detail routes before the
  Vacancy catch-all, descriptions, public distinct counts/results, hierarchy semantics,
  existing filters, pagination, breadcrumbs and relevant explicitly-sector-linked Companies.
- **Out of scope:** automatic classification, location archives and a second card/filter UI.
- **Dependencies:** SMV-084; archive copy/description decision.
- **Acceptance:** parent includes same-type descendants; Category scope survives all filters;
  each Vacancy occurs once; wrong-type/unknown slug is 404; valid empty archive is a 200
  noindex empty state; counts equal public results.
- **Tests/verification:** typed slug collisions, parent/child counts, public lifecycle,
  duplicate relationships, additional filters, pagination, empty/invalid archives and query
  count/eager loading.
- **Documentation:** `MVP.md`, `FRONTEND.md`, `SEO.md`, `DATA_MODEL.md` and route docs.
- **Open decision:** source for archive descriptions; recommended nullable Category copy only
  if curated copy cannot live in configuration/content.
- **Effort:** large — routes, reusable query constraints, hierarchy/counts, UI and SEO meet.

#### SMV-086 — Clickable Vacancy and Company taxonomy labels

- **Classification/status:** MVP — NOT STARTED.
- **Goal/evidence:** link current unlinked Vacancy badges and new explicit Company sector
  labels to SMV-085 archives. Blog chips are already correctly linked and type-scoped.
- **In scope:** deliberate sector/function-area labels on Vacancy cards/detail; sector labels
  on Company profiles; canonical archive URLs; accessible non-nested card interactions.
- **Out of scope:** linking free-form tags, employment/workplace archives or card redesign.
- **Dependencies:** SMV-084 and SMV-085.
- **Acceptance:** labels use matching taxonomy type; card primary links and label links do not
  nest or conflict; long labels wrap; non-public/legacy types are not linked.
- **Tests/verification:** URLs/type collisions, escaped labels, keyboard focus, card click
  targets and eager-loaded Category relations.
- **Documentation:** `FRONTEND.md`, `SEO.md`.
- **Open decision:** maximum card labels; recommended one function area plus one sector.
- **Effort:** small — mostly component wiring after canonical archives exist.

#### SMV-087 — Relevant Vacancies on Company and Vacancy detail

- **Classification/status:** MVP — NOT STARTED.
- **Goal/evidence:** improve existing Company and Vacancy blocks without duplicating them.
  Company detail already loads own public Vacancies; Vacancy detail currently returns at most
  three same-Company public Vacancies.
- **In scope:** Company label “Vacatures bij dit bedrijf”, maximum six and featured-first;
  Vacancy ranking same Company → shared function area → shared sector, featured within group,
  deterministic ordering, deduplication and eager loading.
- **Out of scope:** package entitlement, recommendations from tags/text/AI and unrelated filler.
- **Dependencies:** existing taxonomy is enough; SMV-084 improves data coverage but is not a
  code blocker.
- **Acceptance:** visibility always wins; current Vacancy excluded; relevance outranks
  featured; candidates appear once; insufficient matches produce a smaller/absent block.
- **Tests/verification:** ranking precedence, featured ties, duplicate category matches,
  expired/filled/future/deleted records, hidden Company, deterministic limit and query count.
- **Documentation:** `ARCHITECTURE.md`, `MVP.md`, `FRONTEND.md`.
- **Open decision:** Company limit/CTA; recommended six and Company-filtered `/vacatures`.
- **Effort:** medium — bounded ranking query and existing component consolidation.

#### SMV-088 — Blog editorial taxonomy fallback

- **Classification/status:** MVP — NOT STARTED.
- **Goal/evidence:** retain implemented manual `BlogPost::vacancies()`/`companies()` and add
  explicit sector/function-area fallback only when manual public Vacancies do not fill three.
- **In scope:** separate Filament selectors for explicit function areas/sectors; manual-first,
  then function-area, then sector ranking; public filtering, deduplication and existing cards.
- **Out of scope:** matching Blog category/tag slugs, AI recommendations, related-post logic
  and automatic Company inference.
- **Dependencies:** SMV-084 taxonomy ownership; can follow SMV-087 query/ranking conventions.
- **Acceptance:** manual relations remain intact and first; hidden manual records do not leak;
  explicit taxonomy fills only remaining slots; featured is secondary to relevance; maximum
  three and no unrelated filler.
- **Tests/verification:** manual + fallback mix, typed slug collisions, expired records,
  deduplication, inverse existing relations and Filament sync/detach.
- **Documentation:** `BLOG.md`, `DATA_MODEL.md`, `ARCHITECTURE.md`, `SEO.md`.
- **Open decision:** whether editors need manual order; recommended defer pivot `sort_order`
  until editorial evidence requires it.
- **Effort:** medium — existing relations are strong; explicit taxonomy and ranking are new.

#### SMV-089 — Discovery SEO, sitemap and regression gate

- **Classification/status:** MVP release gate — NOT STARTED.
- **Goal/evidence:** extend, not replace, the completed SMV-040–043/076 SEO foundation for
  new taxonomy discovery and verify all discovery slices together.
- **In scope:** unique archive metadata, canonical/robots pagination policy, breadcrumbs,
  public non-empty sitemap eligibility, internal links, performance review and focused/full
  regression matrix.
- **Out of scope:** legacy redirect inventory, schema types without evidence and SEO changes to
  arbitrary filtered listing URLs.
- **Dependencies:** SMV-083 and SMV-085–088.
- **Acceptance:** clean archives index/follow; page 1 canonical is clean; clean page 2+ is
  self-canonical; additional filters noindex/follow to clean archive; sitemap excludes empty,
  wrong-type and non-public archives; no visibility leaks or N+1 regressions.
- **Tests/verification:** salary boundaries/periods, combined filters/homepage, typed slug
  collisions, archive/public visibility, related ranking/deduplication, expired records,
  Blog manual relations, sitemap and query counts.
- **Documentation:** `SEO.md`, `MVP.md`, `BACKLOG.md`, launch/checklist documentation.
- **Open decision:** confirm valid-empty archive 200/noindex policy; recommended as audited.
- **Effort:** medium — primarily integration tests and SEO wiring across several completed
  feature tickets.

SMV-045 and SMV-046 remain mandatory after the SMV-044 inventory and a staging host are
available, but are not independently actionable before those dependencies exist.

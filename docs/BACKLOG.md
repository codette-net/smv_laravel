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
| SMV-050 Packages audit/completion | **P0 — DONE** | One editable Package catalogue now supplies public presentation and Order snapshots: Standaard EUR 189/60 days, Superior EUR 398/configurable duration and Maatwerk from EUR 950/quote-only, all net prices approved through 2026-12-31. Configured MVP VAT is 21%; broader tax treatment and fulfilment automation remain outside this ticket. |
| SMV-050B Commercial Vacancy ranking MVP | **P0 — DONE** | Signed Package, Company and Vacancy score inputs, an entitlement-safe SQL score boundary, authorized Filament inline/bulk management and deterministic default public ordering are implemented. SMV-116 now supplies the authoritative active-entitlement package component. Explicit user sorts, visibility, filters, featured state and publication dates remain authoritative. |
| SMV-051 Orders/payments audit/completion | **REQUIRED MVP — REFINED** | Existing Order/OrderItem/Payment schema and models are only a historical foundation. The missing purchase workflow is now split into SMV-110 (Order foundation), SMV-111 (provider-neutral/fake gateway) and SMV-112 (confirmed Stripe integration). |
| SMV-110 Order foundation | **P0 — DONE** | `CreateOrder` provides provider-independent, atomic Order/OrderItem creation with immutable product/price/currency/VAT snapshots, ownership and placement linkage; no gateway integration. |
| SMV-111 Payment gateway abstraction and fake gateway | **P0 — DONE** | Provider-neutral Payment attempts, deterministic Fake pending/success/failure/cancellation outcomes, amount integrity and idempotent confirmation are implemented without network access. |
| SMV-112 Stripe checkout/payment integration | **P1 — DONE** | Hosted Stripe Checkout, signed/idempotent webhook reconciliation, owner-scoped customer Order history and staff payment references use immutable Order snapshots through the SMV-111 gateway boundary. The Fake remains the automated-test provider; authoritative confirmation now enters SMV-116 fulfilment without publishing. |
| SMV-116 Company placement credits and entitlements | **P0 — DONE** | Eligible paid OrderItems grant individually tracked Company credits exactly once from immutable quantity/duration/priority/benefit snapshots. Company-scoped allocation, publication-time activation, expiry, SQL ranking, audit history, recovery command, employer overview, staff inspection and deterministic acceptance fixtures are implemented without bypassing review. |
| SMV-117 Order cancellation, refunds and internal audit | **DONE** | Authorized unpaid cancellation and provider-authoritative complete refunds now use durable audited operations, idempotent reconciliation and fail-closed placement-credit review safeguards. |
| SMV-119 Commerce acceptance UX and role-aware account | **DONE** | Actual Company ownership makes `/account` employer-first while candidate activity remains accessible in the same account. Placement lifecycle counts/blockers, consistent Order/payment badges, Dutch EUR formatting, customer refund/review wording and discoverable staff actions are covered without changing payment, publication or ranking behavior. |
| SMV-118 Promotional Vacancy banner and UI/UX polish | **P2 — RESERVED FOR UI/UX ROUND** | Add an optional scheduled, admin-managed full-width Vacancy banner variant with optional image/short text, using the normal detail URL, visibility and apply tracking. Keep it independent from ranking, featured state and automatic Superior fulfilment. |
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
| SMV-071 Responsive/accessibility pass | **PARTIALLY DONE** | Responsive layouts, labels, focus styles and keyboard hooks exist. SMV-072B adds Chromium evidence at 320/375/768/1024/1440px plus keyboard menu/listbox and reduced-motion checks; manual screen-reader, zoom, real-device and wider browser-matrix evidence remains required. |
| SMV-072 End-to-end smoke tests | **PARTIALLY DONE** | SMV-072B adds a guarded isolated Playwright/Chromium suite for critical public, candidate, employer, staff, analytics, responsive and keyboard paths plus a separate read-only staging command. Keep partial until manual staging, screen-reader/real-device and production browser-matrix checks are retained as release evidence. |
| SMV-072A User roles, account types and authorization consistency | **DONE** | Company ownership/activity now defines public account context, while bounded Spatie staff roles exclusively define panel authorization. The legacy `users.role` value remains read-only compatibility data, User administration prevents arbitrary roles and admin-to-super-admin escalation, and acceptance fixtures include a dual-context account. |
| SMV-073 Migration dry run | **NOT STARTED** | Needs the SMV-109 production-like dataset, production-like data/export inputs and deployment environment. |
| SMV-074 Production launch checklist | **NOT STARTED** | Depends on content, redirects, staging crawl, SMV-109 acceptance data, migration dry run and operational configuration. |
| SMV-109 Production-like acceptance dataset | **DONE** | `AcceptanceDemoSeeder` provides guarded, synthetic, repeatable role/company/Vacancy/import/history/application/save/click/detail-view fixtures for SMV-072 and SMV-073 without production credentials or partner endpoints. |
| SMV-113 Filament Vacancy application destination reactivity | **DONE** | Filament Vacancy create/edit forms now normalize enum-backed mode state, react immediately across all three modes, validate only the active destination, and restrict active external links to HTTP(S). |
| SMV-075 Prototype/showcase and release-cache cleanup | **DONE** | The intended showcase layout is available as `<x-app-layout>`, useful Tidy/component references remain, obsolete duplicate/onboarding prototypes and tracked conflict artefacts are removed, and `artisan view:cache` succeeds. |
| SMV-076 Public SEO regression hardening | **DONE** | Blog and archive pagination use clean self-canonicals, public metadata is escaped once at the output boundary, indexable static pages are present in the sitemap, and the environment-aware robots path matches the Filament dashboard. |
| SMV-077 Company discovery and filtering | **DONE** | Public-only Company search, one-category filtering, richer reusable cards and the homepage employer banner exist. |
| SMV-077A Dedicated Company category archives | **NOT STARTED** | A future category directory/archive may extend the current in-page single-category discovery without introducing dead routes now. |

## Phase I — Vacancy discovery extension

The evidence and proposed policy for this phase are documented in
`MVP_DISCOVERY_EXTENSION_AUDIT.md`. These tickets are planned, not implemented.

| Ticket | Status | Exact remainder |
| --- | --- | --- |
| SMV-082 Compensation data and normalization contract | **DONE** | Nullable explicit salary basis, normalized admin/import writes and reusable EUR-month-FTE / EUR-hour comparability scopes are implemented without reclassifying legacy rows. |
| SMV-083 Search/filter completion and salary/rate ranges | **DONE** | Homepage and Vacancy listing share primary/secondary GET filters, structured taxonomies and inclusive comparable compensation overlap. |
| SMV-083A Structured province data/filter | **DEFERRED** | Add only after Vacancy has an explicit validated province value; free-text place names must not be guessed into provinces. SMV-114 may provide the normalization/reference layer without replacing `vacancies.location`. |
| SMV-114 Benelux place reference library | **P1/P2 — PROPOSED** | Establish normalized Netherlands/Belgium/Luxembourg place names, aliases and country/region metadata as a reference layer; do not add geocoding, radius search or an immediate foreign-key replacement. |
| SMV-115 Vacancy cover image | **P1/P2 — PROPOSED** | Add an optional Vacancy-specific media asset using existing conventions, with Vacancy image precedence and Company-image fallback where supported. |
| SMV-084 Sector/function-area ownership and administration | **NOT STARTED** | Complete explicit Vacancy/Company assignment and import/admin boundaries using the existing Category architecture. |
| SMV-085 Public sector/function-area archives | **NOT STARTED** | Add type-safe overview/detail routes, counts, filters, pagination and public Company context. |
| SMV-086 Linked public taxonomy labels | **NOT STARTED** | Link deliberate Vacancy/Company taxonomy labels to canonical archives without nested links. |
| SMV-087 Relevant Vacancies for Company and Vacancy detail | **NOT STARTED** | Apply visibility-safe, deduplicated relevance and featured ordering to existing blocks. |
| SMV-088 Blog editorial taxonomy fallback | **NOT STARTED** | Preserve manual Blog relations and add explicit function-area/sector fallback selection. |
| SMV-089 Discovery SEO and regression gate | **NOT STARTED** | Verify archive SEO/sitemap policy, visibility, performance and cross-feature regressions. |

## Next work queue

### Immediate post-SMV-071 queue

The approved commercial implementation order is now:

1. **SMV-050** — completed the configurable package catalogue and approved MVP pricing.
2. **SMV-050B** — completed the focused commercial ranking MVP with an entitlement-safe boundary.
3. **SMV-112** — completed the confirmed Stripe provider integration against the SMV-110/111 boundaries.
4. **SMV-116** — completed paid Company placement credits/entitlements.
5. **SMV-117** — completed the minimal cancellation/refund and internal-audit workflow.
6. **SMV-097A** — completed privacy-minimal Vacancy-detail views, reporting, CSV and retention; retain browser/staging verification as release evidence.
7. **SMV-119** — completed the role-aware commerce acceptance dashboard and operational status presentation.

In parallel, continue the Phase G release gates: finish evidence for SMV-071, implement
SMV-072 browser smoke, complete SMV-044/045 legacy URL inventory and redirects, run the
SMV-046 staging SEO crawl, approve/freeze content, then execute SMV-073 and SMV-074.
SMV-109 is a shared prerequisite for browser and dry-run work. SMV-112/116 now supply the Stripe
payment and paid-placement path; SMV-117 remains the explicit reversal/refund boundary. SMV-118
remains reserved for the later consolidated UI/UX round;
SMV-114 and SMV-115 remain valuable but less launch-critical.

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

- **Status:** DONE
- **Result:** persisted Package rows are the editable catalogue for public copy, benefits,
  net minor-unit prices, currency, placement count/duration, checkout/quote semantics,
  price validity, VAT configuration and staff-only fulfilment notes. The enum remains only
  the stable placement identifier and safe bootstrap configuration supports fresh/test setups.
- **Approved launch catalogue:** Standaard EUR 189/60 days, Superior EUR 398 with a
  configurable initial 60-day duration, and Maatwerk from EUR 950 through Contact only.
  Prices exclude VAT and are approved through 2026-12-31.
- **Safety:** checkout eligibility fails closed for inactive/expired/tax-incomplete rows in
  production; local/test may explicitly allow expired fixtures. Package selection and Order
  creation grant no right; only authoritative eligible payment creates credits, and payment
  never publishes a Vacancy.
- **Separate boundaries:** SMV-050B owns ranking, SMV-116 owns placement credits/entitlement,
  and SMV-117 owns minimal cancellation/refund handling. Unsupported VAT jurisdictions,
  bundle/subscription products, advertising integrations and quotation management remain
  deferred.

#### SMV-050B — Commercial Vacancy ranking MVP

- **Status:** P0 — DONE
- **Result:** one deterministic database-query commercial score is active for the default
  general Vacancy result ordering without weakening lifecycle or search/filter eligibility.
- **Score:** effective score is the sum of a configurable package score (initial defaults:
  Standaard 0, Superior +20), configurable Company priority and configurable Vacancy override.
  Vacancy overrides may be negative. A package score applies only while the Vacancy has a
  valid active paid placement entitlement; package selection and Order/Payment state alone are
  insufficient. `VacancyPackagePriorityProvider` reads the snapshotted package score through
  the active entitlement validity window while Company priority and Vacancy adjustment remain
  independent.
- **Ordering:** `VacancySearch` applies effective priority after `Vacancy::publiclyVisible()`
  and requested filters for the default homepage/listing result order, followed by publication,
  creation and ID tie-breakers. Any valid explicit user sort keeps its previous ordering.
  Current free-text matching is boolean rather than weighted; ranking cannot bypass it.
- **Administration:** the dedicated policy-protected Filament **Vacatureranking** page provides
  title/reference search, Company/status/package filters, effective sorting, 25/50/100/250
  pagination, immediate bounded inline Vacancy editing and transactional bulk set/add/reset.
  Company administration exposes the single Company priority, and Package administration
  exposes package priority. The application has no active model Activitylog convention to
  extend; ordinary employers/candidates remain excluded. Temporary boost expiry was not needed.
- **Out of scope:** HubSpot, advertisement-channel integration, private advertising-budget
  accounting, entitlement issuance and banners. Public Superior copy may describe increased
  visibility, promotion, advertising and guidance, but must not publish operational budgets.
- **Verification:** configured defaults, paid-Order non-entitlement, positive/negative totals,
  actual public ordering, explicit sorts, filtering, stable pagination, non-public exclusion,
  homepage behavior, Filament operations/authorization, import reruns and manual copies are
  covered. Ranking never writes lifecycle dates, `is_featured` or provider metadata.

#### SMV-110 — Order foundation

- **Status:** DONE
- **Result:** `CreateOrder` supplies the provider-independent Order boundary required by the
  employer placement flow.
- **Scope:** User/employer, Company, Order, one or more OrderItems where appropriate, selected
  advertising product/package, immutable product/price/currency/VAT snapshots, totals, order
  status and a safe relation to Vacancy/placement intent. Historical Orders must remain
  readable from their snapshots even when current Package pricing changes.
- **Dependencies:** SMV-050 decisions and SMV-078 placement intent; review the existing
  Package/Order/OrderItem models before changing schema.
- **Out of scope:** real provider calls, checkout UI and payment webhooks (SMV-111/112).
- **Acceptance:** a purchase creates an auditable Order boundary without reading mutable current
  package price for historical totals; draft/pending/paid/failed/cancelled transitions are
  explicit and entitlement is not granted by unverified client input.

#### SMV-111 — Payment gateway abstraction and fake gateway

- **Status:** DONE
- **Result:** Order/payment workflows are testable without contacting Stripe.
- **Scope:** document a provider-neutral gateway contract and deterministic fake gateway for
  pending, successful, failed, cancelled, duplicate callback/webhook and retry/idempotency
  scenarios.
- **Dependencies:** SMV-110 and approved Order/payment statuses.
- **Out of scope:** selecting or integrating a real payment provider.
- **Acceptance:** payment state changes are idempotent, auditable and cannot double-grant an
  entitlement; feature tests cover every fake outcome and duplicate callback. The fake gateway
  remains available for all automated tests after Stripe is introduced.

#### SMV-112 — Stripe checkout / payment provider

- **Status:** P1 — DONE
- **Result:** owned pending Orders enter hosted Stripe Checkout using immutable OrderItem money
  snapshots. A signed `/stripe/webhook` boundary validates provider mode, internal references,
  amount and currency, then records each Stripe event once and applies provider-authoritative
  success/failure/expiry without trusting browser return routes. Customer Order history and
  retry are available under `/account/bestellingen`; staff can inspect Stripe references on the
  existing Order resource. The Fake gateway remains the deterministic automated-test default.
- **Operations:** credentials, explicit test/live mode and the webhook signing secret are
  deployment inputs documented in `PAYMENTS.md`; no secret or full payload is logged.
- **Out of scope:** changing the provider-neutral fake gateway into a live dependency; SMV-111
  remains the deterministic test boundary. SMV-116 owns fulfilment behind the authoritative
  confirmation boundary rather than duplicating grant logic in Stripe-specific code.

#### SMV-116 — Company placement credits and entitlements

- **Status:** P0 — DONE.
- **Result:** `ConfirmPayment` idempotently grants one Company-owned `PlacementCredit` per
  purchased unit from immutable OrderItem entitlement snapshots. Unique grant/vacancy keys,
  row locks and deduplicated events protect grant and consumption. A linked eligible Vacancy is
  allocated but remains pending; employers may use a compatible unused credit for an owned
  draft instead of creating another Order.
- **Lifecycle:** duration starts at first public Company/Vacancy eligibility, expiry clamps the
  Vacancy boundary, and ordinary edits/reactivation never renew the term. Manual/imported
  publications continue without credits. Active Superior credits supply the snapshotted package
  score in the existing SQL ranking; missing/expired rights supply zero.
- **Operations/UI:** `placements:reconcile-paid-orders` repairs missing grants without webhook
  replay. `/account/plaatsingsrechten` is Company-scoped; Filament inspection is read-only and
  retains grant/allocation/activation history. Acceptance fixtures cover available, pending,
  active Standard/Superior and expired states without fake Stripe events.
- **Deferred:** refund/cancellation reversal is SMV-117. Bundle pricing/subscriptions remain
  unimplemented, while one OrderItem can already issue several separately consumed credits.

#### SMV-117 — Order cancellation, refunds and internal audit

- **Status:** DONE.
- **Goal:** let authorized editors/sales staff and administrators perform permitted commercial
  reversals without treating cancellation and refund as the same operation.
- **Scope:** authorized staff cancel unpaid Orders after any open Checkout Session is safely
  expired, and request complete refunds for the immutable paid amount through the Fake/Stripe
  gateway boundary. `order_operations` audits operator, mandatory reason, amount, timestamps,
  idempotency key, provider reference/outcome and safe reconciliation failures.
- **Safety:** confirmed refunds revoke unused credits once. Allocated or active credits stay
  attached, become unavailable and lose package ranking while an explicit review remains open;
  publication and manual Company/Vacancy priority are not changed. Late valid payment still
  wins over a cancellation and flags the audit operation for review. Partial refunds remain
  deferred.
- **Acceptance:** authorization, state transitions, provider success/failure/idempotency and
  audit evidence are tested; no refund is represented as a local-only successful provider
  outcome.

#### SMV-119 — Commerce acceptance UX and role-aware account dashboard

- **Status:** DONE.
- **Account context:** Company ownership, rather than one assigned role, makes the account
  employer-first. Employers see Company/Vacancy actions, lifecycle statistics, credits and
  Orders first. Users with Applications or saves retain a secondary candidate overview; users
  without a Company remain candidate-first and can start the existing employer onboarding flow.
- **Commerce presentation:** account surfaces use one Dutch money formatter and shared Order,
  Payment and placement status labels/badges. Credit totals distinguish available, assigned or
  scheduled, active and review-required rights. Actual Company approval, Vacancy approval and
  future-publication blockers are explained without changing activation rules.
- **Refund review:** customers see financial refund completion separately from an operational
  placement review. Staff reach existing cancel/refund actions from the Bestellingen list and
  see the next manual review step on Order and placement-right details. Because no universal
  commercial resolution is approved, review-required credits remain pending; the UI never
  reinstates, extends, republishes or changes payment state automatically.
- **Compatibility:** no ranking query, entitlement transition, publication lifecycle, schema or
  payment-provider behavior changed.

#### SMV-118 — Promotional Vacancy banner and UI/UX polish

- **Status:** P2 — RESERVED FOR UI/UX ROUND
- **Goal:** add an optional visually distinctive presentation variant to an existing Vacancy,
  not a new Vacancy type or automatic package benefit.
- **Scope:** admin-managed placement and optional schedule, optional banner-specific image and
  short text, full container/grid width where supported (`col-span-2` on suitable desktop
  grids), responsive mobile treatment and reusable Blade/Tailwind presentation using existing
  image conventions.
- **Safety:** preserve the normal Vacancy detail URL, public visibility, application destination
  and outbound tracking. Keep banner placement independent from commercial score,
  `is_featured` and Superior; never promise it automatically with a package.
- **Acceptance:** inactive/out-of-window/non-public Vacancies never render as banners; ordinary
  cards remain unchanged; accessible responsive output and existing click/detail behavior are
  covered in the later consolidated UI/UX/browser pass.

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
- **Packages:** `AdvertisingPackage` is the stable selection identifier; persisted Package
  rows supply editable labels, benefits, prices and structured rules. The
  `vacancies.placement_package` value remains intent only, never an Order, payment or
  entitlement. Maatwerk uses the allowlisted SMV-062 Contact context.
- **Security/SEO:** owner policies and Company-scoped validation protect every private
  Vacancy mutation; auth and wizard pages are `noindex, nofollow`, use clean canonicals
  and are absent from the sitemap. Ordinary employers retain no Filament access.
- **Account/publication:** `/account` is the ordinary authenticated destination, with
  owner-scoped Company profile/media editing and owned Vacancy history. Filament
  publish-now fills a missing `published_at` on transition to `published`; explicit
  future scheduling and historical publication dates are preserved. Deadline is the
  application cutoff and expiry the listing cutoff; both currently end public visibility.
- **Deferred:** SMV-112 owns Stripe checkout/webhooks and a later boundary owns paid
  entitlement fulfilment. Candidate notifications and external/e-mail
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
- **SMV-071 audit evidence (20 November 2026):** the shared header now labels mobile navigation,
  restores focus to the mobile trigger and account trigger after Escape, and gives the mobile
  menu button an explicit button type. The shared custom listbox now preserves its server-side
  hidden value and moves focus to the selected option (or first option) when opened with click,
  Enter, Space or either arrow key. The public stylesheet applies a reduced-motion policy to
  transitions and animations. These boundaries are covered by
  `tests/Feature/AccessibilityRegressionTest.php`; the related public, account and interaction
  feature suites remain green apart from one existing homepage assertion that depends on an
  outdated section-order assumption. Browser-level keyboard/screen-reader checks at 320/375/768/
  1024px and desktop were not available in this environment, so the ticket remains PARTIALLY DONE.

#### SMV-109 — Production-like acceptance dataset

- **Status:** DONE
- **Goal:** provide safe, reusable data for manual acceptance, browser smoke, import and
  monitoring scenarios, outbound-click reporting and future Order/payment testing. This is
  not a substitute for unit-test factories.
- **Scope:** representative candidate, employer, editor, admin and super-admin Users;
  multiple Companies; manual and imported Vacancies across draft/pending/published/archived/
  expired/filled states; internal/e-mail/external application modes; saves and internal
  Applications; ImportSources; import history with missing/reappearing cases; outbound-click
  history; reporting rows; and Orders once SMV-110 exists.
- **Safety:** disposable or clearly non-production records only. Never include production
  credentials, real partner secrets or copied personal data.
- **Dependencies:** stable factories/seeders and the Phase G staging acceptance plan.
- **Acceptance:** `AcceptanceDemoSeeder` is explicitly invoked, refuses production, uses stable
  `.test` accounts and `.invalid` fixture endpoints, reruns without multiplying deterministic
  records, and is documented in `docs/ACCEPTANCE_DATASET.md`. It supports named browser
  scenarios, import monitoring/recovery checks and the SMV-073 dry run without modifying
  production data. Future Order/payment fixtures remain SMV-110/111 scope.

#### SMV-113 — Fix Filament Vacancy application destination reactivity

- **Status:** DONE
- **Goal:** make the Filament Vacancy create/edit form react immediately to application-mode
  changes.
- **Verified cause:** edit hydration supplies the enum-cast `ApplicationMode`, while the form's
  visibility and required closures only matched raw string values.
- **Acceptance:** internal mode exposes only internal behavior; external mode exposes and
  validates an external URL; e-mail mode exposes and validates an e-mail destination; new and
  existing forms update immediately; inactive destination fields do not validate stale values.
- **Result:** create/edit transitions are covered by focused Filament tests; the existing public
  placement and application destinations remain unchanged.
- **Out of scope:** changing the public placement flow or application-domain semantics.

#### SMV-072 — Critical-path end-to-end smoke suite

- **Status:** PARTIALLY DONE
- **Why now:** feature tests are broad, but release confidence also needs browser-level route and interaction coverage.
- **Scope:** homepage search, Vacancy filtering/detail/application, Company/Blog navigation, admin login and one import preview/run/history happy path.
- **Dependencies:** SMV-062 and SMV-075 preferably complete; SMV-109 supplies the reusable
  roles, records and lifecycle states for deterministic browser scenarios.
- **Implemented:** SMV-072B provides the repeatable local Playwright/Chromium suite described
  in `BROWSER_SMOKE.md`, with guarded SQLite reset, deterministic fixtures, failure traces and
  an optional non-mutating staging command.
- **Acceptance remaining:** retain a clean full browser/Pest run, then complete manual shared-
  staging, screen-reader/zoom/real-device and production browser-matrix evidence. Real Stripe
  test-mode, destructive staff actions and live imports remain separate manual checks.

#### SMV-073 — Production-like migration dry run

- **Status:** NOT STARTED
- **Why now:** verifies migrations, private/public storage, seed policy and import runtime before cutover.
- **Scope:** disposable production-like environment, backup/restore rehearsal, migrations, builds, representative feed run and rollback/incident notes.
- **Dependencies:** hosting/staging details, SMV-044/045, approved content and the SMV-109
  production-like acceptance dataset.
- **Acceptance:** timed repeatable runbook with verified data/media/import integrity and named rollback owner.

#### SMV-074 — Production launch checklist

- **Status:** NOT STARTED
- **Why now:** final integration gate rather than another feature phase.
- **Scope:** environment, queues if adopted, mail, storage, HTTPS, robots/sitemap, redirects, analytics/Search Console, monitoring and post-launch checks.
- **Dependencies:** SMV-046, SMV-062/063, SMV-071–073 and SMV-109.
- **Acceptance:** signed checklist, production smoke pass, indexability verified and rollback/monitoring responsibilities assigned.

### I. Vacancy discovery extension

#### SMV-082 — Compensation data and normalization contract

- **Classification/status:** MVP — DONE.
- **Result:** the eight existing amount/currency/period fields remain canonical and nullable
  `salary_basis` adds `gross_fte`, `gross_offered_hours` and `unknown`. Filament and the
  employer flow reject new zero/negative/reversed manual ranges; imports normalize known
  currency/period/basis values and turn zero endpoints into null with a warning.
- **Comparability:** `Vacancy::withComparableMonthlySalary()` requires EUR + month +
  `gross_fte` and a valid positive one- or two-sided interval. The hourly-rate equivalent
  requires EUR + hour. These scopes deliberately do not imply public visibility.
- **Out of scope:** currency conversion, assumed hours, bonus/commission percentages,
  package entitlement and bulk guessing/backfill.
- **Existing data:** no basis is inferred or backfilled. Existing null/unknown, zero,
  unsupported-currency and unsupported-period rows remain stored and non-comparable until an
  editor or trusted source contract explicitly reviews them.
- **Acceptance:** ambiguous rows remain unknown; minimum/maximum ordering and enum values are
  validated at every write boundary; salary and rate can still coexist; imports preserve
  warnings instead of guessing.
- **Tests/verification:** fixed, one-sided, equal, reversed, negative and zero bounds;
  currency/period/basis mismatches; import warnings; manual and Filament persistence; public
  labels and JobPosting output.
- **Documentation:** `DATA_MODEL.md`, `IMPORTS.md`, `IMPORT_DESIGN.md`, `MVP.md` and this audit.
- **Next:** SMV-083 may add filtering by composing public visibility with these comparability
  scopes. No filter parameters or range matching were added here.

#### SMV-083 — Search/filter completion and salary and hourly-rate range filtering

- **Classification/status:** MVP — DONE.
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
- **Result:** keyword and place remain visible on both surfaces; secondary filters are grouped
  behind an accessible, state-preserving disclosure with a count and same-page reset. The
  shared query supports canonical employment, workplace, sector/function hierarchy,
  experience, qualification, Company and explicit EUR monthly-FTE/hourly-rate overlap.
  Invalid amount input receives Dutch feedback and is never partially interpreted.
- **Open decision resolved:** public copy explicitly says “Bruto maandsalaris (EUR, FTE)” and
  “Uurtarief (EUR)”; no conversion or inference is performed.
- **Effort:** medium — one shared query path, two responsive forms and boundary-heavy tests.

#### SMV-083A — Structured province data and filter

- **Classification/status:** post-MVP follow-up — DEFERRED.
- **Reason:** Vacancy currently stores a free-text place only. Deriving province from that
  string would be unreliable and would make shared URLs/data semantics unstable.
- **Scope when justified:** explicit nullable province value, controlled Dutch options,
  admin/import normalization, combined place/province filtering and migration coverage.
- **Out of scope:** geocoding, radius search and guessing province from arbitrary text.

#### SMV-114 — Benelux place reference library

- **Classification/status:** P1/P2 — PROPOSED normalization/reference work.
- **Goal:** provide canonical place names, aliases and country/region metadata for the
  Netherlands, Belgium and Luxembourg without guessing geography from free text.
- **In scope:** a reviewable reference layer that can later support autocomplete, import
  normalization and reliable province/region filters; define how it relates to SMV-083A.
- **Out of scope:** geocoding, radius search, external-provider selection and an immediate
  foreign-key replacement for `vacancies.location`.
- **Acceptance:** aliases resolve deterministically or remain unknown; country and region
  metadata are explicit; existing free-text Vacancy locations remain backward compatible.

#### SMV-115 — Vacancy cover image

- **Classification/status:** P1/P2 — PROPOSED media enhancement.
- **Goal:** support an optional Vacancy-specific image for campaigns, teams or locations while
  retaining Company imagery as the fallback.
- **In scope:** existing Media Library conventions, safe upload/replace/remove behavior and
  Vacancy-image precedence wherever the relevant public UI supports it. Decide whether employer
  editing or staff/admin upload is the first MVP surface.
- **Out of scope:** making an image mandatory, replacing Company media, or introducing a new
  media subsystem.
- **Acceptance:** a Vacancy without its own image renders normally; a valid Vacancy image takes
  precedence; replacement/removal cannot expose another User's media.

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

## Phase H — External feed operations and Joobs onboarding

SMV-083 is already assigned above to Vacancy compensation/search filtering and is not
reused. The initial SMV-090–099 entries below were documentation proposals only; these
updated proposals retain those IDs where possible and add SMV-100+. No application
changes are approved. Phase H is now substantially mature at the generic-platform level:
Joobs contract/mapping, source-scoped employer resolution, verified snapshots, bounded
scheduling, monitoring/recovery, per-source publication/missing policy, manual-copy workflow
and outbound-click reporting/24-month retention are implemented. Jos is the temporary import
operations owner; Dylan is not confirmed. Remaining work is partner
activation input, production operational acceptance, optional detail-view tracking,
cross-provider expansion and future commercial reporting/ranking. Phase G release gates and
the MVP Order/payment work remain primary and must not be obscured by feed expansion.

| Suggested ticket | Priority/status | Goal and acceptance boundary | Demo blocker |
| --- | --- | --- | --- |
| SMV-090 Demo scope and safe acceptance preparation | P0 — DONE (documentation/audit) | Demo flow, isolated upload-fixture boundary, manual acceptance, staging prerequisites and blockers are documented in `IMPORT_MVP_PLAN.md`; no live feeds or removal sync. Owner scope/content review remains. | No; SMV-091 is the staging readiness gate. |
| SMV-091 Staging acceptance and smoke | P0 — PARTIALLY DONE / EVIDENCE REQUIRED | The staging demo at `vacatures.marketing` has been published and viewed by stakeholders. Viewing is not formal acceptance: retain the public/admin, SEO/noindex, responsive/accessibility, runtime, privacy and defect-evidence checklist in `IMPORT_MVP_PLAN.md`. | Production readiness gate, not a completed demo ticket. |
| SMV-092 Joobs mapping contract | P1 — DONE (design contract) | Field mapping, source-scoped advertisement identity, employer-resolution boundary, advertised/workplace location behavior, raw salary/date policy and fail-closed synchronization rules are finalized in `JOOBS_FEED_MAPPING.md`. Partner employer/category/product/timezone guarantees remain explicit activation inputs. | No. |
| SMV-093 Verified snapshot lifecycle | P1 — DONE | Source opt-in requires a complete preflighted snapshot: unique references, valid mappings/resolution and non-empty input unless explicitly allowed. Per-source database/cache lock rejects overlap. Unsafe/failed/partial runs retain existing missing state; verified absence updates `missing_since` only, without deletion or status overwrite. | No. |
| SMV-094 Joobs mapping and fixture acceptance | P1 — DONE (fixture persistence contract) | SMV-094A proves the XML contract without persistence. SMV-094B runs the two-city fixture through configured contract validation and the generic mapper, employer resolver, Vacancy upsert and snapshot lifecycle; identity, reruns, metadata, sanitation, URLs, raw salary, dates, location/SEO and unsafe paths are tested. Taxonomy/value lists, employer-ID guarantee and timezone approval remain activation inputs. | No; partner/operations gates remain. |
| SMV-095A Generic scheduled and bounded import execution | P1 — DONE (infrastructure) | Disabled-by-default hourly/6-hourly/daily source schedules dispatch unique database-queue jobs. Jobs use the generic runner, two attempts, 10-minute timeout, existing 15-minute source lock and 16-minute queue retry window. Laravel schedules a bounded `--stop-when-empty` worker; approved active remote sources with an active default mapping are eligible. No partner feed is activated. | No; operations verification and partner activation remain. |
| SMV-096 Expiration status maintenance | P2 — NEEDS REVIEW | Public expiry is query-driven; add scheduled status mutation only if admin workflow requires it. | No. |
| SMV-097 Basic vacancy views and outbound click reporting | P1 — DONE | Successful rendered public Vacancy-detail GET responses create minimized anonymous raw views; HEAD, preview, non-public and failed responses do not. Existing outbound tracking now shares staff filters, aggregate reporting and CSV with raw views plus **raw outbound-apply CTR = raw outbound clicks / raw detail views** (undefined when views are zero). Both event types have bounded daily 24-month pruning. No fingerprint, cookie, IP or identity is stored. | Verify production schedules and representative browser inclusion/exclusion behavior during staging acceptance. |
| SMV-098 Listing impression analytics | P2 — DEFERRED | Define meaningful card visibility, bot/dedupe rules and CTR only after SMV-097. Do not call detail views impressions. | No. |
| SMV-099 Partner/commercial reporting | P2 — DEFERRED | Partner-confirmed clicks/conversions and product reconciliation only after terms/data access; never invoice from internal clicks alone. | No. |
| SMV-100 Multi-employer Company mapping | P1 — DONE | Added reviewed source-scoped employer mappings, deterministic provider-ID/composite resolution, legacy single-Company behavior for sources not configured for per-record resolution, nullable provider-neutral Vacancy metadata, distinct advertised/workplace presentation and JobPosting fallback. Unresolved rows cannot drive missing detection. | No; partner identity approval remains a live-feed gate. |
| SMV-101 Import monitoring/recovery | P1 — DONE | Existing Import runs now derive source health, overdue state, two-run repeated failures, safe snapshot outcomes and substantial verified-count drops. Filament shows source/run operations data and offers an administrator-only fresh manual run through the normal locked importer. Manual/scheduled provenance and an operations runbook are included; email alerts remain disabled until a dedicated recipient/owner is configured. | No; production cron/queue ownership remains an operations gate. |
| SMV-102 Targeted search relevance | P2 — DEFERRED | Improve only against measured queries and curated typed taxonomy; no arbitrary boosts. | No. |
| SMV-103 Cross-provider expansion | P2 — DEFERRED | VONQ/Brockmeyer/Appcast/Adver/8vance from verified specs; extend existing VNOM only for confirmed operational/commercial gaps. | No. |
| SMV-104 Launch content decision pass | P0 — PROPOSED | Approve homepage proposition, geography/audience, navigation label, About history and contact details from `CONTENT_STATUS.md`; direct-channel package names/prices are settled by SMV-050. | Yes for accurate launch copy. |
| SMV-105 Employer/package content integration | P0 — DONE (package scope) | SMV-050 renders the approved configurable Standaard/Superior/Maatwerk catalogue and manual fulfilment wording in existing pages/components without implying checkout or automation. Broader page-copy approval remains SMV-104. | No package-code blocker; sales operations must deliver the published manual benefits. |
| SMV-106 Brand assets and claim evidence | P1 — PROPOSED | Receive approved imagery/logo provenance and evidence dates for history/reach/client claims; integrate only licensed, approved assets. | Conditional; current cautious copy/assets can launch if explicitly accepted. |
| SMV-107 Per-source publication and missing-vacancy policy | P1 — DONE | Every ImportSource defaults new advertisements to staff review and missing advertisements to review-only. Trusted sources may explicitly auto-publish new records; verified complete snapshots may explicitly archive missing records without deletion. Source-driven archives are distinguishable for safe reappearance, while manual suppression and unrelated operational fields remain intact. Joobs can be configured `auto_offline` without provider-specific lifecycle code. | No; production source configuration and remaining partner inputs still require approval. |
| SMV-108 Duplicate imported Vacancy as manual SMV Vacancy | P1 — DONE | Authorized Vacancy staff can create a separate manual draft from an imported Vacancy. Editorial content, the existing Company, taxonomies, tags and only a safe current application destination are copied; import identity, provider metadata, snapshot/publication state, featured/filled state and commercial placement intent are cleared. The original remains feed-owned and later updates/reconciliation cannot mutate the manual copy. | No; this does not activate Joobs or change ranking, packages or source policies. |

These proposals and estimates are detailed in `IMPORT_MVP_PLAN.md`. Repository evidence
is in `IMPORT_ARCHITECTURE_AUDIT.md`; the verified field matrix is in
`JOOBS_FEED_MAPPING.md`; commercial status is in `PARTNER_INVENTORY.md`. SMV-092's design
contract is complete; remaining partner confirmations gate live activation, not the
SMV-100 architecture. Dylan's newly supplied working guide is
assessed in `CONTENT_STATUS.md`; it is not approved copy.

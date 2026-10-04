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
| SMV-079 Saved Vacancies | **NOT STARTED** | Candidate/user save/unsave persistence and an account list are deliberately deferred. |
| SMV-080 Candidate application account history | **NOT STARTED** | A future account view may expose only internally observable SMV applications; external/e-mail applications cannot be tracked. |
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
| SMV-077 Company discovery and filtering | **NOT STARTED** | Company search/filtering is deliberately separate from SMV-063. |

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
  Order/payment/provider handling. E-mail notifications, saved Vacancies and candidate
  application history are not represented as complete.

#### SMV-079 — Saved Vacancies

- **Status:** NOT STARTED
- **Scope:** authenticated User-owned save/unsave persistence, one unique save per User
  and Vacancy, a saved list under `/account`, and real saved state on public cards/detail.
- **Lifecycle:** define what remains visible when a Vacancy expires, is archived or is
  removed; do not restore a decorative bookmark until the action works end to end.

#### SMV-080 — Candidate application account history

- **Status:** NOT STARTED
- **Scope:** relate authenticated Users to internal `Application` records where safely
  available and show a private `Mijn sollicitaties` account view.
- **Boundary:** SMV cannot promise status tracking for external links or e-mail
  applications that never enter the internal Application domain.

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

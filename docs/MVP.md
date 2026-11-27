# SMV MVP

## Product goal

Build a stable, commercially credible first version of the new Sales en Marketing Vacatures platform in Laravel.

The MVP is the foundation of a recruitment platform. It must already support the core job-board business flow, company presence, reliable vacancy ingestion, strong search-engine migration fundamentals and enough content capability to support sales/demo use.

Current repository reality (audited 6 October 2026): the recruitment core, Company and
Vacancy administration, the internal/external/e-mail application flow, the generic
JSON/XML/CSV/XLSX import pipeline, the technical SEO foundation and the native Blog are
implemented and covered by feature tests. The current public frontend also includes the
canonical homepage, vacancy discovery, searchable single-category Company discovery,
Company detail pages, Blog archives and the
static Dutch pages `/adverteren`, `/over-ons`, `/tarieven` and `/contact`. The Contact
page includes a validated, spam-protected, e-mail-only general enquiry form.

The main remaining MVP/release work is now release validation plus the final commercial
reversal boundary on top of the completed payment and entitlement foundation. It is:

- use the completed SMV-050B ranking layer with the completed SMV-116 paid entitlement source;
- operate the completed Stripe Checkout/webhook integration against immutable Order snapshots
  and idempotent Company placement-credit fulfilment;
- operate the implemented SMV-117 cancellation/refund workflow and complete SMV-097A public
  Vacancy-detail view reporting;
- complete final stakeholder approval of public copy and template imagery;
- inventory and map valuable legacy URLs once exports are available;
- complete accessibility, end-to-end, migration and launch validation.

General Contact submissions use Laravel Mail with a configurable recipient, retain no
database record and require no account. Direct employer Vacancy advertising is a
separate staged flow (SMV-078), rather than an expansion of the Contact form. Guests may
select Standaard or Superior, authenticate through the normal `web` guard, create a
Vacancy draft for an owned Company, preview it and hand it off in `pending` state. The
selected package remains intent only until an eligible verified payment grants a Company credit;
publication is never implied.
SMV-110 provides immutable Order product/price/tax snapshots and SMV-111 makes payment
outcomes testable through a provider-neutral fake gateway, which remains intact after Stripe
integration. SMV-112 adds hosted Stripe Checkout, verified webhooks and private customer Order
history. Order creation validates purchaser, Company and
optional Vacancy ownership and remains separate from Payment and publication.
The acceptance dataset includes the approved Package catalogue plus deterministic draft,
pending-payment and paid examples with clearly synthetic Fake-gateway attempts.
The authenticated `/account` foundation lets an employer complete presentation/contact
fields and Media Library branding for owned Companies and review owned Vacancies.
All authenticated Users can also save public Vacancies and Company profiles and revisit
them through the private account. Guests can begin either action before authentication.
The generic registration entry explicitly offers `Werkzoekende` and `Werkgever`, backed
by one User identity and one shared login. Work-seeker registration does not create
Company ownership; employer registration performs the pending-Company onboarding. These
are current intents rather than mutually exclusive product identities: employers retain
save/application features and an existing work seeker can later complete Company
onboarding and placement with the same account.
Submitted Vacancies stay read-only while awaiting moderation. Publishing in Filament
now supports publish-now by leaving the date empty and scheduling by choosing a future
date; this does not grant employers publication rights. Employer and Filament Vacancy
descriptions and the long-form Company description now support a deliberately limited
rich-text set through one reusable editor UI. Domain-aware server-side boundaries
protect Company and Vacancy writes, Vacancy imports, preview/public rendering and
plain-text SEO output; arbitrary HTML and embedded media remain outside the MVP.

## Presentation target

A strong demonstrable version is desired around the beginning of September 2026 for a sales presentation.

The presentation build should visibly demonstrate:

1. A modern public frontend.
2. Company pages with active vacancies.
3. Vacancy discovery/search/filtering.
4. Vacancy detail and application/contact flow.
5. Admin management in Filament.
6. A credible vacancy import workflow including source configuration, mapping and normalized preview.
7. Import support that visibly handles practical business formats, including XML/JSON feeds and manual CSV/XLSX uploads.
8. Basic content/blog presence if the recruitment core and imports are stable.

The presentation target must not encourage fake or brittle implementations that would need to be discarded immediately afterward.

## MVP scope

### Recruitment core

- Users
- Companies
- Vacancies
- Applications or external application handling
- Categories / relevant taxonomies
- Packages
- Orders / payments where required by the existing business flow
- Vacancy imports
- API/feed integration foundation

### Company pages

Company pages are part of the MVP and should support the current data model where available:

- name
- slug
- logo
- cover / visual
- description / introduction
- tagline if available
- location if available
- website
- relevant social links
- optional video if supported by existing data
- active vacancies

Company is a first-class domain entity and should not be treated as just a text field on a vacancy.

The public Company index now combines free-text profile search with exactly one typed
Company category in shareable GET state. Cards expose only public Vacancy counts, use
Media Library-first contained logos with a letter fallback and retain the private saved
Company interaction. The homepage introduces real public employers in a bounded logo
strip; dedicated Company category archives remain a later decision.

### Public vacancies

Required public flow:

- vacancy listing/discovery
- practical search and filters
- pagination
- vacancy detail
- company relation
- active/expired state handling
- featured handling if part of current commercial logic
- application/contact action
- related/internal links where useful

Public Vacancy routes now form part of the implemented recruitment core. SEO/canonical policy for filtered/expired URLs remains part of the later SEO phase.

Compensation storage supports salary and freelance rate simultaneously. Comparable monthly
salary is explicitly EUR, monthly and gross fulltime-equivalent; comparable freelance rate is
explicitly EUR and hourly. Positive fixed, minimum-only, maximum-only and ranged values are
supported. Existing ambiguous values remain visible but are not silently reclassified.
SMV-083 implements the public amount-filter controls and matching logic through the shared
homepage/Vacancy search. Monthly EUR gross-FTE salary and EUR hourly rate remain explicit
separate modes and use inclusive interval overlap without conversion.

### Applications

The MVP supports three canonical application destination modes:

- internal application stored by SMV
- application by email
- external application URL

Authenticated Users can view their own securely linked internal Applications at
`/account/sollicitaties`. The page exposes only the submission date, public Vacancy
context where still available, and a deliberately mapped candidate-facing status.
Guest or historical e-mail-only Applications are not claimed by e-mail matching.
External destinations create a minimized raw outbound-click event through a controlled
redirect, but never create fake Applications. E-mail destinations remain untracked.
A raw outbound click is not a completed application, unique candidate, partner-confirmed
click or billable CPC event.
A full ATS workflow, candidate notifications, messaging and timelines remain outside
the current MVP.

Administrators and super-administrators can review raw outbound apply click aggregates
under Filament reporting. The report defaults to the latest 30 UTC days, filters by date,
Vacancy, Company and ImportSource, and exports the same filtered per-Vacancy aggregates
as CSV. Editors and non-staff users cannot access this commercial report. It does not
provide conversion, unique-human, partner-confirmed or billing metrics.

These privacy-minimal raw events are retained for at most 24 months to support bounded
operational and year-over-year commercial reporting. A daily UTC scheduled command prunes
events strictly older than that boundary in batches and logs its cutoff and count. Reports
for older periods return only available data, normally zero; SMV does not create or infer
longer-term historical aggregates. The event still contains no user ID, IP address,
user-agent, cookie, fingerprint or application content.

SMV-097A retains this privacy boundary for successful public Vacancy-detail responses.
Preview, non-public and irrelevant internal responses do not count. Each accepted response
is a raw view, not a unique visitor; SMV adds no cookie, fingerprint or IP tracking merely to
deduplicate analytics. Raw view events use the same documented 24-month maximum as raw
outbound clicks. Staff reporting will show views and **raw outbound-apply CTR**, defined as
raw outbound clicks divided by raw detail views for the same filters and period; when the
view denominator is zero, CTR is unavailable rather than zero or fabricated. Browser-level
acceptance must confirm the inclusion and exclusion boundaries.

### Commercial Orders and payments

The configurable Package catalogue supplies public presentation and immutable checkout inputs.
Standaard is EUR 189 excluding VAT for one 60-day placement. Superior is EUR 398 excluding
VAT for one placement with an admin-configurable duration (initially 60 days), additional
manual promotion/visibility and personal editorial guidance. Maatwerk starts at EUR 950
excluding VAT but is quote-only and opens the existing Contact flow. Approved 2026 prices
expire after 2026-12-31 in production unless staff enter a newly approved period.

Public descriptions and benefit bullets are editable independently from structured duration,
placement quantity, checkout eligibility, VAT and price-validity fields. Superior fulfilment
notes are visible to staff. Public copy may describe additional promotion, advertising,
guidance and increased visibility, but operational channel budgets are private and must not
be published. No HubSpot, advertisement-channel integration or budget accounting is part of
the MVP commercial path.
The configured supported MVP VAT rate is 21%. Package-level VAT is snapshotted into Orders,
and tax-incomplete products cannot enter checkout; unsupported tax situations require an
explicit later decision.

The implementation remains provider-neutral: SMV-110 defines an auditable
Order/OrderItem boundary with immutable product, price, currency, VAT and total snapshots;
SMV-111 defines the gateway contract and fake pending/success/failure/cancelled,
duplicate-callback and retry/idempotency scenarios. SMV-112 integrates hosted Stripe Checkout
and signed, idempotent webhook reconciliation without replacing the Fake used by automated
tests. The browser return URL never confirms payment; only a verified provider event can do so.
Historical Orders never recalculate their purchased price from mutable current Package rows.

`vacancies.placement_package` remains intent only. Selecting a Package or creating an Order
does not publish a Vacancy or grant placement entitlement. An eligible authoritative paid
transition grants Company-owned placement credits exactly once from immutable OrderItem
snapshots. Each credit retains its purchased package benefits; Company-scoped allocation and
redemption are atomic and idempotent. Allocation never bypasses review, and duration starts only
when the Vacancy and Company become publicly eligible. Future five-for-four products
may grant multiple separately consumed credits and later subscriptions may issue recurring
grants, without turning the model into a generalized wallet or implementing those products now.

SMV-050B now derives an effective commercial score only after public visibility and
search/filter eligibility. Its bounded integer components are configured package priority
(Standaard 0 and Superior +20 by default), Company priority and a Vacancy adjustment that may
be negative. The package component comes only from a valid active paid placement credit; package
intent, an Order or Payment state alone still returns zero.
The default general homepage/listing result order uses effective priority followed by stable
date/ID tie-breakers. Any valid user-selected sort retains its existing order. Current free-text
search has boolean matching rather than weighted relevance, so priority only reorders eligible
matches. Featured presentation and lifecycle dates remain separate.

SMV-117 adds staff-only unpaid cancellation and complete paid refunds. Cancellation expires an
open unpaid Stripe Checkout Session where possible and never suppresses a later authoritative
payment. Refund requests use the successful PaymentIntent, immutable total including VAT and a
durable idempotency key. Verified refund events reconcile the same audit record. Unused credits
are revoked; allocated or active credits are frozen for explicit review and stop contributing
package ranking without changing Vacancy publication or manual Company/Vacancy priority.
Customer Order history shows useful financial status without exposing the internal reason.
Partial refunds, customer self-service, chargebacks, credit notes and accounting automation
remain deferred.

SMV-118 is outside the commercial implementation sequence and reserved for the consolidated
UI/UX round. It may add an optional scheduled full-width banner presentation to an existing
Vacancy, but that placement is separate from ranking, `is_featured` and Superior entitlement.

### Production-like acceptance data

SMV-109 provides a disposable, repeatable dataset for manual acceptance and browser smoke:
representative roles, Companies, manual/imported Vacancy lifecycle states, application modes,
saves, Applications, ImportSources, import history, missing/reappearing records, outbound
clicks and reporting rows. Run it explicitly with
`php artisan db:seed --class=Database\Seeders\AcceptanceDemoSeeder` against a local,
testing or isolated staging database. It is separate from factories, refuses production and
contains no production credentials, partner secrets or copied personal data. See
`docs/ACCEPTANCE_DATASET.md` for the fixture table and scenario guide. The same dataset
supports SMV-072 and SMV-073; later Order/payment fixtures belong to SMV-110/111.

Current Vacancy and Company save totals and rankings are available only to Filament
staff through aggregate pivot queries. Public save counts, individual saver identities,
historical save events and behavioural profiles are outside scope.

### Vacancy imports

See `IMPORTS.md`.

This is a critical MVP module and must include a decent Filament/admin mapping interface, not only backend parsers.

First-class MVP formats:

- JSON
- XML
- CSV
- XLSX

The architecture must separate source transport from source format and support reusable mappings across repeated feed runs.

Publication and missing-advertisement behavior are configured per ImportSource. New and
untrusted sources default to staff review: new imported Vacancies remain pending and
verified missing records retain their current status with a visible `missing_since` signal.
An explicitly trusted source may auto-publish new valid records. A source may separately
archive published advertisements absent from a verified complete snapshot; it never deletes
them. Reappearance preserves identity and manual suppression. Imported mapped fields remain
feed-owned and may be refreshed; permanent SMV editorial ownership requires a separate
manual Vacancy. Authorized staff can now create that separate Vacancy through **Maak
handmatige kopie**. The copy starts as a manual draft, retains deliberately selected
editorial content and relations, carries no import identity or snapshot lifecycle state,
and is reviewed through the normal manual Vacancy workflow before publication.

The MVP must be capable of demonstrating:

- remote feed or uploaded source configuration
- record discovery/selection
- field mapping
- normalization
- Company/taxonomy resolution
- preview before import
- validation/failure feedback
- create/update behavior without duplicate imported vacancies

### Blog

A simple Blog is part of MVP scope and is implemented as a native Laravel/Filament
module. It includes public index/detail pages, typed Blog categories and tags, archives,
Media Library cover images, manual relations to public Vacancies and Companies, shared
SEO/JSON-LD and sitemap integration.

Reason:

- quick to administer with Filament
- useful for September presentation content
- useful for ongoing SEO/content strategy
- can help create internal links to companies/vacancies

Eight supplied stakeholder articles are available as idempotent native Blog seed
content. This is not a general WordPress migration. Comments, newsletters, author pages
and automatic related-content matching remain outside the MVP.

### CMS/pages

Basic static content/page management may be retained or added if already present or essential to replacing the legacy site. Do not build a large generic CMS before the recruitment core is stable.

## Language

The public SMV website is Dutch.

Public navigation, forms, validation-facing labels, vacancy UI, company UI, blog UI and SEO-facing content should use Dutch copy.

Internal code identifiers, database names and developer documentation may remain English.

## Out of scope for MVP unless already working

- AI matching
- skill-based matching
- talent pools
- candidate CV database
- advanced candidate dashboard
- complex marketing automation
- campaign management
- advanced employer branding blocks
- career stories module beyond ordinary blog/content capability
- comments
- complex editorial workflow
- advanced analytics dashboards
- multi-channel social distribution
- advanced editor/page builder
- newsletter automation
- universal ETL/import platform
- generic standalone import SaaS

These can be later phases.

## MVP success criteria

The MVP is ready for controlled launch when:

- critical public flows work end to end
- admin can manage the core recruitment data
- import sources can be configured
- import mappings can be configured and reused
- JSON/XML/CSV/XLSX sources can enter the same normalized import pipeline
- imports can be previewed and failures understood
- recurring imports can update known vacancies without creating duplicates
- existing important URLs have a migration decision
- redirects/canonicals/indexability are tested
- required structured data is present and valid
- staging/production deployment is repeatable
- a documented production-like acceptance dataset supports repeatable browser/import/
  monitoring scenarios without production credentials
- the employer commercial flow has an auditable Order boundary and provider-neutral payment
  tests, or an explicitly approved launch waiver
- there are no known critical authorization or data-loss issues
- core behavior has automated test coverage appropriate to risk

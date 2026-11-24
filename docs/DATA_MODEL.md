# SMV Data Model

## Purpose

Document the confirmed SMV-001 foundation, intended domain rules and known legacy
WordPress fields. Import mapping structures are now documented in `IMPORTS.md`; future
changes still require real source evidence and an explicit ticket.

## Primary domains

The repository currently has a broad model/schema foundation for:

- users
- companies
- vacancies
- applications
- categories / taxonomies
- packages
- orders / payments
- imports
- blog posts / blog categories

## Known legacy vacancy fields

Legacy WordPress metadata previously identified includes:

```text
_job_title
_job_description
_job_location
_job_reference
_job_deadline
_job_expires
_job_listing_featured
_job_cover_image
_application
_apply_link
_company_select
_salary_min
_salary_max
_rate_min
_rate_max
_filled
_featured
```

Potential normalized Laravel concepts include:

```text
_job_location       -> vacancies.location
_salary_min         -> vacancies.salary_min
_salary_max         -> vacancies.salary_max
_job_expires        -> vacancies.expires_at
_job_reference      -> vacancies.source_reference/reference, depending on provenance
_apply_link         -> application destination / URL
_company_select     -> company relationship
```

These mappings are guidance, not authorization to rename existing working columns without repository inspection.

The legacy `_job_cover_image` value is not yet a Vacancy-specific media contract. Company
logo/cover media and Blog featured media use the existing Media Library conventions; SMV-115
may add an optional Vacancy cover collection with Vacancy-first/Company-fallback presentation
without making an image required.

## Known legacy company fields

```text
_company_name
_company_description
_company_email
_company_phone
_company_website
_company_logo
_company_location
_company_tagline
_company_facebook
_company_linkedin
_company_instagram
_company_twitter
_company_video
_company_since
```

## Known legacy application fields

```text
_candidate_name
_candidate_email
_candidate_location
_candidate_title
_candidate_photo
_candidate_linkedin
_candidate_experience
_candidate_education
_candidate_languages
_applying_for_job_id
```

Because candidate/application data is personal data, migration/import/logging decisions must minimize unnecessary exposure.

## Company relationship

Company is a first-class entity. A Vacancy belongs to a Company rather than using a
duplicated employer string as its authoritative owner.

Conceptually:

```text
Company
├── Vacancies
├── Blog/content links (optional MVP relation)
├── Employer branding (later expansion)
└── Commercial/package relations as needed
```

Public Company discovery does not add persistence. It reuses `Category` through
`categoryables`, restricted to `CategoryType::company_category`, and applies one category
slug at a time. Public Company counts use `Company::publiclyVisible()` and card Vacancy
counts use `Vacancy::publiclyVisible()`. The categoryable unique constraint prevents one
Company/category relation from inflating aggregate counts. Company descriptions remain
sanitized rich text in storage and are converted to plain text before card truncation.

## Structured taxonomies and tags

`Category` remains the controlled polymorphic taxonomy model. Vacancy-facing category
types are `employment_type` (Dienstverband), `workplace` (Werklocatie), `sector`,
`function_area` (Functiegebied), `experience` (Ervaring) and `qualification`
(Opleidingsniveau). Categories have stable,
type-scoped slugs and may have a same-type parent; Sector uses this for its practical
one-level hierarchy. Legacy `vacancy_category`, `job_type` and `career_level` values,
plus existing Company and Blog category types, remain readable compatibility taxonomies.
Qualification uses the existing polymorphic Category storage: no assignment means unknown,
while “Geen specifieke opleiding vereist” is an explicit value.
Employment type adds the canonical Loondienst/Freelance/Stage choices without rewriting or
detaching existing Fulltime/Parttime assignments; those remain available as compatibility
values until a deliberate hours/employment taxonomy migration is approved.

Flexible descriptive Vacancy tags use Spatie Laravel Tags, not `Category`. Tags such
as AI, CRM, SaaS and B2B are free-form; filterable employment, workplace, sector,
function-area and experience values must remain structured Categories. Future imports
may map source values to Category IDs/slugs by type and may separately attach Tags.

## Vacancy import provenance

Imported vacancy provenance uses `ImportSource`. `import_source_id` is the authoritative
provider relationship and `source_reference` is the provider's stable vacancy identifier
where available. Imported vacancy uniqueness is provider-scoped through:

```text
import_source_id + source_reference
```

Manual vacancies may have both fields null. The physical `imports.source` column remains
only for legacy compatibility and must not become provider identity in new import code.

An imported Vacancy is never converted or detached in place. The staff manual-copy action
creates a new row with a new primary key and generated slug, `source=manual`, `status=draft`
and null source identity/snapshot fields. It reuses the existing Company and copies approved
editorial fields plus Category and Tag relations. Provider requisition/product/language/raw
salary metadata, seen/missing/source-offline state, publication timing, featured/filled
state and placement-package intent are not inherited. No source-to-copy foreign key was
added: the current system has no general Vacancy audit/provenance convention, and import
ownership must not be implied by a lightweight audit field.

Vacancy slugs are stable after creation and do not regenerate merely because a title is
changed.

## Vacancy compensation contract

Salary and freelance rate remain independent and may coexist on a Vacancy. The canonical
fields are `salary_min`, `salary_max`, `salary_currency`, `salary_period`, `salary_basis`,
`rate_min`, `rate_max`, `rate_currency` and `rate_period`. Amounts use the established whole-
currency-unit integer precision; a missing endpoint is null and is never treated as zero.

`salary_basis` is nullable for backward compatibility and accepts `gross_fte`,
`gross_offered_hours` or `unknown`. Null and `unknown` are both non-comparable. Existing rows
were not inferred or backfilled. They can later be reviewed explicitly in Filament or through
a documented source mapping.

Comparable monthly salary means explicit EUR + `month` + `gross_fte` and a valid positive
one- or two-sided range. Comparable hourly rate means explicit EUR + `hour` and the same range
rules. Zero, negative, reversed, missing or unsupported metadata is non-comparable. These
rules are available through Vacancy helpers/scopes and remain separate from
`publiclyVisible()`. SMV-083 composes those scopes with inclusive interval overlap in the
shared homepage/listing query. There is no currency,
assumed-hours or FTE conversion in this contract.

## Orders and payments

The repository contains historical `Package`, `Order`, `OrderItem` and `Payment` models and
tables, but the employer placement flow currently stores only `Vacancy::placement_package`
intent. It does not yet create an Order, Payment or entitlement, so these models are a
foundation rather than a completed commercial workflow.

SMV-110 establishes the provider-independent Order boundary. `CreateOrder` validates that the
purchaser owns the Company and that any linked Vacancy belongs to that Company, then creates
an Order and one or more OrderItems atomically. OrderItems retain the purchased product code,
label, quantity, unit price, subtotal, currency, VAT rate/amount and total snapshots; the
Order retains aggregate money snapshots and a stable `SMV-*` reference. The current approved
catalog is the `AdvertisingPackage` enum (the historical `packages` table is also supported
when an active row is explicitly supplied). `placement_package` remains intent only and is
linked through an OrderItem; creating an Order does not grant entitlement or publication.
VAT is an explicit basis-point input and defaults to unresolved/no tax until commercial rules
are approved. SMV-111 defines provider-neutral payment state and fake pending/success/failure/cancelled/
duplicate-callback/retry scenarios without network access. Payment is a separate attempt
record: an Order can have multiple failed/cancelled attempts, while only one successful
attempt transitions the Order to `paid`. `idempotency_key` and `provider_reference` are
unique attempt/provider boundaries; amount and currency always come from the Order snapshot.
The historical `provider`/`provider_payment_id` and `raw_payload` fields remain readable for
legacy records, but the old `mollie` schema default is not provider selection. SMV-112 is the
confirmed Stripe integration. The current migration's historical
`payments.payment_provider` default of `mollie` is legacy schema state, not a live provider
configuration; Stripe implementation must introduce explicit provider configuration rather
than activating that default.

## Benelux place reference

Vacancy `location` remains free text. SMV-114 may introduce a normalized Netherlands,
Belgium and Luxembourg place reference with aliases and country/region metadata for import
normalization and future autocomplete. It must initially remain a reference/normalization
layer rather than an immediate foreign-key replacement. SMV-083A's structured province value
remains deferred until an explicit validated value is approved; no geocoding, radius search
or province guessing is part of this contract.

SMV-001 established soft deletion on important operational models where the schema
already supported it. Financial and historical records are protected from accidental
parent-deletion cascades. Historical read screens may later need to include soft-deleted
parents explicitly.

Mapping profiles, source transformations and company matching policy belong to SMV-030
and must be based on real feed examples rather than invented here.

## Blog

`BlogPost` is a native Laravel content model. Its MVP fields are title, stable unique
slug, nullable excerpt, content, legacy `featured_image` compatibility reference,
Media Library `featured` media, status, `published_at`, timestamps and `deleted_at`.
The existing required `author_id` remains for schema compatibility but is not shown on
public pages. `publiclyVisible()` requires a published, non-future post; soft-deleted
posts are excluded.

SMV-061 adds optional controlled content relations without a second taxonomy system:

- multiple `Category` records through the existing `categoryables` morph relation,
  restricted to `CategoryType::blog_category` in Blog administration and public queries;
- multiple Spatie Tags through `HasTags`, restricted to type `blog`;
- `blog_post_vacancy` and `blog_post_company` many-to-many pivots for manually selected
  related content.

The public Blog only renders related records when their existing public visibility
scope permits it. The relation itself is retained for editorial continuity. WordPress
blog content is deliberately not migrated or imported.

### Legacy planning reference

### posts

- id
- title
- slug
- excerpt
- content
- featured image/media reference if required
- status or publication state
- published_at
- timestamps
- SEO fields if no generic SEO mechanism exists

### blog categories

- id
- name
- slug

Exact field naming follows current repository conventions.

### Saved Vacancies

`users` and `vacancies` have a many-to-many relationship through `saved_vacancies`.
The composite unique constraint allows one save per User/Vacancy pair; both foreign
keys cascade only the pivot row when a parent is physically deleted. Vacancy soft
deletion therefore retains the save relationship. Saving is role-independent, while
new attachments are allowed only for Vacancies and Companies that are publicly visible.

`users` and `companies` use the equivalent `saved_companies` pivot with a unique
User/Company pair and the same parent-deletion behavior. This remains distinct from
Company ownership (`companies.user_id`) and from editorial Blog relations.

### User identity and registration intent

Public work seekers and employers share the same `users` table and Laravel `web` guard.
`RegistrationContext` is validated, transient onboarding intent (`job_seeker` or
`employer`), not a second stored identity model and not an authorization grant. The
existing `users.role`/Spatie roles retain initial classification where the current
architecture needs it, but product capabilities are not needlessly exclusive: saved
content and owned Applications work for any authenticated User. Employer management
continues to require actual Company ownership or permissions. A User may therefore gain
an employer role through later Company onboarding without creating a second User.

### Candidate Applications

`applications.candidate_id` is the nullable, indexed relationship to `users`. New
internal submissions made during an authenticated session set this value server-side.
Guest submissions retain a null value. Historical rows are not assigned by matching
`candidate_email`, because an e-mail string alone is not treated as ownership proof.

`Application::candidate()` and `User::applications()` provide the ownership boundary.
The Vacancy relationship includes soft-deleted rows for historical continuity, but the
candidate account independently checks current Vacancy and Company public visibility
before rendering their title, Company or public URL. Application status continues to
use `ApplicationStatus`; candidate labels are a presentation mapping on that enum and
do not create a second stored lifecycle.

Save statistics remain derived data. Current totals and rankings aggregate the unique
`saved_vacancies` and `saved_companies` rows in SQL and exclude soft-deleted content.
There is no save/unsave Activitylog stream in SMV-080.

### Outbound apply clicks

`outbound_apply_clicks` is the provider-neutral raw event foundation added by SMV-097B.
Each row contains only `vacancy_id` and `clicked_at` (plus its primary key). Company and
ImportSource attribution remain normalized through `Vacancy`; no personal or request
metadata is copied into the event. A physical Vacancy deletion cascades its click rows,
while Vacancy soft deletion preserves them for later authorized aggregate reporting.
The composite `(vacancy_id, clicked_at)` index supports per-Vacancy ranges and the
standalone `clicked_at` index supports report-wide UTC date ranges. SMV-097C does not add
counter or aggregate tables: Filament totals and aggregate CSV rows are derived from the
raw events and existing Vacancy relationships.

Raw outbound click rows have an approved maximum retention of 24 months for bounded
operational and year-over-year commercial reporting. A daily UTC command removes rows
whose `clicked_at` is strictly older than the run's 24-month cutoff, in bounded ID batches.
Rows exactly at the cutoff remain. No longer-term aggregate is created, so reporting beyond
the retained window returns available/zero raw-event results rather than synthetic history.

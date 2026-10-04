# SMV Architecture

## Status

The repository audit and foundation stabilization are complete. The application now has
working public recruitment flows, Filament administration, a format-independent import
pipeline, a native Blog and the technical SEO foundation. The remaining work is mostly
commercial-scope confirmation, content/brand approval, legacy URL migration and release
hardening.

## Guiding principles

- Laravel conventions first.
- Existing working code first.
- Company is a first-class domain entity.
- Imports are a reusable subsystem.
- Public frontend reuses the existing Tailwind design/component system.
- Filament is the primary admin interface.
- SEO requirements influence route/content decisions from the start.
- Avoid infrastructure or abstraction that the MVP does not need.

## Current foundation and required modules

```text
Users

Companies
├── public company page
├── vacancies
├── content relations where useful
└── package/commercial relations where current model requires them

Vacancies
├── public discovery
├── detail
├── taxonomies
├── company
├── application destination
├── owner-scoped public draft/preview/pending hand-off
└── import provenance

Applications

Contact
├── public GET form plus rate-limited POST endpoint
├── ContactRequest validation and honeypot normalization
├── configurable internal Laravel Mail delivery
└── no ContactMessage database persistence

Packages / Orders / Payments

Imports
├── upload, HTTP and API source configuration
├── JSON/XML/CSV/XLSX readers and bounded discovery
├── reusable mappings, transforms and normalized preview
├── validation plus source-scoped taxonomy resolution
├── provider-scoped Vacancy upsert
├── safe run history/logging and missing-record reporting
└── synchronous execution (queue/scheduling remain deferred)

Blog
├── posts and Media Library cover images
├── typed categories and tags
├── manual Vacancy/Company relations
├── public index/detail/archive routes
└── metadata, BlogPosting JSON-LD and sitemap integration

SEO / migration
├── metadata, Open Graph and environment-aware robots
├── JobPosting, Organization and BlogPosting structured data
├── dynamic sitemap
├── clean and paginated canonicals
└── legacy inventory/redirects (deferred pending exports)
```

## Request/application layering

Use normal Laravel request flow and keep business logic out of views and bloated controllers.

Example only:

```text
Route
  -> Controller
     -> FormRequest (where needed)
     -> Action/Service (where useful)
     -> Eloquent
```

Do not create Actions/Services purely to satisfy this diagram.

The general Contact flow follows `Route → ContactController → ContactRequest →
ContactRequestMail`. `CONTACT_MAIL_TO` selects the environment-specific internal
recipient; Laravel's normal mail configuration controls sender and transport. Local
development defaults to the `log` mailer. Production must configure its actual mailer,
sender and recipient. The named `contact` limiter and a honeypot provide lightweight
abuse protection without external services. Mail errors are reported through Laravel's
exception handler but never shown verbatim to visitors, and no submitted Contact record
is written to the database.

## Admin architecture

Filament should manage the operational data needed by the business, including at minimum the domains that exist in the final MVP.

Import mapping is explicitly an admin UX problem as well as a backend problem.

Current Filament panel access is limited to `super-admin`, `admin` and `editor`.
Employer and candidate roles do not have unrestricted panel access. Editor permissions
are conservative pending later editorial refinement. The public `/account` area is
separate from the Filament `/dashboard` panel.

## Public employer placement

The first SMV-078 slice uses the existing Laravel `web` guard rather than a second auth
system. Public `/inloggen` and `/registreren` routes serve the shared public layout;
Filament keeps its own panel login and remains restricted by `User::canAccessPanel()`.
Registration assigns the Spatie `employer` role and creates one pending Company.

Company ownership is the nullable `companies.user_id` foreign key. `User::companies()`
is a one-to-many relation, so a User can manage zero, one or multiple Companies. There
is no Company membership/team pivot. Vacancy draft authorization therefore follows the
owning Company and never trusts a submitted Company outside that User's relation.

The placement flow stores only bounded session state before persistence: an
`AdvertisingPackage` selection and Laravel's normal intended URL. Once submitted, the
Vacancy records `placement_package` as commercial intent, not as a paid entitlement.
New public employer Vacancies remain `draft` through data entry/preview and become
`pending` on hand-off. They are never published or featured by this flow. SMV-050 owns
the definitive package/entitlement domain and SMV-051 owns Orders and Payments.

SMV-078B adds a deliberately small public account foundation. Authenticated users enter
through `/account`; employers can update only presentation/contact fields on Companies
whose `user_id` they own and can list Vacancies belonging to those Companies. Status,
owner, slug, featured state, import provenance and publication remain protected. Logo
and cover replacement reuse the existing single-file Media Library collections.

Vacancy publication is normalized server-side when a record transitions from a
non-published status to `published`: a missing `published_at` becomes `now()`. An
explicit future timestamp remains scheduled and an existing published record retains
its original timestamp on unrelated edits. Public eligibility remains query-driven, so
scheduled records become visible without a scheduler after the timestamp passes.
`deadline_at` is the candidate application cutoff and `expires_at` is the listing's
active-until boundary. Under the current MVP lifecycle both elapsed boundaries remove a
Vacancy from public surfaces; their business meanings and administration labels remain
separate.

Vacancy and Company descriptions use explicit domain semantic-HTML boundaries.
`VacancyDescription` and `CompanyDescription` share the narrowly scoped
`LimitedRichText` sanitizer policy, while keeping persistence and rendering decisions
in their own domains. The policy permits only paragraphs, line breaks, `h2`/`h3`,
strong/emphasis, ordered/unordered lists and links with approved schemes. Both models
normalize changed descriptions before persistence; Vacancy imports use the Vacancy
boundary. Public detail pages sanitize again defensively for pre-existing database
rows without rewriting them. Metadata and structured-data descriptions derive plain
text from the corresponding domain service.

## Public frontend

Blade + Tailwind remains the preferred frontend stack.
Alpine.js is suitable for lightweight behavior.

Blade `x-*` components are a preferred implementation pattern. Existing Mosaic/Tailwind
components, markup and assets are the reusable design base. Current demo routes, route
names, layout wiring and prototype page architecture are not authoritative.
`x-app-layout` may be adapted where useful, but broken demo architecture need not be
preserved. Useful components and template markup should be composed into the production
public flow.

The public interface and SEO-facing copy are Dutch; internal identifiers and developer
documentation may remain English.

The production public surface uses `layouts.public`, `HomeController`, the dedicated
public controllers and Blade components under `components/ui`, `components/vacancy`,
`components/company`, `components/blog` and `components/home`. The older
`pages/component`, `pages/job`, `vacatures` prototype views and dashboard-style layout
are not routed. The retained catalogue is compileable through `<x-app-layout>` and
obsolete duplicates were removed in SMV-075, so `artisan view:cache` remains a release
validation command rather than a known blocker.

Do not add Vue/React/another design system without explicit approval.

## Background processing

Imports may become long-running. If current import size/hosting supports queues, isolate the execution so moving work to queued jobs is straightforward.

Do not require queue infrastructure for simple preview/parsing work when synchronous execution is safe and faster to ship.

## Deployment

Open decision: the Hostinger/staging deployment workflow still requires confirmation
from repository/server operations information that is not currently available.

The build must support a staging environment before production cutover.

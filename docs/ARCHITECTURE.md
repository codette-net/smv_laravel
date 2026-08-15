# SMV Architecture

## Status

This document defines architectural intent. Repository reality must be audited before treating any proposed class/table name as final.

## Guiding principles

- Laravel conventions first.
- Existing working code first.
- Company is a first-class domain entity.
- Imports are a reusable subsystem.
- Public frontend reuses the existing Tailwind design/component system.
- Filament is the primary admin interface.
- SEO requirements influence route/content decisions from the start.
- Avoid infrastructure or abstraction that the MVP does not need.

## Expected modules

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
└── import provenance

Applications

Packages / Orders / Payments

Imports
├── sources
├── mappings
├── runs
├── parsing
├── transformation
├── validation
└── persistence/reporting

Blog
├── posts
├── categories
├── SEO
└── optional links to recruitment content

SEO / migration
├── metadata
├── structured data
├── sitemap
├── canonicals
└── redirects
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

## Admin architecture

Filament should manage the operational data needed by the business, including at minimum the domains that exist in the final MVP.

Import mapping is explicitly an admin UX problem as well as a backend problem.

## Public frontend

Blade + Tailwind remains the preferred frontend stack.
Alpine.js is suitable for lightweight behavior.

Do not add Vue/React/another design system without explicit approval.

## Background processing

Imports may become long-running. If current import size/hosting supports queues, isolate the execution so moving work to queued jobs is straightforward.

Do not require queue infrastructure for simple preview/parsing work when synchronous execution is safe and faster to ship.

## Deployment

TODO: verify current Hostinger/staging deployment workflow from repository/server notes.

The build must support a staging environment before production cutover.

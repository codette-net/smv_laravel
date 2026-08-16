# SMV Architecture

## Status

The repository audit is complete. A broad Eloquent/schema foundation exists and the
inconsistencies identified by the audit were stabilized in SMV-001. Public recruitment
flows remain mostly prototype/incomplete; imports have foundation models and identity
but no execution pipeline; technical SEO is largely pending.

Sections below distinguish this current foundation from required MVP modules.

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
└── import provenance

Applications

Packages / Orders / Payments

Imports
├── sources, runs and logs (foundation exists)
├── provider-scoped vacancy identity (exists)
├── mappings (future)
├── parsing/transformation (future)
├── validation (future)
└── persistence/reporting pipeline (future)

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

Current Filament panel access is limited to `super-admin`, `admin` and `editor`.
Employer and candidate roles do not have unrestricted panel access. Editor permissions
are conservative pending later editorial refinement; an employer dashboard is not yet
implemented.

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

Do not add Vue/React/another design system without explicit approval.

## Background processing

Imports may become long-running. If current import size/hosting supports queues, isolate the execution so moving work to queued jobs is straightforward.

Do not require queue infrastructure for simple preview/parsing work when synchronous execution is safe and faster to ship.

## Deployment

Open decision: the Hostinger/staging deployment workflow still requires confirmation
from repository/server operations information that is not currently available.

The build must support a staging environment before production cutover.

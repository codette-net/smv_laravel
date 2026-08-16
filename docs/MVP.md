# SMV MVP

## Product goal

Build a stable, commercially credible first version of the new Sales en Marketing Vacatures platform in Laravel.

The MVP is the foundation of a recruitment platform. It must already support the core job-board business flow, company presence, reliable vacancy ingestion, strong search-engine migration fundamentals and enough content capability to support sales/demo use.

Current repository reality: a broad domain/schema foundation exists and SMV-001
stabilized its key inconsistencies. Public application flows remain mostly prototypes,
the import pipeline is not implemented, technical SEO remains largely pending, and the
Mosaic/Tailwind base provides substantial reusable frontend components and assets.

## Presentation target

A strong demonstrable version is desired around the beginning of September 2026 for a sales presentation.

The presentation build should visibly demonstrate:

1. A modern public frontend.
2. Company pages with active vacancies.
3. Vacancy discovery/search/filtering.
4. Vacancy detail and application/contact flow.
5. Admin management in Filament.
6. A credible vacancy import workflow including mapping and preview.
7. Basic content/blog presence if the recruitment core and imports are stable.

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

### Public vacancies

Required public flow:

- vacancy listing/discovery
- search and/or practical filters
- pagination
- vacancy detail
- company relation
- active/expired state handling
- featured handling if part of current commercial logic
- application/contact action
- related/internal links where useful

The current routes are prototype wiring rather than the final public route contract.
Final route strategy is deliberately deferred to SMV-040 and the SEO migration inputs.

### Applications

Support the business flow present in the current site and repository.

Possible modes include:

- internal application stored by SMV
- application by email
- external application URL

Do not invent a single mandatory mode until current data/model behavior has been inspected.

### Vacancy imports

See `IMPORTS.md`.

This is a critical MVP module and must include a decent Filament/admin mapping interface, not only backend parsers.

### Blog

A simple Blog is part of MVP scope but late in delivery order.

The Blog model/schema foundation exists; public pages and Filament editorial CRUD are
not yet complete.

Reason:

- quick to administer with Filament
- useful for September presentation content
- useful for ongoing SEO/content strategy
- can help create internal links to companies/vacancies

Keep it simple enough that it cannot jeopardize import, recruitment or SEO work.

### CMS/pages

Basic static content/page management may be retained or added if already present or essential to replacing the legacy site. Do not build a large generic CMS before the recruitment core is stable.

## Language

The public SMV website is Dutch.

Public navigation, forms, validation-facing labels, vacancy UI, company UI, blog UI and
SEO-facing content should use Dutch copy.

Internal code identifiers, database names and developer documentation may remain
English.

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

These can be later phases.

## MVP success criteria

The MVP is ready for controlled launch when:

- critical public flows work end to end
- admin can manage the core recruitment data
- import mappings can be configured and reused
- imports can be previewed and failures understood
- existing important URLs have a migration decision
- redirects/canonicals/indexability are tested
- required structured data is present and valid
- staging/production deployment is repeatable
- there are no known critical authorization or data-loss issues
- core behavior has automated test coverage appropriate to risk

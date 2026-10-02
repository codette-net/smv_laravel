# SMV MVP

## Product goal

Build a stable, commercially credible first version of the new Sales en Marketing Vacatures platform in Laravel.

The MVP is the foundation of a recruitment platform. It must already support the core job-board business flow, company presence, reliable vacancy ingestion, strong search-engine migration fundamentals and enough content capability to support sales/demo use.

Current repository reality (audited 1 October 2026): the recruitment core, Company and
Vacancy administration, the internal/external/e-mail application flow, the generic
JSON/XML/CSV/XLSX import pipeline, the technical SEO foundation and the native Blog are
implemented and covered by feature tests. The current public frontend also includes the
canonical homepage, vacancy and company discovery/detail pages, Blog archives and the
static Dutch pages `/adverteren`, `/over-ons`, `/tarieven` and `/contact`.

The main remaining MVP/release work is no longer foundational implementation. It is:

- make the currently disabled Contact form operational;
- confirm the required commercial package/payment/employer-posting scope;
- complete final stakeholder approval of public copy and template imagery;
- inventory and map valuable legacy URLs once exports are available;
- remove or quarantine obsolete prototype/showcase views that currently break
  `artisan view:cache`;
- complete accessibility, end-to-end, migration and launch validation.

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

### Applications

The MVP supports three canonical application destination modes:

- internal application stored by SMV
- application by email
- external application URL

Candidate accounts and a full ATS workflow remain outside the current MVP.

### Vacancy imports

See `IMPORTS.md`.

This is a critical MVP module and must include a decent Filament/admin mapping interface, not only backend parsers.

First-class MVP formats:

- JSON
- XML
- CSV
- XLSX

The architecture must separate source transport from source format and support reusable mappings across repeated feed runs.

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
- there are no known critical authorization or data-loss issues
- core behavior has automated test coverage appropriate to risk

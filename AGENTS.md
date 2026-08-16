# SMV Agent Instructions

## Project purpose

SMV is the Laravel rebuild of salesenmarketingvacatures.nl.
The MVP is the first version of a broader recruitment platform, not merely a visual replacement of the existing WordPress job board.

The rebuild must preserve the useful existing business flows, existing content value, SEO equity, and import capability while creating a maintainable Laravel foundation.

## Current development environment

- Primary development machine for this phase: ThinkPad T495, Windows 11.
- Repository workflow: GitHub.
- `main` is the stable/release branch.
- `develop` is the integration branch.
- Feature branches are created from `develop` and merged back into `develop` through review/PR.
- Do not work directly on `main`.

## Technology

Use the technology already present in the repository. Expected stack includes:

- PHP 8.3+
- Laravel
- MySQL
- Filament
- (Spatie) libraries like media manager, tags, and permissions
- Blade
- Tailwind CSS
- Alpine.js where small client-side interactions are needed
- Vite

TODO: verify exact framework/package versions from `composer.json` and `package.json` before making assumptions.

Do not introduce another frontend framework or CSS framework without explicit approval.

## Existing code first

Before implementing any feature:

1. Read the relevant project documentation in `/docs`.
2. Inspect the existing implementation.
3. Identify reusable models, migrations, services, Blade components, Tailwind components, Filament resources, routes, tests and assets.
4. Report conflicts or duplicated concepts before creating another implementation.

Prefer extending working code over replacing it.
Do not refactor unrelated working code merely for stylistic consistency.

## MVP priorities

The three non-negotiable MVP priorities are:

1. A credible and fast recruitment frontend using the existing Tailwind template/component system.
2. A robust vacancy import system with a usable field-mapping interface.
3. Strong technical SEO and a controlled migration from the legacy WordPress site.

The simple Blog module is also part of the MVP, but is deliberately scheduled late because Filament can provide the editorial interface quickly and it is primarily useful for content, SEO and the September presentation.

## Frontend rules

Existing Tailwind templates and components form the primary design system.

Priority order:

1. Reuse an existing component.
2. Adapt or compose existing components.
3. Create a new component only when the project has no suitable existing pattern.

Reuse existing:

- layouts
- navigation
- vacancy cards
- company cards
- buttons
- forms
- badges
- typography
- spacing patterns
- responsive patterns
- empty states where available

Do not replace working components simply to standardize them.

The user has existing template components with semantic names and structure. The exact tree will be documented in `docs/FRONTEND.md` after repository inspection.

Legacy and template image sets may be used when they are explicitly included in the repository and their usage is documented.

Do not invent image paths or silently add remote stock imagery.

## Laravel architecture

Follow Laravel conventions and the conventions already established by the repository.

Prefer:

- thin controllers
- Form Requests for non-trivial validation
- Policies for authorization
- Eloquent relationships
- Enums for finite domain states
- Services / Actions for substantial business logic
- queues/jobs for long-running imports when appropriate

Avoid unnecessary abstraction. Introduce a service or action because it owns meaningful business logic, not merely to move one line out of a controller.

## Database rules

Never rewrite old committed migrations that may already have been executed outside a throwaway local database.
Create new migrations for schema changes.

Never run destructive production database commands.
Never assume production can be reset or reseeded.

Before changing schema, inspect existing migrations and models and explain how the proposed change fits the current data model.

## Core domain

Expected MVP domains include:

- Users
- Companies
- Vacancies
- Applications
- Categories / taxonomies
- Packages
- Orders / payments
- Vacancy imports / feeds
- Basic content / Blog

Company is a first-class domain entity. A company can own or relate to vacancies and content and can grow later into richer employer-branding functionality.

Do not design Phase 2/3 functionality into the MVP unless a small structural choice is necessary to avoid an obvious dead end.

## Vacancy imports

Imports are a first-class product feature, not a one-off migration script.

The import system must be designed to support:

- multiple sources
- uploaded files and/or remote feeds where required
- JSON
- XML
- reusable mapping profiles
- discovery of source fields
- mapping source fields to SMV fields
- ignored fields
- default values where appropriate
- transformations where required
- validation before persistence
- preview before final import
- duplicate detection
- creation of new vacancies
- updating existing imported vacancies
- useful failure reporting
- import history/logs
- safe reruns

The admin interface must provide a clear mapping workflow.

Mapping logic must not be hardcoded inside controllers or Filament page classes.
Source-specific differences should be isolated behind a reusable import pipeline.

Do not create hundreds of source-specific conditional branches in one importer.

See `docs/IMPORTS.md` before modifying import-related code.

## SEO

SEO is a release requirement, not post-MVP polish.

Do not change a public URL without checking `docs/SEO.md` and `docs/SEO_MIGRATION.md`.

Important public pages should support, where applicable:

- unique title
- meta description
- canonical URL
- appropriate robots/indexing directives
- semantic headings
- crawlable internal links
- structured data
- XML sitemap inclusion
- correct HTTP status behavior

Vacancy pages should use valid JobPosting structured data when appropriate.

Legacy URLs must be evaluated before launch and mapped to the correct new destination where needed.
Avoid redirect chains and mass-redirecting unrelated pages to the homepage.

Staging must not accidentally become indexable.
Production must not accidentally retain staging `noindex` rules.

SEO migration work must include a pre-launch and post-launch crawl/check.

## Blog

The MVP includes a deliberately simple Blog module.

Prefer a conventional implementation:

- posts
- blog categories
- slug
- title
- excerpt
- content
- featured image 
- publish status
- published_at
- SEO fields or reusable SEO mechanism
- Filament editorial CRUD
- public index/detail pages

Keep authoring straightforward. Do not build an advanced block editor, comments, multi-author workflow or editorial approval system unless explicitly requested.

Where useful, a blog post may later relate to companies or vacancies, but do not over-engineer this before the core flow is stable.

## Testing and completion

For every implementation task:

1. Run the relevant existing tests first when practical.
2. Add or update automated tests for important domain behavior.
3. Run the relevant test subset after changes.
4. Run repository formatting/static-analysis commands if configured.
5. Inspect `git diff`.
6. Report what changed, what was tested and any unresolved risk.

Do not claim a task is complete when tests are failing unless the failures are clearly pre-existing and documented.

## Git discipline

- Never develop directly on `main`.
- Feature work starts from `develop` unless explicitly instructed otherwise.
- Keep each branch focused on one coherent feature/fix.
- Keep unrelated formatting/refactors out of a feature branch.
- Prefer small, reviewable commits.
- Never force-push shared branches unless explicitly instructed.
- Never commit secrets, `.env`, API keys, database dumps containing personal data, or credentials.

Suggested branch names:

- `feat/company-pages`
- `feat/vacancy-search`
- `feat/import-mapping`
- `feat/blog`
- `feat/seo-foundation`
- `fix/...`
- `chore/...`

## Security and privacy

Treat vacancy applications and candidate details as personal data.
Do not expose private candidate/application data on public routes.
Do not log secrets or full sensitive payloads unnecessarily.
Validate uploaded/imported data and files.
Escape/render user-controlled HTML safely according to the project's intended content model.

## Working style for Codex

For substantial tasks, use this sequence:

1. Inspect.
2. Summarize the existing state.
3. Propose the smallest coherent implementation.
4. Identify files likely to change.
5. Implement.
6. Test.
7. Review the diff.
8. Summarize results and risks.

If repository reality conflicts with these documents, do not silently choose one. Report the conflict and preserve working behavior until it is resolved.

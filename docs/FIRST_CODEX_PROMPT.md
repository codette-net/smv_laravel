# First Codex Prompt — Repository Audit

Historical reference: this prompt was used for the now-complete SMV-001 repository
audit. Do not treat it as evidence that the repository is still unaudited or rerun it in
place of the scoped backlog tasks.

```text
Inspect this repository as preparation for the SMV MVP rebuild.

Read first:
- AGENTS.md
- docs/MVP.md
- docs/ARCHITECTURE.md
- docs/DATA_MODEL.md
- docs/FRONTEND.md
- docs/IMPORTS.md
- docs/SEO.md
- docs/SEO_MIGRATION.md
- docs/LEGACY_WORDPRESS.md
- docs/BLOG.md
- docs/BACKLOG.md

Then inspect the actual repository, including:
- composer.json
- package.json
- routes
- migrations
- Eloquent models and relationships
- enums
- policies/authorization
- Filament resources/pages
- existing import-related code
- Blade layouts/views/components
- Tailwind configuration and existing template/component structure
- public/resource assets
- tests
- existing SEO metadata/schema/sitemap/redirect logic

Do not modify any files.
Do not create migrations.
Do not install packages.
Do not run destructive commands.

Report:

1. Current implemented architecture.
2. Features already implemented and usable.
3. Features partially implemented.
4. Missing MVP functionality.
5. Current data model and notable inconsistencies.
6. Existing reusable frontend components/templates and where they live.
7. Existing usable image/assets structure.
8. Current import-system implementation and what is missing for a reusable mapping workflow.
9. Current SEO implementation and migration risks.
10. Current Blog/content implementation, if any.
11. Technical debt that materially blocks MVP delivery.
12. Any conflict between repository reality and the project docs.
13. A recommended implementation order using small reviewable feature branches.
14. Which planned backlog tasks should be deleted, merged or split because of code that already exists.

For every conclusion, cite the relevant repository files/classes/routes so the audit is actionable.

Do not propose rewriting working functionality solely for style.
```

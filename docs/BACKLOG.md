# SMV Agent Backlog

## How to use this backlog

This is the initial order only. The first repository audit may change task boundaries because existing code may already implement all or part of a task.

Every task should eventually contain:

- Goal
- Existing context
- Requirements
- Out of scope
- Acceptance criteria
- Tests

## Phase A — Agent readiness / audit

### SMV-001 Repository audit

No code changes.

Read all `/docs` and inspect:

- composer.json
- package.json
- routes
- migrations
- models
- policies
- Filament resources/pages
- Blade views/components
- Tailwind config/components
- existing assets
- tests
- import-related code
- SEO-related code

Report:

1. implemented features
2. partial features
3. missing MVP features
4. existing reusable frontend pieces
5. current data model
6. import status
7. SEO status/risks
8. technical debt that blocks MVP
9. conflicts between docs and repository
10. recommended task order

### SMV-002 Documentation sync

Update TODO/unknown sections in `/docs` only after the repository audit and user review.

## Phase B — Recruitment core

Provisional tasks:

- SMV-010 Company domain audit/completion
- SMV-011 Company Filament admin
- SMV-012 Public company page
- SMV-020 Vacancy lifecycle audit/completion
- SMV-021 Vacancy Filament admin
- SMV-022 Vacancy listing/search/filter
- SMV-023 Vacancy detail
- SMV-024 Application destination/internal application flow

## Phase C — Imports

- SMV-030 Import subsystem audit/design
- SMV-031 Import sources
- SMV-032 Parser/field discovery
- SMV-033 Mapping model/backend
- SMV-034 Filament mapping interface
- SMV-035 Normalized preview
- SMV-036 Validation/failure reporting
- SMV-037 Import persistence/update/duplicate handling
- SMV-038 Import run history/rerun
- SMV-039 First real partner/feed adapter

## Phase D — SEO migration foundation

- SMV-040 Current route/SEO audit
- SMV-041 Metadata/canonical foundation
- SMV-042 JobPosting structured data
- SMV-043 Sitemap/robots
- SMV-044 Legacy URL inventory import
- SMV-045 Redirect implementation/testing
- SMV-046 Staging SEO crawl/checklist

## Phase E — Commercial flow

Provisional, dependent on current implementation/business requirements:

- SMV-050 Packages audit/completion
- SMV-051 Orders/payments audit/completion
- SMV-052 Employer vacancy-posting flow

## Phase F — Blog / presentation content

- SMV-060 Blog domain + migration
- SMV-061 Blog Filament resource
- SMV-062 Blog public index/detail
- SMV-063 Blog SEO/internal links

## Phase G — Polish/release

- SMV-070 Frontend consistency pass
- SMV-071 Responsive/accessibility pass
- SMV-072 End-to-end smoke tests
- SMV-073 Migration dry run
- SMV-074 Production launch checklist

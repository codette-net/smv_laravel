# SMV Agent Backlog

## How to use this backlog

The repository audit and SMV-001 foundation stabilization are complete. This is the
agreed delivery order; individual task boundaries may still be refined when their
current implementation is inspected.

Every task should eventually contain:

- Goal
- Existing context
- Requirements
- Out of scope
- Acceptance criteria
- Tests

## Phase A — Agent readiness / audit

### SMV-001 Repository audit
Status: Complete

### Foundation stabilization
Status: Complete
Result of SMV-001 audit.
Core schema/model inconsistencies, authorization and baseline tests stabilized.

### SMV-002 Documentation sync
Status: Complete

## Phase B — Recruitment core

Planned tasks:

- SMV-010 Company domain audit/completion
- SMV-011 Company Filament admin
- SMV-012 Public company page
- SMV-013 Public company index
- SMV-020 Vacancy lifecycle audit/completion
- SMV-021 Vacancy Filament admin
- SMV-022 Vacancy listing/search/filter
- SMV-022A Vacancy listing visual alignment
- SMV-022B Vacancy taxonomy + Spatie Tags foundation
- SMV-023 Vacancy detail
- SMV-024 Application destination/internal application flow

## Phase C — Imports

### SMV-030 Import subsystem audit/design
Audit the stabilized import foundation against real feed examples before implementation.

Must evaluate:

- VNOM XML
- Michael Page XML
- provisional Orange Career / 8vance JSON
- MVP format support: JSON, XML, CSV and XLSX
- transport versus format separation
- reader/parser boundary
- record path and record selection/filtering
- nested field/path discovery
- reusable mapping model
- normalized Vacancy representation
- salary/content transformations
- Company resolution
- structured taxonomy and Spatie Tag resolution
- provider-scoped upsert identity
- normalized preview
- validation/failure reporting

Deliver a concrete architecture/schema/task plan for SMV-031 through SMV-039. Do not build the complete importer in SMV-030.

### SMV-031 Import sources
Implement ImportSource configuration for transport, format and source access.

### SMV-032 Parser/field discovery
Implement JSON/XML/CSV/XLSX readers, record extraction/selection where applicable, and field discovery.

### SMV-033 Mapping model/backend
Implement reusable field mappings, defaults, transforms and source-specific normalization configuration.

### SMV-034 Filament mapping interface
Implement the admin mapping workflow in Filament.

### SMV-035 Normalized preview
Show normalized Vacancy data, Company/taxonomy resolution and pre-import status.

### SMV-036 Validation/failure reporting
Implement record-level validation, warnings, failures and understandable admin reporting.

### SMV-037 Import persistence/update/duplicate handling
Implement format-independent Vacancy persistence/upsert using `import_source_id + source_reference`.

### SMV-038 Import run history/rerun
Implement run history, safe reruns, counters and missing-record reporting.

### SMV-039 First real partner/feed adapter
Implement and validate the first production-quality partner/feed configuration using the generic pipeline.

## Phase D — SEO migration foundation

- SMV-040 Current route/SEO audit — completed as the technical SEO foundation
- SMV-041 Metadata/canonical foundation — completed
- SMV-042 JobPosting structured data — completed
- SMV-043 Sitemap/robots — completed
- SMV-044 Legacy URL inventory import
- SMV-045 Redirect implementation/testing
- SMV-046 Staging SEO crawl/checklist

## Phase E — Commercial flow

Planned, with exact scope dependent on current implementation and business requirements:

- SMV-050 Packages audit/completion
- SMV-051 Orders/payments audit/completion
- SMV-052 Employer vacancy-posting flow

## Phase F — Blog / presentation content

- SMV-060 Native Laravel/Filament blog — completed: BlogPost domain, Filament CRUD,
  public index/detail, shared SEO and sitemap integration
- SMV-061 Blog taxonomy and editorial relations — deferred: blog categories, tags and
  editorial relations to vacancies and companies
- future: richer Blog SEO after content exists

## Phase G — Polish/release

- SMV-070 Frontend consistency pass
- SMV-071 Responsive/accessibility pass
- SMV-072 End-to-end smoke tests
- SMV-073 Migration dry run
- SMV-074 Production launch checklist

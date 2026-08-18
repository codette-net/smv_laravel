# SMV Vacancy Imports

## Importance

Vacancy imports are a critical MVP feature and a commercial capability of SMV.

The goal is not merely to parse one known feed. The system must provide a maintainable ingestion pipeline and a clear admin interface for mapping partner fields to SMV fields.

## Current implementation status

SMV-001 stabilized the existing foundation:

- `ImportSource`, `Import` and `ImportLog` model/schema foundations
- `import_source_id` provider relationships
- provider-scoped vacancy identity using `import_source_id + source_reference`
- relevant import enums and casts
- nullable legacy `imports.source` compatibility data

The following are not implemented yet:

- readers/parsers and source field discovery
- mapping model/backend and mapping UI
- transformations and normalized preview
- validation/failure workflow
- vacancy persistence/upsert pipeline
- queued execution and safe rerun workflow
- a real partner adapter

Future import code must use `import_source_id` / `ImportSource` as provider identity. It
must not use legacy `imports.source` for that purpose.

Structured imported vacancy attributes such as employment type, workplace, sector,
function area and experience must map to the controlled `Category` taxonomy by type;
flexible descriptive values may map to Spatie Tags. Source-specific aliases and
normalization rules remain part of SMV-030 rather than the Category model.

## Supported source direction

MVP should be architected for sources such as:

- JSON feeds/files
- XML feeds/files
- partner/ATS feeds
- custom feeds

Potential integrations discussed for the broader platform include ATS/feed providers such as Broadbean, Connexys, Recruitee, AFAS and OTYS, but no adapter should be claimed as implemented until it actually exists and is tested.

## Required workflow

Target admin workflow:

```text
Create/select import source
        ↓
Provide URL or upload/source configuration
        ↓
Fetch/read sample
        ↓
Detect source fields / record shape
        ↓
Map source fields to SMV fields
        ↓
Configure ignore/default/transform behavior
        ↓
Save reusable mapping profile
        ↓
Preview normalized vacancies
        ↓
Show validation problems
        ↓
Run import
        ↓
Show summary + failed records
        ↓
Allow safe rerun/update
```

## Mapping interface

A mapping UI should clearly pair source fields with destination fields, for example:

```text
SOURCE FIELD                  SMV FIELD
job_title              ->    title
company_name           ->    company.name
location               ->    location
salary.minimum         ->    salary_min
salary.maximum         ->    salary_max
description_html       ->    description
apply_url              ->    application_url
reference              ->    source_reference
```

Each mapping row may need operations such as:

- direct map
- ignore
- default value
- transformation

Do not overbuild a universal ETL platform. Start with transformations genuinely needed by real SMV feeds.

## Future domain concepts

The precise mapping schema is intentionally deferred to SMV-030. Candidate concepts to
evaluate against real feed examples include:

```text
ImportSource
ImportMapping
ImportMappingField
ImportRun
ImportRecord / ImportFailure
```

Possible services/components:

```text
SourceReader
Parser
FieldDiscovery
Mapper
Transformer
Validator
VacancyImporter
```

The key architectural requirement is separation between:

1. reading/parsing source data
2. mapping/normalization
3. validation
4. persistence/updating
5. reporting

## Duplicate/update behavior

Prefer stable external identifiers supplied by a source.

A rerun should not create duplicate vacancies when the same source vacancy is already known.

The established identity strategy is:

```text
import_source_id + source_reference
```

If a source has no reliable identifier, define and test a fallback strategy rather than silently guessing.

## Company matching

Importing vacancy data may require matching or creating companies.
Do not create duplicate companies simply because naming/casing differs.

The exact company matching and transformation policies remain open until SMV-030 and
inspection of real source examples.

## HTML/content

Source descriptions may contain HTML.
Define an explicit safe normalization/sanitization strategy.
Do not blindly store/render arbitrary unsafe source HTML.

## Validation and preview

Preview should show the normalized values SMV is about to persist, not only raw source data.

Useful validation output should identify:

- source record
- field
- reason
- whether the record will be skipped or can still import

## Logging

An import run should make it possible to understand:

- source
- start/end/status
- number read
- created
- updated
- skipped
- failed
- important error details

Avoid logging secrets or excessive candidate/personal data.

## Next import task

SMV-030 must audit the stabilized foundation against real feed examples and design the
smallest reusable mapping/pipeline model before implementation begins.

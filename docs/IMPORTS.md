# SMV Vacancy Imports

## Importance

Vacancy imports are a critical MVP feature and a commercial capability of SMV.

The goal is not merely to parse one known feed. The system must provide a maintainable ingestion pipeline and a clear admin interface for mapping partner fields to SMV fields.

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
reference              ->    external_reference
```

Each mapping row may need operations such as:

- direct map
- ignore
- default value
- transformation

Do not overbuild a universal ETL platform. Start with transformations genuinely needed by real SMV feeds.

## Suggested domain concepts

Names are provisional until the existing repository is audited.

Possible concepts:

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

The exact identity strategy must be documented per source, for example:

```text
source_id + external_reference
```

If a source has no reliable identifier, define and test a fallback strategy rather than silently guessing.

## Company matching

Importing vacancy data may require matching or creating companies.
Do not create duplicate companies simply because naming/casing differs.

The exact matching policy must be deliberately designed after inspecting real source examples.

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

## First Codex import task

Before implementing anything, Codex must inspect all existing import-related models, migrations, resources, commands, jobs and services and report what already exists.

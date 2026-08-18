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

MVP has first-class format support requirements for:

- JSON
- XML
- CSV
- XLSX

XLSX is intentionally in MVP scope: manual spreadsheet imports are commercially useful
and valuable for the September presentation. This documents an architectural target,
not an implemented parser or spreadsheet dependency. SMV-030 must select concrete
libraries only after inspecting the repository, Laravel/PHP compatibility and real
partner feeds.

Potential integrations discussed for the broader platform include ATS/feed providers such as Broadbean, Connexys, Recruitee, AFAS and OTYS, but no adapter should be claimed as implemented until it actually exists and is tested.

## Transport, format and reader boundary

Source transport and data format are separate concerns. A source may arrive through:

- file upload
- HTTP/HTTPS URL
- API endpoint

Future sources may add SFTP or another provider-specific transport. Formats remain
JSON, XML, CSV and XLSX regardless of how a source arrives. For example:

```text
remote XML feed:       transport = http,   format = xml
uploaded spreadsheet:  transport = upload, format = xlsx
remote JSON feed:      transport = http,   format = json
```

Parser implementations must not encode transport assumptions. The intended conceptual
boundary is:

```text
ImportSource
    ↓
transport
    ↓
Reader
    ↓
source records
```

Potential readers include `JsonReader`, `XmlReader`, `CsvReader` and
`SpreadsheetReader`. Their exact PHP interfaces and classes are deliberately deferred
to SMV-030. Every reader must expose source records to the same downstream pipeline.

JSON and XML can contain nested structures, so a source needs a configurable or
discoverable record path identifying the repeated structure that represents one
vacancy, for example `response.jobs[]` or `feed.vacancies.vacancy`. SMV-030 should
evaluate automatic discovery with an explicit admin override.

Field discovery operates on this normalized source-record shape, not only CSV headers.
Nested values must therefore have stable path notation, such as `title`,
`company.name`, `salary.minimum`, `salary.maximum`, `categories.*` and
`application.url`. The exact path syntax remains an SMV-030 decision.

## Required workflow

Target admin workflow:

```text
Create/select import source
        ↓
Provide URL or upload/source configuration
        ↓
Fetch/read sample
        ↓
Detect source records, record path and fields
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

Mappings belong to, or are associated with, an `ImportSource` and must be reusable
across runs. They include field mappings, ignored fields, defaults, transformations,
taxonomy aliases and Company mappings where appropriate. SMV-030 should choose the
smallest useful schema; versioning is not a requirement yet.

## Format-independent normalization pipeline

Once a reader has produced source records, all formats converge before mapping and
persistence:

```text
ImportSource
      ↓
Fetch / Upload
      ↓
Reader / Parser
      ↓
Source records
      ↓
Field discovery
      ↓
Mapping profile
      ↓
Normalization / transformations
      ↓
Validation
      ↓
Company / taxonomy resolution
      ↓
Vacancy persistence / upsert
      ↓
Import run report
```

`NormalizedVacancyData` is the conceptual name for the intermediate representation
between source mapping and Eloquent persistence; it is not a prescribed class name.
It lets source-specific fields such as `jobTitle`, `position_name`, `functie` and
`vacancy.title` converge on the canonical SMV field `title`. Taxonomy values must also
be normalized before persistence. The concrete DTO/class design remains SMV-030 work.

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

Filament is the administration and mapping interface, not the import engine. The
pipeline must remain usable without Filament. Filament will eventually manage Import
Sources, configuration, mappings, normalized previews, execution, run history,
validation/failure inspection and reruns. A native Filament CSV `ImportAction` may be
useful as a convenience later, but is not the architectural foundation.

## Duplicate/update behavior

Prefer stable external identifiers supplied by a source.

A rerun should not create duplicate vacancies when the same source vacancy is already known.

The established identity strategy is:

```text
import_source_id + source_reference
```

If a source has no reliable identifier, define and test a fallback strategy rather than silently guessing.

This identity is format-independent: JSON, XML, CSV and XLSX records use the same
duplicate/update rule after normalization. Manual vacancies remain outside imported
identity.

## Company matching

Importing vacancy data may require matching or creating companies. The preferred
resolution direction is:

1. stable external Company identifier where available;
2. explicit saved source-to-SMV Company mapping;
3. normalized exact Company-name match where safe;
4. admin resolution or optional creation.

Do not silently create duplicate Companies because of naming/casing differences. AI or
fuzzy Company matching is not an MVP requirement. Exact policy remains subject to
SMV-030 and real-feed inspection.

Structured imported values must resolve to the canonical `Category` taxonomy by type:

- `employment_type` / Dienstverband
- `workplace` / Werklocatie
- `sector` / Sector
- `function_area` / Functiegebied
- `experience` / Ervaring

Flexible descriptive values may resolve to Spatie Tags. Source-specific aliases must
be reusable mapping data, not branches in `Category` itself. Examples include `Full
Time` → `employment_type: fulltime`, `hybrid_working` → `workplace: hybride`, and
`Information Technology` → `sector: it`.

The exact company matching and transformation policies remain open until SMV-030 and
inspection of real source examples.

## HTML/content

Source descriptions may contain HTML.
Define an explicit safe normalization/sanitization strategy.
Do not blindly store/render arbitrary unsafe source HTML.

## Validation and preview

Preview should show the normalized values SMV is about to persist, not only raw source data, and occur before a production run.

Useful validation output should identify:

- source record
- field
- reason
- whether the record will be skipped or can still import

Where practical, each preview row should expose the raw/source value, mapped SMV
field, normalized value, Company resolution, taxonomy resolution and validation
status. For example:

```text
source:     hybrid_working
field:      workplace
normalized: Hybride
status:     valid
```

## Missing source records

A future run should be able to report records that existed in a previous successful
feed run but are absent from the current source. Automatic deletion is not the default.
Later work may add a configurable deactivation/closure policy; the exact policy is
deferred beyond the first persistence implementation.

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

SMV-030 must inspect real feeds, audit the stabilized foundation and design the
smallest reusable transport/format architecture, reader boundary, mapping model,
normalized representation and Company/taxonomy resolution direction before
implementation begins.

## Scope discipline

This architecture should be reusable within SMV, but the MVP is not a generic
universal ETL product or standalone import SaaS. Optimize for real SMV vacancy-feed
requirements.

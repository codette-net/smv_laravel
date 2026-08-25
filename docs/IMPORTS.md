# SMV Vacancy Imports

## Importance

Vacancy imports are a critical MVP feature and a commercial capability of SMV.

The goal is not merely to parse one known feed. The system must provide a maintainable ingestion pipeline and a clear admin interface for mapping partner fields to SMV fields.

The MVP should support real SMV vacancy-feed requirements without turning the importer into a generic universal ETL product.

## Current implementation status

SMV-001 stabilized the existing foundation:

- `ImportSource`, `Import` and `ImportLog` model/schema foundations
- `import_source_id` provider relationships
- provider-scoped vacancy identity using `import_source_id + source_reference`
- relevant import enums and casts
- nullable legacy `imports.source` compatibility data

The following are not implemented yet:

- source upload UI and production import execution
- mapping model/backend and mapping UI
- transformations and normalized preview
- validation/failure workflow
- company/taxonomy resolution
- vacancy persistence/upsert pipeline
- queued execution and safe rerun workflow
- real partner/feed adapters

SMV-033 adds reusable `ImportMapping` / `ImportMappingField` records, a code-owned
destination registry and a side-effect-free normalized mapping result. Salary and rate
now retain independent nullable currency and period metadata; supported periods are
hour, day, week, month and year. Mapping does not yet resolve taxonomy values or write
Companies, Vacancies, Tags or Categories.

SMV-036 adds an ephemeral domain-validation and resolution boundary. It classifies
records as ready, warning, needs resolution or error. Explicit taxonomy aliases are
source-scoped `ImportTaxonomyMapping` records; only an unambiguous exact Category
name/slug match is automatic. No fuzzy matching or Category creation occurs. The
ImportSource Company remains the owner, flexible Tags need no controlled resolution,
and Vacancy persistence remains SMV-037.

Future import code must use `import_source_id` / `ImportSource` as provider identity. It must not use legacy `imports.source` for that purpose.

Structured imported vacancy attributes such as employment type, workplace, sector, function area and experience must map to the controlled `Category` taxonomy by type; flexible descriptive values may map to Spatie Tags.

Source-specific aliases and normalization rules must not live in the `Category` model.

## Filament's role

Filament is the administration and mapping interface, not the core import engine.

Do not build the import architecture around Filament's native `ImportAction`. Native CSV import may later be useful as a convenience feature, but the SMV import pipeline must remain independently usable by uploaded files, remote feeds, APIs, scheduled imports and future CLI/background jobs.

Filament should eventually manage:

- Import Sources
- source configuration
- transport and format
- mapping profiles
- record selection/filtering
- normalized preview
- validation output
- import execution
- run history
- failures
- reruns

## Supported MVP formats

First-class MVP formats:

- JSON
- XML
- CSV
- XLSX

XLSX is intentionally included because manual spreadsheet import is commercially useful and valuable for the September presentation.

SMV-032 uses the already-installed `league/csv` package for CSV and OpenSpout 4.32
for streaming XLSX rows. JSON, XML, CSV and XLSX readers now converge into
`SourceRecord`; mapping, normalization and persistence remain later work.

## Transport and format are separate concepts

Source transport and source format must be modeled independently.

### Candidate transports

- file upload
- HTTP/HTTPS URL
- API endpoint

Possible future transports:

- SFTP
- provider-specific authentication/fetching
- scheduled partner connectors

### Formats

- JSON
- XML
- CSV
- XLSX

Examples:

```text
VNOM
transport: http
format: xml

Michael Page
transport: http
format: xml

Orange Career / 8vance
transport: upload or http/api
format: json

Manual employer spreadsheet
transport: upload
format: xlsx
```

Parser implementations must not contain transport-specific assumptions.

## Required architecture

Target pipeline:

```text
                     ImportSource
                          |
              +-----------+-----------+
              |                       |
           transport                format
      HTTP / Upload / API     JSON/XML/CSV/XLSX
              |                       |
              +-----------+-----------+
                          |
                        Reader
                          |
                   Record selection
                          |
                    Source records
                          |
                   Field discovery
                          |
                    Mapping profile
                          |
            Normalization / transforms
                          |
               NormalizedVacancyData
                          |
                     Validation
                          |
              Company/Taxonomy resolver
                          |
                    Vacancy upsert
                          |
                   Import run report
```

The key rule is that JSON, XML, CSV and XLSX converge into the same downstream pipeline before mapping/persistence.

## Reader/parser boundary

The exact interface remains an SMV-030 design decision, but the architecture should support format-specific readers such as:

```text
JsonReader
XmlReader
CsvReader
SpreadsheetReader
```

Conceptually, all readers must expose source records through the same downstream representation.

## Record path and record selection

JSON and XML feeds may contain nested structures, and not every source record is necessarily relevant to SMV.

The import architecture therefore needs two related concepts:

1. **record path** — which repeated structure represents one vacancy
2. **record selection/filtering** — which of those records should be imported

### Example: VNOM

A vacancy record is a `<job>` node.

Legacy WP All Import selection logic:

```xpath
/job[
    function[1][contains(.,"Sales")]
    or function[1][contains(.,"Marketing")]
]
```

### Example: Michael Page

A vacancy record is also a `<job>` node, but the relevant field is nested:

```xpath
/job[
    sector/term[1][contains(.,"Sales")]
    or sector/term[1][contains(.,"Marketing")]
]
```

Do not hardcode Sales/Marketing filtering into `XmlReader`.

The reader should read records; source-specific inclusion rules belong to ImportSource configuration / record-selection configuration.

## Field discovery

Field discovery must work on the source-record representation, not only CSV headers.

Nested source fields should be representable using stable path notation.

Examples:

```text
title
company.name
company.email
company.logo_url

detailed_location.city
detailed_location.region

salary.min
salary.max
salary.currency
salary.period

description.role
description.candidate
description.company
description.deal

sector.term
subSector.term
industry.term

consultant.email
```

Repeated/nested values must also be representable, for example:

```text
categories.*
tags.*
description.bulletPoints.*
```

The exact path syntax is an SMV-030 decision.

## Format-independent mapping

Once records are read and selected, mapping must be format-agnostic.

Target workflow:

```text
Create/select import source
        ↓
Configure transport + format
        ↓
Provide URL or upload/source configuration
        ↓
Fetch/read sample
        ↓
Detect record path / record shape
        ↓
Configure record selection if required
        ↓
Discover source fields
        ↓
Map source fields to SMV fields
        ↓
Configure ignore/default/transform behavior
        ↓
Save reusable mapping profile
        ↓
Preview normalized vacancies
        ↓
Show company/taxonomy resolution
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
title                  ->    title
company.name           ->    company.name
detailed_location.city ->    location
salary.min             ->    salary_min
salary.max             ->    salary_max
description            ->    description
apply_url              ->    application_url
identifier             ->    source_reference
function               ->    function_area
experience             ->    experience
industry.term          ->    sector
```

Each mapping row may need operations such as:

- direct map
- ignore
- default value
- transformation
- combine multiple source fields
- taxonomy map
- company resolution rule

Do not overbuild a universal transformation DSL. Add transformations that real SMV feeds require.

## Normalized vacancy boundary

There should be a normalized intermediate representation between source mapping and Eloquent persistence.

Conceptual name:

```text
NormalizedVacancyData
```

Its purpose is to ensure source-specific names converge to canonical SMV concepts.

Potential normalized concepts include:

```text
title
description
company
location
published_at
deadline_at
expires_at
application_mode
application_url
application_email
salary_min
salary_max
salary_currency
salary_period
employment_type
workplace
sector
function_area
experience
tags
source_reference
```

Do not add database fields merely because one feed contains a value. SMV-030 must distinguish:

- map now
- transform
- taxonomy map
- ignore
- domain gap / future field

## Real feed examples

### VNOM XML

Observed source fields include:

```text
title
date
referencenumber
url
applyUrl
identifier
company
city
country
postalcode
description
salary
owner
education
jobtype
experience
function
category
parentJobId
recordType
lastactivitydate
```

Likely mapping candidates:

```text
title           -> title
identifier      -> source_reference (verify against multiple records)
company         -> company
city            -> location
description     -> description
applyUrl        -> application_url
date            -> published_at
function        -> function_area
experience      -> experience mapping
jobtype         -> source-specific employment/contract mapping
category        -> source-specific taxonomy mapping
salary          -> salary normalization
```

Important ambiguity: `Loondienst` is not automatically the same thing as `Fulltime`.

### Michael Page XML

Important nested fields:

```text
uniqueJobID
ref
title
employer.name
published

salary.min
salary.max
salary.currency
salary.period

description.role
description.bulletPoints.*
description.candidate
description.company
description.deal

summary.content
summary.title

consultant.name
consultant.email
consultant.cvxemail

Job_Detail_URL

location.text

sector.term
subSector.term
industry.term
```

Likely canonical interpretation:

```text
sector.term   -> function_area
industry.term -> sector
location.text -> location
uniqueJobID   -> source_reference
published     -> published_at
```

This feed also demonstrates that multiple source fields may need to combine into one SMV field, especially the vacancy description.

### Orange Career / 8vance JSON

The available JSON example is provisional; the final structure may differ.

Observed nested fields include:

```text
company.name
company.email
company.phone
company.website
company.logo_url

detailed_location.latitude
detailed_location.longitude
detailed_location.street_name
detailed_location.street_number
detailed_location.postal_code
detailed_location.city
detailed_location.country
detailed_location.region

working_hours_minimum
working_hours_maximum

experience_years_minimum
experience_years_maximum

salary_low
salary_high
salary_type
salary_currency
salary_currency_symbol

apply_url

primary_contact_person.name
primary_contact_person.email
primary_contact_person.phone
```

This feed is useful for architecture validation, but must not be treated as the final Orange Career adapter specification.

Potential domain gaps include:

- working-hours range
- richer location fields
- numeric experience range

SMV-030 should classify these explicitly rather than automatically adding columns.

## Salary normalization

Real sources encode salary differently.

Examples:

VNOM:

```text
€3200 - €3800 Month
```

Michael Page:

```text
salary.min = 36000
salary.max = 60000
salary.currency = EUR
salary.period = 3
```

Orange Career / 8vance:

```text
salary_low = 2500
salary_high = 3000
salary_type = maand
salary_currency = EUR
```

The mapping/normalization layer must normalize source salary data into the SMV compensation model.

Do not assume one source period/code has the same semantics as another.

## Description/content normalization

Source descriptions may contain HTML and may be split across multiple fields.

Examples:

- VNOM: one HTML `description`
- Michael Page: role, candidate, company, deal, bullet points
- JSON feeds may provide plain text or HTML fields

The importer may therefore need:

- safe HTML normalization/sanitization
- field combination
- ordering rules for combined sections

Do not blindly store/render arbitrary unsafe source HTML.

## Structured taxonomy mapping

Imported structured values must map to canonical SMV Category types:

```text
employment_type / Dienstverband
workplace       / Werklocatie
sector          / Sector
function_area   / Functiegebied
experience      / Ervaring
```

Flexible descriptive values may map to Spatie Tags.

Mappings must be reusable per ImportSource.

Source-specific aliases belong to import mapping/configuration, not to the Category model.

## Company resolution

Preferred conceptual resolution order:

1. stable external company identifier where available
2. explicit saved source-to-SMV Company mapping
3. normalized exact company match where safe
4. admin resolution / optional Company creation

Do not introduce AI/fuzzy company matching as an MVP requirement.

Do not silently create duplicate Companies merely because casing/punctuation differs.

## Duplicate/update behavior

The established authoritative identity is:

```text
import_source_id + source_reference
```

This identity is format-independent.

JSON, XML, CSV and XLSX must all use the same duplicate/update rules after normalization.

Manual Vacancies may have:

```text
import_source_id = null
source_reference = null
```

A rerun should not create duplicates when the same source vacancy is already known.

## Missing source records

A recurring feed may stop returning a previously imported Vacancy.

The import system should eventually detect and report this.

Do not make automatic deletion the default.

Potential later behavior:

- report missing records
- mark as no longer present
- optionally close/deactivate after explicit configuration

## Reusable mappings

Mappings should belong to or be associated with an ImportSource and be reusable across runs.

Reusable source configuration may include:

- field mappings
- ignored fields
- defaults
- transformations
- combined fields
- taxonomy aliases
- company mappings
- record-selection rules

## Preview

Preview must show normalized destination data, not only raw source data.

Useful preview output may include:

```text
SOURCE VALUE         SMV FIELD        NORMALIZED VALUE        STATUS
Sales Support        function_area    Sales / Sales Support   valid
hybrid_working       workplace        Hybride                 valid
ACME B.V.            company          ACME BV (#17)           matched
unknown-sector       sector           —                       needs mapping
```

Preview should expose where practical:

- raw/source value
- source field/path
- mapped SMV field
- normalized value
- Company resolution
- taxonomy resolution
- validation state
- whether the record can import

Preview occurs before a production import run.

## Validation and failure reporting

Useful validation output should identify:

- source record
- source reference where available
- field/path
- reason
- normalized value if relevant
- whether the record will be skipped or can still import

## Logging

An import run should make it possible to understand:

- source
- transport/format
- start/end/status
- number read
- selected
- created
- updated
- skipped
- failed
- missing since previous run where known
- important error details

Avoid logging secrets or excessive personal data.

## Future domain concepts

The precise mapping schema remains an SMV-030 design outcome.

Candidate concepts to evaluate against the real feeds include:

```text
ImportSource
ImportMapping
ImportMappingField
ImportRun
ImportRecord / ImportFailure
```

Possible services/components:

```text
SourceFetcher
ImportReader
RecordSelector
FieldDiscovery
Mapper
Transformer
Validator
CompanyResolver
TaxonomyResolver
VacancyImporter
```

These are candidates, not mandatory class names.

## Dependencies

Do not select a Filament import plugin as the architectural foundation.

SMV-030 should evaluate implementation dependencies against the current Laravel/PHP/Filament stack and real feed samples.

A spreadsheet library such as Laravel Excel may be appropriate for CSV/XLSX handling.

XML and JSON should not be forced through a spreadsheet abstraction if dedicated/native readers provide a cleaner implementation.

## Scope discipline

Build a clean reusable importer for SMV.

Do not attempt to build:

- a universal ETL framework
- a standalone import SaaS
- a generic data-integration platform

Modularity is desirable because SMV has multiple feed providers, but real SMV vacancy-feed requirements remain the priority.

## Next import task

SMV-030 must audit the stabilized import foundation against real feed examples and design the smallest reusable pipeline before implementation begins.

SMV-030 must explicitly evaluate:

- VNOM XML
- Michael Page XML
- provisional Orange Career / 8vance JSON
- JSON/XML/CSV/XLSX format support
- transport/format separation
- reader boundary
- record path and record selection
- nested field/path discovery
- mapping schema
- normalized vacancy representation
- salary/content transformations
- company resolution
- structured taxonomy/Spatie Tag resolution
- provider-scoped upsert identity
- preview and failure-reporting requirements

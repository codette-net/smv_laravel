# SMV Vacancy Imports

## Importance

Vacancy imports are a critical MVP feature and a commercial capability of SMV.

The goal is not merely to parse one known feed. The system must provide a maintainable ingestion pipeline and a clear admin interface for mapping partner fields to SMV fields.

The MVP should support real SMV vacancy-feed requirements without turning the importer into a generic universal ETL product.

## Current implementation status

SMV-031 through SMV-039 are implemented. The current generic pipeline provides:

- admin-configured upload, HTTP and API sources with private uploaded files and guarded
  remote fetching;
- JSON, XML, CSV and XLSX readers converging on `SourceRecord`;
- record selection, bounded field discovery and reusable `ImportMapping` records;
- a code-owned destination registry, transforms and side-effect-free normalized preview;
- validation plus source-scoped explicit taxonomy aliases without fuzzy matching or
  automatic Category creation;
- legacy source-configured Company association plus optional reviewed, source-scoped
  per-record employer resolution, and provider-scoped Vacancy upsert through
  `import_source_id + source_reference`;
- mapped Category/Tag persistence, stable slugs and idempotent reruns;
- safe Import history/logs, created/updated/skipped/failed counters, reversible
  missing/restored source reporting and conservative per-source publication/missing
  policies; missing records are never hard-deleted;
- production-quality VNOM configuration validated through the generic pipeline, plus
  end-to-end parity coverage for representative VNOM, Michael Page, Orange Career,
  CSV and XLSX fixtures.

Production execution is currently synchronous. Queue/chunk scheduling, retry policy,
stale-run recovery and unattended activation are operational follow-up work, not hidden
completed features. Source identifiers and business mappings still require provider
approval before enabling an automatic production feed.

Only verified complete snapshots may reconcile missing records. Empty, malformed,
partial, invalid, unresolved or failed input fails closed before missing policy is
applied. See [IMPORT_ARCHITECTURE_AUDIT.md](IMPORT_ARCHITECTURE_AUDIT.md),
[JOOBS_FEED_MAPPING.md](JOOBS_FEED_MAPPING.md), and
[IMPORT_MVP_PLAN.md](IMPORT_MVP_PLAN.md) for field coverage and proposals.

Future import code must use `import_source_id` / `ImportSource` as provider identity. It must not use legacy `imports.source` for that purpose.

Structured imported vacancy attributes such as employment type, workplace, sector, function area and experience must map to the controlled `Category` taxonomy by type; flexible descriptive values may map to Spatie Tags.

Source-specific aliases and normalization rules must not live in the `Category` model.

## Filament's role

Filament is the administration and mapping interface, not the core import engine.

Do not build the import architecture around Filament's native `ImportAction`. Native CSV import may later be useful as a convenience feature, but the SMV import pipeline must remain independently usable by uploaded files, remote feeds, APIs, scheduled imports and future CLI/background jobs.

Filament currently manages:

- Import Sources
- source configuration
- transport and format
- mapping profiles
- record selection/filtering
- normalized preview
- validation output
- synchronous import execution
- run history
- row failures and safe logs

Queue dispatch, automatic retry/recovery and unattended schedule management are not
implemented.

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

SMV-039 validated the following production configuration through the generic pipeline:

```text
transport       HTTP (private upload for local verification)
format          XML
record path     job
selection       function contains Sales OR function contains Marketing

identifier      -> source_reference
title           -> vacancy.title
description     -> vacancy.description (generic safe-HTML sanitization at persistence)
city            -> vacancy.location
date            -> vacancy.published_at (date transform)
default         -> vacancy.application_mode = external
applyUrl        -> vacancy.application_url
salary          -> salary min/max/currency/period (generic compensation-text transforms)
function        -> taxonomy.function_area
```

The ImportSource Company **VNOM** is authoritative. Source `company` never reassigns a
Vacancy or overwrites the Company profile. `identifier` is the strongest stable opaque
identity candidate in the available snapshots; verify it against another production
snapshot before unattended activation. The public HTTPS feed requires no documented
credentials, but automatic execution still requires an active source and explicit
admin/super-admin approval.

`Loondienst` is not automatically the same thing as `Fulltime`. Experience strings
such as `1 tot 3 jaar` have no approved Junior/Medior/Senior rule, and source `category`
such as `Office` is not proven to mean canonical sector or function area. These fields
remain unmapped until business-approved aliases exist. Full reproducible Filament
settings, ignored fields, lifecycle behavior and the manual checklist live in
`tests/Fixtures/Imports/vnom/notes.md`.

### Joobs Indeed-style XML

The authoritative v1 specification and example are stored in
`docs/integrations/joobs/`. The example is valid XML with two ads, distinct
`referencenumber` values, and a shared `requisitionid`; map the former to
`source_reference` and do not deduplicate by the latter. The full verified field-by-field
matrix, including unsupported destinations and approval needs, is in
`docs/JOOBS_FEED_MAPPING.md`.

The generic XML reader and Vacancy description sanitizer are reused. Configured
`source_contract=joobs-1.0` validates the whole document before preview/execution, while
the normal mapper, validator, employer resolver, Vacancy upsert and verified snapshot
lifecycle handle persistence. SMV-094B tests the supplied fixture, idempotency, metadata,
exact application URLs, sanitation, configured inclusive expiry, disappearance,
reappearance and unsafe snapshots. The fixture keeps new records review-required and
configures Joobs missing advertisements for verified-only automatic offline behavior.

Live activation still requires the employer identity guarantee, closed category/product
sets and taxonomy aliases, written business-timezone confirmation, and final publication/
removal policy. Tests use the reviewed exact `company + joobs_companyurl` fallback and
`Europe/Brussels`; neither is silently inferred.

Joobs requires the entire `<source feedversion="joobs-1.0">` snapshot to represent ads
still running and says absent ads must go offline. Do not enable this source for automatic
removal until full-document parse/version/identity checks pass before any writes, empty or
filtered-empty feeds fail closed, and a per-source lock/manual override policy is tested.
Its `expirationdate` is the inclusive last paid day, not `deadline_at`; the current app
timezone is UTC. `Europe/Brussels` end-of-day converted to UTC is proposed pending partner
approval.

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

The mapping/normalization layer normalizes source salary data into the SMV compensation model.
Salary and rate may coexist. Their canonical metadata includes currency and period, while
salary additionally accepts explicit basis values `gross_fte`, `gross_offered_hours` and
`unknown`. Missing basis stays null/unknown; it is never inferred from a monthly period,
employment type, amount or description.

Recognized currency codes are uppercased and recognized period labels become `hour`, `day`,
`week`, `month` or `year`. Whole-unit positive endpoints are retained independently. Imported
zero endpoints become null with a warning; negative, fractional under the current integer
precision, and reversed ranges fail validation. Do not assume one source period/code has the
same semantics as another, and do not convert currency, assumed hours or FTE. Any existing
annual-to-monthly transform is only valid when explicitly configured from a documented source
contract; it does not establish salary basis and cannot make a row comparable by itself.

Future SMV-083 filtering will use only explicit EUR/month/`gross_fte` salaries and EUR/hour
rates. Import mapping exposes `vacancy.salary_basis`, but no source default is assumed.

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

`vacancy.description` is normalized through the same `VacancyDescription` service as
manual and Filament Vacancy writes. The canonical output permits only `p`, `br`,
`strong`, `em`, `ul`, `ol`, `li`, `h2`, `h3` and links with approved schemes. Plain-text
feeds are wrapped into paragraphs while unsafe elements, executable content, inline
styles/classes, images, embeds and tables are removed. Import preview/persistence must
not introduce a separate provider-specific HTML path.

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

A recurring feed may stop returning a previously imported Vacancy. After a verified
complete snapshot, the runner records `missing_since` and applies the ImportSource's
provider-neutral policy:

- `review_required` (default): retain the existing status for staff review;
- `auto_offline`: archive a published missing Vacancy and record `source_offlined_at`.

Neither policy deletes the Vacancy. Source attribution, import/click history and other
relations remain available. A reappearing reference clears the missing marker and keeps
the same identity. It returns to `published` only when the source itself caused the archive,
both source policies permit automatic publication, and deadline/expiry/filled safeguards
still allow it. Manual suppression remains intact.

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

### Operational limitation for large imports

Large remote imports currently execute synchronously. Background/chunked execution and
stale-run recovery are required before unattended production scheduling. Import history
may indicate that an old processing run appears to be taking longer than expected, but
the UI does not mutate or recover that run automatically.

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

## Next import work

Do not reopen SMV-030 through SMV-039 as if the pipeline were absent. SMV-092 now defines
the Joobs mapping and identity contract in `JOOBS_FEED_MAPPING.md`: advertisement identity
is `(import_source_id, referencenumber)`, requisition identity is correlation metadata,
and employer identity is an explicit source-scoped mapping to an existing Company. Follow
`IMPORT_MVP_PLAN.md`: use the completed SMV-100 and SMV-093 employer/snapshot foundations,
and use the completed SMV-094B configured transforms and persistence contract.
Provider-specific code remains a last resort for a proven
generic gap; unresolved partner value lists and identity guarantees fail closed.

### Automatic execution

Automatic imports are provider-neutral and disabled by default. An eligible source must
be active, administrator-approved, HTTP/API based, explicitly enabled and have an active
default mapping. Cadences are hourly, every six hours or daily. Uploaded files remain
manual-only.

`php artisan imports:dispatch-due` only evaluates eligibility and cadence, then dispatches
one unique `RunScheduledImport` job per source to the `imports` database queue. The job
reloads eligibility and invokes `VacancyImportRunner`; it contains no mapping or
persistence logic. Queue uniqueness prevents duplicate queued/in-flight work, while the
existing source lock protects queued and manual execution together. Other sources remain
independent.

Jobs use two attempts, a 600-second timeout and 60-second retry backoff. The existing
source lock remains 900 seconds and the database reservation becomes retryable after
960 seconds. Keep
the invariant `job timeout < source lock < queue retry_after`. Failed runs are recorded
before the job requests a retry. Retries still use provider-scoped upserts and verified
snapshot safety, so they do not duplicate Vacancies or reconcile unsafe absence.

Laravel schedules due dispatch and this bounded worker once per minute:

```text
php artisan queue:work --queue=imports --stop-when-empty --max-time=600 --timeout=600 --tries=2
```

Production invokes `php artisan schedule:run` once per minute with the deployed PHP
binary. `QUEUE_CONNECTION=database` and `DB_QUEUE_RETRY_AFTER=960` are required; no daemon
is assumed. Verify cron interval/output, CLI path, execution/RAM limits, database/cache
availability and source ownership before enabling automation. Joobs is not enabled by
this infrastructure and retains all documented activation gates.

### Monitoring and recovery

Source health is derived from the existing Import history; there is no parallel incident
or run table. The health model distinguishes disabled and manual-only sources, never-run,
active processing, stale/incomplete processing, overdue schedules, consecutive failures,
lock skips and healthy runs. A scheduled source becomes overdue when its last actual
execution attempt (not merely `last_scheduled_at`) exceeds its configured cadence plus a
25% tolerance, with a minimum tolerance of 15 minutes. Two consecutive failed or
incomplete runs are the MVP repeated-failure threshold. Processing older than 15 minutes
is shown as incomplete/stale because the bounded job should have ended within 10 minutes.

Run history records nullable `execution_origin` as `manual` or `scheduled`; historical
rows without provenance remain readable as unknown. Counts distinguish read, applied
(`imported_rows + updated_rows`) and rejected (`skipped_rows + failed_rows`) records.
Where two verified complete snapshots exist, the source overview warns when the newest
count is at least 50% lower and the previous baseline was at least 10 records. This is
visibility only: it never changes a completed outcome or adds a new reconciliation rule.
Source-specific expected-count enforcement remains an activation decision.

Administrators may start a fresh run from the ImportSource view using its active default
mapping. This calls `VacancyImportRunner`, records manual provenance and retains the same
source lock, provider-scoped upsert identity and snapshot-safety checks. It never resumes
partial writes or deletes data. Endpoint display strips query and fragment data; normal
admin history exposes only allowlisted log context and safe outcome codes.

No import email notification is sent yet. The repository has mail transport support but
no dedicated import-operations recipient or escalation owner. `CONTACT_MAIL_TO` is not an
operations mailbox and is deliberately not reused. Configure and approve a dedicated
destination/owner before a later alert-delivery ticket. Until then the Filament overview
and `IMPORT_OPERATIONS.md` checks are the operational notification boundary.

### Verified source snapshots

Missing reporting is opt-in per ImportSource through verified snapshot reconciliation.
The runner acquires a shared cache/database lock for the source, reads and preflights every
selected record, then applies records. Only a snapshot with unique non-empty references,
successful mapping/validation/employer resolution and successful application can mark
absent imported Vacancies with `missing_since`. Empty input fails closed unless the source
has an explicit `allow_empty` capability. Unsafe, partial, malformed, unresolved and failed
runs are retained in Import history with an outcome code and never reconcile omissions.

SMV-107 keeps reconciliation opt-in and adds policy only after that safety boundary.
`review_required` preserves the previous status; `auto_offline` archives published missing
records without deletion. Reappearance clears source absence, but only a recorded
source-driven archive can be restored automatically. Import run metadata records the
publication and missing policies used for the run.

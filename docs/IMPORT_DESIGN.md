# SMV Import Subsystem Design

## Status and scope

This is the authoritative SMV-030 design for implementing SMV-031 through SMV-039.
It is based on the current Laravel foundation and the provisional VNOM, Michael Page
and Orange Career / 8vance feed examples. It does not implement an importer, select a
provider adapter, or require schema changes by itself.

Those three feeds are representative validation examples only. They do not constrain
the core architecture to their exact nesting, identifiers, arrays, field names,
taxonomy terminology, compensation structures or irrelevant/additional fields. Future
sources remain configurable through record discovery, selection, mapping and narrowly
scoped transformations.

The design is reusable within SMV, but is deliberately not a universal ETL product or
a standalone import SaaS. It optimizes for vacancy feeds, a clear Filament mapping
workflow, safe reruns and commercially useful manual spreadsheet imports.


## Current foundation audit

### What exists and can be retained

| Item | Current state | Design decision |
| --- | --- | --- |
| `ImportSource` | soft-deletable provider entity with encrypted `credentials`, `endpoint_url`, active flag and legacy `default_mapping` JSON | Retain as the provider/configuration root. Extend it in SMV-031; do not use its current `type` as both transport and format. |
| `Import` | belongs to an `ImportSource`, has timestamps, counters, mapping snapshot and run status | Retain as the import-run entity and table. Use **Import run** in admin/UI copy; do not rename the historical table or model in the first implementation. |
| `ImportLog` | run-level `info`/`warning`/`error`, message and JSON context | Retain for operational/run events. It is not sufficient for record-level preview, validation or retry outcomes. |
| `Vacancy` provenance | nullable `import_source_id`, nullable `source_reference`, provider-scoped unique key | Authoritative persistence identity. Manual vacancies remain outside this identity. |
| `Company` | first-class, media-library-backed entity | Reuse for resolution and optional explicitly configured creation. |
| `Category` and Tags | five canonical Vacancy Category types and Spatie Tags | Reuse for controlled taxonomy and flexible tags. |
| queues | database queue is the configured default | Use for production runs and expensive parsing after the synchronous sample/preview path is proven. |

SMV-031 provides ImportSource configuration and SMV-032 provides readers, source
records, selection, field discovery and a guarded fetch boundary. Mapping, import job,
persistence pipeline and preview UI remain future work.

### Corrections and additions required later

`ImportSource.type` and `Import.type` currently use `ImportType`, whose values mix
formats (`csv`, `xml`, `json`) with acquisition concepts (`api`, `manual`). This is
not suitable once a JSON API, uploaded CSV or remote XLSX are all valid combinations.

SMV-031 should introduce separate enums and columns:

```text
ImportTransport: upload | http | api
ImportFormat:    json | xml | csv | xlsx
```

`ImportType` should be retired from new code only after the new fields have been
backfilled. Do not alter past migrations. `source` on `imports` remains legacy
compatibility data and must never be used as provider identity.

The initial schema additions should be new migrations, not edits to the existing
foundation migrations. See [Proposed schema](#proposed-schema) for the smallest
coherent set.

## Architecture

Filament is the operational configuration and mapping interface. It must call the
same application services as a future CLI, scheduler or queued job; it is not the
import engine and must not become the place where source-specific logic lives.

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
                    Source acquisition
                          |
                        Reader
                          |
                    SourceRecord stream
                          |
              Record path + selection rules
                          |
                   Field discovery/sample
                          |
                    Mapping profile
                          |
                 NormalizedVacancyData
                          |
          Domain validation + Company/Taxonomy resolution
                          |
                    Vacancy upsert
                          |
              Import record outcomes + run report
```

The reader emits source records, not Eloquent models. JSON, XML, CSV and XLSX must
converge before mapping, normalization and persistence.

## Transport and format design

### Import source configuration

`ImportSource` should own durable provider-level configuration:

| Concern | Upload | HTTP | API |
| --- | --- | --- | --- |
| `transport` | `upload` | `http` | `api` |
| `format` | json/xml/csv/xlsx | json/xml/csv/xlsx | normally json or xml |
| endpoint | none | required HTTPS URL | required HTTPS URL |
| file | stored on the individual Import run | none | none |
| credentials | normally none | optional | optional encrypted credentials |
| headers | normally none | optional non-secret config | optional, with secrets in encrypted credentials |
| schedule readiness | manual | eligible later | eligible later |

Suggested source fields are `company_id`, `transport`, `format`, `endpoint_url`,
encrypted `credentials`, non-secret JSON `configuration`, `record_path`,
`selection_rules`, `active_mapping_id`, `is_active`, `approved_at`, `approved_by` and
`last_imported_at`. A remote source may run automatically only when both active and
approved. Uploaded files belong to an individual run, because two uploads for the same
source are distinct inputs. Store a private disk/path, original display name, detected
MIME type, byte size and SHA-256 on the run; never store an arbitrary user filename as
a filesystem path.

`configuration` is for non-secret settings such as request timeout, headers without
secrets, CSV delimiter/encoding and selected worksheet. Authentication tokens, basic
passwords and API keys stay only in the encrypted `credentials` field. SFTP is
explicitly out of the initial enum and UI.

## Reader and source-record boundary

### Proposed contracts

The exact namespaces can be chosen in SMV-032. The required responsibilities are:

```php
interface SourceReader
{
    /** @return iterable<SourceRecord> */
    public function read(SourceInput $input, ReaderOptions $options): iterable;
}

final readonly class SourceRecord
{
    public function __construct(
        public int|string $position,
        public array $data,
        public array $meta = [],
    ) {}
}
```

`SourceInput` describes one safely acquired local input (private upload path or a
downloaded temporary file), never a raw user URL. `ReaderOptions` holds format-specific
but non-provider-specific options such as encoding, delimiter, sheet and record path.
`SourceRecord::data` is an associative nested array; `meta` can include XML node path,
spreadsheet sheet/row, or parser diagnostics. It must not include credentials.

Proposed readers:

- `JsonReader` — native JSON decoding for bounded inputs; later a streaming strategy
  for very large array feeds.
- `XmlReader` — `XMLReader`-based streaming extraction of one configured record node;
  do not load unbounded partner XML entirely into memory.
- `CsvReader` — streaming tabular reader with explicit delimiter/encoding/header
  handling.
- `SpreadsheetReader` — streaming XLSX reader, one selected sheet and a header row.

Do not force XML/JSON through a spreadsheet abstraction. CSV can share a small tabular
header-normalization utility with `SpreadsheetReader`, but retains a dedicated reader
because it has different encoding and delimiter failure modes.

### Parsing trade-offs

For MVP-sized, trusted samples, `json_decode` is practical and makes field discovery
easy. Production JSON readers should reject oversized inputs before decoding and move
to a streaming decoder if actual feeds justify it. `SimpleXML`/DOM are convenient for
small samples but are not the production default for unbounded XML; use `XMLReader`
with external entity and network access disabled. The reader should extract each
configured `<job>` node and normalize that node to an array before it reaches mapping.

## Record path, discovery and selection

### Record path

Use a stable, format-neutral dot path with `*` for the repeated record segment:

```text
job.*                  # root XML/JSON sequence where each item is a job
response.jobs.*        # nested JSON
feed.vacancies.*       # nested XML normalized to a sequence
sheet:Vacatures.*      # tabular worksheet, if a sheet selector is needed
```

The physical XML implementation may translate the configured path into an XMLReader
node matcher rather than treating this syntax as XPath. The UI can auto-suggest paths
from a bounded sample, but an administrator must confirm or override the result.
Validation must prove that the path produces at least one record and that each item is
an object/associative row, not a scalar.

### Field paths

Within `SourceRecord::data`, use dot notation and `*` for repeated values:

```text
salary.min
company.name
location.text
description.role
description.bulletPoints.*
primary_contact_person.email
```

A discovery result should contain path, value kind (`string`, `number`, `date`,
`array`, `null`), presence count and a small redacted sample set. Missing values map to
`null`; repeated paths resolve to ordered arrays rather than implicit comma-joined
strings. A transform decides whether to join, map or reject them.

### Record selection

Selection happens after record extraction and before mapping. It is a per-source,
reusable ordered set of simple rules:

```text
path: function                 operator: contains_any   values: [Sales, Marketing]
path: sector.term              operator: contains_any   values: [Sales, Marketing]
```

Initial operators: `equals`, `not_equals`, `contains`, `contains_any`, `exists`,
`in` and groups `all`/`any`. Values are case-insensitive strings unless a typed
transform is explicitly configured. This covers the current WP All Import XPath rules
without exposing XPath or creating a universal query language. XML-specific XPath may
be retained only as a migration aid, not the primary mapping UI abstraction.

## Mapping model and destination registry

### Proposed mapping schema

One active mapping profile per source is enough for the first implementation, but the
schema permits saved alternatives without versioning:

```text
import_mappings
  id, import_source_id, name, is_active, configuration, timestamps, soft_deletes

import_mapping_fields
  id, import_mapping_id, destination_key, operation, source_paths,
  default_value, transform, options, position, timestamps
```

`source_paths`, `transform` and `options` are JSON because their exact content differs
by destination. `operation` is a constrained enum: `map`, `default`, `combine`,
`taxonomy`, `tags`, `source_reference`, `ignore`. In practice, `ignore` may live only
in discovery/UI state; it need not create a row unless preserving an explicit choice is
valuable. Do not add mapping versioning yet.

`ImportSource.default_mapping` is legacy prototype configuration. New code should use
`ImportMapping`; SMV-033 can offer a one-time migration path only if real data exists.

### Destination-field registry

SMV-033 should define a PHP registry/value-object list, not Filament strings scattered
across components. Each entry contains a stable key, Dutch label, value shape,
allowed operations, optional validator and resolver type.

| Key | Shape / operations | Current destination |
| --- | --- | --- |
| `source_reference` | string; map/default | `vacancies.source_reference` |
| `vacancy.title` | string; map/default | required Vacancy title |
| `vacancy.description` | html/text; map/combine/default | required description after sanitization |
| `vacancy.location` | string; map/default | `vacancies.location` |
| `vacancy.published_at` | date; map/default | `vacancies.published_at` |
| `vacancy.deadline_at` / `expires_at` | date; map/default | current lifecycle fields |
| `vacancy.application_url` / `application_email` | URL/email; map/default | destination selected by application mode |
| `vacancy.application_mode` | enum; default/map | `external`, `email`, `internal` |
| `vacancy.salary_min` / `salary_max` | integer money; map/transform | current salary fields |
| `vacancy.salary_currency` / `salary_period` | string/enum; map/transform | later Vacancy schema fields |
| `vacancy.rate_min` / `rate_max` | integer money; map/transform | current rate fields |
| `vacancy.rate_currency` / `rate_period` | string/enum; map/transform | later Vacancy schema fields |
| `company.external_id` | string; map | reserved for a future explicit employer model, not an MVP resolver |
| `company.name` | string; map | Company resolution/creation input |
| `company.website`, `email`, `phone`, `logo_url` | URL/email/string; map | optional Company enrichment inputs |
| `taxonomy.employment_type`, `workplace`, `sector`, `function_area`, `experience` | value/list; taxonomy transform | canonical Category types |
| `tags` | list; tags transform | Spatie Tags |

Unsupported source fields should remain discoverable but cannot be selected as a
destination until a later domain decision adds a registry entry.

## Normalized vacancy boundary

`NormalizedVacancyData` is a format-independent, non-Eloquent DTO produced after
mapping and transforms but before resolving/persisting models. A concrete proposed
shape is:

```text
NormalizedVacancyData
  source_reference: string|null
  source_url: string|null
  vacancy:
    title, description, location, published_at, deadline_at, expires_at,
    application_mode, application_url, application_email,
    salary_min, salary_max, salary_currency, salary_period,
    rate_min, rate_max, rate_currency, rate_period
  compensation_meta:
    salary_source_text, salary_interpretation_status,
    rate_source_text, rate_interpretation_status
  company:
    external_id, name, website, email, phone, logo_url
  taxonomy_values:
    employment_type[], workplace[], sector[], function_area[], experience[]
  tags: string[]
  provenance:
    import_source_id, record_position, original_reference_candidates
  warnings: NormalizationWarning[]
```

It intentionally carries source compensation metadata even though the current Vacancy
schema does not. That allows preview/reporting to say a value was ignored or ambiguous
without silently losing the reason. Persistence only maps fields supported by the
current schema and approved resolution outcome.

### Feed comparison

| Feed | Direct / transformed support | Taxonomy / resolution | MVP gap or defer |
| --- | --- | --- | --- |
| VNOM XML | title, city, description, apply URL, date, `identifier`, salary text after parsing | `function` → function area; explicit aliases for experience; `Loondienst` is not inferred as Fulltime | education and owner are not targets; salary period/currency metadata not persisted |
| Michael Page XML | title, published, URL, structured salary, location text, combined description | `sector.term` → function area; `industry.term` → sector; Company normally resolves to source/provider because employer is empty | salary period `3` must remain unknown until provider documentation verifies it; consultant is not Company |
| 8vance JSON (provisional) | title, city, company contact, website, logo URL, apply URL, structured salary | numeric experience needs explicit transform/alias policy | working-hours range is a candidate field; address/coordinates/contact person deferred |
| CSV/XLSX | header paths map to the same registry | same taxonomy/company resolver | sheet/header/delimiter/encoding configuration only |

### Domain-gap decisions

- **Working hours minimum/maximum:** commercially useful but no current Vacancy fields
  or public UX. Do not add in SMV-030; validate whether feeds and presentation need it
  before a separate migration.
- **Currency and compensation period:** SMV supports salary and rate independently.
  The current schema has `salary_min`, `salary_max`, `rate_min` and `rate_max`, but is
  missing `salary_currency`, `salary_period`, `rate_currency` and `rate_period`.
  Add those four fields in the later persistence/domain migration; do not discard this
  information in preview or normalized data in the meantime.
- **Detailed address and coordinates:** not required for the public location filter;
  defer address, latitude and longitude.
- **Numeric experience range:** map only through an explicit source alias/range rule to
  existing categorical experience. Do not invent a numeric schema now.
- **Source contact person and education:** preserve only in source/preview diagnostics
  unless a concrete public/admin requirement emerges; do not overload Company owner or
  Vacancy fields.

## Salary and description normalization

### Salary

Use a dedicated, small `CompensationNormalizer` transform family rather than bespoke
logic in readers. It accepts raw source fields plus explicit mapping options and emits
salary and rate values independently, with metadata and warnings. Salary and rate are
not mutually exclusive: a source may provide either one or both, and both are retained.

Canonical compensation fields are:

```text
salary_min, salary_max, salary_currency, salary_period
rate_min,   rate_max,   rate_currency,   rate_period
```

Canonical periods include `hour`, `day`, `week`, `month` and `year`. Annual salary may
safely normalize to monthly salary by dividing by 12; monthly salary remains monthly.
Hourly, daily and weekly compensation must never be converted to monthly amounts using
assumed working hours. Rate values always retain their own period semantics. An
ambiguous source period remains a warning/unresolved mapping rather than a guess.

| Feed | Input | Handling |
| --- | --- | --- |
| VNOM | `€3200 - €3800 Month` | Parse currency symbol, numeric bounds and textual period; map as salary only when configured. |
| Michael Page | `min`, `max`, `currency`, `period`, `show` | Use bounds if `show` permits. Keep period code unresolved until provider evidence defines it; never guess that `3` means monthly or annual. |
| 8vance | `salary_low`, `salary_high`, `salary_type`, `salary_currency` | Map numeric bounds and normalized period once source values are verified. |

The first MVP should treat amounts as whole numbers, retain original values in record
diagnostics, reject malformed ranges and warn on absent/ambiguous period/currency.
Do not compare or sort values across unverified periods. Salary versus rate is chosen
by explicit mapping configuration, never inferred from a number alone; one does not
replace or discard the other.

### Description composition

Support one `combine` operation with an ordered list of source paths, a separator and
an allowed block wrapper. For Michael Page this can combine:

```text
description.role
description.bulletPoints.*
description.candidate
description.deal
```

The transform drops empty segments, preserves bullet lists as a list, and produces one
sanitized Vacancy description. This is intentionally not a general template language.
`description.company` is a vacancy-description segment unless a distinct Company
profile policy is explicitly configured; it must not silently overwrite a Company.

## Company and media resolution

### Resolution order

For the MVP, the feed provider is the owning Company for imported Vacancies unless a
future source explicitly contains a different employer model that SMV chooses to
support. SMV does not infer hidden end-clients behind recruitment/intermediary feeds.

1. Resolve the Company configured for the `ImportSource`.
2. Create that feed-owner Company when an approved new ImportSource explicitly allows
   it, then retain the source-to-Company association for deterministic later runs.
3. Use that Company for every Vacancy imported from the source.

For example, a VNOM feed resolves to Company **VNOM**, and VNOM owns the imported
Vacancies. Michael Page follows the same source-owner direction while its employer
field is empty. A future explicit employer model may add source-scoped external Company
mapping; it is not an MVP prerequisite and must not use fuzzy or AI matching.

Feed-specific direction:

- **VNOM:** Company VNOM is the feed owner; the record `company` value does not cause
  creation or inference of a hidden end-client.
- **Michael Page:** the source/brand is the Company owner; a consultant is not the
  owning Company.
- **8vance:** the configured source owner remains the Company unless later business
  support explicitly introduces an employer model.

When initially creating a feed-owner Company, available profile, contact and media data
may populate missing fields. On recurring imports, preserve existing manually
maintained logo, description, contact, social and profile data. The default update
policy fills safe missing values only; it never blindly overwrites an existing Company.
A future explicit source policy may permit authoritative updates, but that is not the
default.

### Remote logos

`company.logo_url` may be used when initially creating/populating a feed-owner Company,
through a later controlled media step. It must validate the URL after DNS/IP checks,
enforce redirects, time/size/content-type limits, hash downloaded content and use Media
Library's `logo` collection. It must not re-download or replace an existing Company
logo on every run, use the legacy `companies.logo` path as its primary target, or expose
arbitrary remote URLs publicly.

## Taxonomy and tag resolution

`Category` remains authoritative for exactly these Vacancy-facing types:

```text
employment_type, workplace, sector, function_area, experience
```

Use a source-scoped `import_taxonomy_mappings` table: import source, Category type,
normalized source key, nullable Category ID, display value and timestamps. A null
Category is an explicit unresolved mapping, not permission to create a category during
a production run. Free descriptive values map through the `tags` registry operation to
Spatie Tags.

Examples:

```text
VNOM function                 -> taxonomy.function_area
Michael Page sector.term      -> taxonomy.function_area
Michael Page industry.term    -> taxonomy.sector
"hybrid_working"              -> workplace: hybride
"Full Time"                   -> employment_type: fulltime
```

`Loondienst` is a contract/employment relationship label, not proof of Fulltime. It
must stay unresolved until an explicit source mapping says otherwise. Preview exposes
unresolved taxonomy values and blocks a record only when the source configuration marks
that destination required.

## Identity and upsert

The only imported Vacancy identity is:

```text
import_source_id + source_reference
```

Recommended candidate order per feed:

| Feed | Preferred reference | Reason / fallback |
| --- | --- | --- |
| VNOM | `identifier` | Looks globally stable and opaque. Preserve `referencenumber` as display/reference metadata. Verify stability against two real feed snapshots before activation. |
| Michael Page | `uniqueJobID` | Provider-specific stable-looking identifier; retain `ref` and `id` diagnostics. Verify lifecycle/change semantics with a real sample. |
| 8vance | verified provider vacancy ID | The supplied provisional fields contain no suitable identity. Do not use title + Company automatically. Block production configuration until a stable ID is confirmed or an explicit fallback policy is approved. |

An upsert finds only a non-deleted Vacancy by this pair, updates permitted imported
fields, and sets `VacancySource::Import`. It never turns a manual Vacancy into an
imported one. Deletion/absence from a feed is not a delete instruction.

## Preview, validation and reporting

### Preview result

Preview is side-effect free and returns at least this per selected sample record:

```text
record position and source reference candidates
redacted source values and selected paths
destination mapping operations
NormalizedVacancyData
Company resolution
taxonomy/tag resolutions
warnings and errors
can_import
proposed action: create | update | skip | blocked
```

No Vacancy, Company, Category, Tag or Media row is written by preview. A production
run snapshots the active mapping configuration so later mapping changes do not make a
historical report ambiguous.

### Error taxonomy

| Class | Meaning | Typical handling |
| --- | --- | --- |
| source/reader error | inaccessible source, invalid encoding, malformed XML/JSON | fail source/run; log safe diagnostic |
| mapping error | missing required path or invalid mapping configuration | block preview/run until configuration changes |
| normalization warning | optional value absent, salary period unknown | display; record can continue if supported fields are valid |
| domain validation error | required title/description/source reference invalid | record fails without stopping unrelated records |
| unresolved Company/taxonomy | required resolver result missing | block or skip according to source policy; never auto-guess |
| persistence error | constraint, database or media failure | record fails, retains safe diagnostic and allows rerun |

`ImportLog` remains the chronological run event log. `import_records` is needed for
per-record outcomes, source position, source reference, action, normalized payload,
warnings/errors and timestamps. Do not store full raw payloads or secrets by default;
retain selected/redacted fields and hashes sufficient to diagnose a failure.

### Run counters and missing records

An `Import` run records `read_rows`, `selected_rows`, `imported_rows`, `updated_rows`,
`skipped_rows`, `failed_rows`, start/end time, input metadata and status. Existing
`total_rows` can represent read rows only if renamed/documented consistently; a new
`selected_rows` and `skipped_rows` are needed to make selection visible. `ImportStatus`
needs at least `pending`, `processing`, `completed`, `completed_with_errors`, `failed`
and `cancelled` if cancellation is implemented.

After a successful recurring run, SMV-038 can compare source references selected in
that run with active imported Vacancies for that source. Report missing references; do
not automatically soft-delete, fill, expire or close a Vacancy. Any later closure
policy must be source-configured, previewable and explicitly approved.

## Queue architecture

| Work | Initial execution | Later queue decision |
| --- | --- | --- |
| source configuration validation | synchronous | stays synchronous |
| bounded sample fetch/read/discovery | synchronous with strict limits | queue only if source latency requires it |
| preview of a bounded sample | synchronous | queue only for expensive spreadsheet/XML samples |
| production fetch/read/upsert | queued database job | required for non-trivial feeds/uploads |
| run summaries/notifications | after persistence | queued after commit |

Jobs receive an Import run ID, acquire a source/run lock, update counters
transactionally in batches and are idempotent at the source-reference upsert boundary.
The current database queue is sufficient for this first implementation; no queue code
is created by SMV-030.

## Security requirements

| Risk | Required mitigation |
| --- | --- |
| SSRF | Admin-only source configuration, unsafe-scheme restriction, HTTPS preference, localhost/private/link-local/reserved IP blocking after DNS resolution and every redirect, short timeouts, response-size limits and no user-controlled internal URLs |
| XML XXE | `XMLReader`, disable external entities/network access, reject DTD/entity expansion, size/time limits |
| hostile upload/XLSX/ZIP bomb | private storage, extension/MIME/signature checks, ZIP entry/expanded-size limits, row/cell limits, timeouts and virus scanning if hosting provides it |
| CSV formula injection | never render unescaped cells as spreadsheet exports; prefix/escape formula-leading values in any later export |
| unsafe HTML | sanitize imported descriptions before storage/rendering with an explicit allowlist; do not trust feed HTML |
| credentials | encrypted storage, redact from logs/previews/exceptions, never return to Filament clients, rotate without historic disclosure |
| remote logo | apply SSRF controls plus content/image validation, hashes and Media Library storage |
| authorization | policies restrict source/mapping/run actions to authorized administrative roles; importing remains unavailable to employer/candidate roles |
| retries | retry safe transport failures only; do not retry validation/data errors blindly or duplicate writes |

There is no hardcoded provider whitelist. A production remote `ImportSource` is
admin-configured and may execute automatically only when explicitly active and
approved. Credentials and secrets must never leak through redirects, logs, previews or
exception messages.

## Spreadsheet dependency recommendation

`openspout/openspout` 4.32 is already present transitively through Filament Actions
and its lock metadata supports PHP 8.3. SMV-032 uses it for streaming XLSX rows after
an API spike against the committed fixture. CSV uses the already-installed
`league/csv` reader. Production deployment must still verify `zip`, `xmlreader`,
`libxml`, `dom`, `fileinfo` and suitable encoding support.

Do not add `maatwebsite/excel`/PhpSpreadsheet by default. Laravel Excel is a broader
Laravel wrapper around PhpSpreadsheet and may be justified only if real spreadsheet
requirements need its richer cell/style/formula facilities. SMV needs streaming row
input, not spreadsheet authoring. Keep `CsvReader` separate even if both readers share
header normalization.

## Proposed schema

These are implementation proposals for later migrations, not SMV-030 changes:

```text
import_sources (extend)
  company_id, transport, format, configuration, record_path, selection_rules,
  active_mapping_id, approved_at, approved_by

imports (extend; remains the run entity)
  transport, format, input_disk, input_path, original_filename, input_sha256,
  input_size, read_rows, selected_rows, skipped_rows, mapping_snapshot, error_summary

import_mappings
  import_source_id, name, is_active, configuration

import_mapping_fields
  import_mapping_id, destination_key, operation, source_paths, default_value,
  transform, options, position

import_taxonomy_mappings
  import_source_id, category_type, source_key, source_value, category_id nullable

import_records
  import_id, position, source_reference, action, status, source_digest,
  source_excerpt, normalized_data, warnings, errors, vacancy_id nullable
```

Add unique constraints appropriate to mappings: source plus normalized external/company
key, source plus Category type plus source key, and run plus record position. Make
historical foreign keys restrictive/nulling consistently with the existing retention
policy; record outcomes and logs must remain inspectable after ordinary operational
soft deletion.

## Filament workflow

The eventual UI should compose the existing Filament panel conventions and remain
visually understandable for the September presentation:

```text
Import Sources
    ↓
Source configuration (transport + format + access)
    ↓
Test/sample fetch
    ↓
Record path detection and selection rules
    ↓
Field discovery
    ↓
Mapping and Company/taxonomy aliases
    ↓
Normalized preview
    ↓
Validate and run
    ↓
History, record outcomes and safe rerun
```

The UI must show raw sample, mapping operation, normalized destination value and
resolution outcome side by side. It should not expose raw credentials, whole unbounded
payloads or a generic transformation-language editor.

## SMV-031 to SMV-039 implementation plan

### SMV-031 — Import sources

- **Goal:** introduce source transport/format configuration and safe source inputs.
- **Models/services:** new transport/format enums, ImportSource/Import migrations and
  casts, source input storage/access validator, policies and minimal Filament source
  resource.
- **Acceptance:** upload, HTTP and API configuration validate independently of format;
  secrets are encrypted/redacted; no source uses legacy `imports.source` as identity.
- **Tests:** casts, authorization, invalid transport/format combinations, private upload
  metadata and credential redaction.
- **Depends on:** SMV-030.

### SMV-032 — Readers, records and discovery

- **Goal:** read bounded JSON/XML/CSV/XLSX samples into `SourceRecord` and discover
  paths/record shapes.
- **Models/services:** source input acquisition, four readers, record-path validator,
  field-discovery service; evaluate/use existing OpenSpout only after API spike.
- **Acceptance:** all three sample feed shapes plus a CSV/XLSX fixture produce the
  same record representation; XML is streaming and XXE-safe.
- **Tests:** nested/repeated paths, delimiter/encoding cases, malformed formats, size
  limits and no parser-specific mapping logic.
- **Depends on:** SMV-031.

### SMV-033 — Mapping backend

- **Goal:** persist reusable source mappings and normalize registry-backed fields.
- **Models/services:** mapping tables/models, destination registry, mapping executor,
  combine and compensation transforms, Company/taxonomy alias stores.
- **Acceptance:** direct/default/combine/taxonomy/source-reference operations are
  reusable and source-specific aliases are not in Category.
- **Tests:** VNOM salary text, Michael Page description combination, 8vance nested
  values, invalid mapping and registry restrictions.
- **Depends on:** SMV-031, SMV-032.

### SMV-034 — Filament mapping interface

- **Goal:** make source configuration, discovery, mapping and aliases understandable
  in Filament without putting pipeline logic in pages.
- **Models/services:** Filament resources/pages calling SMV-032/033 services.
- **Acceptance:** admin can select discovered fields, supported destinations and
  transforms, save an active mapping and configure selection/aliases.
- **Tests:** policy access, persisted mapping state and service integration; no brittle
  component-markup assertions.
- **Depends on:** SMV-033.

### SMV-035 — Normalized preview

- **Goal:** display side-effect-free normalized records and proposed actions.
- **Models/services:** `NormalizedVacancyData`, resolution services, preview result
  objects and bounded preview action.
- **Acceptance:** raw/mapped/normalized/resolved/warning/error values display for all
  sample feeds; preview writes no domain data.
- **Tests:** preview is non-persistent, unresolved results and create/update/skip
  decisions.
- **Depends on:** SMV-033, SMV-034.

### SMV-036 — Validation and failure reporting

- **Goal:** persist understandable run and record outcomes.
- **Models/services:** `import_records`, expanded run statuses/counters, structured
  error taxonomy and reporting views.
- **Acceptance:** reader/mapping/domain/resolution/persistence failures remain
  distinguishable and safely redacted.
- **Tests:** each error class, counter accuracy, log/record retention and redaction.
- **Depends on:** SMV-035.

### SMV-037 — Persistence and provider-scoped upsert

- **Goal:** queue-safe format-independent upsert of normalized records.
- **Models/services:** persistence action, Company/taxonomy resolver integration,
  transaction/batching boundary and run lock.
- **Acceptance:** provider-scoped identity updates the correct Vacancy; manual
  vacancies are untouched; mapped Categories/Tags persist; no automatic missing-record
  deletion.
- **Tests:** VNOM/Michael Page identity candidates, duplicate prevention, update vs
  create, unresolved policies and idempotent rerun.
- **Depends on:** SMV-036.

### SMV-038 — Run history and reruns

- **Goal:** complete run history, safe reruns and missing-record reporting.
- **Models/services:** queued run orchestration, history/report UI, input/mapping
  snapshot access and missing-record comparer.
- **Acceptance:** counters/outcomes are auditable; rerun uses a snapshot; missing
  references are reported only, not auto-deleted.
- **Tests:** interrupted/failed runs, rerun idempotency, queue locking and missing
  record reporting.
- **Depends on:** SMV-037.

### SMV-039 — First real partner configuration

- **Goal:** validate the generic pipeline with one production-quality source.
- **Models/services:** source-specific configuration, saved selection/mapping/aliases
  only; no bespoke reader branch unless a documented format gap proves necessary.
- **Acceptance:** real sample preview, approval, import/rerun and failure report are
  demonstrated against the selected partner.
- **Tests:** anonymized fixtures/snapshots, source identity stability and regression
  cases from the real feed.
- **Depends on:** SMV-038 and business confirmation of a partner/feed.

## Open business questions

The following require source documentation, real sample snapshots or business input
before production configuration:

1. Is VNOM `identifier` stable across job updates and reposts, and what should happen
   when it changes while `referencenumber` stays stable?
2. What does Michael Page salary `period = 3` mean, and does `show = 1` reliably
   authorize public salary display?
3. Which stable vacancy and Company identifiers does the final 8vance feed expose?
4. Which future sources, if any, need an explicitly supported employer model instead
   of the established feed-owner Company policy?
5. Which remote feed sizes/cadences does hosting support, and what bounded operational
   limits should automatic runs enforce?

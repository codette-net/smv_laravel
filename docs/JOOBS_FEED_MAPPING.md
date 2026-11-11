# Joobs Feed v1 Mapping and Identity Contract

**Specification status:** fields and meanings below were checked against the actual
partner files at `docs/integrations/joobs/joobs-job-feed-v1.md` and
`docs/integrations/joobs/joobs-job-feed-v1-example.xml`. The table is exhaustive for
the v1 standard fields and Joobs extensions listed in that specification. It does not
invent target fields for unsupported data. It records proposals separately from
verified support.

**SMV-092 status (10 November 2026):** implementation decisions are finalized below.
Unanswered partner guarantees remain explicit activation dependencies; they do not
silently become assumptions. This document is the mapping contract for SMV-100,
SMV-093 and SMV-094B, not approval to change schema or execute a live feed.

Priority: **P0** demo-safe use only; **P1** required before Joobs production onboarding;
**P2** useful later or only if commercial requirements demand it.

## Final decision summary

The field matrix uses these implementation classes:

- **Supported:** the generic reader/mapper/domain already has the required target and
  behavior, although SMV-094B still needs end-to-end Joobs mapping tests.
- **SMV-100 (implemented):** reviewed source-scoped Company resolution and the minimal
  provider-neutral source metadata/location schema.
- **SMV-093/094B:** feed lifecycle, provider transform, validation or persistence work.
- **Partner decision:** SMV has a fail-closed default, but Joobs must confirm a guarantee
  or provide a value list before activation.
- **Deferred:** safely ignored for the first live feed; no invented target.

Final identity and presentation decisions:

1. `ImportSource` identifies one configured provider endpoint/feed and is the namespace
   for every external identifier. It is not a Company and does not identify an employer.
2. A Joobs advertisement is one `<job>` identified by the exact opaque pair
   `(import_source_id, referencenumber)`. This is also the existing Vacancy upsert key.
3. `requisitionid` correlates multiple advertisements for one Joobs vacancy. It must be
   preserved as source metadata but never used as the Vacancy upsert/deduplication key.
4. `Vacancy` remains the public advertisement record. A separate Advertisement model is
   not justified: the existing provider-scoped unique key and lifecycle already represent
   one source advertisement per Vacancy.
5. `vacancies.location` continues to contain the advertised city/display location and
   remains the location used by current listing search/filter options. Actual workplace
   city/country is separate nullable source metadata; detail pages and JobPosting location
   should prefer actual workplace when present and fall back to `location` for legacy rows.
6. Exact source values need bounded, provider-neutral storage. The smallest reusable
   SMV-100 schema is nullable Vacancy columns `source_requisition_id`,
   `advertised_state`, `advertised_country`, `advertised_postal_code`, `workplace_city`,
   `workplace_country`, `source_salary_text`, `source_language` and
   `source_product_code`. Do not store an arbitrary feed payload or Joobs-prefixed
   columns. SMV-094B owns population and transformations.

## Envelope and every v1 field

All element content is CDATA in the example. Values are UTF-8. Empty values are omitted.
Fields marked “yes” by the spec are required even though the two-city sample omits
optional `streetaddress` and uses different `referencenumber` / `url` values per city.

The current mapper omits absent elements from normalized preview. At persistence, the
runner still writes `null` for any mapped vacancy destination whose source element is
omitted. This means an optional mapped value such as `salary` can clear a previous
normalized salary on update; there is no source-field ownership/override or raw-record
history. Decide whether omission means “clear because the employer stopped publishing
it” (reasonable for source-owned fields) or “leave the editor value unchanged”, then
test it. Do not present omitted values as preserved today.

| XML field | Meaning / requirement | Candidate Laravel destination | Existing support | Required transformation and validation | Gap / priority |
| --- | --- | --- | --- | --- | --- |
| `/source` root | XML document root | Import payload only | SMV-094A `JoobsFeedContract` verifies one `source` root and well-formed XML before returning records | Keep provider contract validation before mapping or snapshot eligibility | Supported contract; SMV-093 must integrate it with run state |
| `/source/@feedversion` | Version; v1 is `joobs-1.0`. Within v1 optional fields may be added; breaking changes use a new version | No persisted target | SMV-094A requires exact `joobs-1.0` | Unknown/missing version fails the feed closed | Supported contract; SMV-093 integration |
| `/source/publisher` | Feed publisher, sample `Joobs` | `ImportSource.name` is configured independently | Source name exists; parser can read root field but runner does not use it | Optional contract check against configured provider; do not create Companies | P2 |
| `/source/publisherurl` | Publisher site URL | No target; optionally source metadata | No dedicated destination | Validate HTTPS only if retained; not a vacancy/application URL | Missing; P2 |
| `/source/job` | One published advertisement; record path | One Vacancy advertisement | XML reader supports `source.job`, CDATA and raw values; Joobs contract rejects duplicate references | Require complete document validation before snapshot eligibility | Parsing supported; snapshot lifecycle is SMV-093 |
| `job/title` | Required job title only; no location, salary or contract type | `vacancies.title` | Destination + nonempty/max 255 validation | Trim; validate required, UTF-8 and <=255; do not append city/salary/type | Supported; P1 |
| `job/date` | Required publication timestamp, ISO 8601 | `vacancies.published_at` | Destination and generic date transform exist | Parse explicit timezone/offset; sample `Z` is UTC; reject invalid/non-offset ambiguous timestamps | Supported generally; Joobs contract tests missing; P1 |
| `job/referencenumber` | Required stable unique ad ID; order + vacancy + city | `vacancies.source_reference`; unique by `import_source_id + source_reference` | Upsert and unique DB index support provider-scoped identity | Trim; preserve as opaque string and case/leading zeros; require nonempty, <=255, unique within the feed | Supported identity; add duplicate-key preflight; P1 |
| `job/requisitionid` | Required Joobs vacancy ID; shared across city ads | Allowlisted source metadata `source_requisition_id` | Parser/contract and nullable persistence target exist | Preserve exact opaque value; never use for Vacancy uniqueness or collapse city ads | SMV-094B population |
| `job/url` | Required Joobs job/application page, includes UTM parameters; candidate applies on Joobs | `vacancies.application_url`; `application_mode=external` | Contract and generic validator require a valid URL; fixture proves exact query preservation | SMV-094B must require HTTPS/approved host policy and persist the original URL byte-for-byte; never rewrite/append UTM | Mapping supported; provider policy/test in SMV-094B |
| `job/company` | Required hiring employer name, not Joobs publisher | Company resolution to `vacancies.company_id` | `ImportSource.company_id` is required and assigned to every Vacancy; `company.name` mapping is preview-only and runner ignores it | Resolve per record to an approved Company; never overwrite its profile from this text | Multi-employer mismatch; see Company section; P1 |
| `job/city` | Required place where the advertisement is shown | `vacancies.location` (advertised display/search location) | Location destination and exact-match public filter exist | Trim and preserve city as the current `location`; do not replace it with workplace city | Supported mapping; SMV-094B test |
| `job/state` | Required region/state/province for advertising location | `vacancies.advertised_state` | Nullable destination and generic mapping key exist | Preserve the trimmed literal; do not infer or normalize province aliases | SMV-094B mapping |
| `job/country` | Required advertising country ISO 3166 code; allowed examples BE/NL/LU/FR/DE | `vacancies.advertised_country` | Nullable destination and uppercase two-letter validation exist | Require uppercase ISO 3166-1 alpha-2; invalid values block the record/feed and remain visible in failure diagnostics | SMV-094B mapping |
| `job/postalcode` | Required advertising postal code | `vacancies.advertised_postal_code` | Nullable destination and generic mapping key exist | Preserve the trimmed string, including leading zeros; no numeric cast | SMV-094B mapping |
| `job/streetaddress` | Optional workplace street and number, when known | No target | Not supported | Preserve only if product/public display requires it; validate length; consider privacy and avoid putting it in public city filter | Missing; P2 |
| `job/description` | Required complete description; permitted simple HTML tags `p, br, h2, h3, ul, ol, li, strong, em, a` | `vacancies.description` | Import mapper + Vacancy save use `VacancyDescription` sanitizer; policy allows these tags and approved link schemes | Decode CDATA as HTML, sanitize at normalization/persistence, reject unsafe URLs/scripts/attributes; preserve semantic tags allowed by policy | Strong fit; test actual fixture and all allowed/forbidden forms; P1 |
| `job/salary` | Optional free text, sample `€3800 - €4600 per month` | Allowlisted `source_salary_text`; optionally normalized salary fields | Raw-text destination exists; generic parser can derive amounts/currency/period but no basis | Always preserve exact text. Normalize only an unambiguous integer range/currency/period; leave basis null/unknown and non-comparable. Unsafe locale formats remain raw only | Safe transform/tests in SMV-094B |
| `job/education` | Optional: Professional certificate, Bachelors, Masters, PhD | `taxonomy.qualification` | Type-scoped Category alias/exact resolver exists | Map only via Joobs-approved aliases to existing qualification Categories; unknown values unresolved, no auto-create | Value aliases and example-specific tests needed; P1 |
| `job/jobtype` | Optional: `fulltime`, `parttime`, or both comma-separated | `taxonomy.employment_type` | Type-scoped resolver exists; values/categories may be represented as arrays but comma splitting is not a registered transform | Split on comma, trim, normalize case, map each token to an existing Category; invalid token blocks or warns per approved policy | Split/normalization missing; confirm both values means two categories; P1 |
| `job/category` | Required job field in English; closed list supplied on request; comma-separated when several | Candidate `taxonomy.function_area` or `taxonomy.sector` | Both Category types exist; explicit aliases by source/type; no fuzzy match or auto-create | Obtain the actual closed list and owner-approved destination for each code/value; split comma values; do not infer sector/function based on English label | Partner must provide closed list; mapping decision required; P1 |
| `job/experience` | Optional range vocabulary (six listed bands) | `taxonomy.experience` | Category resolver exists | Map exact source values to approved Categories; do not infer from description text | Aliases/Category data coverage needed; P1 |
| `job/expirationdate` | Required final paid runtime day, `YYYY-MM-DD`, inclusive | `vacancies.expires_at` | Expiry destination and public query cutoff exist; generic date transform parses to instant | Interpret as end of that calendar day in approved business timezone, then convert to UTC. It is not `deadline_at` | Current UTC date parse would place midnight at start of day and expire too early; explicit timezone/date-only transform needed; P1 |
| `job/remotetype` | Optional only when not fully on-site: `Hybrid remote` or `Fully remote` | `taxonomy.workplace` | Category resolver exists | Map exact values to approved hybrid/remote Categories. Since spec omits field for fully on-site, absence may mean on-site only if partner confirms omission semantics | No default/transform for taxonomy; explicit onsite semantics confirmation; P1 |
| `job/joobs_language` | Required ad language: `nl`, `fr`, `en`, `de` | Allowlisted `source_language` metadata | Nullable destination and generic mapping key exist | Validate exact allowed code and preserve; do not map to Category/site locale | Joobs allowed-list validation in SMV-094B; filtering deferred |
| `job/joobs_contracttype` | Optional: permanent, temporary, freelance, internship, student | `taxonomy.employment_type` candidate, but not necessarily equivalent to jobtype | Existing controlled employment type Categories and resolver; no independent contract-type destination | Keep contract kind semantically separate from work schedule (`jobtype`); map only values with approved taxonomy meaning. Avoid conflating temporary contract and part-time | Distinct concept is a domain gap; decide if MVP needs a separate Category type/field; P1 if needed |
| `job/joobs_workplacecity` | Required real workplace city; may differ from advertising `city` | `vacancies.workplace_city` | Nullable destination, mapping key and public detail/JobPosting behavior exist | Preserve independently from ad city; do not overwrite `vacancies.location` or convert to a tag | SMV-094B mapping |
| `job/joobs_workplacecountry` | Required real workplace country | `vacancies.workplace_country` | Nullable destination and uppercase two-letter validation exist | Apply the same uppercase ISO 3166-1 alpha-2 validation independently from advertising country | SMV-094B mapping |
| `job/joobs_product` | Required purchased product formula/code, agreed with partner | Allowlisted `source_product_code`; never `placement_package` | Nullable destination and generic mapping key exist | Preserve opaque code exactly. Validate against partner list only after supplied; unknown code blocks activation/import for review | Allowed list is a partner decision; commercial mapping deferred |
| `job/joobs_logo` | Optional HTTPS employer logo URL | No automatic target; Company has Media Library logo and legacy `logo` string | `company.logo_url` is preview destination only; importer does not update Company or fetch media | Validate HTTPS/host/content type/size; do not hotlink or server-fetch arbitrary URL without SSRF-safe downloader, approval and attribution rules | No safe media ingestion; do not import automatically in MVP; P2 |
| `job/joobs_companyurl` | Required employer page on Joobs | No current source-company profile URL field; not `Company.website` | Company has `website`, but runner does not write company fields | Preserve as provider employer reference only; do not label as employer's own website or overwrite a manually managed Company website | Target semantic mismatch; source-company reference optional future field; P2 |

## Example verification and multi-city identity

The supplied XML parses as UTF-8 XML with `<source feedversion="joobs-1.0">`, two
`<job>` records and two distinct `referencenumber` values. Both records share
`requisitionid=k7Qd2XvP`. The first ad is advertised in Antwerpen, BE; the second in
Breda, NL. Both carry workplace Antwerpen, BE. This confirms that ad reference, ad
location, and actual workplace are distinct concepts. The example omits optional
`streetaddress`, as allowed.

Use the provider-scoped unique key `(import_source_id, referencenumber)`. Store
`requisitionid` separately if product/reporting needs it; it must not deduplicate the
two city advertisements. The current importer can upsert those two records if the
mapping and Company resolution produce two rows, but its current fixed Company
association prevents a multi-employer feed.

### SMV-094A parser contract verification

The supplied example is now copied into the test-fixture boundary and exercised through
the existing `XmlReader`. `JoobsFeedContract` adds only provider-level document checks
that the generic reader intentionally does not own: root `source`, version `joobs-1.0`,
required `referencenumber`/`requisitionid`, unique advertisement references and a valid
HTTP(S) application URL. It returns in-memory `SourceRecord` values and does not invoke
mapping, persistence validation or the import runner.

The contract verifies both advertisements, their shared requisition, different advertising
cities, actual Antwerpen workplace, exact UTM-tagged URLs, CDATA HTML description, exact
free-text salary and available `joobs_product`. Optional salary, education, job type,
experience, remote type, contract type and logo fields may be absent. HTML and salary are
preserved only at the parsing boundary: publication sanitization remains the existing
Vacancy domain boundary, and safe raw-value persistence remains a later ticket.

Negative coverage rejects malformed XML, missing advertisement identifiers, duplicate
`referencenumber`, unsupported feed versions, and missing/invalid application URLs. This
does not yet validate every required Joobs business field, prove whole-snapshot safety,
map products, resolve Companies, normalize dates/salary/taxonomy, or write any records.

### Identifier failure and change rules

- A missing/blank `referencenumber` or `requisitionid` invalidates that record and makes
  the snapshot ineligible for absence reconciliation. SMV-094A already rejects these at
  the contract boundary.
- A duplicate `referencenumber` invalidates the whole feed before writes. Do not pick the
  first/last record and do not reconcile absence from the remaining set.
- Identical `referencenumber` values in different ImportSources are independent identities
  and may coexist. The existing unique database key supports this.
- If Joobs changes a `referencenumber`, SMV must treat it as a new advertisement. The old
  key may become missing only after a verified complete snapshot; there is no safe rename
  inference. Joobs should confirm that references remain stable for the paid run.
- If `requisitionid` changes while `referencenumber` stays the same, update the correlation
  metadata and flag the change in the run log for review; do not create a new Vacancy.
- Case, punctuation and leading zeros are significant in both identifiers. Store the
  trimmed opaque string; do not lowercase, numeric-cast or otherwise normalize it.

## Company association and reseller feeds

### Verified repository behavior

`ImportSource.company_id` is required in the Filament form and the runner assigns this
one Company to every imported Vacancy. `company.name`, `company.website`, and
`company.logo_url` can appear in mapping/preview destinations, but persistence ignores
all company destinations. `Company` is a first-class, soft-deletable model with owner,
active/pending status, profile fields and single-file Media Library logo. Manually
managed Company fields must not be overwritten by a partner feed.

### Smallest safe proposal

- **Single-employer source:** retain the configured `ImportSource.company_id` behavior.
  A source should be approved only after its Company and vacancy ownership meaning are
  confirmed.
- **Multi-employer/reseller source:** add an explicit per-source employer mapping from
  an agreed stable provider employer ID to an existing Company. The Joobs v1 fields do
  not contain a stable employer ID. Ask Joobs whether `joobs_companyurl` is stable and
  unique or request an opaque employer ID; the specification only says it is a Joobs
  page URL, so do not assume it is a durable ID.
- If there is no stable ID, allow an administrator to explicitly map a normalized exact
  source name + source-company URL pair to a chosen Company. This is a manual alias,
  not automatic name matching. Detect collisions/renames and require review. Never use
  fuzzy matching, casing/punctuation similarity, or a source-level default Company as
  fallback for an unmatched multi-employer record.
- For no/ambiguous company mapping, skip/quarantine the record and report the source
  values. Do not publish it under Joobs or another arbitrary Company. A new imported
  Company should be created only after an explicit reviewed workflow; default it to
  pending and do not expose its profile until staff review.
- Import may attach a Vacancy to a manually maintained Company, but must never write
  name, website, logo, description, status, owner, categories or media. Company profile
  edits and employer ownership remain in their existing admin/account flows.
- Do not fetch/hotlink `joobs_logo` as part of the first Joobs integration. Logo ingest
  needs a constrained downloader, file validation, provenance and approval policy.
  `joobs_companyurl` is a Joobs employer page, not an authoritative company website.

### SMV-100 employer-resolution contract — implemented

Use a reusable source-scoped mapping entity rather than adding Joobs conditionals to the
runner. Its minimum responsibility is:

- namespace every external employer key by `import_source_id`;
- store the exact external key plus last-seen source name/source URL for review;
- point to one existing `company_id` and record review/audit state;
- enforce one mapping for each `(import_source_id, external_employer_key)`;
- resolve before Vacancy persistence; missing, inactive, soft-deleted or ambiguous mappings
  quarantine the record and make the snapshot ineligible for absence reconciliation;
- never update Company name, slug, owner, status, website, descriptions, categories,
  contacts, logo or media from the feed.

The provider-neutral SMV-100 entity should therefore be an `ImportSourceEmployer` (or
equivalent) with `import_source_id`, `company_id`, nullable opaque
`external_employer_id`, exact `source_name`, nullable exact `source_url`, review status
and audit timestamps/actor. Enforce source plus external ID uniqueness when an ID exists;
otherwise enforce the reviewed source/name/URL alias as a unique tuple. Resolution uses
one explicit key path and must not silently fall through from a failed ID lookup to an
alias lookup.

The Joobs v1 specification supplies no explicit stable employer ID. Preferred activation
input is a new opaque Joobs employer ID. If Joobs cannot provide one, the safe fallback is
an administrator-created source-scoped alias based on the exact pair `company` plus
`joobs_companyurl`. The pair is an external lookup key only after review; it is not a
partner-guaranteed identity. Name-only, URL-only, fuzzy, normalized-global and automatic
Company creation are prohibited. The same real employer arriving from another ImportSource
gets a separate external mapping that may deliberately point to the same Company.

New Company creation is not required for the first Joobs feed. If later approved, it must
be an explicit staff action that creates a pending Company, then attaches the source mapping.
The import must not create a public Company implicitly.

Implementation note: multi-employer mode is an explicit ImportSource configuration.
Provider-ID lookups never fall through to composite aliases. Missing/inactive/unmapped
identities skip the record, prevent that run from performing missing detection, and never
use the ImportSource default Company. Sources without this mode retain legacy behavior.

The spec identifies each `company` as the hiring employer and a partner feed contains
multiple customers' ordered ads in one feed, so multi-employer support appears required
for Joobs production unless the delivered endpoint is separately scoped per employer.
That scope needs partner confirmation. It does not block the staging demo unless Joobs
is explicitly part of the demo acceptance scope.

## Advertised location and actual workplace contract

- `city` is the advertised city and maps to the existing `vacancies.location`. Current
  public location search/filter options remain exact matches against this field, so the
  Breda advertisement remains discoverable under Breda even though the workplace is
  Antwerpen.
- `state`, `country` and `postalcode` are structured advertised-location metadata. Do not
  concatenate them into `location` for the first integration because that would change
  existing filter values and behavior.
- `joobs_workplacecity` and `joobs_workplacecountry` are the actual workplace. SMV-100
  should add nullable structured storage independent of the advertised fields. Do not
  create a second Vacancy or a taxonomy tag for workplace data.
- Vacancy detail and JobPosting location should prefer actual workplace when present.
  Cards/listing filters continue to use advertised `location`. If the two differ, the
  detail UI must label the advertised city and actual workplace clearly rather than imply
  that the employer is located in the advertised city.
- Existing/manual Vacancies with no separate workplace metadata continue to display and
  expose `vacancies.location`; no backfill or guessed country/province is allowed.
- Multiple Joobs advertisements for one requisition remain multiple Vacancies, one per
  advertised city, linked only by source requisition metadata. This preserves current URLs,
  search behavior and ad-level lifecycle/reporting.

## Salary and SMV-082

SMV-082 permits `salary_min/max`, currency, period and `salary_basis` (`gross_fte`,
`gross_offered_hours`, `unknown`). The sample string can plausibly yield EUR 3,800–4,600
per month, but the feed gives no explicit gross/net or FTE basis. Therefore set period
to month only when exact source text parses unambiguously, keep basis unknown/null, and
do not let it match SMV's comparable monthly salary search (requires explicit
`gross_fte`). The current parser strips punctuation from numeric tokens, so localized
decimal/thousands formats are unsafe. Preserve the exact source salary in an import
record/source-value store before normalization; no raw-salary field exists today.

## Date and publication policy

The application timezone is UTC. `date` contains an explicit UTC `Z` in the example;
parse its offset and store UTC as `published_at`. `expirationdate` is date-only and the
last paid day, not an application deadline. Recommended rule: interpret the date through
the end of the calendar day in `Europe/Brussels` (the Joobs/Belgian business timezone),
then convert to UTC. Brussels and Amsterdam currently share DST rules, but partner
approval is needed for ads in FR/DE/LU and future timezone policy. Do not parse the date
as `00:00:00 UTC`, since that removes the final paid day. Add a boundary test around
midnight and DST transitions.

Whether a fully valid paid Joobs ad should be auto-published or remain `pending` for
moderation is a product decision. Current imports create pending records; there is no
automatic publication. Joobs removal and expiry requirements cannot be fulfilled until
the approved synchronization lifecycle is implemented.

## Feed validation and snapshot reconciliation contract

Feed validation and reconciliation are separate stages:

1. **Validate:** fetch succeeded; payload is non-truncated, well-formed `joobs-1.0`; all
   records parse; required contract fields are present; references are unique; employer,
   product/category and required mappings resolve; row selection did not unexpectedly
   remove records; expected-count safeguards pass.
2. **Apply:** upsert valid advertisements by source/reference only after preflight
   succeeds. A persistence error fails the run and cannot reconcile absence.
3. **Reconcile:** only an explicitly enabled complete verified snapshot may mark previously
   seen references absent. Absence currently sets `missing_since`; it never deletes the
   Vacancy or overwrites its editorial status/publication state.

Safe lifecycle decisions for SMV-093:

- HTTP/auth/timeout/malformed/version failures, empty payloads, empty `<source>`, duplicate
  references, unresolved employers and filtered-empty results fail closed with no absence
  action. An intentional legitimate empty feed needs an explicit operator confirmation or
  partner-provided count/status signal before it may be treated as complete.
- A missing advertisement receives `missing_since` after one verified complete snapshot.
  Public unpublishing is not implemented or implied by SMV-093; retain the row/history
  until a separately approved publication/removal policy exists.
- An advertisement whose inclusive `expires_at` has passed is already hidden by the public
  query. A later omission still records source absence; expiry and feed absence remain
  separate facts.
- A reappearing reference clears source absence. Preserve `Vacancy.status`; manually
  archived/expired records remain hidden, while a previously published source-available
  record may become visible again.
- Import-owned mapped fields may update on reappearance, but manual editorial overrides
  require an explicit field-ownership mechanism. Until implemented, mapped fields are
  source-owned and staff must not be promised durable inline overrides.

## Unresolved partner inputs and fail-closed defaults

1. Stable opaque employer ID, or written stability/uniqueness guarantee for
   `joobs_companyurl`. Default: reviewed source-scoped `company + joobs_companyurl` alias;
   no automatic matching.
2. Complete closed `category` list and intended SMV function-area/sector mapping. Default:
   unknown values remain unresolved and block the row.
3. Complete `joobs_product` codes. Default: preserve exact code and block unknown values
   from live activation; never map to SMV `placement_package`.
4. Business timezone for inclusive `expirationdate`, especially ads outside Belgium.
   Default: `Europe/Brussels`, converted to UTC, pending written confirmation.
5. Whether omitted `remotetype` guarantees fully on-site. Default: no workplace taxonomy
   is inferred from absence.
6. Whether optional-field omission means clear the previous source-owned value. Default:
   treat a complete snapshot as the current source truth, but implement and test clearing
   per mapped field in SMV-094B; never clear manual Company profile data.
7. Reference stability for the full paid run and any expected minimum/count signal for a
   legitimate empty feed. Default: changed reference is a new ad; empty feed fails closed.
8. Automatic publication versus moderation and the owner of editorial override policy.
   Default: new imported Vacancies remain `pending`.

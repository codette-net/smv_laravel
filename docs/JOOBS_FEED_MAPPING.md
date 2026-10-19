# Joobs Feed v1 Mapping Audit

**Specification status:** fields and meanings below were checked against the actual
partner files at `docs/integrations/joobs/joobs-job-feed-v1.md` and
`docs/integrations/joobs/joobs-job-feed-v1-example.xml`. The table is exhaustive for
the v1 standard fields and Joobs extensions listed in that specification. It does not
invent target fields for unsupported data. It records proposals separately from
verified support.

Priority: **P0** demo-safe use only; **P1** required before Joobs production onboarding;
**P2** useful later or only if commercial requirements demand it.

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
| `/source` root | XML document root | Import payload only | XML reader reads configured record path; no root/schema contract validation | Require exactly one `source` root, well-formed UTF-8 XML and a supported `feedversion` before records can be persisted/reconciled | Validate as Joobs contract; P1 |
| `/source/@feedversion` | Version; v1 is `joobs-1.0`. Within v1 optional fields may be added; breaking changes use a new version | No persisted target | XML reader does not expose or validate root attributes as contract metadata | Require exact supported version; unknown version fails run closed | Missing contract validation; P1 |
| `/source/publisher` | Feed publisher, sample `Joobs` | `ImportSource.name` is configured independently | Source name exists; parser can read root field but runner does not use it | Optional contract check against configured provider; do not create Companies | P2 |
| `/source/publisherurl` | Publisher site URL | No target; optionally source metadata | No dedicated destination | Validate HTTPS only if retained; not a vacancy/application URL | Missing; P2 |
| `/source/job` | One published advertisement; record path | One normalized Vacancy advertisement | XML reader supports `job` records and CDATA; generic pipeline accepts records | Configure record path `job`; require complete document parse before accepting snapshot; reject duplicate ad references | Parse supported, snapshot contract missing; P1 |
| `job/title` | Required job title only; no location, salary or contract type | `vacancies.title` | Destination + nonempty/max 255 validation | Trim; validate required, UTF-8 and <=255; do not append city/salary/type | Supported; P1 |
| `job/date` | Required publication timestamp, ISO 8601 | `vacancies.published_at` | Destination and generic date transform exist | Parse explicit timezone/offset; sample `Z` is UTC; reject invalid/non-offset ambiguous timestamps | Supported generally; Joobs contract tests missing; P1 |
| `job/referencenumber` | Required stable unique ad ID; order + vacancy + city | `vacancies.source_reference`; unique by `import_source_id + source_reference` | Upsert and unique DB index support provider-scoped identity | Trim; preserve as opaque string and case/leading zeros; require nonempty, <=255, unique within the feed | Supported identity; add duplicate-key preflight; P1 |
| `job/requisitionid` | Required Joobs vacancy ID; shared across city ads | No current column/destination; proposed `source_requisition_id` if needed | Not supported | Preserve separately; never use for Vacancy uniqueness. Two city ads remain two Vacancy rows | Schema/domain gap; confirm reporting requirement; P1 if needed, otherwise P2 |
| `job/url` | Required Joobs job/application page, includes UTM parameters; candidate applies on Joobs | `vacancies.application_url`; `application_mode=external` | Both destinations and validator exist; generic persistence preserves URL string | Require absolute HTTPS URL; preserve full path/query and every UTM byte/value; never rewrite/append UTM; test redirect-safe validation | Scheme allowlist/Joobs tests absent; no click redirect; P1 |
| `job/company` | Required hiring employer name, not Joobs publisher | Company resolution to `vacancies.company_id` | `ImportSource.company_id` is required and assigned to every Vacancy; `company.name` mapping is preview-only and runner ignores it | Resolve per record to an approved Company; never overwrite its profile from this text | Multi-employer mismatch; see Company section; P1 |
| `job/city` | Required place where the advertisement is shown | `vacancies.location` (current free-text field) | Location destination exists | Preserve advertisement city; optionally format with `state`, `postalcode`, `country` for display, without confusing it with workplace | No structured location fields; P1 |
| `job/state` | Required region/state/province for advertising location | No separate target; can be included in display text `location` | Only free-text location exists | Preserve literal source value; do not map Dutch region aliases without approved rules | Structured province absent; combine to display or defer; P1 |
| `job/country` | Required advertising country ISO 3166 code; allowed examples BE/NL/LU/FR/DE | No country destination | No country field/validation | Validate against ISO 3166-1 alpha-2; retain raw code if invalid for review, block invalid display mapping | Schema/validation gap; P1 |
| `job/postalcode` | Required advertising postal code | No separate target; possible display text | Only free-text location exists | Preserve as string including leading zeros; no numeric cast; validate length/characters per country only if approved | Structured field absent; P1 |
| `job/streetaddress` | Optional workplace street and number, when known | No target | Not supported | Preserve only if product/public display requires it; validate length; consider privacy and avoid putting it in public city filter | Missing; P2 |
| `job/description` | Required complete description; permitted simple HTML tags `p, br, h2, h3, ul, ol, li, strong, em, a` | `vacancies.description` | Import mapper + Vacancy save use `VacancyDescription` sanitizer; policy allows these tags and approved link schemes | Decode CDATA as HTML, sanitize at normalization/persistence, reject unsafe URLs/scripts/attributes; preserve semantic tags allowed by policy | Strong fit; test actual fixture and all allowed/forbidden forms; P1 |
| `job/salary` | Optional free text, sample `€3800 - €4600 per month` | `salary_min`, `salary_max`, `salary_currency`, `salary_period`; raw value has no target | Generic text parser and SMV-082 normalized fields exist | Parse only unambiguous amounts, currency and explicit period. Preserve exact raw value. Never infer salary basis from monthly period | Parser strips punctuation, does not preserve raw text or basis; sample must remain non-comparable under SMV-082 absent explicit `gross_fte`; P1 |
| `job/education` | Optional: Professional certificate, Bachelors, Masters, PhD | `taxonomy.qualification` | Type-scoped Category alias/exact resolver exists | Map only via Joobs-approved aliases to existing qualification Categories; unknown values unresolved, no auto-create | Value aliases and example-specific tests needed; P1 |
| `job/jobtype` | Optional: `fulltime`, `parttime`, or both comma-separated | `taxonomy.employment_type` | Type-scoped resolver exists; values/categories may be represented as arrays but comma splitting is not a registered transform | Split on comma, trim, normalize case, map each token to an existing Category; invalid token blocks or warns per approved policy | Split/normalization missing; confirm both values means two categories; P1 |
| `job/category` | Required job field in English; closed list supplied on request; comma-separated when several | Candidate `taxonomy.function_area` or `taxonomy.sector` | Both Category types exist; explicit aliases by source/type; no fuzzy match or auto-create | Obtain the actual closed list and owner-approved destination for each code/value; split comma values; do not infer sector/function based on English label | Partner must provide closed list; mapping decision required; P1 |
| `job/experience` | Optional range vocabulary (six listed bands) | `taxonomy.experience` | Category resolver exists | Map exact source values to approved Categories; do not infer from description text | Aliases/Category data coverage needed; P1 |
| `job/expirationdate` | Required final paid runtime day, `YYYY-MM-DD`, inclusive | `vacancies.expires_at` | Expiry destination and public query cutoff exist; generic date transform parses to instant | Interpret as end of that calendar day in approved business timezone, then convert to UTC. It is not `deadline_at` | Current UTC date parse would place midnight at start of day and expire too early; explicit timezone/date-only transform needed; P1 |
| `job/remotetype` | Optional only when not fully on-site: `Hybrid remote` or `Fully remote` | `taxonomy.workplace` | Category resolver exists | Map exact values to approved hybrid/remote Categories. Since spec omits field for fully on-site, absence may mean on-site only if partner confirms omission semantics | No default/transform for taxonomy; explicit onsite semantics confirmation; P1 |
| `job/joobs_language` | Required ad language: `nl`, `fr`, `en`, `de` | No current vacancy language field | Not supported | Validate allowed code, preserve value. Do not map to Category or confuse with site locale | New metadata only if filtering/rendering/commercial need; otherwise preserve in raw source payload; P2 |
| `job/joobs_contracttype` | Optional: permanent, temporary, freelance, internship, student | `taxonomy.employment_type` candidate, but not necessarily equivalent to jobtype | Existing controlled employment type Categories and resolver; no independent contract-type destination | Keep contract kind semantically separate from work schedule (`jobtype`); map only values with approved taxonomy meaning. Avoid conflating temporary contract and part-time | Distinct concept is a domain gap; decide if MVP needs a separate Category type/field; P1 if needed |
| `job/joobs_workplacecity` | Required real workplace city; may differ from advertising `city` | No current separate target; proposed `workplace_city` | Not supported | Preserve independently from ad city; do not overwrite `vacancies.location` or convert to a tag | Schema/display gap when locations differ; P1 |
| `job/joobs_workplacecountry` | Required real workplace country | No current separate target; proposed `workplace_country` | Not supported | Validate ISO code independently from advertising `country` | Schema/validation gap when different; P1 |
| `job/joobs_product` | Required purchased product formula/code, agreed with partner | No current target; `placement_package` is SMV-owned enum and is not equivalent | No source product destination; runner ignores it | Preserve opaque code exactly; map only to a separately agreed partner product enum/table after commercial confirmation | Commercial mapping/schema gap; P1 for partner reconciliation, not demo |
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

The spec identifies each `company` as the hiring employer and a partner feed contains
multiple customers' ordered ads in one feed, so multi-employer support appears required
for Joobs production unless the delivered endpoint is separately scoped per employer.
That scope needs partner confirmation. It does not block the staging demo unless Joobs
is explicitly part of the demo acceptance scope.

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


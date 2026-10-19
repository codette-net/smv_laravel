# Joobs Feed v1 Mapping Audit

**Status:** preliminary, based only on verified characteristics in the SMV-083 task.
The named partner specification and example XML were not present in the repository.
Attach `joobs-job-feed-v1.md` and `joobs-job-feed-v1-example.xml` before treating
this as the exhaustive specification matrix or approving a production mapping.

No field names beyond those explicitly supplied below are inferred. In particular,
the required base fields and optional Joobs extension names beyond the confirmed
examples remain unknown.

## Known XML structure and confirmed fields

Priority: **P0** means a general launch safety boundary if imports are exercised;
**P1** means needed for Joobs onboarding; **P2** means defer/optional pending business
need.

| Source XML element / attribute | Target Laravel field or relation | Required transformation | Existing support | Missing functionality / decision | Priority |
| --- | --- | --- | --- | --- | --- |
| `/source` root | Import payload | Require expected root and `feedversion="joobs-1.0"`; reject unsupported version before reconciliation | XML reader reads records by configured path but does not validate root or feedversion | Add source-contract validation before any snapshot can be trusted | P1 |
| `/source/job` | One Vacancy advertisement | Configure XML record path `job`; one record per advertisement | XML reader supports record path `job`; generic mapping handles records | Validate exact Joobs document shape and parser completion | P1 |
| `job/referencenumber` | `vacancies.source_reference` (provider-scoped key) | Required stable opaque string; trim, preserve leading zeros/case unless spec says otherwise | Generic required source-reference mapping and provider-scoped upsert implemented | Confirm uniqueness, length, case semantics from partner spec/example; current DB key scope is `import_source_id + source_reference` | P1 |
| `job/requisitionid` | No current canonical field; possibly future `requisition_reference` | Preserve separately from ad key; may repeat across cities | Not implemented as a vacancy destination | Add field only if owner confirms reporting/grouping need; never use it as upsert key because it is shared | P1 |
| `job/title` | `vacancies.title` | Required trimmed text, length <= 255 | Destination and validation supported | Actual Joobs element presence/content still requires reference-file verification | P1 |
| `job/description` | `vacancies.description` | Extract CDATA as HTML, sanitize through `VacancyDescription`; no raw executable markup | Generic description mapping and same service sanitizer used at persistence | Confirm policy preserves acceptable description structure and test hostile HTML in Joobs example | P1 |
| `job/url` | `vacancies.application_url`, with `application_mode=external` | Preserve the full URL and query string, including existing UTM parameters; validate absolute allowed scheme/host; never append duplicate UTM values | URL field, external mode, and URL-presence validation exist | Validator currently uses general `FILTER_VALIDATE_URL`; scheme allowlist, provider-host policy and redirect/click tracking are not defined. Do not rewrite Joobs UTM parameters | P1 |
| `job/city` | `vacancies.location` | Use as the advertising location exactly as documented | Location destination exists | Confirm formatting/country behavior from files; no structured city/province field | P1 |
| `job/joobs_workplacecity` | No current distinct target; candidate descriptive tag or future location field | Keep distinct from `city`; do not overwrite advertising location | No separate workplace-city destination or field | Owner must decide whether to preserve; a free-text tag is not a substitute for structured workplace address | P1 |
| `job/expirationdate` | `vacancies.expires_at` | Treat as inclusive final paid publication date; date-only value needs end-of-day in an explicitly approved timezone, stored consistently | `expires_at` destination and date transform exist; public visibility query excludes elapsed boundary | App timezone is configured as UTC (`config/app.php`); generic `date` transform uses `Carbon::parse` and does not express Joobs paid-day rule. Mapping date-only directly risks treating the final day as expired from its start | P1 |
| `job/salary` | Candidate `salary_min`, `salary_max`, `salary_currency`, `salary_period`, `salary_basis`; raw value currently has no dedicated field | Parse only unambiguous amount/range, currency, and period under an approved Joobs-specific contract; otherwise retain raw value and leave normalized values null | Generic compensation text parser exists and SMV-082 validators warn on unknown period/currency/basis | Parser strips punctuation from numbers (`3.500`/`3,5` can be ambiguous); raw salary cannot be stored in canonical fields. Add raw source value storage or preserve in safe run diagnostics before enabling ambiguous parsing. Never assume monthly or FTE basis | P1 |
| `job/joobs_product` | No current vacancy field or relationship | Preserve as partner product code if commercially required | No product-code destination | Decide whether needed for onboarding/billing; do not map to `placement_package` without a business contract | P2 |
| `job/<all remaining base fields>` | Pending | Pending exact partner specification | Cannot assess from supplied material | Full mapping cannot be completed without the attached specification and example | P1 |
| `job/joobs_<other extensions>` | Pending | Pending exact partner specification | Cannot assess from supplied material | Full mapping cannot be completed without the attached specification and example | P1 |

### Requested semantic fields whose exact XML elements are unknown

The task asks to assess these semantics, but no source elements/values for them were
included in the verified characteristics. They must remain **unmapped pending source
documentation**; do not guess their names or value vocabularies.

| Semantic concept | Candidate existing target | Existing support | Joobs decision / requirement | Priority |
| --- | --- | --- | --- | --- |
| Remote work type | `taxonomy.workplace` (`CategoryType::workplace`) | Type-scoped Category mapping exists | Need exact source element and values, then explicit Joobs aliases (remote/hybrid/on-site); do not infer from city | P1 |
| Employment/contract type | `taxonomy.employment_type` | Type-scoped Category mapping exists | Need exact fields/vocabulary; separate employment from contract if Joobs distinguishes them | P1 |
| Education | `taxonomy.qualification` | Type-scoped Category mapping exists | Need exact source field and approved aliases; unknown values should remain unresolved | P1 |
| Experience | `taxonomy.experience` | Type-scoped Category mapping exists | Need exact source field/range semantics and approved aliases; do not guess level from years | P1 |
| Sector/function/category | `taxonomy.sector`, `taxonomy.function_area` | Mapping destinations and explicit alias resolver exist | Need element definitions and business-approved taxonomy mapping; never auto-create or guess from category label | P1 |
| Company identity/name | `ImportSource.company` | Source-owned Company association exists | Must confirm provider commercial model. Runner uses one configured Company for every row; it does not company-match or create per-record Companies | P1 |

## Identity and multiple cities

The ad is the synchronizable unit. Use Joobs `referencenumber` as the provider-scoped
unique key because it identifies the advertisement. A second city ad sharing one
`requisitionid` must create/update a second Vacancy row. `requisitionid` can be saved
separately only after a decision on reporting/grouping; it cannot be a uniqueness key.

The current runner supports this identity pattern, but every imported Vacancy is
attached to `ImportSource.company_id`. It does not duplicate or match employer
Companies. Owner approval is needed if a single Joobs source represents ads for
multiple employers or if the partner Company differs from the advertiser shown on
public Vacancy cards.

## SMV-082 compensation compatibility

SMV-082 defines whole integer amounts, nullable endpoints, normalized currency and
period, and nullable `salary_basis` (`gross_fte`, `gross_offered_hours`, `unknown`).
Comparability requires explicit EUR/month/`gross_fte`; an hourly rate is a separate
rate record requiring EUR/hour. No basis is inferred from a monthly period, a value,
job type or description.

Joobs `salary` is optional free-form text. The generic parser recognizes a narrow set
of currency and period words, but it does not implement a Joobs-specific grammar or
preserve the original salary string. Punctuation stripping can misread decimal and
thousands separators. Safe MVP behavior is: retain the raw string in a source-value
field or protected import diagnostic, parse only when an unambiguous reviewed pattern
matches, store salary basis as unknown/null unless the contract explicitly supplies
it, and show unresolved values for review. No silent monthly conversion or FTE
assumption is acceptable.

## Date and URL rules to approve

- `expirationdate` means the final paid display date, while `expires_at` comparison is
  an instant (`>= now`). App timezone is currently UTC. Convert an inclusive date into
  the final instant of that date in a named, partner-approved timezone, then normalize
  storage consistently. The task does not specify the business timezone; owner/partner
  confirmation is required.
- Keep `url` byte-for-byte as a URL string after safe validation so its existing UTM
  parameters survive. Do not copy incoming Joobs tracking parameters onto SMV pages
  or assume they prove clicks/conversions.
- For external destinations, require an absolute HTTPS URL unless business-approved
  partners establish otherwise. Protect against non-web schemes and malformed URLs.
  The existing generic URL check does not document a Joobs host allowlist.

## Acceptance coverage required before activation

Automated tests should use the actual example XML when supplied and assert:

1. Valid two-city XML creates two ad rows with the same requisition id and distinct
   referencenumbers.
2. Repeated import is idempotent by source + referencenumber; changed source fields
   update the same rows.
3. City maps to advertising location and `joobs_workplacecity` remains distinct.
4. Description CDATA is sanitized; invalid or disallowed application URLs do not
   import; valid application URL query/UTM parameters are unchanged.
5. Expiration date uses the approved inclusive-date timezone rule and elapsed rows
   are not public.
6. Salary cases include missing, clear range, ambiguous separator, unknown currency,
   unknown period and contradictory values; original text is preserved and ambiguous
   values do not become comparable.
7. Approved taxonomy values resolve; unknown values are reported and do not create
   Categories.
8. Successfully absent ads are marked for the approved depublication policy only
   after complete-feed verification; HTTP, malformed/partial XML, invalid rows,
   concurrent runs and empty-feed cases must not cause accidental depublication.


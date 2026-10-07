# SMV MVP Discovery Extension Audit

## 1. Scope and revision

This read-only implementation audit covers salary discovery, public sector/function-area
browsing, linked taxonomy labels and relevant Vacancy blocks. It was performed on branch
`feat/playwright-test` at revision `d9ddebe`. Existing uncommitted teaser-recording changes
were left untouched. The findings distinguish verified repository behavior from proposed
product policy.

Package and entitlement decisions remain pending under SMV-050. The existing
`vacancies.is_featured` boolean is therefore the only authoritative ranking signal in this
plan; no package entitlement is inferred.

## 2. Evidence-backed current-state matrix

| Capability | Status | Verified implementation | Gap |
| --- | --- | --- | --- |
| Salary/rate storage | **Partial** | `Vacancy` casts and fills salary/rate min, max, currency and `CompensationPeriod`; migrations `2026_06_29_145304_create_vacancies_table.php` and `2026_08_21_100100_add_compensation_metadata_to_vacancies.php`. | No salary basis, working-hours metadata or comparable-range scope. |
| Salary administration | **Partial** | `VacancyForm` and `SaveVacancyPlacementRequest` accept non-negative salary bounds; employer flow enforces maximum ≥ minimum. | Filament does not edit currency/period and does not enforce bound ordering; rate metadata and basis are not administered. |
| Salary import | **Partial** | `DestinationRegistry`, `NormalizedVacancyData`, `VacancyImportRunner` and `annual_salary_to_monthly` support all eight canonical fields. | Source ambiguity remains possible; `IMPORT_DESIGN.md` still incorrectly says the four metadata columns are missing. No working-hours/basis target exists. |
| Public salary filter | **Missing** | Homepage and `/vacatures` share `VacancySearch` and `VacancyFilterOptions`. | No amount or compensation-mode parameters, controls, validation or overlap query. |
| Vacancy sector/function assignment | **Implemented** | `CategoryType::sector` and `function_area`, `categoryables`, `Vacancy::categories()`, Filament taxonomy fields and source-scoped import aliases. Unique `(type, slug)` prevents same-type collisions. | Public discovery is filter-only; hierarchy matching currently uses exact category slug. |
| Company sector assignment | **Partial** | `Company::categories()` can attach any typed Category and `VacancyTaxonomyTest` proves a sector attachment. | Company admin/public queries intentionally use only `company_category`; no sector-specific editorial UI or public presentation. |
| Vacancy taxonomy filters | **Implemented** | `VacancySearch::whereHasCategory()`, `VacancyFilterOptions::taxonomyOptions()`, homepage and listing filter components; combined filter and pagination tests exist. | Parent selection does not include descendants; no archive context. |
| Public taxonomy directory/archives | **Missing** | Blog archives demonstrate type-scoped binding, pagination canonicals and sitemap rules. | No sector/function-area routes, overview, descriptions, breadcrumbs or sitemap entries. `categories` has no description field. |
| Clickable taxonomy labels | **Partial** | Vacancy cards/detail render Category labels; Blog category/tag chips are linked. | Vacancy labels are badges/spans. Company pages expose no sector links. Card links require a non-nested-link pattern. |
| Company Vacancy block | **Partial** | `CompanyController::show()` uses the Company relation plus `Vacancy::publiclyVisible()`; `companies/show.blade.php` renders the records. | Heading differs from the requested label, there is no featured-first order or bounded result policy, and a shared component already exists but is not consistently used. |
| Vacancy related block | **Partial** | `VacancyController::show()` excludes the current Vacancy, applies public visibility and limits to three. | It only selects the same Company and orders newest; no function-area/sector fallback, featured priority or relevance score. |
| Blog related content | **Implemented/partial** | `BlogPost::vacancies()` and `companies()`, inverse relations, Filament multi-selects and public visibility filtering are implemented by SMV-061. | Vacancy selection is manual-only and newest-first. Blog sector/function-area editorial links and an explicit manual-first fallback policy are absent. |
| SEO foundation | **Implemented** | Shared metadata, clean/self pagination canonicals, filtered Vacancy `noindex, follow`, JobPosting/Organization/BlogPosting JSON-LD and public-only sitemap are tested. | Taxonomy archive URLs, archive sitemap eligibility and filtered archive policy do not yet exist. |

## 3. Reusable implementation

- `Vacancy::publiclyVisible()` remains the single active/public boundary. Every count,
  archive and related block must start from it and additionally require a publicly visible
  Company where the existing listing does so.
- `VacancySearch` is already shared by `HomeController` and `VacancyController`; salary
  parsing and category archive constraints should extend this path rather than introduce a
  second search implementation.
- `Category`, `categoryables`, `CategoryType`, parent/children and the unique `(type, slug)`
  constraint already support sector and function-area ownership on Vacancy and Company.
  No second taxonomy table is justified.
- `VacancyFilterOptions`, the homepage collapsible form and the listing filter form already
  provide type-scoped options, GET state, auto-submit, reset links and query-preserving
  pagination.
- Blog’s type-scoped route binding and archive canonical helper are proven patterns for
  public Vacancy taxonomy routes, but Blog taxonomy itself must remain isolated.
- Existing Vacancy, Company and Blog cards plus `company.vacancy-card` can be reused.
- `BlogPost::vacancies()`, `BlogPost::companies()` and both inverse relations already satisfy
  manual editorial linking. They must be extended, not recreated.
- `is_featured` is usable only as a secondary ordering signal while commercial/package
  confirmation is pending.

## 4. Gaps and documentation inconsistencies

1. `IMPORT_DESIGN.md` lines describing compensation metadata as a later schema change are
   stale: the columns and enum casts now exist.
2. Salary display prefers salary over rate in `Vacancy::compensationLabel()`, but the label
   does not currently communicate currency, period or basis. Filtering it without stricter
   comparability would be misleading.
3. Filament exposes four numeric compensation bounds but not currency/period. Employer
   placement currently only accepts salary bounds. Imported data can carry richer metadata
   than editors can verify.
4. No model field records whether monthly salary is fulltime-equivalent or represents the
   offered hours. No working-hours range exists. This is a material matching ambiguity.
5. Hierarchy is validated as same-type, but filters are exact-category only. Parent/child
   archive behavior is not implemented.
6. Company categories in current public/admin behavior mean `company_category`, not sector.
   Reusing the polymorphic relation is safe only with explicit type constraints everywhere.
7. Vacancy-detail related content is narrower than its UI label suggests. Company detail
   uses `latest()` instead of featured-first deterministic ordering.
8. Public Vacancy cards eager-load all Category types and take the first two/four without a
   deliberate label priority. Clickable sector/function labels require prepared, typed data.

## 5. Recommended data and relationship changes

### Compensation

Keep the existing eight compensation fields. Add only metadata proven necessary by the
salary-data audit. The recommended minimum is a nullable salary-basis enum such as
`full_time_equivalent`, `offered_hours`, `unknown`; imported and existing rows default to
unknown. Do not infer basis from employment type, Category names or a missing hours value.
Add working-hours fields only if source/product evidence requires them for display; they are
not required merely to build the initial comparable-range filter.

The reliable initial filter compares only:

- employment salary: EUR + month + the approved salary basis;
- freelance rate: EUR + hour.

Other currencies/periods and unknown/inconsistent metadata remain displayable but are not
matched. There is no currency conversion and no assumed hours conversion. Bonus,
commission and turnover percentage are outside the comparable fixed range and need no new
fields in this slice.

### Taxonomy

Continue using `Category` and `categoryables`. Vacancies may explicitly own both sector and
function-area Categories. Companies may explicitly own sector Categories in addition to
their existing `company_category` records. A Company sector does **not** dynamically confer
that sector on its Vacancies: Vacancy assignment remains explicit, so changing a Company
does not silently reclassify published jobs. Admin/import workflows may offer a deliberate
copy/fill action later, but not live inheritance.

Add a nullable Category description only if approved archive copy cannot be sourced
elsewhere. Never classify existing records from names alone. Existing/imported assignments
remain type-scoped; unresolved source values continue through `ImportTaxonomyMapping`.

Parent archives include their own Category and all descendants of the same type. Child
archives match only that child. Counts use distinct Vacancy IDs. The current one-level UI
can be implemented first, but query code should safely traverse the stored hierarchy rather
than assume globally unique slugs.

## 6. Proposed search and ranking rules

### Salary overlap

Recommended GET state: `vergoeding=maand|uur`, `bedrag_van`, `bedrag_tot`. This keeps two
amount inputs while making the compared period explicit. Homepage and listing use identical
parsing/query logic.

Normalize each known Vacancy range as:

- lower = `min` when present, otherwise `max`;
- upper = `max` when present, otherwise `min`.

A selected range overlaps inclusively when Vacancy upper ≥ selected minimum (if provided)
and Vacancy lower ≤ selected maximum (if provided). Therefore minimum-only, maximum-only,
equal endpoints, fixed amounts and one-sided stored ranges behave predictably. Unknown
amounts are excluded only when either amount input is active. With no amount input, existing
records remain visible regardless of compensation metadata.

Reject non-scalar/non-numeric values, negatives and minimum > maximum with Dutch validation
feedback and do not run a partially interpreted query. Zero is technically valid but may be
rejected as a product decision if it represents “unknown” in legacy data. Any filter change
naturally omits `page`; pagination uses the existing query-string preservation. “Zoeken”,
“Wis filters”, debounce and the homepage collapsible panel remain unchanged.

### Category archives

Recommended routes, defined before `/vacatures/{vacancy}`:

- `/vacatures/categorieen` — combined sector/function-area overview;
- `/vacatures/sector/{slug}`;
- `/vacatures/functiegebied/{slug}`.

Binding must constrain type before slug lookup. A wrong-type same slug and unknown slug are
404. A valid Category with zero current Vacancies returns a stable 200 empty state with
`noindex, follow` and is excluded from overview/sitemap; this avoids URL churn when supply
temporarily reaches zero.

Archive base pages are indexable with unique metadata, breadcrumb/internal links and a
self-canonical. Page 1 omits `?page=1`; clean page 2+ self-canonicalizes with only `?page=N`.
Additional search/filter parameters retain the archive’s Category constraint but are
`noindex, follow` and canonicalize to the clean archive URL. Results and counts use the same
public query and `distinct` Vacancy identity.

### Related Vacancies

- **Company detail:** own public Vacancies only; `is_featured DESC`, `published_at DESC`,
  `id DESC`; maximum six; heading “Vacatures bij dit bedrijf”; no filler.
- **Vacancy detail:** exclude current; maximum three; relevance group 1 same Company, 2
  shared function area, 3 shared sector. A candidate belongs to its best group only. Within
  each group use featured, publication date and ID descending. Relevance always precedes
  featured. Use one bounded query or a small query object with distinct IDs and eager-loaded
  card relations.
- **Blog detail:** public manual Vacancy links come first. If fewer than three remain,
  explicitly selected Blog function-area Categories fill next, then explicitly selected
  sectors. Featured is secondary inside each relevance group. Never derive relations from
  Blog category/tag slugs. Keep manual Company links as implemented. Consider a pivot
  `sort_order` only if editors need ordering; do not require it for the first fallback slice.

All explicitly linked records are rechecked through public visibility. Expired, filled,
scheduled, draft, soft-deleted or Company-ineligible Vacancies never render. Insufficient
matches produce a smaller block or no block.

## 7. Product decisions still required

| Decision | Recommended default | Blocks |
| --- | --- | --- |
| Monthly salary basis | Filter only explicitly marked gross monthly FTE salary; leave unknown/unoffered-hours rows unmatched. | Final salary matching policy, not the data audit. |
| Currency scope | EUR only; no conversion. | Salary filter acceptance. |
| Zero amount | Treat zero as invalid input and legacy zero as unknown unless product confirms free/zero remuneration. | Salary validation detail. |
| Company sectors | Explicit Company-sector assignment; never inherited automatically by Vacancies. | Admin labels and sector-company blocks. |
| Parent archive semantics | Parent includes itself and descendants; child matches itself. | Archive counts/results. |
| Valid empty archive | 200 empty state, noindex and no sitemap. | SEO acceptance wording. |
| Blog fallback | Manual Vacancies first, explicit function area second, explicit sector third; limit three. | Blog related selection. |
| Company block limit | Six with CTA to the Company-filtered Vacancy listing. | Presentation only. |

Package confirmation does not block these rules because `is_featured` is already persisted.
It must remain only an ordering hint and must never bypass relevance or visibility.

## 8. Dependencies and implementation order

1. **SMV-082** compensation data/basis audit and normalization contract.
2. **SMV-083** salary/rate filtering in shared search and both UIs.
3. **SMV-084** explicit sector/function-area ownership in admin/import flows.
4. **SMV-085** public taxonomy overview and archives.
5. **SMV-086** linked taxonomy labels on Vacancy/Company presentation.
6. **SMV-087** Company and Vacancy related-Vacancy ranking.
7. **SMV-088** Blog explicit taxonomy fallback while retaining manual relations.
8. **SMV-089** cross-feature SEO, sitemap, query-performance and regression verification.

SMV-082 can proceed immediately as a data-contract task while the basis decision is
confirmed; it must not backfill ambiguous values. SMV-087 is independent of package work
and can also proceed after its limits/copy are accepted.

## 9. Focused validation plan

- Salary: minimum-only, maximum-only, inclusive/equal boundaries, fixed and one-bound
  Vacancies, null values, salary versus rate, currency/period/basis mismatches, negative and
  malformed input, reversed bounds, combined filters, page reset and pagination state.
- Homepage: results remain on `/`, debounce and collapsible filters work, search/reset remain
  visible and state matches `/vacatures` for the same parameters.
- Taxonomy: same slug across sector/function/blog types, parent/child counts, invalid and
  valid-empty routes, distinct results, explicit Company sectors and no implicit inheritance.
- Visibility: draft, future, filled, expired, deadline-passed, soft-deleted and
  non-public-Company Vacancies are absent from counts, archives, links and related blocks.
- Ranking: same Company outranks shared function area, which outranks sector; featured only
  breaks ties inside a group; deterministic ordering and deduplication are asserted.
- Blog: existing manual Vacancy/Company relations remain, hidden manual records do not leak,
  explicit taxonomy fallback never uses Blog tag/category slug coincidence.
- SEO: archive metadata/canonical/robots, page-1 normalization, page-2 self-canonical,
  filtered archive policy, sitemap public/non-empty eligibility and breadcrumbs.
- Performance: query-count assertions or profiling for archive cards, counts, related blocks
  and sitemap; all rendered relations are eager-loaded.


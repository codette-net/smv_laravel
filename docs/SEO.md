# SMV SEO Requirements

## Principle

SEO is part of the MVP architecture and release criteria.
It must not be postponed until after routes, content and migration behavior have become difficult to change.

The public recruitment pages now have the MVP technical SEO foundation: reusable
metadata/Open Graph output, canonical URLs, environment-aware robots directives, a
dynamic public-entity sitemap and JobPosting/Organization structured data. Legacy
redirect implementation and launch verification remain release work.

## Current canonical routes

- homepage: `/`
- vacancy listing: `/vacatures`
- vacancy detail: `/vacatures/{vacancy-slug}`
- company listing: `/bedrijven`
- company detail: `/bedrijven/{company-slug}`

Vacancy and Company route binding uses stable slugs. Updating a title/name does not
regenerate an existing slug. Application destinations, import source references and
provider URLs are never canonical public URLs.

## Metadata and indexability policy

The public Blade layout provides title, description, canonical, robots and baseline
Open Graph fields. Non-production environments always output `noindex, nofollow`.

Clean listing pages and unfiltered pagination pages are indexable and self-canonical.
Vacancy search, filter and sort combinations remain usable but output `noindex, follow`
and canonicalize to `/vacatures`; no programmatic taxonomy landing pages are implied.
Application form and confirmation pages are `noindex, nofollow` and canonicalize to the
vacancy detail page.

The dynamic `/sitemap.xml` contains only the homepage, clean listing pages, publicly
visible Companies and publicly visible Vacancies belonging to public Companies. The
query is chunked. `/robots.txt` advertises the sitemap in production and blocks crawling
in non-production environments.

## Structured data

Public Vacancy detail pages output JobPosting JSON-LD from known domain data only.
Employment type is omitted because current taxonomy labels are not yet mapped safely to
Schema.org values. Salary is included only when a valid currency, period and value/range
are present; rate data is not silently presented as salary. Company pages output bounded
Organization data and only include real configured profile URLs/media.

## Public-page requirements

Important indexable pages should have, where appropriate:

- unique useful `<title>`
- meta description
- one clear page topic / heading hierarchy
- canonical URL
- appropriate index/follow behavior
- crawlable internal links
- useful copy/content
- descriptive URLs
- responsive/mobile usability
- strong performance

## Vacancy SEO

Vacancy pages should support:

- canonical URL
- suitable title/description
- company relation
- location and employment information where data exists
- correct expired-vacancy behavior
- valid JobPosting structured data where applicable

Structured data must reflect visible/current vacancy data and should not present expired or unavailable jobs as active.

Public vacancy slugs are stable after creation and should not change merely because a
vacancy title is edited. The final public route strategy remains open until SMV-040.

## Company SEO

Company pages should provide useful unique content and crawlable links to active vacancies.
Avoid thin pages when there is insufficient company information.

## Blog SEO

The simple Blog can support organic growth through useful content and internal linking.
Blog implementation should support:

- clean slugs
- metadata
- canonical URL
- categories
- published state
- internal links to relevant vacancies/companies where editorially useful

## Technical SEO

MVP/release work should account for:

- robots.txt
- XML sitemap
- canonical tags
- redirect handling
- correct 404/410 behavior where applicable
- no redirect chains
- HTTPS
- mobile usability
- Core Web Vitals/performance awareness
- image dimensions/optimization/alt text where applicable

## Internal linking

Important vacancies, companies and content should not become orphaned.
Use crawlable HTML links rather than relying only on JavaScript actions.

## Staging

Staging should be protected from indexing.
Before production launch explicitly verify that production is indexable and does not inherit staging restrictions.

## Production SEO launch checklist

- confirm `APP_ENV=production` and the canonical application URL/HTTPS host
- crawl staging while verifying it remains `noindex`
- validate representative JobPosting and Organization JSON-LD
- verify `/robots.txt` and `/sitemap.xml` on the production host
- import and test the approved legacy redirect map without chains
- compare high-value legacy pages with their Laravel destinations
- submit the production sitemap in Search Console
- crawl production immediately after cutover and monitor 404s, redirects and indexing
- monitor Search Console, analytics and important rankings after launch

Future work includes the legacy redirect inventory/implementation, Search Console and
analytics setup, redirect monitoring, taxonomy landing-page strategy, richer content and
Blog SEO, and an automated sitemap refresh strategy if dynamic generation no longer fits
production scale.

## Ahrefs-oriented checks

Use Ahrefs as an input for practical migration and SEO work, including:

- identify high-value legacy pages
- identify pages with valuable backlinks
- identify important organic landing pages/keywords where data is available
- crawl the new staging implementation before launch
- compare old/new crawl issues

Do not treat a generic SEO score as the goal; preserve real search value and fix concrete technical/content issues.

The WordPress migration is selective. Prioritize URLs with traffic, backlinks,
commercial relevance or useful evergreen content; do not reproduce every historical
page or create irrelevant blanket redirects.

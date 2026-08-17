# SMV SEO Requirements

## Principle

SEO is part of the MVP architecture and release criteria.
It must not be postponed until after routes, content and migration behavior have become difficult to change.

The repository currently has little production SEO implementation. Metadata,
canonicals, structured data, sitemap/robots behavior and redirects remain release work,
not implemented features.

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

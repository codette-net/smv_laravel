# SMV SEO Migration

## Objective

Move from the legacy WordPress/Cariera site to Laravel while preserving as much existing search equity, useful content and URL value as practical.

## Before changing public routes

Create an inventory of the current live site.

Collect where possible:

- URL
- HTTP status
- indexability
- title
- meta description
- canonical
- H1
- sitemap presence
- internal links / incoming internal links
- structured data
- organic landing-page value
- backlinks

Sources can include:

- crawl export
- Google Search Console
- GA4
- Ahrefs
- WordPress database/site exports

## Suggested repository data files

```text
docs/data/legacy-urls.csv
docs/data/redirects.csv
docs/data/top-organic-pages.csv
docs/data/top-backlinked-pages.csv
```

Do not commit exports containing secrets or unnecessary personal data.

## URL decision

Every important legacy URL should receive a deliberate action:

```text
KEEP     same URL/intent remains
301      permanent move to a close equivalent
410      intentionally removed with no replacement when appropriate
NOINDEX  retained but intentionally not indexed where justified
MERGE    content consolidated into a stronger relevant destination
```

Avoid redirecting unrelated removed content to the homepage.
Avoid redirect chains.

## Redirect mapping template

Use `docs/data/redirects.csv` with at least:

```text
legacy_url,new_url,action,reason,priority,verified
```

## Content parity

For high-value pages, compare old and new versions so migration does not accidentally remove the content/search intent responsible for ranking.

## Launch checks

Before cutover:

- crawl staging/new site
- verify canonical host/URLs
- verify robots/indexability rules
- validate important structured data
- verify sitemap URLs
- test redirects
- check internal links
- check important 404s
- test representative vacancy/company pages

After cutover:

- crawl production
- check redirect failures/chains
- verify sitemap/robots
- inspect Search Console indexing/crawl issues
- monitor important landing pages and rankings

## TODO inputs

- current live URL export
- Ahrefs exports
- Search Console top pages/queries
- current WordPress permalink/taxonomy structures
- final Laravel route strategy

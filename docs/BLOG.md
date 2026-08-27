# SMV Blog MVP

## Goal

Provide a simple content module that can be managed comfortably through Filament and used for September presentation content plus ongoing SEO/content work.

Blog is part of the MVP and is implemented as a compact native Laravel/Filament module.
Old WordPress articles are deliberately not migrated or imported.

## Delivery priority

Build after the recruitment core, import mapping and SEO foundations are stable so this
low-risk presentation/content module does not displace core delivery.

## Minimal domain

### BlogPost

Required fields/concepts:

- title
- slug
- excerpt
- content
- featured image (optional)
- publication status
- published_at
- optional featured image through the existing Spatie Media Library
- soft deletes

Posts are publicly visible only when status is `published`, `published_at` is present
and not in the future, and the record is not soft-deleted. Slugs are generated when
empty, unique, and stable after creation.

## Admin

Filament provides a straightforward interface for:

- list/search/filter posts
- create/edit posts
- set publication state/date
- upload/select a featured image through Media Library

## Public frontend

- blog index
- blog detail
- reuse existing Tailwind components
- responsive layout
- shared metadata/canonical/Open Graph output, BlogPosting JSON-LD and sitemap inclusion

Routes are `/blog` and `/blog/{blogPost-slug}`. `/` is the only homepage; `/home` is
intentionally not routed.

## Deferred follow-up

SMV-061 will cover Blog taxonomies and editorial relations to relevant Companies and
Vacancies. It may add categories, tags and explicit editorial relationships, but must
not become a recommendation engine.

## Explicitly out of scope

- comments
- complex multi-author workflow
- review/approval workflow
- page builder
- custom block-editor platform
- newsletter automation
- categories, tags and editorial Company/Vacancy relations (SMV-061)
- author pages

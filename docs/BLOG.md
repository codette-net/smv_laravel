# SMV Blog MVP

## Goal

Provide a simple content module that can be managed comfortably through Filament and used for September presentation content plus ongoing SEO/content work.

## Delivery priority

Build after the recruitment core, import mapping and SEO foundations are stable.

## Minimal domain

### Post

Expected fields/concepts:

- title
- slug
- excerpt
- content
- featured image (optional)
- publication status
- published_at
- blog category relation
- SEO metadata or shared SEO mechanism

### Category

- name
- slug

## Admin

Filament should provide a straightforward interface for:

- list/search/filter posts
- create/edit posts
- select category
- set publication state/date
- upload/select featured image if implemented
- edit SEO metadata if not derived/shared elsewhere

## Public frontend

- blog index
- category filtering/navigation if inexpensive
- blog detail
- reuse existing Tailwind components
- responsive layout
- SEO metadata/canonical

## Optional lightweight integration

If simple and useful, allow editorial links/relations to relevant companies or vacancies. Do not create a recommendation engine for MVP.

## Explicitly out of scope

- comments
- complex multi-author workflow
- review/approval workflow
- page builder
- custom block-editor platform
- newsletter automation

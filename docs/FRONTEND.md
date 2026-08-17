# SMV Frontend

## Principle

Do not redesign the MVP from scratch.

The public frontend is based primarily on the existing Mosaic Tailwind template,
existing Blade conversions of that template, and selected job-board HTML templates.

The goal is to reuse and adapt the existing visual system to implement the MVP quickly
and consistently.

## Priority

reuse existing component
    ↓
adapt existing component
    ↓
compose existing components
    ↓
extract reusable component from an existing template/page
    ↓
create new component only when necessary

Do not introduce a second design system.

## Frontend stack

Public frontend:

- Laravel Blade
- Tailwind CSS
- Alpine.js for lightweight interaction
- Vite
- Mosaic-derived Blade components/templates

The current frontend is only partially implemented.
Some layouts/pages are prototypes or template conversions rather than completed
application flows.

Blade `x-*` components are a preferred production pattern. Existing demo routes, route
names, layout wiring and prototype page architecture are not authoritative. Preserve
useful Blade components, Mosaic markup and assets, but do not preserve broken demo
architecture merely because it currently exists. `x-app-layout` may be adapted or
reused where it helps establish the canonical public layout.

The public website is Dutch. Navigation, forms, validation-facing labels,
vacancy/company UI, blog UI and SEO-facing copy should be Dutch. Internal code and
developer documentation may remain English.

Codex must inspect existing implementation before extending it.

## Important repository conventions

### `resources/views/components`

Contains reusable Blade components.

General UI components include forms, buttons, dropdowns, pagination, modals,
navigation and other Mosaic-derived UI primitives.

Notable application components currently include:

```text
components/app/header.blade.php
components/app/sidebar.blade.php

components/job/job-list.blade.php
components/job/job-sidebar.blade.php
```

Prefer extending these components where appropriate rather than duplicating them.

### `resources/views/pages/component`

Files ending in `-page.blade.php` are Mosaic component showcase/demo pages.

Examples include:

```text
accordion-page.blade.php
alert-page.blade.php
badge-page.blade.php
button-page.blade.php
dropdown-page.blade.php
form-page.blade.php
modal-page.blade.php
pagination-page.blade.php
tabs-page.blade.php
tooltip-page.blade.php
```

These are not necessarily production pages.

They should be treated as a component catalogue.

Codex may inspect these files to:

* locate useful markup
* identify existing Tailwind patterns
* extract reusable components
* keep styling consistent

Do not wire these demo pages directly into public MVP routes unless explicitly useful.

### Existing job pages

The repository currently contains job-related Blade files in:

```text
resources/views/pages/job/
    company-profile.blade.php
    job-listing.blade.php
    job-post.blade.php
```

and:

```text
resources/views/vacatures/
    company-profile.blade.php
    job-listing.blade.php
    job-post.blade.php
```

These are early/prototype implementations.

Before implementing public vacancy or company pages:

1. inspect both sets
2. determine which implementation is the newest/useful basis
3. identify duplicate markup
4. extract reusable components where useful
5. avoid maintaining two competing versions

Do not delete either set during initial discovery unless their relationship is understood.

### HTML job-board templates

Additional source templates exist under:

```text
resources/job_board_templates/
    index.html
    job-post.html
    post-a-job.html
    signin.html
```

These may be used as source material for:

* vacancy listing
* vacancy detail
* employer vacancy submission
* authentication-related flows

Reuse useful Tailwind markup and interaction patterns.

Do not copy entire template pages blindly.
Integrate relevant sections into the application's Blade layouts and components.

## Layouts

Current Blade layouts include:

```text
layouts/app.blade.php
layouts/authentication.blade.php
layouts/empty.blade.php
layouts/guest.blade.php
layouts/public.blade.php
```

The layout/controller/view flow is not yet considered final.

Some missing wiring or adaptation may be required to make the Mosaic-derived layout
work properly with Laravel routes/controllers.

Codex may improve this integration.

Establish one coherent public layout from the useful existing patterns. Do not create a
parallel architecture merely to avoid adapting existing components, and do not treat
the current layout wiring as a contract when it is incomplete or broken.

## Existing public views

Current top-level views include:

```text
home.blade.php
welcome.blade.php
```

These are existing experiments/prototypes and are not authoritative production pages.

## CSS

Current CSS includes:

```text
resources/css/app.css

resources/css/template/style.css

resources/css/template/additional-styles/
    range-slider.css
    theme.css
    toggle-switch.css
    utility-patterns.css

resources/css/template/vendors/
    aos.css
```

Before creating new utility CSS:

* inspect existing styles
* prefer Tailwind utilities
* reuse existing template styles where appropriate

Avoid broad CSS rewrites during MVP development.

## Images and visual assets

Template, brand and selected legacy images are stored under:

```text
resources/images/
```

This is the preferred location for frontend source assets that are processed through
the project's Vite build.

Use semantic existing filenames where possible.

Template assets include, among others:

```text
company-bg.jpg
profile-bg.jpg
hero-illustration.svg
auth-image.jpg
onboarding-image.jpg
meetup-image.jpg
testimonial-*.jpg
company-icon-*.svg
```

The complete directory should be inspected before selecting or duplicating artwork.

### Asset rules

Prefer assets in this order:

1. real SMV brand assets
2. appropriate legacy SMV imagery
3. appropriate existing template imagery
4. new placeholder only when no suitable asset exists

Do not invent arbitrary remote placeholder image services.

Do not rename large numbers of existing template assets without a functional reason.

Where an asset has a known license/source requirement, preserve that information.

## Static public files

Assets intended to be processed and versioned by Vite should remain under
`resources/`.

Files that must exist at a stable public URL without Vite processing may live in
`public/`, for example where applicable:

* favicon/static browser files
* robots.txt
* manifest files
* specifically required public SEO assets

Do not move assets between `resources/` and `public/` without checking how they are
referenced.

## Key public MVP pages

Prioritize a coherent production-quality experience for:

1. homepage
2. vacancy listing/search
3. vacancy detail
4. company detail
5. application flow
6. employer vacancy submission where included
7. company listing if useful
8. blog index/detail late in MVP

## Vacancy frontend rule

Manually created and imported vacancies must use exactly the same application models
and public rendering components.

The frontend must not care whether a vacancy originated from:

* Filament/manual entry
* JSON import
* XML import
* legacy migration
* later external API integration

Source-specific presentation is only allowed when genuinely required by source data.

## Component extraction strategy

When implementing a page:

1. inspect Mosaic demo pages
2. inspect existing application components
3. inspect corresponding existing SMV prototype views
4. inspect the job-board HTML template if relevant
5. compose the page from the best existing patterns
6. extract repeating markup into Blade components

Avoid premature component abstraction for one-off markup.

## Accessibility

When adapting template code:

* retain semantic HTML
* use proper form labels
* preserve keyboard accessibility
* maintain visible focus states
* use buttons for actions and links for navigation
* retain meaningful alt text
* ensure interactive Alpine elements expose appropriate state where necessary

Template markup may be improved where required for accessibility.

## Responsive behavior

The MVP must work well on:

* desktop
* tablet
* mobile

Do not sacrifice responsive behavior merely to reproduce a template screenshot.

## Next frontend implementation inventory

Before changing the frontend, Codex should create a short inventory containing:

* current layout relationships
* duplicated vacancy views
* reusable job components
* useful Mosaic component demo patterns
* useful sections from `job_board_templates`
* currently working routes/pages
* broken/missing layout wiring
* recommended canonical vacancy/company views

The repository-level inventory is complete; each public-flow task should still confirm
its canonical view and reusable components before replacing prototype wiring.

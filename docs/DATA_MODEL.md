# SMV Data Model

## Purpose

Document the intended domain model and known legacy WordPress fields. Exact current Laravel tables/columns must be verified from migrations and models before schema work.

## Primary domains

Expected domains:

- users
- companies
- vacancies
- applications
- categories / taxonomies
- packages
- orders / payments
- imports
- blog posts / blog categories

## Known legacy vacancy fields

Legacy WordPress metadata previously identified includes:

```text
_job_title
_job_description
_job_location
_job_reference
_job_deadline
_job_expires
_job_listing_featured
_job_cover_image
_application
_apply_link
_company_select
_salary_min
_salary_max
_rate_min
_rate_max
_filled
_featured
```

Potential normalized Laravel concepts include:

```text
_job_location       -> vacancies.location
_salary_min         -> vacancies.salary_min
_salary_max         -> vacancies.salary_max
_job_expires        -> vacancies.expires_at
_job_reference      -> vacancies.external_reference/reference
_apply_link         -> application destination / URL
_company_select     -> company relationship
```

These mappings are guidance, not authorization to rename existing working columns without repository inspection.

## Known legacy company fields

```text
_company_name
_company_description
_company_email
_company_phone
_company_website
_company_logo
_company_location
_company_tagline
_company_facebook
_company_linkedin
_company_instagram
_company_twitter
_company_video
_company_since
```

## Known legacy application fields

```text
_candidate_name
_candidate_email
_candidate_location
_candidate_title
_candidate_photo
_candidate_linkedin
_candidate_experience
_candidate_education
_candidate_languages
_applying_for_job_id
```

Because candidate/application data is personal data, migration/import/logging decisions must minimize unnecessary exposure.

## Company relationship

Company should be a real relation rather than duplicated employer strings where feasible.

Conceptually:

```text
Company
├── Vacancies
├── Blog/content links (optional MVP relation)
├── Employer branding (later expansion)
└── Commercial/package relations as needed
```

## Vacancy import provenance

Imported vacancies should be identifiable by source and stable external identifiers where available so reruns can update instead of duplicate records.

Exact schema is to be decided after the current import code is audited.

Likely concepts:

- import source
- source/external vacancy identifier
- import run
- last imported/seen timestamp
- mapping profile

## Blog

Minimal expected concepts:

### posts

- id
- title
- slug
- excerpt
- content
- featured image/media reference if required
- status or publication state
- published_at
- timestamps
- SEO fields if no generic SEO mechanism exists

### blog categories

- id
- name
- slug

Exact field naming follows current repository conventions.

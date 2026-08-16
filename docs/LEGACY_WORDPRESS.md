# Legacy WordPress / Cariera Notes

## Purpose

Capture known legacy behavior and fields that the Laravel rebuild may need to preserve or migrate.

## Known main user/business flows

The legacy site includes the broad flows:

- find/search vacancies
- view vacancy detail
- apply/contact
- employer posts vacancy
- admin manages vacancies
- share vacancies
- related jobs/content behavior

The exact live behavior should be verified before final migration decisions.

## Known vacancy metadata

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

## Known company metadata

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

## Known application metadata

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

## Migration principle

Do not duplicate WordPress schema in Laravel merely for migration convenience.
Map legacy data into deliberate Laravel domain concepts.

When legacy behavior is unclear, verify before removing it.

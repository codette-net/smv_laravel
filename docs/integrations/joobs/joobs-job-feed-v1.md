# Joobs Job Feed — version 1.0

How Joobs sends job ads to partner job boards. Example file: `joobs-job-feed-v1-example.xml`.

## In one paragraph

Joobs publishes **one private XML feed per partner**. It contains **only the ads our customers have ordered on your platform**, never our whole database. The format follows the **Indeed Job Sync XML** format: if your platform already imports Indeed-style feeds, it can import ours without changes. A few optional extensions start with `joobs_`; ignoring them is safe. **If your platform needs another format, we adapt on our side** (see "What we can adapt").

## How it works

| Topic | Rule |
|---|---|
| Access | A private URL with a secret key. Keep it confidential; we can rotate it without downtime. |
| Refresh | We rebuild the feed every 5 minutes. Fetching it once an hour is plenty; at least every 6 hours. |
| Content | Only ads ordered for your platform and still running. |
| One entry = one ad | Each `<job>` is one ad to publish. |
| Several cities | Same vacancy in several cities = one `<job>` per city. They share `<requisitionid>` and each has its own `<referencenumber>` (the Indeed rule). |
| Removal | **An ad that is no longer in the feed must go offline.** This happens when the paid runtime ends (`<expirationdate>`), when the employer closes the vacancy, or when the order is stopped. |
| Applications | Candidates apply through `<url>`, on Joobs. Please link your "Apply" button to it, unchanged. You may add your own tracking parameters. |
| Encoding | UTF-8. Every value is in CDATA. Empty values are left out rather than sent empty. |

## Fields

### Standard fields (Indeed Job Sync meaning)

| Tag | Always present? | Content |
|---|---|---|
| `title` | yes | Job title only (no location, salary or contract type). |
| `date` | yes | Publication date, ISO 8601 (`2026-10-08T14:00:00Z`). |
| `referencenumber` | yes | Unique, stable ID of this ad (order + vacancy + city). Never changes while the ad runs. |
| `requisitionid` | yes | ID of the vacancy on Joobs. Same value for every city of one vacancy. |
| `url` | yes | Job page on Joobs where the candidate applies, with tracking parameters. |
| `company` | yes | Name of the hiring employer. |
| `city`, `state`, `country`, `postalcode` | yes | Where the ad is shown. `country` is an ISO 3166 code (`BE`, `NL`, `LU`, `FR`, `DE`). |
| `streetaddress` | when known | Street and number of the workplace. |
| `description` | yes | Full job description, simple HTML: `p`, `br`, `h2`, `h3`, `ul`, `ol`, `li`, `strong`, `em`, `a`. |
| `salary` | only when the employer publishes it | Free text, e.g. `€3800 - €4600 per month`. |
| `education` | when known | `Professional certificate`, `Bachelors`, `Masters`, `PhD`. |
| `jobtype` | when known | `fulltime`, `parttime`, or both, comma-separated. |
| `category` | yes | The job's field in English, comma-separated if several (`Sales`, `Marketing`, …). Closed list, sent on request. |
| `experience` | when known | `Less than 6 months`, `6 months to 2 years`, `3 to 6 years`, `7 to 10 years`, `11 to 15 years`, `More than 15 years`. |
| `expirationdate` | yes | Last day of the paid runtime, `YYYY-MM-DD`. |
| `remotetype` | only when not fully on-site | `Hybrid remote` or `Fully remote`. |

### Joobs extensions (optional to read)

| Tag | Always present? | Content |
|---|---|---|
| `joobs_language` | yes | Language of the ad: `nl`, `fr`, `en`, `de`. |
| `joobs_contracttype` | when known | `permanent`, `temporary`, `freelance`, `internship`, `student`. |
| `joobs_workplacecity`, `joobs_workplacecountry` | yes | The real workplace. Differs from `city` when the employer ordered extra nearby cities. |
| `joobs_product` | yes | The formula the customer ordered on your platform (codes agreed with you). |
| `joobs_logo` | when available | Employer logo, https. |
| `joobs_companyurl` | yes | Employer page on Joobs. |

### Tracking parameters on `url`

`utm_source=<your platform>` · `utm_medium=jobboard` · `utm_campaign=<order reference>` · `utm_content=<city>`. This lets the employer see which applications came through you, and from which city.

## What we never send

- Personal contact details of the employer (names, e-mail addresses, phone numbers). Candidates apply through Joobs.
- Ads nobody ordered on your platform, ads imported from other job boards, or test accounts.
- Prices or commercial terms.

## What we can adapt for your platform

The content stays the same; the shape can change. Tell us what your import expects:

- other tag names, another root element, or your own XML layout;
- one entry with several locations instead of one entry per city;
- plain text instead of HTML in the description;
- an explicit status field (`active` / `inactive`) instead of removal by absence;
- your own category, contract or region codes;
- another date format;
- your own tracking parameters;
- JSON instead of XML.

## Versioning

The version is on the root element (`feedversion="joobs-1.0"`). Within version 1 we only **add** optional fields; we never rename, remove or change the meaning of an existing one. Anything that would break your import gets a new version, announced in advance.

## Contact

David Verbustel, Joobs: david@joobs.be

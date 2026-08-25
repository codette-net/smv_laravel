# VNOM production import configuration

VNOM is the first production-quality configuration of the generic SMV import pipeline.
There is no VNOM-specific reader, mapper or persister.

## Import source

| Setting | Value |
| --- | --- |
| Company | VNOM |
| Transport | HTTP |
| Format | XML |
| Endpoint | `https://www.vnom.nl/feeds/jobs.xml` |
| Record path | `job` |
| Active | Yes, when the integration is operationally enabled |
| Automatic approval | Admin/super-admin approval required; HTTPS and SSRF checks remain active |
| Credentials | None for the currently documented public feed |

For local/manual verification, upload `jobs_for_test.xml` privately and retain the same
format, record path, selection rules, mapping and aliases. Do not seed the production URL.

## Selection rules

Selection and taxonomy resolution are deliberately separate. Configure:

```json
{
  "logic": "or",
  "rules": [
    {"field": "function", "operator": "contains", "value": "Sales"},
    {"field": "function", "operator": "contains", "value": "Marketing"}
  ]
}
```

This reproduces the relevant legacy Sales/Marketing universe without PHP provider code.

## Mapping

| Source | SMV destination | Operation |
| --- | --- | --- |
| `identifier` | `source_reference` | Direct |
| `title` | `vacancy.title` | Direct |
| `description` | `vacancy.description` | Direct; preserve CDATA formatting through the generic HTML allowlist sanitizer |
| `city` | `vacancy.location` | Direct |
| `date` | `vacancy.published_at` | Date transform |
| — | `vacancy.application_mode` | Default `external` |
| `applyUrl` | `vacancy.application_url` | Direct |
| `salary` | `vacancy.salary_min` | Compensation-text minimum transform |
| `salary` | `vacancy.salary_max` | Compensation-text maximum transform |
| `salary` | `vacancy.salary_currency` | Compensation-text currency transform |
| `salary` | `vacancy.salary_period` | Compensation-text period transform |
| `function` | `taxonomy.function_area` | Direct plus source-scoped aliases where required |

`identifier` is the chosen provider identity because it is the stable opaque candidate
in the available snapshots. `referencenumber` remains useful operational context, but
there is no canonical persisted destination for it today. Confirm identifier stability
against a second production snapshot before enabling unattended execution.

## Deliberately ignored or unresolved

- `company`: informative only; the ImportSource Company VNOM owns every Vacancy.
- `url`: ignored because `applyUrl` is the canonical external application destination.
- `owner`, `education`, `country`, `postalcode`, `parentJobId`, `recordType`, and
  `lastactivitydate`: no justified MVP destination.
- `jobtype` (`Loondienst`): not equivalent to Fulltime and therefore not mapped.
- `experience` (`1 tot 3 jaar`, etc.): no approved Junior/Medior/Senior conversion.
- `category` (`Office`, etc.): its business meaning is not sufficiently clear to force
  it into sector or function area.

Create explicit `ImportTaxonomyMapping` records per distinct `function` value when it
does not exactly match one canonical Function Area. Never create Categories automatically.

## Compensation and lifecycle

The generic compensation-text transforms recognize bounded numeric ranges, EUR/USD/GBP
markers, and hour/day/week/month/year terms. `€2900 - €3500 Month` becomes salary
2900–3500 EUR per month. Missing values remain null; ambiguous periods produce preview
warnings instead of assumed monthly conversion.

Repeated execution uses `ImportSource + identifier`, preserves slugs and Company profile
data, and creates a separate history run. A record absent from a trustworthy later
selected feed is reported with `missing_since`; it is not deleted or hidden. When it
returns, it is reported as restored.

## Manual Filament verification

1. Create/select Company **VNOM**.
2. Create an Import source using the settings above, or upload the bounded test XML.
3. Enter record path `job` and the selection JSON above.
4. Create mapping **VNOM productie** and add the mapping rows above.
5. Add/verify Function Area aliases under the preview resolution actions.
6. Preview: verify identifier, title, city, salary, warnings and external apply URL.
7. Execute and inspect Import history counters/logs.
8. Execute unchanged again and confirm updates without duplicates.
9. Use controlled local fixture variants—not the live feed—to verify missing/restored reporting.

# SMV Partner and Commercial Inventory

**Status:** working inventory, 31 October 2026. Unless explicitly marked confirmed, the
relationships and payment models below are stakeholder-reported or prospective and need
owner verification. This document does not authorize imports, invoicing or ranking changes.

## Inventory

| Partner | Commercial status | Feed or integration | Payment model | Reporting | Open questions | Technical readiness | Owner/contact |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Joobs | Confirmed willingness to proceed with the 13 October reseller proposal; Store addition intended during November, with partner communications in late November/early December. Agreement details remain open. | Joobs v1 complete-snapshot XML specification and two-ad example are present. Intended reseller feed is probably multi-employer. | Reseller pricing proposed; flexibility, channel package definitions and the EUR 389 four-for-three discrepancy are unresolved. Separate referral programme: 25% of monthly subscription revenue for two years, plus possible partner page; opportunity only. | Joobs says it tracks clicks and applications. SMV now records and reports privacy-minimal raw outbound apply clicks with a 24-month maximum, but has no historic or detail-view metrics. | Audience and Belgian share; average applications; best roles; experience/languages; Superior budget/channels/visibility; per-job reporting; discount; pricing discrepancy; invoice cadence/terms; order turnaround; manual email ordering. | Generic mapping, employer resolution, verified snapshots, fixture persistence, scheduling, monitoring and per-source publication/offline policy exist. Joobs can be configured `auto_offline`; live activation still requires the unresolved partner inputs and production operations approval. | Dylan is the proposed import-operations owner, pending explicit confirmation; commercial owner remains undocumented. |
| VONQ | Previously reported as an active revenue source; spelling and agreement require confirmation. | Partner/feed details not verified in this review. | Unknown. Do not infer CPC. | Unknown. | Confirm agreement, current feed, payment basis, contacts and reporting access. | Generic importer may fit after a real spec/sample is supplied. | Not documented. |
| Brockmeyer | Stakeholder-reported revenue source. | Appcast/programmatic relationship is unverified. | Unknown. | Unknown. | Confirm legal counterparty, Appcast role, feed ownership, payment basis and data access. | No verified source contract/spec in current documentation. | Not documented. |
| VNOM | Existing feed; prospective CPC arrangement and request for consistently lower search positioning. | Existing VNOM fixtures and generic configuration evidence. | CPC is prospective, not confirmed. | Existing partner-side/internal availability not documented. | Confirm agreement, feed availability, CPC definition, validation, billing source and requested visibility rule. | Generic import foundation is strongest here; production activation still needs operational approval. Do not manipulate dates or ordering. | Not documented. |
| Adver | Prospective vacancy supply and revenue source. | Feed/integration not verified. | Unknown/prospective. | Unknown. | Confirm commercial model, endpoint/spec, ownership, volumes and reporting. | Not ready without verified sample/spec and agreement. | Not documented. |
| Michael Page | Historical feed reported broken/offline; possible reactivation. | Repository contains fixture/notes, but current endpoint status is not verified. | Unknown. | Unknown. | Confirm permission, current feed, identity/mapping changes, payment and support owner. | Requires revalidation against current source before reuse. | Not documented. |
| Appcast | Programmatic recruitment distribution should fit the generic architecture. | No verified direct feed or contract documented. | Programmatic model unknown. | Unknown. | Confirm whether direct or via Brockmeyer, source format, billing event and reconciliation data. | Architectural fit only; not implementation-ready. | Not documented. |

Imported mapped Vacancy fields are feed-owned and may be overwritten by a later feed run.
Permanent SMV editorial ownership should use a separate manual Vacancy; a future “create
manual copy from imported Vacancy” action is deferred. Publication and missing behavior are
configured per ImportSource and are independent of commercial ranking. Ranking, boosts and
partner priority remain a separate future decision.

## Joobs commercial decision checklist

- Define the direct SMV **Standaard** package and Joobs-channel **Normaal** package, and
  confirm whether they are the same product.
- Define Superior fulfilment: placement/positioning, duration, newsletter and social
  distribution, paid media budget, channels, targeting, reporting and responsible owner.
- Explain or correct the EUR 389 four-for-three proposal versus EUR 356 for four EUR 89
  Normaal placements; record VAT treatment and channel-specific prices.
- Approve reseller discount authority, floors, volume tiers and exception owner.
- Agree manual email order contents, acknowledgement, turnaround and error/cancellation
  handling until the feed is live.
- Agree invoicing frequency, payment term, purchase-order requirements, VAT and dispute
  process. Do not build invoicing before this is approved.
- Supply dated, sourced historic audience, Belgian share, views, apply clicks,
  applications, role performance, experience levels and languages. Do not backfill or
  claim historic SMV metrics that were not collected.
- Define reporting expectations and authoritative sources for SMV views/clicks,
  Joobs-confirmed clicks/applications and any billable event.

## Search relevance and commercial priority

Three separate concepts must remain explicit:

1. **Vacancy completeness and search relevance:** data quality, filters and matching to
   the user's query.
2. **Organic SEO visibility:** indexability, metadata, structured data, internal links and
   external search-engine performance.
3. **Commercial positioning:** an explicit sponsored/promoted/deprioritized rule with
   contract, disclosure, start/end dates and deterministic tie-breaking.

VNOM's request for consistently lower search positioning is an open business requirement,
not an instruction to alter the current sort. Publication dates must not be edited to
simulate priority. A future version II mechanism should store explicit source or vacancy
priority, effective dates, reason/audit data and a clear interaction with relevance and
sponsored disclosure. It stays outside the MVP critical path unless an owner approves it
as a release requirement.

## Reporting definitions

- **Listing impression:** a vacancy card was rendered/meaningfully visible in a result
  list under an agreed measurement rule; not the same as a page request.
- **Vacancy detail view:** a request for one public vacancy detail page after agreed bot,
  preview and duplicate exclusions.
- **Outbound apply click:** SMV recorded a user following the controlled apply redirect to
  the stored external URL. It does not prove arrival or application completion.
- **Partner-confirmed click:** a click confirmed by Joobs or another partner's reporting;
  it may use different attribution/deduplication rules.
- **Completed application:** an application completion confirmed by the destination or an
  internal SMV submission. An outbound click is not completion.
- **Billable click:** a contract-defined, validated event accepted for invoicing. It must
  not be inferred from raw SMV events without reconciliation and fraud/duplicate rules.

The minimum privacy-conscious SMV report should aggregate vacancy detail views and
outbound apply clicks by UTC date, vacancy and import source; support a date range and
per-vacancy totals or CSV; avoid raw IP/user-agent storage unless a documented short-lived
anti-abuse need is approved; define bot/preview/duplicate exclusions; and set a retention
period with staff-only access. Listing impressions can safely follow after the first two
events because visibility measurement is more complex.

SMV-097B now stores one `outbound_apply_clicks` row per successful controlled redirect:
only the Vacancy key and UTC event timestamp. Company and ImportSource are derived through
the Vacancy. No user id, IP address, user-agent, cookie, fingerprint, partner secret or
application content is stored. Repeated requests, including double clicks and undetected
bots/link scanners, remain separate raw events; therefore totals are neither unique humans
nor applications. SMV-097D records the approved maximum retention period as **24 months**,
supporting bounded operational and year-over-year commercial reporting without making a
broader legal claim. A daily UTC schedule deletes raw events strictly older than the cutoff
in bounded batches. The command supports a non-mutating dry run and logs its run time,
cutoff and eligible/deleted counts without logging individual events. Events exactly at the
cutoff remain. There is no longer-term aggregate store, so reports outside the retained
window return only available data, normally zero.

SMV-097C provides `super-admin`/`admin` users with inclusive UTC date, Vacancy, Company and
ImportSource filters, totals per Vacancy/Company/source and aggregate CSV rows. The default
window is the latest 30 UTC calendar days. CSV contains identifiers/reference, Vacancy,
Company, source, current status, raw click count and reporting period; it contains no raw
click rows or personal/request metadata. These SMV counts still cannot prove a unique human,
completed application, partner acceptance, billability or hiring outcome.

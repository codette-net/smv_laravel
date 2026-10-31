# SMV Content Status

This document records only the decisions and inputs still relevant after the first
SMV-063 public content pass. It is intended to keep the next stakeholder review short.

**Post-demo review, 31 October 2026.** The Tim source document and matching PDF were
already represented in the first content pass. Dylan's newly supplied
`SMV_Content_en_Merkinput_Gids_Dylan_voorlopig.docx` has now been read in full. It is a
partially completed working guide, not approved website copy. Answers below are treated
as stakeholder suggestions until the named approval or evidence is supplied.

## READY

- Positioning: a Dutch platform specialized in sales and marketing Vacancies,
  Companies and professional content.
- Audience: juniors, mediors, trainees, interns and other career stages where the
  available Vacancy data supports them.
- Employer entry point: `Adverteren`, leading to `/adverteren`.
- Initial public pricing: Standaard EUR 189 and Superior EUR 398, both excluding VAT
  and with a 60-day publication period; Maatwerk remains on request.
- Direct employer contact: `sales@salesenmarketingvacatures.nl` and `06 30852152`.
- Response wording: on working days SMV tries to respond within 24 hours; this is not
  presented as an absolute SLA.
- SMV-062 general Contact form: operational with configurable internal e-mail delivery,
  cautious success wording and no database retention.
- SMV-078 employer entry: Standaard and Superior start at `/vacature-plaatsen`; Maatwerk
  opens Contact with an allowlisted advertising reason. The chosen package remains a
  non-binding intent until the separate commercial/payment work is completed.
- SMV-078B account foundation: authenticated employers use `/account` for owned Company
  profile completion and owned Vacancy status/history; `/dashboard` remains Filament for
  authorized staff only.
- The eight supplied articles are normalized as native BlogPost seed content. The
  introductory e-mail in `blog_11082026.pdf` is not treated as an article.

## NEEDS APPROVAL

- Final approval of homepage, advertising, About, pricing, Contact and footer copy.
- Final editorial approval of the lightly cleaned stakeholder Blog articles.
- Whether the existing Tidy stock images on About and Contact should remain at launch.
- Whether and how the recent ownership change should be described beyond the current
  deliberately concise wording.
- Final homepage proposition. Tim proposes a narrower sales/marketing specialist message;
  Dylan proposes a broader inspiration and career-stage promise. Both support a clear
  candidate/employer split, but neither supplied final edited hero copy.
- Final geographic claim. Tim refers to Benelux while also saying the present market is
  the Netherlands; Dylan proposes no Belgian activity before a Netherlands vacancy-volume
  threshold is reached. Do not publish a Benelux leadership claim without a decision.
- Final audience emphasis. Tim prioritizes medior sales/marketing talent; Dylan also
  prioritizes students, trainees, juniors and freelance/interim professionals. The
  implemented site can serve these groups where data exists, but the launch promise needs
  one approved hierarchy.
- Final tone and visual direction. Both favor a professional/premium experience, but Tim
  describes it as smooth, fast and restrained while Dylan also asks for commercial,
  ambitious language and youthful references. Existing blue/white/dark styling can remain
  while this is edited; no redesign is required for release.
- Whether the employer-facing navigation label remains `Adverteren` or changes to
  `Voor werkgevers`. Dylan prefers the latter; the implemented entry point is currently
  `Adverteren`.
- Whether any 24-hour placement wording is operationally supportable. Tim asks for a
  strict turnaround outside business hours; Dylan uses tentative "before the weekend"
  wording. The current cautious working-day response wording remains safer.

## NEEDS INPUT

- Exact historic start year and wording for the platform history.
- Verified newsletter reach and measurement date.
- Verified social reach and measurement date.
- Publishable client testimonials and permission to use names or logos.
- Final package terms, including the exact delivery included with each package.
- Final Superior details: positioning, newsletter/social distribution, media budget
  and operational fulfilment.
- Reconcile package naming and pricing across channels. The site currently uses
  Standaard/Superior at EUR 189/EUR 398; the Joobs proposal refers to Normaal/Superior and
  includes an unexplained EUR 389 four-for-three offer while four individual Normaal jobs
  would total EUR 356.
- Refund, cancellation and in-flight Vacancy amendment terms.
- Confirmation of any additional public contact address, including a complete `info@`
  address if one should be published.
- Final owned photography and usage rights.
- Written permission for any client logos.
- Team names, roles, approved biographies and portraits if a team section is wanted.
- Approved FAQ questions and answers.
- A matching cover image for `Remarketing en retargeting uitgelegd`; the article is
  intentionally seeded without a cover rather than using a guessed image.
- Approved hero, candidate, employer, advertising and team images with provenance and
  usage rights. Dylan supplied preferences but no repository asset files.
- A complete publishable `info@...` address. Dylan's `info@` note is incomplete and must
  not be published as an address.
- Evidence and measurement dates for "15 years", high-quality traffic, client names,
  performance claims, LinkedIn reach and any Netherlands/Benelux leadership claim.

## LATER

- SMV-077: Company discovery and filtering.
- Optional Contact autoresponder or stronger anti-spam protection, only when a concrete
  communication or abuse need is confirmed.
- Vacancy of the day/week/month and employer of the month.
- More prominent stage and traineeship discovery where data quality supports it.
- Job alerts and newsletter subscription.
- Function and career landing pages for Accountmanagement, Sales and Marketing.
- Additional employer and recruitment content.
- Possible expansion beyond the Netherlands.
- Vacancy/employer of the day/week/month, free school/stage publishing, newsletters/job
  alerts and conversion-led vacancy advice. These are product ideas, not approved launch
  content promises.

## Page-by-page release assessment

| Page or section | Supplied and implemented | Direct next use | Still required | Release impact |
| --- | --- | --- | --- | --- |
| Homepage and hero | Both stakeholders support specialist sales/marketing positioning and a candidate/employer choice; a working hero, search, vacancies, employer block and Blog block exist. | Edit one restrained proposition using existing Blade structure after approval. | Resolve hero wording, geography, audience priority, proof claims and final CTA label. No approved hero image was supplied. | Final copy approval is a release dependency; a new layout is not. |
| About / company information | Existing About page and concise ownership wording exist; both sources offer history fragments. | Keep existing structure and edit approved factual history only. | Verify start year (2010 versus 2011/"15 years"), ownership narrative, team names/roles and portraits. | Non-blocking if the current cautious copy is accepted; blocking for stronger history/team claims. |
| Vacancy and candidate explanation | Discovery, detail, salary/location/experience filtering and application flows exist. Dylan emphasizes relevant information and career stages. | Add only reviewed explanatory microcopy to existing pages/components. | Decide which career stages are primary and avoid promising job alerts, complete salary data or recruitment advice that is not operational. | Not a technical blocker; misleading claims are a content blocker. |
| Employer / advertising | `/adverteren`, `/tarieven` and `/vacature-plaatsen` exist. Stakeholders describe assisted intake, review, publication and later reporting. | Edit service steps that are actually delivered; retain the current non-binding package intent. | Confirm label, turnaround, amendment service, fulfilment owner, reporting cadence and multi-vacancy handling. | Commercial wording must be approved before production sales activity. |
| Packages | Standaard EUR 189 and Superior EUR 398 excluding VAT for 60 days are implemented as presentation/intent. | Retain only if owners reconfirm them for the direct SMV channel. | Reconcile Normaal/Standaard naming, Superior visibility, EUR 100 media indication, newsletter/social delivery, refunds and Joobs reseller pricing. | Blocking for partner/order communications; no checkout implementation is implied. |
| Contact and calls to action | Working Contact form, sales address, phone and cautious working-day response wording exist. | Existing form and CTA components can be reused. | Confirm complete `info@` address, contact reasons, public phone number and final CTA vocabulary. | Current verified contact route can launch; unverified alternatives cannot. |
| Blog | Native Blog and stakeholder articles exist; Dylan suggests candidate-led themes, weekly cadence, Tim as author and Jos as publisher. | Continue existing editorial workflow after article approval. | Confirm editorial owner, cadence, promotion responsibility, remaining cover and factual claims. | Existing approved posts can launch; cadence is not a blocker. |
| SEO titles/descriptions | Existing metadata foundation and page-level copy exist. No final stakeholder SEO-title/description set was supplied. | Derive titles/descriptions only after page proposition is approved, using existing SEO mechanisms. | Final keywords, claims and page copy; legacy redirect inventory remains separate. | Metadata review is required, but no new SEO architecture is needed. |

## Small content implementation sequence

1. **Ready after one owner review (1-2 h Codex, 1-2 h human):** approve one homepage
   proposition, navigation label, restrained About wording, verified contact details and
   direct-channel package names/prices; then edit existing Blade copy in a separate task.
2. **Requires commercial confirmation (1-2 h Codex, 2-4 h human):** finalize the employer
   service steps, turnaround language, Standaard/Normaal and Superior contents, media
   budget, reporting promise and reseller-channel differences.
3. **Missing assets/evidence (implementation 1-3 h after receipt; review 1-2 h):** select
   approved images/logo variants, record provenance, verify history/reach figures and
   secure logo/testimonial permissions.
4. **Safe after launch:** FAQ expansion, team section, newsletters/job alerts, career
   landing pages, monthly featured content and broader geographic positioning.

Content work remains independent from Joobs feed implementation except where package
names, partner reporting promises or advertised geography appear in partner-facing copy.

## Asset provenance

- `public/images/about-hero.jpg`, `public/images/about-intro.jpg` and
  `public/images/request-demo-bg.jpg` are existing Tidy/template stock assets already
  present in the repository. No external stock was downloaded for SMV-063.
- `database/seeders/media/blog/*.jpg` contains stakeholder-supplied article covers.
  Covers were matched by article/date and subject. `blog_29082026.jpg` is used for the
  content-strategy article, `blog_11082026.jpg` for the new-business article and
  `blog_24092026.jpg` for the social/e-mail article. The remarketing article has no
  sufficiently supported cover match.
- Original stakeholder source documents remain under `docs/source/` and are not used
  directly as public web assets.

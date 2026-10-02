# SMV Content Status

This document records only the decisions and inputs still relevant after the first
SMV-063 public content pass. It is intended to keep the next stakeholder review short.

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
- The eight supplied articles are normalized as native BlogPost seed content. The
  introductory e-mail in `blog_11082026.pdf` is not treated as an article.

## NEEDS APPROVAL

- Final approval of homepage, advertising, About, pricing, Contact and footer copy.
- Final editorial approval of the lightly cleaned stakeholder Blog articles.
- Whether the existing Tidy stock images on About and Contact should remain at launch.
- Whether and how the recent ownership change should be described beyond the current
  deliberately concise wording.

## NEEDS INPUT

- Exact historic start year and wording for the platform history.
- Verified newsletter reach and measurement date.
- Verified social reach and measurement date.
- Publishable client testimonials and permission to use names or logos.
- Final package terms, including the exact delivery included with each package.
- Final Superior details: positioning, newsletter/social distribution, media budget
  and operational fulfilment.
- Refund, cancellation and in-flight Vacancy amendment terms.
- Confirmation of any additional public contact address, including a complete `info@`
  address if one should be published.
- Final owned photography and usage rights.
- Written permission for any client logos.
- Team names, roles, approved biographies and portraits if a team section is wanted.
- Approved FAQ questions and answers.
- A matching cover image for `Remarketing en retargeting uitgelegd`; the article is
  intentionally seeded without a cover rather than using a guessed image.

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

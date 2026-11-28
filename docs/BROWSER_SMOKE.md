# Browser smoke tests

SMV uses Playwright with Chromium for a compact local critical-path suite. The suite covers
public navigation/search/pagination, candidate and employer account journeys, representative
Filament authorization/operations, raw analytics, responsive breakpoints and core keyboard
behavior. It supplements Pest; it does not replace policy tests, a screen-reader audit or
manual Stripe acceptance.

## Safety and isolation

`npm run test:browser` refuses to reset data unless its runner has set the explicit safety
flag and the database is a `.sqlite` file below `storage/framework/testing`. It then runs
`migrate:fresh` with `AcceptanceDemoSeeder` and adds pagination-only records to that isolated
file. The browser environment uses the Fake payment gateway, array mail, synchronous queues
and blocks requests to every non-local origin. It never targets the configured application
database, Stripe, mail delivery or partner feeds.

Failure screenshots, videos and traces are written below `test-results/`; the optional HTML
report is written to `playwright-report/`. Both paths and the SQLite database are ignored by
Git.

## Setup and commands

Build assets and install the pinned Playwright Chromium browser once:

```powershell
$env:SMV_PHP_BINARY = 'C:\Users\User\.config\herd\bin\php83\php.exe'
npm run test:browser:install
npm run build
```

Run the local suite or one file on Windows:

```powershell
npm run test:browser
npm run test:browser -- tests/Browser/accounts.spec.mjs
```

On Linux, make PHP available on `PATH` or set `SMV_PHP_BINARY`, then use the same npm
commands. No second browser framework or Playwright web server is required; the runner starts
an isolated Laravel server itself.

## Optional read-only staging smoke

The staging project is never reset or seeded by browser automation. After supplying an
approved URL, run only the separate read-only public check:

```powershell
$env:SMV_STAGING_BASE_URL = 'https://vacatures.marketing'
npm run test:browser:staging
```

This check does not register, authenticate, apply, order, pay, import, cancel or refund. It
may still require the operator to handle Hostinger directory protection. Do not point the
local state-changing command at staging.

## Covered browser contract

- Public: homepage, shared navigation, Vacancy search/filter disclosure/clear/pagination,
  Vacancy and Company details, Blog, contact and commercial pages, Vite assets and browser
  runtime failures.
- Candidate: login, registration without an unimplemented verification detour, dashboard,
  Vacancy/Company saves, saved lists, internal Application form/history and guest redirects.
- Employer: employer-first dashboard, package selection, draft creation, all three application
  modes, Order/payment history, available/assigned/active credit states, credit selection and
  approval guidance. Automated payments remain Fake.
- Staff: editor/admin access boundaries, controlled staff-role selection, read-only legacy
  classification, protected super-admin assignment, approval/ranking/package/Order/credit/
  ImportSource surfaces, cancel/refund visibility, raw reporting and CSV download. Destructive
  staff actions are not executed.
- Analytics: an eligible detail GET increments the isolated raw-view count, HEAD does not,
  and an external-apply navigation increments the isolated click count.
- Responsive/keyboard: 320, 375, 768, 1024 and 1440 pixel widths; public menu, search/cards,
  detail/Application pages, employer account/Order/credit pages, overflow, Escape focus return,
  reduced motion and keyboard operation of the custom Company listbox.

## Remaining manual acceptance

- Screen-reader passes with NVDA/VoiceOver, including announcements, errors and Filament
  dialogs; automated role/label assertions are not a screen-reader audit.
- Visual checks for text overlap, focus visibility, stacking and modal behavior across actual
  phones/tablets and at browser zoom.
- Firefox/WebKit or the production browser matrix; the committed automated engine is Chromium.
- Shared staging access/runtime, production-like mail, real Stripe test-mode Checkout/webhooks,
  refunds and live operational imports.
- Staff execution of approval, ranking bulk updates and destructive Order actions remains
  manual; Pest remains the deeper behavioral and authorization authority.

Reproduce a failure with the reported file/grep command. Inspect `test-results` or run
`npx playwright show-trace <trace.zip>` locally; do not commit generated evidence.

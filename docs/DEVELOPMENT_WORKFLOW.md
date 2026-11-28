# Development Workflow — Windows 11 / GitHub / Codex

## Confirmed local environment

The current development machine runs Windows 11 with Laravel Herd, PHP 8.3 and
Node.js 22.15. The application stack is Laravel 13.4, Filament 5.5, Pest 4.5,
Tailwind CSS 4.3, Vite 8 and MySQL.

Codex's sandboxed shell may not inherit the interactive PowerShell PATH. When needed,
use `C:\Users\User\.config\herd\bin\php83\php.exe`; do not infer that PHP is absent
from an unavailable unqualified `php` command.

## Branch model

```text
main
  ↑ feature PRs

feat/*
fix/*
chore/*
```

Feature branches are created from the latest stable `main` and merge back into `main`
after review. The permanent `develop` workflow was retired on 31 October 2026. Do not
delete or synchronize the old branch until its unique commit has been reviewed separately.

## Initial Git setup

From PowerShell in the repository:

```powershell
git status
git remote -v
git branch --show-current
git fetch origin
```

```powershell
git switch main
git pull --ff-only
```

## Start a feature

```powershell
git switch main
git pull --ff-only
git switch -c feat/import-mapping
```

After implementation/review:

```powershell
git status
git diff
git add <intentional files>
git commit -m "feat: add import field mapping"
git push -u origin feat/import-mapping
```

Open PR:

```text
feat/import-mapping -> main
```

## Codex workflow

Start Codex from the repository root so it sees `AGENTS.md` and project files.

The repository audit and SMV-001 foundation stabilization are complete. For later
tasks, use the current backlog item and its relevant architecture documents rather than
rerunning the original audit as an implementation substitute.

For later implementation tasks:

1. create/switch to the feature branch yourself
2. confirm working tree is clean
3. give Codex one scoped task
4. require inspection before implementation
5. review its diff
6. run/test in browser where relevant
7. commit/push only the intended files

## Windows considerations

Prefer repository scripts and PHP/Composer/npm commands already used by the project.

### Hostinger production Composer install

Deploy the committed `composer.lock` and run Composer in install mode, not update
mode, so production uses the reviewed dependency versions without rewriting the
lockfile:

```sh
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
```

This still runs Composer's production autoload/package discovery scripts. Do not run
`composer update` on the production host. Laravel Boost is not a production dependency;
developers with Boost installed can run `php artisan boost:update --ansi` explicitly.

Be alert to:

- Windows path separators in ad-hoc scripts
- CRLF/LF noise in Git diffs
- file permission changes that do not apply the same way as Linux production
- symlink behavior (`storage:link`) when moving between Windows dev and Linux hosting
- case-insensitive local filesystem hiding filename-case bugs that break on Linux production

Do not change project naming/casing casually. Production hosting is expected to be Linux/case-sensitive.

## Before every feature branch

```powershell
git switch main
git pull --ff-only
git status
```

Expected: clean working tree.

## Acceptance dataset

For manual acceptance, browser smoke or a disposable dry run, seed the dedicated dataset
explicitly:

```text
php artisan db:seed --class=Database\Seeders\AcceptanceDemoSeeder
```

`AcceptanceDemoSeeder` is guarded to `local`, `testing` and `staging`, uses only synthetic
`.test` accounts and `.invalid` fixture endpoints, and is not called by `DatabaseSeeder`.
Do not run it against production or a shared database. The fixture accounts and scenario
references are documented in `docs/ACCEPTANCE_DATASET.md`.

## Browser smoke suite

The Chromium suite is documented in `docs/BROWSER_SMOKE.md`. It owns a guarded SQLite file
below `storage/framework/testing`, starts its own local Laravel server and must be launched
through `npm run test:browser`; do not reproduce its `migrate:fresh` command manually against
another database. Use `npm run test:browser:install` once per machine and `npm run build`
before release verification. `npm run test:browser:staging` is a separate read-only public
check and requires `SMV_STAGING_BASE_URL`.

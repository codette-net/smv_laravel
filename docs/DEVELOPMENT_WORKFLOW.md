# Development Workflow — Windows 11 / GitHub / Codex

## Branch model

```text
main
  ↑ release merge

develop
  ↑ feature PRs

feat/*
fix/*
chore/*
```

Feature branches are created from the latest `develop`.

## Initial Git setup

From PowerShell in the repository:

```powershell
git status
git remote -v
git branch --show-current
git fetch origin
```

Create `develop` once if it does not exist:

```powershell
git switch main
git pull --ff-only
git switch -c develop
git push -u origin develop
```

If it already exists:

```powershell
git switch develop
git pull --ff-only
```

## Start a feature

```powershell
git switch develop
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
feat/import-mapping -> develop
```

## Codex workflow

Start Codex from the repository root so it sees `AGENTS.md` and project files.

For the first session, use the repository-audit prompt in `BACKLOG.md` / SMV-001 and instruct Codex not to modify files.

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

Be alert to:

- Windows path separators in ad-hoc scripts
- CRLF/LF noise in Git diffs
- file permission changes that do not apply the same way as Linux production
- symlink behavior (`storage:link`) when moving between Windows dev and Linux hosting
- case-insensitive local filesystem hiding filename-case bugs that break on Linux production

Do not change project naming/casing casually. Production hosting is expected to be Linux/case-sensitive.

## Before every feature branch

```powershell
git switch develop
git pull --ff-only
git status
```

Expected: clean working tree.

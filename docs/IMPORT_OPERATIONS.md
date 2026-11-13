# Import operations runbook

## Purpose and ownership

Use this runbook for scheduled and manual vacancy imports. It covers application-level
checks and safe recovery for the current Hostinger-compatible bounded-worker model. It
does not replace hosting uptime, cron or database monitoring. Assign a named operator and
a dedicated alert destination before enabling unattended partner feeds.

Joobs is not activated by these procedures. Its employer identity, category/product
mappings, timezone, empty-feed semantics, publication/removal policy and field ownership
must still be approved separately.

## Routine checks

1. Open **Imports → Importbronnen** in Filament. Check source health, latest outcome,
   last dispatch, last execution attempt, last successful/verified run and recent counts.
2. Open **Importhistorie** for the source. Inspect origin, timestamps, snapshot outcome,
   reconciliation result and safe log codes. Never request or paste credentials/feed
   payloads into a run note.
3. On the server, use the deployed PHP binary:

```text
php artisan schedule:list
php artisan queue:monitor database:imports --max=50
php artisan queue:failed
```

`schedule:list` must contain `imports:dispatch-due` and the bounded `queue:work` command.
A growing database queue with no new Import attempt indicates the worker is not consuming
jobs. A changing `last_scheduled_at` without a new attempt also points to queue/worker
failure; dispatch time is not execution proof.

## Safe rerun

1. Correct the source, mapping, employer/taxonomy aliases or infrastructure cause first.
2. Keep the source active. Automatic execution may be disabled while investigating.
3. From the ImportSource view choose **Opnieuw uitvoeren**. Only administrators can do so.
4. Inspect the new normal Import run. A lock collision becomes `skipped` with
   `concurrency_rejected`; do not bypass or manually delete the lock while a worker may
   still be running.

The action starts from the beginning through `VacancyImportRunner`. It does not resume a
partial run. Provider-scoped upserts remain idempotent, and unsafe snapshots cannot mark
Vacancies missing.

## Failed queue jobs

Inspect first:

```text
php artisan queue:failed
php artisan queue:retry <failed-job-id>
```

Retry only after confirming that no worker/run for the same source is active and that the
underlying cause is corrected. Prefer the Filament fresh-run action when the failed queue
payload is old or source configuration changed. To discard one obsolete failed-job row,
use `php artisan queue:forget <failed-job-id>` only after its Import history is retained.
Do not use `queue:flush` as routine recovery.

For an explicitly supervised one-off drain using the same production bounds:

```text
php artisan queue:work --queue=imports --stop-when-empty --max-time=600 --timeout=600 --tries=2
```

## Common symptoms

- **Overdue:** verify cron calls `schedule:run` every minute, then check queue backlog,
  failed jobs, PHP CLI path, database/cache availability and hosting execution limits.
- **Running for more than 15 minutes:** treat as stale/incomplete. Check OS/hosting
  processes and the database queue before recovery. Never remove a cache lock solely to
  make the UI green.
- **Repeatedly failing:** two consecutive failed/incomplete runs. Disable automatic
  imports, inspect safe codes, correct the cause and perform one supervised fresh run.
- **Skipped / source locked:** another run owns the source lock. Wait for its bounded
  timeout and inspect that run. Different sources may continue normally.
- **Unsafe/incomplete snapshot:** reconciliation was prevented. Do not mark missing jobs,
  delete Vacancies, edit `missing_since`, or retry until the data/mapping cause is known.
- **Large count drop:** compare with the provider/source and previous verified snapshot.
  The warning is informational and is not proof of an invalid feed.

## Disable and escalate

Disable **Automatisch importeren** to stop new scheduled dispatches while retaining manual
diagnostics and history. Set the entire source inactive only when no execution should be
allowed. Never delete source data or Vacancies as an incident response.

Escalate when credentials may be compromised, a source cannot produce a complete safe
snapshot, queue/lock state cannot be reconciled, counts differ materially without a
provider explanation, or publication/removal behavior is disputed. Preserve Import and
failed-job evidence. Application rollback must use the normal reviewed release process;
do not roll back migrations or production data ad hoc.

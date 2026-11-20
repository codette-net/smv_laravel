<?php

namespace App\Console\Commands;

use App\Support\Analytics\OutboundApplyClickPruner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PruneOutboundApplyClicks extends Command
{
    protected $signature = 'analytics:prune-outbound-clicks
                            {--dry-run : Count expired events without deleting them}';

    protected $description = 'Remove raw outbound apply clicks older than the approved 24-month retention period';

    public function handle(OutboundApplyClickPruner $pruner): int
    {
        try {
            $cutoff = $pruner->cutoff();
            $dryRun = (bool) $this->option('dry-run');
            $count = $dryRun
                ? $pruner->countExpired($cutoff)
                : $pruner->prune($cutoff);

            $context = [
                'ran_at_utc' => now('UTC')->toIso8601String(),
                'cutoff_utc' => $cutoff->toIso8601String(),
                'dry_run' => $dryRun,
                'eligible_count' => $count,
                'deleted_count' => $dryRun ? 0 : $count,
            ];

            Log::info('Outbound apply click retention completed.', $context);

            if ($dryRun) {
                $this->info("Dry run: {$count} outbound apply click event(s) are older than {$context['cutoff_utc']}; nothing deleted.");
            } else {
                $this->info("Deleted {$count} outbound apply click event(s) older than {$context['cutoff_utc']}.");
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Outbound apply click retention failed.', [
                'ran_at_utc' => now('UTC')->toIso8601String(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            $this->error('Outbound apply click retention failed. Review the application log.');

            return self::FAILURE;
        }
    }
}

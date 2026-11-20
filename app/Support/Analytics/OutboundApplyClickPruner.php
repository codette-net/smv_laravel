<?php

namespace App\Support\Analytics;

use App\Models\OutboundApplyClick;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class OutboundApplyClickPruner
{
    public const RETENTION_MONTHS = 24;

    public const BATCH_SIZE = 1000;

    public function cutoff(?CarbonInterface $reference = null): CarbonImmutable
    {
        $reference ??= now('UTC');

        return CarbonImmutable::instance($reference)
            ->utc()
            ->subMonthsNoOverflow(self::RETENTION_MONTHS);
    }

    public function countExpired(CarbonInterface $cutoff): int
    {
        return OutboundApplyClick::query()
            ->where('clicked_at', '<', $cutoff)
            ->count();
    }

    public function prune(CarbonInterface $cutoff, int $batchSize = self::BATCH_SIZE): int
    {
        if ($batchSize < 1) {
            throw new InvalidArgumentException('The outbound click prune batch size must be at least one.');
        }

        $lastId = 0;
        $deleted = 0;
        $upperId = OutboundApplyClick::query()
            ->where('clicked_at', '<', $cutoff)
            ->max('id');

        if ($upperId === null) {
            return 0;
        }

        do {
            $ids = OutboundApplyClick::query()
                ->where('clicked_at', '<', $cutoff)
                ->where('id', '>', $lastId)
                ->where('id', '<=', $upperId)
                ->orderBy('id')
                ->limit($batchSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $lastId = (int) $ids->last();
            $deleted += OutboundApplyClick::query()
                ->where('clicked_at', '<', $cutoff)
                ->whereKey($ids)
                ->delete();
        } while ($lastId < $upperId);

        return $deleted;
    }
}

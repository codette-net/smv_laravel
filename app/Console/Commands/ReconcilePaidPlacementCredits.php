<?php

namespace App\Console\Commands;

use App\Actions\Placements\GrantPaidOrderPlacementCredits;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Command;

class ReconcilePaidPlacementCredits extends Command
{
    protected $signature = 'placements:reconcile-paid-orders {--order= : Reconcile one Order ID}';

    protected $description = 'Idempotently grant missing placement credits from paid OrderItem snapshots';

    public function handle(GrantPaidOrderPlacementCredits $grant): int
    {
        $query = Order::query()->where('status', OrderStatus::Paid->value)->orderBy('id');
        if ($this->option('order') !== null) {
            $query->whereKey((int) $this->option('order'));
        }

        $orders = 0;
        $credits = 0;
        $failures = 0;
        $query->chunkById(100, function ($chunk) use ($grant, &$orders, &$credits, &$failures): void {
            foreach ($chunk as $order) {
                $orders++;
                try {
                    $credits += $grant->execute($order)->count();
                } catch (\Throwable $exception) {
                    $failures++;
                    $this->error("Order {$order->id} ({$order->order_number}) was not reconciled: {$exception->getMessage()}");
                }
            }
        });

        $this->info("Reconciled {$orders} paid order(s); {$credits} eligible credit row(s) are present; {$failures} order(s) require review.");

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}

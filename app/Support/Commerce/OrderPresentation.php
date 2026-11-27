<?php

namespace App\Support\Commerce;

use App\Enums\OrderOperationStatus;
use App\Enums\OrderOperationType;
use App\Enums\PaymentStatus;
use App\Enums\PlacementCreditStatus;
use App\Models\Order;

final class OrderPresentation
{
    /** @return array{label: string, variant: string} */
    public static function paymentStatus(Order $order): array
    {
        $payment = $order->payments->sortByDesc('id')->first();
        $operation = $order->operations->sortByDesc('id')->first();

        $status = match ($payment?->status) {
            PaymentStatus::Open, PaymentStatus::Pending => ['label' => 'In afwachting', 'variant' => 'warning'],
            PaymentStatus::Paid => ['label' => 'Betaald', 'variant' => 'success'],
            PaymentStatus::Failed => ['label' => 'Mislukt', 'variant' => 'danger'],
            PaymentStatus::Cancelled => ['label' => 'Geannuleerd', 'variant' => 'danger'],
            PaymentStatus::Expired => ['label' => 'Verlopen', 'variant' => 'neutral'],
            PaymentStatus::Refunded => ['label' => 'Terugbetaald', 'variant' => 'info'],
            null => ['label' => 'Nog niet gestart', 'variant' => 'neutral'],
        };

        if ($operation?->type !== OrderOperationType::Refund) {
            return $status;
        }

        if ($operation->providerSucceeded()) {
            return ['label' => 'Terugbetaald', 'variant' => 'info'];
        }

        return match ($operation->status) {
            OrderOperationStatus::Requested, OrderOperationStatus::Pending => ['label' => 'Terugbetaling in behandeling', 'variant' => 'warning'],
            OrderOperationStatus::Failed => ['label' => 'Terugbetaling mislukt', 'variant' => 'danger'],
            OrderOperationStatus::RequiresReview => ['label' => 'Terugbetaling wordt gecontroleerd', 'variant' => 'warning'],
            default => $status,
        };
    }

    public static function requiresPlacementReview(Order $order): bool
    {
        return $order->placementCredits
            ->contains(fn ($credit): bool => $credit->status() === PlacementCreditStatus::RequiresReview);
    }

    public static function placementReviewLabel(Order $order): ?string
    {
        return self::requiresPlacementReview($order)
            ? 'Plaatsing: handmatige controle door SMV nodig'
            : null;
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Concept',
            self::Pending => 'In behandeling',
            self::PendingPayment => 'Wacht op betaling',
            self::Paid => 'Betaald',
            self::Cancelled => 'Geannuleerd',
            self::Failed => 'Mislukt',
            self::Refunded => 'Terugbetaald',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending, self::PendingPayment => 'warning',
            self::Paid => 'success',
            self::Cancelled, self::Failed => 'danger',
            self::Refunded => 'info',
        };
    }

    public function badgeVariant(): string
    {
        return $this->getColor() === 'gray' ? 'neutral' : $this->getColor();
    }
}

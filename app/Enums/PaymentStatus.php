<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    /** Historical open state; new attempts use Pending. */
    case Open = 'open';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open, self::Pending => 'In afwachting',
            self::Paid => 'Betaald',
            self::Failed => 'Mislukt',
            self::Cancelled => 'Geannuleerd',
            self::Expired => 'Verlopen',
            self::Refunded => 'Terugbetaald',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Open, self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed, self::Cancelled => 'danger',
            self::Expired => 'gray',
            self::Refunded => 'info',
        };
    }

    public function badgeVariant(): string
    {
        return $this->getColor() === 'gray' ? 'neutral' : $this->getColor();
    }
}

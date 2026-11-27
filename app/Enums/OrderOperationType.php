<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OrderOperationType: string implements HasLabel
{
    case Cancellation = 'cancellation';
    case Refund = 'refund';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cancellation => 'Annulering',
            self::Refund => 'Terugbetaling',
        };
    }
}

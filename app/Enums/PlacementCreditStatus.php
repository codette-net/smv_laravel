<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PlacementCreditStatus: string implements HasColor, HasLabel
{
    case Available = 'available';
    case Allocated = 'allocated';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Expired = 'expired';
    case Reversed = 'reversed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Available => 'Beschikbaar',
            self::Allocated => 'Toegewezen',
            self::Scheduled => 'Ingepland',
            self::Active => 'Actief',
            self::Expired => 'Verlopen',
            self::Reversed => 'Teruggedraaid',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Available => 'success',
            self::Allocated, self::Scheduled => 'warning',
            self::Active => 'info',
            self::Expired, self::Reversed => 'danger',
        };
    }
}

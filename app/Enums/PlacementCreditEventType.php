<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PlacementCreditEventType: string implements HasLabel
{
    case Granted = 'granted';
    case Allocated = 'allocated';
    case Activated = 'activated';
    case Reversed = 'reversed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Granted => 'Toegekend',
            self::Allocated => 'Toegewezen',
            self::Activated => 'Geactiveerd',
            self::Reversed => 'Teruggedraaid',
        };
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PackageType: string implements HasLabel
{
    case Placement = 'placement';
    case Custom = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::Placement => 'Vacatureplaatsing',
            self::Custom => 'Maatwerk / offerte',
        };
    }
}

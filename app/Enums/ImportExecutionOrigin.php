<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ImportExecutionOrigin: string implements HasLabel
{
    case Manual = 'manual';
    case Scheduled = 'scheduled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Handmatig',
            self::Scheduled => 'Gepland',
        };
    }
}

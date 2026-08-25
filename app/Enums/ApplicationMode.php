<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ApplicationMode: string implements HasLabel
{
    case External = 'external';
    case Email = 'email';
    case Internal = 'internal';

    public function getLabel(): string
    {
        return match ($this) {
            self::External => 'Externe sollicitatielink',
            self::Email => 'Solliciteren via e-mail',
            self::Internal => 'Solliciteren via SMV',
        };
    }
}

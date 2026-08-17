<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VacancySource: string implements HasLabel
{
    case Manual = 'manual';
    case Import = 'import';
    case Api = 'api';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Handmatig',
            self::Import => 'Import',
            self::Api => 'API',
        };
    }
}

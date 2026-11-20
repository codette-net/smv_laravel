<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ImportMissingVacancyPolicy: string implements HasColor, HasLabel
{
    case ReviewRequired = 'review_required';
    case AutoOffline = 'auto_offline';

    public function getLabel(): string
    {
        return match ($this) {
            self::ReviewRequired => 'Eerst beoordelen',
            self::AutoOffline => 'Automatisch offline',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ReviewRequired => 'warning',
            self::AutoOffline => 'danger',
        };
    }
}

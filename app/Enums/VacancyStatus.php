<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VacancyStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'published';
    case Expired = 'expired';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Concept',
            self::Pending => 'In afwachting',
            self::Active => 'Gepubliceerd',
            self::Expired => 'Verlopen',
            self::Archived => 'Gearchiveerd',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Active => 'success',
            self::Expired, self::Archived => 'danger',
        };
    }
}

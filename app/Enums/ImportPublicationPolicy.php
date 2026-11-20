<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ImportPublicationPolicy: string implements HasColor, HasLabel
{
    case ReviewRequired = 'review_required';
    case AutoPublish = 'auto_publish';

    public function getLabel(): string
    {
        return match ($this) {
            self::ReviewRequired => 'Eerst beoordelen',
            self::AutoPublish => 'Automatisch publiceren',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ReviewRequired => 'warning',
            self::AutoPublish => 'success',
        };
    }
}

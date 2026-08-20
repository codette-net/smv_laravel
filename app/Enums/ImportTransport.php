<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ImportTransport: string implements HasLabel
{
    case Upload = 'upload';
    case Http = 'http';
    case Api = 'api';

    public function getLabel(): string
    {
        return match ($this) {
            self::Upload => 'Bestand uploaden',
            self::Http => 'HTTP-feed',
            self::Api => 'API',
        };
    }
}

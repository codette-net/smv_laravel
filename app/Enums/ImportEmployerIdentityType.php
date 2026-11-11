<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ImportEmployerIdentityType: string implements HasLabel
{
    case ProviderId = 'provider_id';
    case ReviewedComposite = 'reviewed_composite';

    public function getLabel(): string
    {
        return match ($this) {
            self::ProviderId => 'Provider-ID',
            self::ReviewedComposite => 'Beoordeelde naam + URL',
        };
    }
}

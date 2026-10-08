<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SalaryBasis: string implements HasLabel
{
    case GrossFullTimeEquivalent = 'gross_fte';
    case GrossOfferedHours = 'gross_offered_hours';
    case Unknown = 'unknown';

    public function getLabel(): string
    {
        return match ($this) {
            self::GrossFullTimeEquivalent => 'Bruto fulltime-equivalent (FTE)',
            self::GrossOfferedHours => 'Bruto voor de aangeboden uren',
            self::Unknown => 'Onbekend of niet opgegeven',
        };
    }
}

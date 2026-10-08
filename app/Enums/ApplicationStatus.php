<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ApplicationStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Reviewed = 'reviewed';
    case Contacted = 'contacted';
    case Rejected = 'rejected';
    case Hired = 'hired';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Nieuw',
            self::Reviewed => 'Beoordeeld',
            self::Contacted => 'Contact opgenomen',
            self::Rejected => 'Afgewezen',
            self::Hired => 'Aangenomen',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::New => 'info',
            self::Reviewed => 'warning',
            self::Contacted => 'primary',
            self::Rejected => 'danger',
            self::Hired => 'success',
        };
    }

    public function candidateLabel(): string
    {
        return match ($this) {
            self::New => 'Ontvangen',
            self::Reviewed, self::Contacted => 'In behandeling',
            self::Rejected => 'Afgewezen',
            self::Hired => 'Aangenomen',
        };
    }

    public function candidateBadgeVariant(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Reviewed, self::Contacted => 'warning',
            self::Rejected => 'danger',
            self::Hired => 'success',
        };
    }
}

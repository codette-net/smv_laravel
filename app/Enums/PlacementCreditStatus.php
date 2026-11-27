<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PlacementCreditStatus: string implements HasColor, HasLabel
{
    case Available = 'available';
    case Allocated = 'allocated';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Expired = 'expired';
    case Reversed = 'reversed';
    case RequiresReview = 'requires_review';

    public function getLabel(): string
    {
        return match ($this) {
            self::Available => 'Beschikbaar',
            self::Allocated => 'Toegewezen',
            self::Scheduled => 'Ingepland',
            self::Active => 'Actief',
            self::Expired => 'Verlopen',
            self::Reversed => 'Teruggedraaid',
            self::RequiresReview => 'Controle vereist',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Available => 'success',
            self::Allocated, self::Scheduled => 'warning',
            self::Active => 'success',
            self::Expired => 'gray',
            self::Reversed => 'danger',
            self::RequiresReview => 'warning',
        };
    }

    public function badgeVariant(): string
    {
        return $this->getColor() === 'gray' ? 'neutral' : $this->getColor();
    }
}

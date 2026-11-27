<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderOperationStatus: string implements HasColor, HasLabel
{
    case Requested = 'requested';
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case RequiresReview = 'requires_review';

    public function getLabel(): string
    {
        return match ($this) {
            self::Requested => 'Aangevraagd',
            self::Pending => 'In behandeling',
            self::Succeeded => 'Voltooid',
            self::Failed => 'Mislukt',
            self::RequiresReview => 'Controle vereist',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Requested, self::Pending => 'warning',
            self::Succeeded => 'success',
            self::Failed => 'danger',
            self::RequiresReview => 'info',
        };
    }
}

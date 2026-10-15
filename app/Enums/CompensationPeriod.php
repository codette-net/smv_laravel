<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompensationPeriod: string implements HasLabel
{
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hour => 'Per uur',
            self::Day => 'Per dag',
            self::Week => 'Per week',
            self::Month => 'Per maand',
            self::Year => 'Per jaar',
        };
    }
}

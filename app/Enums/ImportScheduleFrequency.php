<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Carbon;

enum ImportScheduleFrequency: string implements HasLabel
{
    case Hourly = 'hourly';
    case EverySixHours = 'every_six_hours';
    case Daily = 'daily';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hourly => 'Elk uur',
            self::EverySixHours => 'Elke 6 uur',
            self::Daily => 'Dagelijks',
        };
    }

    public function isDue(?Carbon $lastScheduledAt, Carbon $now): bool
    {
        if ($lastScheduledAt === null) {
            return true;
        }

        return $lastScheduledAt->lte(match ($this) {
            self::Hourly => $now->copy()->subHour(),
            self::EverySixHours => $now->copy()->subHours(6),
            self::Daily => $now->copy()->subDay(),
        });
    }
}

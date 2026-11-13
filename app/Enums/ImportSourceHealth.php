<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ImportSourceHealth: string implements HasColor, HasLabel
{
    case Healthy = 'healthy';
    case NeverRun = 'never_run';
    case Running = 'running';
    case Overdue = 'overdue';
    case RepeatedlyFailing = 'repeatedly_failing';
    case RecentFailure = 'recent_failure';
    case Incomplete = 'incomplete';
    case Skipped = 'skipped';
    case Disabled = 'disabled';
    case ManualOnly = 'manual_only';

    public function getLabel(): string
    {
        return match ($this) {
            self::Healthy => 'Gezond',
            self::NeverRun => 'Nog nooit uitgevoerd',
            self::Running => 'Bezig',
            self::Overdue => 'Te laat',
            self::RepeatedlyFailing => 'Herhaald mislukt',
            self::RecentFailure => 'Recent mislukt',
            self::Incomplete => 'Laatste run onvolledig',
            self::Skipped => 'Laatste run overgeslagen',
            self::Disabled => 'Uitgeschakeld',
            self::ManualOnly => 'Alleen handmatig',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Healthy => 'success',
            self::Running => 'info',
            self::NeverRun, self::ManualOnly, self::Skipped, self::Disabled => 'gray',
            self::RecentFailure, self::Incomplete, self::Overdue => 'warning',
            self::RepeatedlyFailing => 'danger',
        };
    }
}

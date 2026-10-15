<?php

namespace App\Filament\Widgets;

use App\Support\Analytics\SavedContentStatistics;
use Filament\Widgets\Widget;

class SavedContentStatisticsWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.saved-content-statistics-widget';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super-admin', 'admin', 'editor']) === true;
    }

    protected function getViewData(): array
    {
        $statistics = app(SavedContentStatistics::class);

        return [
            'currentVacancySaves' => $statistics->currentVacancySaves(),
            'currentCompanySaves' => $statistics->currentCompanySaves(),
            'topVacancies' => $statistics->topVacancies(),
            'topCompanies' => $statistics->topCompanies(),
        ];
    }
}

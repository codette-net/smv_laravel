<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\ImportSource;
use App\Models\Vacancy;
use App\Support\Analytics\OutboundApplyClickCsv;
use App\Support\Analytics\OutboundApplyClickFilters;
use App\Support\Analytics\OutboundApplyClickReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class OutboundApplyClickReporting extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Vacature-engagement';

    protected static ?string $navigationLabel = 'Vacature-engagement';

    protected static string|UnitEnum|null $navigationGroup = 'Rapportage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'rapportage/outbound-apply-clicks';

    protected string $view = 'filament.pages.outbound-apply-click-reporting';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super-admin', 'admin']) === true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => app(OutboundApplyClickReport::class)->vacancies($this->reportFilters()))
            ->columns([
                TextColumn::make('title')
                    ->label('Vacature')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('company.name')
                    ->label('Bedrijf')
                    ->sortable()
                    ->wrap(),
                TextColumn::make('importSource.name')
                    ->label('Importbron / partner')
                    ->placeholder('Geen importbron')
                    ->sortable()
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Huidige status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('outbound_apply_clicks_count')
                    ->label('Externe clicks (ruw)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('vacancy_detail_views_count')
                    ->label('Detailweergaven (ruw)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('raw_outbound_apply_ctr')
                    ->label('Externe sollicitatie-CTR (ruw)')
                    ->state(fn (Vacancy $record): string => $this->formatCtr(
                        (int) $record->outbound_apply_clicks_count,
                        (int) $record->vacancy_detail_views_count,
                    )),
            ])
            ->filters([
                Filter::make('period')
                    ->label('Periode (UTC)')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Datum vanaf')
                            ->default(now('UTC')->subDays(29)->toDateString())
                            ->native(false),
                        DatePicker::make('to')
                            ->label('Datum tot en met')
                            ->default(now('UTC')->toDateString())
                            ->native(false),
                    ])
                    ->columns(2)
                    ->indicateUsing(fn (array $state): string => sprintf(
                        'UTC: %s t/m %s',
                        $state['from'] ?? now('UTC')->subDays(29)->toDateString(),
                        $state['to'] ?? now('UTC')->toDateString(),
                    )),
                SelectFilter::make('vacancy')
                    ->label('Vacature')
                    ->options(fn (): array => Vacancy::query()->withTrashed()->orderBy('title')->get()->mapWithKeys(
                        fn (Vacancy $vacancy): array => [$vacancy->getKey() => "{$vacancy->title} (#{$vacancy->getKey()})"],
                    )->all())
                    ->searchable()
                    ->preload()
                    ->static(),
                SelectFilter::make('company')
                    ->label('Bedrijf')
                    ->options(fn (): array => Company::query()->withTrashed()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->static(),
                SelectFilter::make('import_source')
                    ->label('Importbron / partner')
                    ->options(fn (): array => ImportSource::query()->withTrashed()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->static(),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByDesc('outbound_apply_clicks_count')
                ->orderBy('title')
                ->orderBy('id'))
            ->emptyStateHeading('Geen detailweergaven of outbound apply clicks binnen deze filters')
            ->emptyStateDescription('Pas de periode of filters aan.');
    }

    public function downloadCsv(): StreamedResponse
    {
        $filters = $this->reportFilters();
        $csv = app(OutboundApplyClickCsv::class)->generate($filters);
        $filename = "outbound-apply-clicks-{$filters->fromDate()}-{$filters->toDate()}.csv";

        return response()->streamDownload(
            fn () => print $csv,
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadCsv')
                ->label('Gefilterde CSV downloaden')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (): StreamedResponse => $this->downloadCsv()),
        ];
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $filters = $this->reportFilters();
        $report = app(OutboundApplyClickReport::class);
        $totalClicks = $report->total($filters);
        $totalViews = $report->totalViews($filters);

        return [
            'totalClicks' => $totalClicks,
            'totalViews' => $totalViews,
            'totalCtr' => $this->formatCtr($totalClicks, $totalViews),
            'companyTotals' => $report->companies($filters),
            'sourceTotals' => $report->importSources($filters),
            'periodFrom' => $filters->fromDate(),
            'periodTo' => $filters->toDate(),
        ];
    }

    private function reportFilters(): OutboundApplyClickFilters
    {
        return OutboundApplyClickFilters::fromArray([
            'date_from' => data_get($this->getTableFilterState('period'), 'from'),
            'date_to' => data_get($this->getTableFilterState('period'), 'to'),
            'vacancy_id' => data_get($this->getTableFilterState('vacancy'), 'value'),
            'company_id' => data_get($this->getTableFilterState('company'), 'value'),
            'import_source_id' => data_get($this->getTableFilterState('import_source'), 'value'),
        ]);
    }

    public function formatCtr(int $clicks, int $views): string
    {
        $ctr = app(OutboundApplyClickReport::class)->rawCtr($clicks, $views);

        return $ctr === null ? 'N/B' : number_format($ctr, 2, ',', '.').' %';
    }
}

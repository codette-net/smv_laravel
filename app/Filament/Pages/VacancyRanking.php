<?php

namespace App\Filament\Pages;

use App\Enums\AdvertisingPackage;
use App\Enums\VacancyStatus;
use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Vacancy;
use App\Support\Commerce\CommercialPriority;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class VacancyRanking extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Vacatureranking';

    protected static ?string $navigationLabel = 'Vacatureranking';

    protected static string|UnitEnum|null $navigationGroup = 'Commercieel';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsUpDown;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'commercieel/vacatureranking';

    protected string $view = 'filament.pages.vacancy-ranking';

    public static function canAccess(): bool
    {
        return Gate::allows('manageRanking', Vacancy::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Vacancy::query()
                ->withCommercialPriority()
                ->with(['company', 'importSource']))
            ->columns([
                TextColumn::make('title')
                    ->label('Vacature / referentie')
                    ->description(fn (Vacancy $record): string => $record->source_reference
                        ?? $record->reference
                        ?? "#{$record->getKey()}")
                    ->searchable(['title', 'source_reference', 'reference'])
                    ->sortable()
                    ->wrap(),
                TextColumn::make('company.name')
                    ->label('Bedrijf')
                    ->url(fn (Vacancy $record): ?string => $record->company === null
                        ? null
                        : CompanyResource::getUrl('edit', ['record' => $record->company]))
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('placement_package')
                    ->label('Pakketintentie')
                    ->placeholder('Geen')
                    ->badge()
                    ->sortable(),
                TextColumn::make('package_priority')
                    ->label('Pakket')
                    ->numeric()
                    ->tooltip('Alleen actief binnen een geldig betaald en gepubliceerd plaatsingsrecht.'),
                TextColumn::make('company_priority')
                    ->label('Bedrijf')
                    ->numeric()
                    ->sortable(),
                TextInputColumn::make('commercial_priority_adjustment')
                    ->label('Vacature')
                    ->type('number')
                    ->step(1)
                    ->rules(['required', 'integer', 'between:'.CommercialPriority::MINIMUM.','.CommercialPriority::MAXIMUM])
                    ->validationAttribute('vacature-aanpassing')
                    ->updateStateUsing(function (Vacancy $record, mixed $state): int {
                        Gate::authorize('manageRanking', $record);
                        $value = (int) $state;
                        $record->update(['commercial_priority_adjustment' => $value]);

                        Notification::make()
                            ->title('Vacatureprioriteit opgeslagen')
                            ->success()
                            ->send();

                        return $value;
                    }),
                TextColumn::make('effective_priority')
                    ->label('Effectief')
                    ->numeric()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('published_at')
                    ->label('Publicatie')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('expires_at')
                    ->label('Verloopt')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('company')
                    ->label('Bedrijf')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(VacancyStatus::class),
                SelectFilter::make('placement_package')
                    ->label('Pakketintentie')
                    ->options(AdvertisingPackage::class),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByDesc('effective_priority')
                ->orderByDesc('published_at')
                ->orderByDesc('vacancies.id'))
            ->paginationPageOptions([25, 50, 100, 250])
            ->defaultPaginationPageOption(50)
            ->toolbarActions([
                BulkActionGroup::make([
                    $this->bulkPriorityAction('setPriority', 'Prioriteit instellen', 'set'),
                    $this->bulkPriorityAction('addPriority', 'Prioriteit optellen/aftrekken', 'add'),
                    BulkAction::make('resetPriority')
                        ->label('Prioriteit resetten')
                        ->requiresConfirmation()
                        ->authorizeIndividualRecords('manageRanking')
                        ->action(fn (Collection $records) => $this->applyBulkPriority($records, 'reset'))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('Geen vacatures gevonden');
    }

    private function bulkPriorityAction(string $name, string $label, string $operation): BulkAction
    {
        return BulkAction::make($name)
            ->label($label)
            ->schema([
                TextInput::make('value')
                    ->label($operation === 'set' ? 'Nieuwe vacatureprioriteit' : 'Wijziging')
                    ->numeric()
                    ->integer()
                    ->minValue(CommercialPriority::MINIMUM)
                    ->maxValue(CommercialPriority::MAXIMUM)
                    ->required(),
            ])
            ->authorizeIndividualRecords('manageRanking')
            ->action(fn (Collection $records, array $data) => $this->applyBulkPriority(
                $records,
                $operation,
                (int) $data['value'],
            ))
            ->deselectRecordsAfterCompletion();
    }

    /** @param Collection<int, Vacancy> $records */
    private function applyBulkPriority(Collection $records, string $operation, int $value = 0): void
    {
        $ids = $records->modelKeys();

        foreach ($records as $record) {
            Gate::authorize('manageRanking', $record);
        }

        DB::transaction(function () use ($ids, $operation, $value): void {
            $vacancies = Vacancy::query()
                ->whereKey($ids)
                ->lockForUpdate()
                ->get(['id', 'commercial_priority_adjustment']);

            $targets = $vacancies->mapWithKeys(function (Vacancy $vacancy) use ($operation, $value): array {
                $target = match ($operation) {
                    'set' => $value,
                    'add' => $vacancy->commercial_priority_adjustment + $value,
                    'reset' => 0,
                    default => throw new \LogicException('Unsupported ranking bulk operation.'),
                };

                if ($target < CommercialPriority::MINIMUM || $target > CommercialPriority::MAXIMUM) {
                    throw ValidationException::withMessages([
                        'value' => 'De resulterende prioriteit moet tussen '.CommercialPriority::MINIMUM.' en '.CommercialPriority::MAXIMUM.' liggen.',
                    ]);
                }

                return [$vacancy->getKey() => $target];
            });

            foreach ($targets as $id => $target) {
                Vacancy::query()->whereKey($id)->update(['commercial_priority_adjustment' => $target]);
            }
        });

        Notification::make()
            ->title('Vacatureprioriteiten bijgewerkt')
            ->body($records->count().' vacature(s) aangepast.')
            ->success()
            ->send();
    }
}

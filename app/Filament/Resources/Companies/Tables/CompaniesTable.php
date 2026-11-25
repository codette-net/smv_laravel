<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Support\Commerce\CommercialPriority;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with('user')
                ->withCount('vacancies'))
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo_media')
                    ->label('Logo')
                    ->collection('logo')
                    ->circular(),
                TextColumn::make('user.name')
                    ->label('Eigenaar')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label('Naam')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                TextColumn::make('status')
                    ->sortable()
                    ->badge(),
                TextColumn::make('location')
                    ->label('Locatie')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('Uitgelicht')
                    ->boolean()
                    ->sortable(),
                TextInputColumn::make('commercial_priority')
                    ->label('Commerciële prioriteit')
                    ->type('number')
                    ->step(1)
                    ->rules(['required', 'integer', 'between:'.CommercialPriority::MINIMUM.','.CommercialPriority::MAXIMUM])
                    ->sortable()
                    ->updateStateUsing(function (Company $record, mixed $state): int {
                        Gate::authorize('manageRanking', $record);
                        $value = (int) $state;
                        $record->update(['commercial_priority' => $value]);

                        Notification::make()->title('Bedrijfsprioriteit opgeslagen')->success()->send();

                        return $value;
                    }),
                TextColumn::make('vacancies_count')
                    ->label('Vacatures')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(CompanyStatus::class),
                TernaryFilter::make('is_featured')
                    ->label('Uitgelicht'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}

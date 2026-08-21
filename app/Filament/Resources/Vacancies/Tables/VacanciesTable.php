<?php

namespace App\Filament\Resources\Vacancies\Tables;

use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VacanciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['company', 'importSource']))
            ->columns([
                TextColumn::make('title')
                    ->label('Functietitel')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('company.name')
                    ->label('Bedrijf')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge(),
                TextColumn::make('lifecycle')
                    ->label('Huidige staat')
                    ->state(fn (Vacancy $record): string => self::lifecycleLabel($record))
                    ->badge()
                    ->color(fn (Vacancy $record): string => self::lifecycleColor($record)),
                IconColumn::make('is_featured')
                    ->label('Uitgelicht')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_filled')
                    ->label('Vervuld')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('location')
                    ->label('Locatie')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Publicatie')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('deadline_at')
                    ->label('Deadline')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('expires_at')
                    ->label('Verloopt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('importSource.name')
                    ->label('Importbron')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Verwijderd op')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Aangemaakt op')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Gewijzigd op')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(VacancyStatus::class),
                SelectFilter::make('company')
                    ->label('Bedrijf')
                    ->relationship('company', 'name')
                    ->searchable(),
                TernaryFilter::make('is_featured')
                    ->label('Uitgelicht'),
                TernaryFilter::make('is_filled')
                    ->label('Vervuld'),
                Filter::make('publicly_visible')
                    ->label('Momenteel publiek')
                    ->query(fn (Builder $query): Builder => $query->publiclyVisible()),
                TrashedFilter::make()
                    ->label('Verwijderd'),
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

    private static function lifecycleLabel(Vacancy $vacancy): string
    {
        return match (true) {
            $vacancy->is_filled => 'Vervuld',
            $vacancy->status !== VacancyStatus::Active => $vacancy->status->getLabel(),
            $vacancy->published_at?->isFuture() => 'Ingepland',
            $vacancy->deadline_at?->isPast() => 'Deadline verstreken',
            $vacancy->expires_at?->isPast() => 'Verlopen',
            default => 'Open',
        };
    }

    private static function lifecycleColor(Vacancy $vacancy): string
    {
        return match (self::lifecycleLabel($vacancy)) {
            'Open' => 'success',
            'Ingepland', 'Deadline verstreken' => 'warning',
            'Vervuld', 'Verlopen' => 'danger',
            default => 'gray',
        };
    }
}

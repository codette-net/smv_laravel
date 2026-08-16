<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\CompanyStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

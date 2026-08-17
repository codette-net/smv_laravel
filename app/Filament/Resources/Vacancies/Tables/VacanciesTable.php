<?php

namespace App\Filament\Resources\Vacancies\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class VacanciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->lineclamp(2),
                TextColumn::make('title')
                    ->label('Job Title/link')
                    ->searchable()
                    ->sortable()
                    ->limit(25, '...')
                    ->url(fn ($record): string => $record->vacancy_url())
                    ->openUrlInNewTab()
                    ->wrap()
                    ->lineclamp(2),
                //                TextColumn::make('slug')
                //                    ->searchable(),
                TextColumn::make('location')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->wrap()
                    ->lineclamp(2),
                //                TextColumn::make('application_email')
                //                    ->searchable(),
                //                TextColumn::make('application_url')
                //                    ->searchable(),
                //                TextColumn::make('salary_min')
                //                    ->numeric()
                //                    ->sortable(),
                //                TextColumn::make('salary_max')
                //                    ->numeric()
                //                    ->sortable(),
                //                TextColumn::make('rate_min')
                //                    ->numeric()
                //                    ->sortable(),
                //                TextColumn::make('rate_max')
                //                    ->numeric()
                //                    ->sortable(),
                //                TextColumn::make('reference')
                //                    ->searchable(),
                //                TextColumn::make('source_reference')
                //                    ->searchable(),
                TextColumn::make('deadline_at')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->wrap()
                    ->lineclamp(2),
                TextColumn::make('expires_at')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->wrap()
                    ->lineclamp(2),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_filled')
                    ->label('Filled')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                //                TextColumn::make('source')
                //                    ->searchable(),
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
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

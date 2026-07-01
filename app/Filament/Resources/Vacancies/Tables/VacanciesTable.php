<?php

namespace App\Filament\Resources\Vacancies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VacanciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('location')
                    ->searchable(),
                TextColumn::make('application_email')
                    ->searchable(),
                TextColumn::make('application_url')
                    ->searchable(),
                TextColumn::make('salary_min')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('salary_max')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rate_min')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rate_max')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reference')
                    ->searchable(),
                TextColumn::make('source_reference')
                    ->searchable(),
                TextColumn::make('deadline_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->boolean(),
                IconColumn::make('is_filled')
                    ->boolean(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('source')
                    ->searchable(),
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
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

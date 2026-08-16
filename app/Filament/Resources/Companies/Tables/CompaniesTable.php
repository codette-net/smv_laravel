<?php

namespace App\Filament\Resources\Companies\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->sortable()
                    ->limit(25, '...')
                    ->searchable(),
//                TextColumn::make('slug')
//                    ->searchable(),
//                TextColumn::make('tagline')
//                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'pending' => 'warning',
                        'suspended' => 'gray',
                        'banned' => 'danger',
                    }),
                TextColumn::make('location')
                    ->searchable()
                ->sortable(),

                IconColumn::make('is_featured')
                    ->boolean(),
//                TextColumn::make('phone')
//                    ->searchable(),
//                TextColumn::make('website')
//                    ->searchable()
//                    ->limit(30, '...'),
//                TextColumn::make('logo')
//                    ->searchable(),
//                ImageColumn::make('cover_image'),


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
            ],position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

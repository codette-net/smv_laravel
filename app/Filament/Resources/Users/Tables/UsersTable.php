<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('E-mailadres')
                    ->searchable(),
                TextColumn::make('account_context')
                    ->label('Accountcontext')
                    ->getStateUsing(fn (User $record): array => $record->accountContextLabels())
                    ->badge(),
                TextColumn::make('staff_roles')
                    ->label('Medewerkersrollen')
                    ->getStateUsing(fn (User $record): array => $record->staffRoleLabels())
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('companies.name')
                    ->label('Bedrijven')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->placeholder('-'),
                TextColumn::make('email_verified_at')
                    ->label('E-mail geverifieerd op')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Legacy registratieclassificatie')
                    ->searchable()
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

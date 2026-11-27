<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email')
                    ->label('E-mailadres'),
                TextEntry::make('account_context')
                    ->label('Accountcontext')
                    ->state(fn (User $record): array => $record->accountContextLabels())
                    ->badge(),
                TextEntry::make('staff_roles')
                    ->label('Medewerkersrollen')
                    ->state(fn (User $record): array => $record->staffRoleLabels())
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('companies.name')
                    ->label('Bedrijven')
                    ->listWithLineBreaks()
                    ->placeholder('-'),
                TextEntry::make('email_verified_at')
                    ->label('E-mail geverifieerd op')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('role')
                    ->label('Legacy registratieclassificatie')
                    ->helperText('Alleen voor achterwaartse compatibiliteit; verleent geen rechten.'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Support\Authorization\StaffRoleCatalog;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('E-mailadres')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label('E-mail geverifieerd op'),
                TextInput::make('password')
                    ->label('Wachtwoord')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Laat leeg om het bestaande wachtwoord te behouden.'),
                Select::make('staff_roles')
                    ->label('Medewerkersrollen')
                    ->multiple()
                    ->options(fn (): array => auth()->user() instanceof User
                        ? StaffRoleCatalog::assignableOptions(auth()->user())
                        : [])
                    ->helperText('Deze Spatie-rollen bepalen toegang tot het beheerpaneel. Kandidaat- en werkgeverscontext worden niet verwijderd.'),
                Placeholder::make('legacy_role')
                    ->label('Legacy registratieclassificatie')
                    ->content(fn (?User $record): string => $record?->role ?? 'candidate')
                    ->helperText('Alleen voor achterwaartse compatibiliteit; dit veld verleent geen rechten.')
                    ->visible(fn (?User $record): bool => $record !== null),
            ]);
    }
}

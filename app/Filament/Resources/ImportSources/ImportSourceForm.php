<?php

namespace App\Filament\Resources\ImportSources;

use App\Enums\ImportFormat;
use App\Enums\ImportTransport;
use App\Models\ImportSource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ImportSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Algemeen')->columns(2)->schema([
                TextInput::make('name')->label('Naam')->required()->maxLength(255),
                Select::make('company_id')->label('Bedrijf')->relationship('company', 'name')->required()->searchable()->preload(),
                Select::make('transport')->label('Transport')->options(ImportTransport::class)->required()->live(),
                Select::make('format')->label('Formaat')->options(ImportFormat::class)->required(),
                Toggle::make('is_active')->label('Actief')->default(true),
            ]),
            Section::make('Bron')->columns(2)->schema([
                TextInput::make('endpoint_url')->label('URL / endpoint')->url()->maxLength(2048)
                    ->visible(fn (Get $get): bool => in_array($get('transport'), [ImportTransport::Http->value, ImportTransport::Api->value], true))
                    ->required(fn (Get $get): bool => in_array($get('transport'), [ImportTransport::Http->value, ImportTransport::Api->value], true)),
                TextInput::make('record_path')->label('Recordpad')->maxLength(255)->placeholder('response.jobs.*')->columnSpanFull(),
                Textarea::make('configuration')->label('Niet-geheime configuratie (JSON)')->json()->rows(4)->columnSpanFull()
                    ->formatStateUsing(fn (?array $state): ?string => $state === null ? null : json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                    ->dehydrateStateUsing(fn (string $state): array => json_decode($state, true, 512, JSON_THROW_ON_ERROR)),
                Textarea::make('selection_rules')->label('Selectieregels (JSON)')->json()->rows(4)->columnSpanFull()
                    ->formatStateUsing(fn (?array $state): ?string => $state === null ? null : json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                    ->dehydrateStateUsing(fn (string $state): array => json_decode($state, true, 512, JSON_THROW_ON_ERROR)),
            ]),
            Section::make('Beveiliging')->schema([
                Textarea::make('credentials')->label('Inloggegevens (JSON)')->json()->rows(4)
                    ->helperText('Alleen invullen om nieuwe inloggegevens op te slaan. Bestaande waarden worden nooit getoond.')
                    ->formatStateUsing(fn () => null)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): array => json_decode($state, true, 512, JSON_THROW_ON_ERROR)),
                Toggle::make('approved_for_automatic_run')->label('Goedgekeurd voor automatische productie-uitvoering')
                    ->visible(fn (): bool => auth()->user()?->can('approve', ImportSource::class) ?? false)
                    ->dehydrated(false),
            ]),
        ]);
    }
}

<?php

namespace App\Filament\Resources\ImportSources;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ImportSourceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Overzicht')->columns(2)->schema([
                TextEntry::make('name')->label('Naam'), TextEntry::make('company.name')->label('Bedrijf'),
                TextEntry::make('transport')->label('Transport')->badge(), TextEntry::make('format')->label('Formaat')->badge(),
                IconEntry::make('is_active')->label('Actief')->boolean(), TextEntry::make('record_path')->label('Recordpad')->placeholder('-'),
                TextEntry::make('endpoint_url')->label('URL / endpoint')->url(fn (?string $state): ?string => $state)->placeholder('-')->columnSpanFull(),
            ]),
            Section::make('Beveiliging en administratie')->columns(2)->schema([
                TextEntry::make('approved_at')->label('Goedgekeurd op')->dateTime()->placeholder('Niet goedgekeurd'),
                TextEntry::make('approvedBy.name')->label('Goedgekeurd door')->placeholder('-'),
                TextEntry::make('last_imported_at')->label('Laatst geïmporteerd')->dateTime()->placeholder('-'),
                TextEntry::make('created_at')->label('Aangemaakt')->dateTime(),
                TextEntry::make('updated_at')->label('Bijgewerkt')->dateTime(),
            ]),
        ]);
    }
}

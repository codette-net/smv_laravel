<?php

namespace App\Filament\Resources\Vacancies\Schemas;

use App\Models\Vacancy;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VacancyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Algemeen')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('title')
                            ->label('Functietitel')
                            ->columnSpanFull(),
                        TextEntry::make('company.name')
                            ->label('Bedrijf'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        IconEntry::make('is_featured')
                            ->label('Uitgelicht')
                            ->boolean(),
                        IconEntry::make('is_filled')
                            ->label('Vervuld')
                            ->boolean(),
                        TextEntry::make('categories.name')
                            ->label('Categorieën')
                            ->badge()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Vacaturetekst')
                    ->schema([
                        TextEntry::make('description')
                            ->label('Beschrijving')
                            ->html()
                            ->columnSpanFull(),
                    ]),
                Section::make('Locatie en voorwaarden')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('location')
                            ->label('Locatie')
                            ->placeholder('-'),
                        TextEntry::make('reference')
                            ->label('Interne referentie')
                            ->placeholder('-'),
                        TextEntry::make('salary_min')
                            ->label('Salaris vanaf')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('salary_max')
                            ->label('Salaris tot')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('rate_min')
                            ->label('Tarief vanaf')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('rate_max')
                            ->label('Tarief tot')
                            ->numeric()
                            ->placeholder('-'),
                    ]),
                Section::make('Publicatie')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('published_at')
                            ->label('Publicatie')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('deadline_at')
                            ->label('Deadline')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('expires_at')
                            ->label('Verloopt')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
                Section::make('Solliciteren')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('application_email')
                            ->label('Sollicitatie e-mailadres')
                            ->placeholder('-'),
                        TextEntry::make('application_url')
                            ->label('Externe sollicitatielink')
                            ->url(fn (?string $state): ?string => $state)
                            ->placeholder('-'),
                    ]),
                Section::make('Import en bron')
                    ->columns(2)
                    ->visible(fn (Vacancy $record): bool => $record->import_source_id !== null)
                    ->schema([
                        TextEntry::make('importSource.name')
                            ->label('Importbron'),
                        TextEntry::make('source_reference')
                            ->label('Bronreferentie')
                            ->placeholder('-'),
                    ]),
                Section::make('Administratie')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('slug')
                            ->label('Slug'),
                        TextEntry::make('deleted_at')
                            ->label('Verwijderd op')
                            ->dateTime()
                            ->visible(fn (Vacancy $record): bool => $record->trashed()),
                        TextEntry::make('created_at')
                            ->label('Aangemaakt op')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Gewijzigd op')
                            ->dateTime(),
                    ]),
            ]);
    }
}

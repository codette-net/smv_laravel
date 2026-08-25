<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Models\Company;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Overzicht')
                    ->columns(2)
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('logo_media')
                            ->label('Logo')
                            ->collection('logo'),
                        SpatieMediaLibraryImageEntry::make('cover_media')
                            ->label('Omslagafbeelding')
                            ->collection('cover'),
                        TextEntry::make('name')
                            ->label('Naam'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        IconEntry::make('is_featured')
                            ->label('Uitgelicht')
                            ->boolean(),
                        TextEntry::make('user.name')
                            ->label('Eigenaar')
                            ->placeholder('-'),
                        TextEntry::make('vacancies_count')
                            ->label('Aantal vacatures')
                            ->state(fn (Company $record): int => $record->vacancies()->count()),
                        TextEntry::make('categories.name')
                            ->label('Categorieën')
                            ->badge()
                            ->separator(',')
                            ->placeholder('-'),
                        TextEntry::make('tagline')
                            ->label('Korte introductie')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->label('Beschrijving')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Contact')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('email')
                            ->label('E-mailadres')
                            ->placeholder('-'),
                        TextEntry::make('phone')
                            ->label('Telefoon')
                            ->placeholder('-'),
                        TextEntry::make('website')
                            ->label('Website')
                            ->placeholder('-'),
                        TextEntry::make('location')
                            ->label('Locatie')
                            ->placeholder('-'),
                    ]),
                Section::make('Sociale media en extern')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('linkedin_url')->label('LinkedIn')->placeholder('-'),
                        TextEntry::make('facebook_url')->label('Facebook')->placeholder('-'),
                        TextEntry::make('instagram_url')->label('Instagram')->placeholder('-'),
                        TextEntry::make('video_url')->label('Video')->placeholder('-'),
                    ]),
                Section::make('Administratie')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('slug')->label('Slug'),
                        TextEntry::make('deleted_at')
                            ->label('Verwijderd op')
                            ->dateTime()
                            ->visible(fn (Company $record): bool => $record->trashed()),
                        TextEntry::make('created_at')->label('Aangemaakt op')->dateTime(),
                        TextEntry::make('updated_at')->label('Bijgewerkt op')->dateTime(),
                    ]),
            ]);
    }
}

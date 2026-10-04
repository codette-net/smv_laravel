<?php

namespace App\Filament\Resources\Vacancies\Schemas;

use App\Enums\ApplicationMode;
use App\Enums\CategoryType;
use App\Enums\VacancyStatus;
use App\Models\Category;
use App\Models\Vacancy;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class VacancyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Algemeen')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Functietitel')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('company_id')
                            ->label('Bedrijf')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->required(),
                        Toggle::make('is_featured')
                            ->label('Uitgelicht')
                            ->default(false),
                        Toggle::make('is_filled')
                            ->label('Vervuld')
                            ->default(false),
                    ]),
                Section::make('Taxonomie en tags')
                    ->description('Gebruik vaste taxonomieën voor filterbare vacaturekenmerken en tags alleen voor vrije, beschrijvende onderwerpen.')
                    ->columns(2)
                    ->schema([
                        ...self::taxonomyFields(),
                        SpatieTagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Bijvoorbeeld AI, CRM of B2B')
                            ->helperText('Gebruik geen tags voor dienstverband, werklocatie, senioriteit of functiegebied.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Vacaturetekst')
                    ->schema([
                        RichEditor::make('description')
                            ->label('Beschrijving')
                            ->required()
                            ->minLength(50)
                            ->maxLength(20000)
                            ->toolbarButtons([
                                ['bold', 'italic', 'link'],
                                ['h2', 'h3'],
                                ['bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ])
                            ->helperText('Toegestaan: alinea’s, tussenkoppen, vet, cursief, lijsten en veilige links.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Locatie en voorwaarden')
                    ->columns(2)
                    ->schema([
                        TextInput::make('location')
                            ->label('Locatie')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('salary_min')
                            ->label('Salaris vanaf')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('salary_max')
                            ->label('Salaris tot')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('rate_min')
                            ->label('Tarief vanaf')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('rate_max')
                            ->label('Tarief tot')
                            ->numeric()
                            ->minValue(0),
                    ]),
                Section::make('Publicatie')
                    ->description('Beheer wanneer de vacature zichtbaar wordt en wanneer reageren of publicatie eindigt.')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(VacancyStatus::class)
                            ->required()
                            ->default(VacancyStatus::Draft->value),
                        DateTimePicker::make('published_at')
                            ->label('Publiceren op')
                            ->helperText('Laat leeg om bij de status Gepubliceerd direct te publiceren. Kies een toekomstig moment om de vacature later automatisch zichtbaar te maken.'),
                        DateTimePicker::make('expires_at')
                            ->label('Verloopt op')
                            ->helperText('Na dit moment is de vacature niet meer publiek actief.'),
                        DateTimePicker::make('deadline_at')
                            ->label('Solliciteren vóór')
                            ->helperText('Na dit moment kunnen kandidaten niet meer solliciteren en is de vacature volgens de huidige MVP-regel niet meer publiek zichtbaar.')
                            ->default(fn () => now()->addMonths(2)),
                    ]),
                Section::make('Solliciteren')
                    ->description('De gekozen manier bepaalt welke bestemming bezoekers op de vacaturepagina zien.')
                    ->columns(2)
                    ->schema([
                        Select::make('application_mode')
                            ->label('Sollicitatiemethode')
                            ->options(ApplicationMode::class)
                            ->required()
                            ->default(ApplicationMode::Internal->value)
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('application_email')
                            ->label('Sollicitatie e-mailadres')
                            ->email()
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => $get('application_mode') === ApplicationMode::Email->value)
                            ->required(fn (Get $get): bool => $get('application_mode') === ApplicationMode::Email->value),
                        TextInput::make('application_url')
                            ->label('Externe sollicitatielink')
                            ->url()
                            ->maxLength(2048)
                            ->visible(fn (Get $get): bool => $get('application_mode') === ApplicationMode::External->value)
                            ->required(fn (Get $get): bool => $get('application_mode') === ApplicationMode::External->value),
                    ]),
                Section::make('Import en bron')
                    ->description('Deze herkomstgegevens worden beheerd door de importworkflow.')
                    ->columns(2)
                    ->visible(fn (?Vacancy $record): bool => $record?->import_source_id !== null)
                    ->schema([
                        Select::make('import_source_id')
                            ->label('Importbron')
                            ->relationship('importSource', 'name')
                            ->disabled(),
                        TextInput::make('source_reference')
                            ->label('Bronreferentie')
                            ->disabled(),
                        TextInput::make('reference')
                            ->label('Interne referentie')
                            ->disabled(),
                    ]),
            ]);
    }

    /** @return array<int, Select> */
    private static function taxonomyFields(): array
    {
        return [
            self::taxonomyField('employment_type_categories', 'Dienstverband', CategoryType::employment_type),
            self::taxonomyField('workplace_categories', 'Werklocatie', CategoryType::workplace),
            self::taxonomyField('sector_categories', 'Sector', CategoryType::sector),
            self::taxonomyField('function_area_categories', 'Functiegebied', CategoryType::function_area),
            self::taxonomyField('experience_categories', 'Ervaring', CategoryType::experience),
        ];
    }

    private static function taxonomyField(string $name, string $label, CategoryType $type): Select
    {
        return Select::make($name)
            ->label($label)
            ->multiple()
            ->options(fn (): array => Category::query()
                ->where('type', $type->value)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all())
            ->searchable()
            ->preload()
            ->dehydrated(false);
    }
}

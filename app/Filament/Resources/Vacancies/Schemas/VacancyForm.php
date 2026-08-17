<?php

namespace App\Filament\Resources\Vacancies\Schemas;

use App\Enums\CategoryType;
use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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
                        Select::make('status')
                            ->label('Status')
                            ->options(VacancyStatus::class)
                            ->required()
                            ->default(VacancyStatus::Draft->value),
                        Toggle::make('is_featured')
                            ->label('Uitgelicht')
                            ->default(false),
                        Toggle::make('is_filled')
                            ->label('Vervuld')
                            ->default(false),
                        Select::make('categories')
                            ->label('Categorieën')
                            ->multiple()
                            ->relationship(
                                'categories',
                                'name',
                                fn (Builder $query): Builder => $query->whereIn('type', [
                                    CategoryType::vacancy_category->value,
                                    CategoryType::job_type->value,
                                    CategoryType::career_level->value,
                                    CategoryType::experience->value,
                                    CategoryType::qualification->value,
                                ]),
                            )
                            ->searchable()
                            ->columnSpanFull(),
                    ]),
                Section::make('Vacaturetekst')
                    ->schema([
                        RichEditor::make('description')
                            ->label('Beschrijving')
                            ->required()
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
                    ->description('Deadline en verloopdatum zijn afzonderlijke, optionele momenten.')
                    ->columns(3)
                    ->schema([
                        DateTimePicker::make('published_at')
                            ->label('Publiceren op'),
                        DateTimePicker::make('deadline_at')
                            ->label('Sollicitatiedeadline')
                            ->default(fn () => now()->addMonths(2)),
                        DateTimePicker::make('expires_at')
                            ->label('Verloopt op'),
                    ]),
                Section::make('Solliciteren')
                    ->description('Gebruik een e-mailadres, een externe sollicitatielink of beide.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('application_email')
                            ->label('Sollicitatie e-mailadres')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('application_url')
                            ->label('Externe sollicitatielink')
                            ->url()
                            ->maxLength(2048),
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
}

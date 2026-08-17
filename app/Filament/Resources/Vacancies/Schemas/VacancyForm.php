<?php

namespace App\Filament\Resources\Vacancies\Schemas;

use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VacancyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('location'),
                TextInput::make('application_email')
                    ->email(),
                TextInput::make('application_url')
                    ->url(),
                TextInput::make('salary_min')
                    ->numeric(),
                TextInput::make('salary_max')
                    ->numeric(),
                TextInput::make('rate_min')
                    ->numeric(),
                TextInput::make('rate_max')
                    ->numeric(),
                TextInput::make('reference'),
                TextInput::make('source_reference'),
                DateTimePicker::make('deadline_at'),
                DateTimePicker::make('expires_at'),
                Toggle::make('is_featured')
                    ->required(),
                Toggle::make('is_filled')
                    ->required(),
                Select::make('status')
                    ->options(VacancyStatus::class)
                    ->required()
                    ->default(VacancyStatus::Draft->value),
                Select::make('source')
                    ->options(VacancySource::class)
                    ->required()
                    ->default(VacancySource::Manual->value),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Vacancies\Schemas;

use App\Models\Vacancy;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VacancyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Company'),
                TextEntry::make('title'),
                TextEntry::make('slug'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('location')
                    ->placeholder('-'),
                TextEntry::make('application_email')
                    ->placeholder('-'),
                TextEntry::make('application_url')
                    ->placeholder('-'),
                TextEntry::make('salary_min')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('salary_max')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('rate_min')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('rate_max')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('reference')
                    ->placeholder('-'),
                TextEntry::make('source_reference')
                    ->placeholder('-'),
                TextEntry::make('deadline_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('expires_at')
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('is_featured')
                    ->boolean(),
                IconEntry::make('is_filled')
                    ->boolean(),
                TextEntry::make('status'),
                TextEntry::make('source'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Vacancy $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}

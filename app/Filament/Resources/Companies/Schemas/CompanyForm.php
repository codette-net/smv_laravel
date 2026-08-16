<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Enums\CategoryType;
use App\Enums\CompanyStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Algemeen')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Eigenaar')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->label('Status')
                            ->options(CompanyStatus::class)
                            ->required()
                            ->default(CompanyStatus::Draft->value),
                        Toggle::make('is_featured')
                            ->label('Uitgelicht')
                            ->default(false),
                        TextInput::make('name')
                            ->label('Naam')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('tagline')
                            ->label('Korte introductie')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Beschrijving')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),
                Section::make('Media')
                    ->description('Nieuwe uploads worden opgeslagen in de Media Library.')
                    ->columns(2)
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('logo_media')
                            ->label('Logo')
                            ->collection('logo')
                            ->image()
                            ->maxSize(5120),
                        SpatieMediaLibraryFileUpload::make('cover_media')
                            ->label('Omslagafbeelding')
                            ->collection('cover')
                            ->image()
                            ->maxSize(5120),
                    ]),
                Section::make('Contact')
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')
                            ->label('E-mailadres')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Telefoon')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('location')
                            ->label('Locatie')
                            ->maxLength(255),
                    ]),
                Section::make('Sociale media en extern')
                    ->columns(2)
                    ->schema([
                        TextInput::make('linkedin_url')
                            ->label('LinkedIn')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('facebook_url')
                            ->label('Facebook')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('instagram_url')
                            ->label('Instagram')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('video_url')
                            ->label('Video')
                            ->url()
                            ->maxLength(255),
                    ]),
                Section::make('Taxonomie')
                    ->schema([
                        Select::make('categories')
                            ->label('Categorieën')
                            ->multiple()
                            ->relationship(
                                'categories',
                                'name',
                                fn (Builder $query): Builder => $query->where('type', CategoryType::company_category->value),
                            )
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }
}

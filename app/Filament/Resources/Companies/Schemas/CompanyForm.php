<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Enums\CompanyStatus;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('tagline'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('website')
                    ->url(),
                TextInput::make('logo'),
                FileUpload::make('cover_image')
                    ->image(),
                TextInput::make('location'),
                TextInput::make('linkedin_url')
                    ->url(),
                TextInput::make('facebook_url')
                    ->url(),
                TextInput::make('instagram_url')
                    ->url(),
                TextInput::make('video_url')
                    ->url(),
                Select::make('status')
                    ->options(CompanyStatus::class)
                    ->required()
                    ->default(CompanyStatus::Draft->value),
                Toggle::make('is_featured')
                    ->required(),
            ]);
    }
}

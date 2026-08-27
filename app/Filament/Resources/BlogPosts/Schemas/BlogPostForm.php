<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Enums\BlogPostStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Artikel')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Titel')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->helperText('Laat leeg om automatisch uit de titel te genereren. Een bestaande slug blijft stabiel.')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->columnSpanFull(),
                    Textarea::make('excerpt')
                        ->label('Korte inleiding')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    RichEditor::make('content')
                        ->label('Inhoud')
                        ->required()
                        ->columnSpanFull(),
                ]),
            Section::make('Publicatie')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            BlogPostStatus::Draft->value => 'Concept',
                            BlogPostStatus::Published->value => 'Gepubliceerd',
                        ])
                        ->required()
                        ->default(BlogPostStatus::Draft->value)
                        ->live(),
                    DateTimePicker::make('published_at')
                        ->label('Publiceren op')
                        ->helperText('Kies een toekomstig moment om het artikel in te plannen.')
                        ->required(fn (Get $get): bool => $get('status') === BlogPostStatus::Published->value),
                ]),
            Section::make('Uitgelichte afbeelding')
                ->description('Nieuwe uploads worden opgeslagen in de Media Library.')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('featured_media')
                        ->label('Afbeelding')
                        ->collection('featured')
                        ->image()
                        ->maxSize(5120),
                ]),
        ]);
    }
}

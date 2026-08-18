<?php

namespace App\Filament\Resources\Applications;

use App\Enums\ApplicationStatus;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Models\Application;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationLabel = 'Sollicitaties';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'candidate_name';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sollicitant')->schema([
                TextEntry::make('candidate_name')->label('Naam'),
                TextEntry::make('candidate_email')->label('E-mailadres'),
                TextEntry::make('candidate_phone')->label('Telefoon')->placeholder('-'),
                TextEntry::make('candidate_location')->label('Woonplaats')->placeholder('-'),
                TextEntry::make('linkedin_url')->label('LinkedIn')->url(fn (?string $state): ?string => $state)->placeholder('-'),
            ]),
            Section::make('Sollicitatie')->schema([
                TextEntry::make('vacancy.title')->label('Vacature'),
                TextEntry::make('vacancy.company.name')->label('Bedrijf'),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('motivation')->label('Motivatie')->placeholder('-')->columnSpanFull(),
                TextEntry::make('cv_path')->label('CV-bestand')->placeholder('-'),
                TextEntry::make('created_at')->label('Ontvangen op')->dateTime(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with('vacancy.company'))
            ->columns([
                TextColumn::make('candidate_name')->label('Naam')->searchable(),
                TextColumn::make('vacancy.title')->label('Vacature')->searchable()->wrap(),
                TextColumn::make('vacancy.company.name')->label('Bedrijf')->searchable(),
                TextColumn::make('candidate_email')->label('E-mail')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->label('Ontvangen')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('vacancy')->relationship('vacancy', 'title')->label('Vacature')->searchable(),
                SelectFilter::make('status')->options(ApplicationStatus::class)->label('Status'),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListApplications::route('/'), 'view' => ViewApplication::route('/{record}')];
    }
}

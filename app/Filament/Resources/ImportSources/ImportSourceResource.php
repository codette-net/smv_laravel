<?php

namespace App\Filament\Resources\ImportSources;

use App\Enums\ImportFormat;
use App\Enums\ImportTransport;
use App\Filament\Resources\ImportMappings\ImportMappingResource;
use App\Filament\Resources\ImportSources\Pages\CreateImportSource;
use App\Filament\Resources\ImportSources\Pages\EditImportSource;
use App\Filament\Resources\ImportSources\Pages\ListImportSources;
use App\Filament\Resources\ImportSources\Pages\ViewImportSource;
use App\Models\ImportSource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ImportSourceResource extends Resource
{
    protected static ?string $model = ImportSource::class;

    protected static ?string $navigationLabel = 'Importbronnen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ImportSourceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ImportSourceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['company', 'approvedBy']))
            ->columns([
                TextColumn::make('name')->label('Naam')->searchable()->sortable(),
                TextColumn::make('company.name')->label('Bedrijf')->searchable()->sortable(),
                TextColumn::make('transport')->label('Transport')->badge(),
                TextColumn::make('format')->label('Formaat')->badge(),
                IconColumn::make('is_active')->label('Actief')->boolean(),
                IconColumn::make('approved_at')->label('Goedgekeurd')->boolean(),
                TextColumn::make('updated_at')->label('Bijgewerkt')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('company')->relationship('company', 'name')->label('Bedrijf')->searchable(),
                SelectFilter::make('transport')->options(ImportTransport::class)->label('Transport'),
                SelectFilter::make('format')->options(ImportFormat::class)->label('Formaat'),
                TernaryFilter::make('is_active')->label('Actief'),
                TernaryFilter::make('approved_at')->label('Goedgekeurd')->nullable(),
            ])
            ->recordActions([Action::make('mapping')->label('Mapping configureren')->url(fn (ImportSource $record): string => ImportMappingResource::getUrl('create', ['import_source_id' => $record->id])), ViewAction::make(), EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportSources::route('/'),
            'create' => CreateImportSource::route('/create'),
            'view' => ViewImportSource::route('/{record}'),
            'edit' => EditImportSource::route('/{record}/edit'),
        ];
    }
}

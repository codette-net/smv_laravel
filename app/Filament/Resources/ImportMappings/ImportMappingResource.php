<?php

namespace App\Filament\Resources\ImportMappings;

use App\Filament\Resources\ImportMappings\Pages\CreateImportMapping;
use App\Filament\Resources\ImportMappings\Pages\EditImportMapping;
use App\Filament\Resources\ImportMappings\Pages\ListImportMappings;
use App\Filament\Resources\ImportMappings\Pages\PreviewImportMapping;
use App\Imports\Mapping\DestinationRegistry;
use App\Imports\Mapping\MappingCompletion;
use App\Imports\Mapping\SourceFieldOptions;
use App\Models\ImportMapping;
use App\Models\ImportSource;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImportMappingResource extends Resource
{
    protected static ?string $model = ImportMapping::class;

    protected static ?string $navigationLabel = 'Importmappings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mappingprofiel')->columns(2)->schema([
                Select::make('import_source_id')->label('Importbron')->relationship('importSource', 'name')->required()->live()->searchable(),
                TextInput::make('name')->label('Naam')->required()->default('Standaard mapping'),
                Toggle::make('is_active')->label('Actief')->default(true), Toggle::make('is_default')->label('Standaardmapping')->default(true),
            ]),
            Section::make('Veldkoppelingen')->description('Selecteer een bronveld per SMV-doelveld. Leeg laten betekent niet mappen.')->schema([
                Repeater::make('fields')->relationship()->orderColumn('position')->schema([
                    Select::make('destination_key')->label('SMV-veld')->options(fn () => collect(app(DestinationRegistry::class)->all())->mapWithKeys(fn ($item) => [$item->key => "{$item->group} — {$item->label}"])->all())->required()->searchable(),
                    Select::make('operation')->label('Bewerking')->options(['direct' => 'Direct', 'default' => 'Standaardwaarde', 'combine' => 'Samenvoegen', 'transform' => 'Transformeren'])->required()->live(),
                    Select::make('source_paths')->label('Bronveld(en)')->multiple()->searchable()->options(function (Get $get): array {
                        $source = ImportSource::find($get('../../import_source_id'));

                        return $source ? app(SourceFieldOptions::class)->for($source) : [];
                    })->visible(fn (Get $get) => $get('operation') !== 'default'),
                    TextInput::make('configuration.value')->label('Standaardwaarde')->visible(fn (Get $get) => $get('operation') === 'default'),
                    Select::make('configuration.transform')->label('Transformatie')->options(['trim' => 'Spaties verwijderen', 'string' => 'Tekst', 'integer' => 'Geheel getal', 'boolean' => 'Boolean', 'date' => 'Datum', 'annual_salary_to_monthly' => 'Jaarsalaris naar maand'])->visible(fn (Get $get) => $get('operation') === 'transform'),
                    TextInput::make('configuration.separator')->label('Scheidingsteken')->default("\n\n")->visible(fn (Get $get) => $get('operation') === 'combine'),
                ])->columns(2)->addActionLabel('Veld koppelen')->defaultItems(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->label('Naam')->searchable(), TextColumn::make('importSource.name')->label('Importbron'), TextColumn::make('completion')->label('Status')->state(fn (ImportMapping $record) => app(MappingCompletion::class)->for($record))->badge(), TextColumn::make('fields_count')->counts('fields')->label('Velden'), TextColumn::make('updated_at')->label('Bijgewerkt')->dateTime()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListImportMappings::route('/'), 'create' => CreateImportMapping::route('/create'), 'edit' => EditImportMapping::route('/{record}/edit'), 'preview' => PreviewImportMapping::route('/{record}/preview')];
    }
}

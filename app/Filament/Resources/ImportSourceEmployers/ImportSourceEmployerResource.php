<?php

namespace App\Filament\Resources\ImportSourceEmployers;

use App\Enums\ImportEmployerIdentityType;
use App\Filament\Resources\ImportSourceEmployers\Pages\CreateImportSourceEmployer;
use App\Filament\Resources\ImportSourceEmployers\Pages\EditImportSourceEmployer;
use App\Filament\Resources\ImportSourceEmployers\Pages\ListImportSourceEmployers;
use App\Models\ImportSourceEmployer;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ImportSourceEmployerResource extends Resource
{
    protected static ?string $model = ImportSourceEmployer::class;

    protected static ?string $navigationLabel = 'Werkgeverkoppelingen';

    protected static string|UnitEnum|null $navigationGroup = 'Imports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Brongebonden werkgeveridentiteit')->columns(2)->columnSpanFull()->schema([
                Select::make('import_source_id')->label('Importbron')->relationship('importSource', 'name')->required()->searchable()->preload(),
                Select::make('company_id')->label('SMV-bedrijf')->relationship('company', 'name')->required()->searchable()->preload(),
                Select::make('identity_type')->label('Identiteitstype')->options(ImportEmployerIdentityType::class)->required()->live(),
                TextInput::make('external_employer_id')->label('Externe werkgever-ID')->maxLength(255)
                    ->required(fn (Get $get): bool => $get('identity_type') === ImportEmployerIdentityType::ProviderId->value),
                TextInput::make('source_name')->label('Exacte bronnaam')->maxLength(255)
                    ->required(fn (Get $get): bool => $get('identity_type') === ImportEmployerIdentityType::ReviewedComposite->value),
                TextInput::make('source_url')->label('Exacte bron-URL')->url()->maxLength(2048)
                    ->required(fn (Get $get): bool => $get('identity_type') === ImportEmployerIdentityType::ReviewedComposite->value),
                Toggle::make('is_active')->label('Actief')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('importSource.name')->label('Importbron')->searchable()->sortable(),
            TextColumn::make('company.name')->label('SMV-bedrijf')->searchable()->sortable(),
            TextColumn::make('identity_type')->label('Type')->badge(),
            TextColumn::make('external_employer_id')->label('Provider-ID')->placeholder('-'),
            TextColumn::make('source_name')->label('Bronnaam')->placeholder('-'),
            IconColumn::make('is_active')->label('Actief')->boolean(),
            TextColumn::make('reviewed_at')->label('Beoordeeld')->dateTime()->sortable(),
        ])->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportSourceEmployers::route('/'),
            'create' => CreateImportSourceEmployer::route('/create'),
            'edit' => EditImportSourceEmployer::route('/{record}/edit'),
        ];
    }
}

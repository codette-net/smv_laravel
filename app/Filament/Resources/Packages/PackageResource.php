<?php

namespace App\Filament\Resources\Packages;

use App\Enums\PackageType;
use App\Filament\Resources\Packages\Pages\EditPackage;
use App\Filament\Resources\Packages\Pages\ListPackages;
use App\Filament\Resources\Packages\Pages\ViewPackage;
use App\Models\Package;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationLabel = 'Pakketten';

    protected static ?string $modelLabel = 'pakket';

    protected static ?string $pluralModelLabel = 'pakketten';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyEuro;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Publieke presentatie')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Publieke titel')->required()->maxLength(255),
                    TextInput::make('slug')
                        ->label('Productcode')
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Stabiele identifier voor plaatsing, Orders en toekomstige Stripe-koppeling.'),
                    Textarea::make('description')
                        ->label('Beschrijving')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),
                    TagsInput::make('features')
                        ->label('Publieke voordelen')
                        ->helperText('Voeg iedere klantbelofte als afzonderlijk punt toe.')
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('cta_label')->label('CTA-tekst')->required()->maxLength(100),
                    Toggle::make('is_featured')->label('Uitgelicht in presentatie'),
                ]),
            Section::make('Prijs en plaatsingsregels')
                ->columns(3)
                ->schema([
                    Select::make('package_type')->label('Pakkettype')->options(PackageType::class)->required(),
                    TextInput::make('price_cents')
                        ->label('Netto prijs')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('eurocent')
                        ->required(),
                    TextInput::make('currency')->label('Valuta')->required()->length(3),
                    TextInput::make('vat_rate_basis_points')
                        ->label('Btw-tarief')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(10_000)
                        ->suffix('basispunten')
                        ->helperText('2100 staat voor 21%. Laat alleen leeg voor een bewust ondersteunde afwijkende belastingsituatie.'),
                    TextInput::make('vacancy_duration_days')
                        ->label('Plaatsingsduur')
                        ->numeric()
                        ->minValue(1)
                        ->suffix('dagen')
                        ->nullable(),
                    TextInput::make('placement_quantity')
                        ->label('Aantal plaatsingen')
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                    DatePicker::make('valid_from')->label('Prijs geldig vanaf'),
                    DatePicker::make('valid_until')->label('Prijs geldig t/m'),
                    TextInput::make('sort_order')->label('Sorteervolgorde')->numeric()->required(),
                    Toggle::make('is_quote_only')->label('Alleen offerte/contact'),
                    Toggle::make('checkout_eligible')->label('Checkout toegestaan'),
                    Toggle::make('is_active')->label('Publiek beschikbaar'),
                ]),
            Section::make('Operationele fulfilment')
                ->description('Alleen zichtbaar voor staff. Leg hier handmatige verplichtingen vast; dit activeert geen automatisering.')
                ->schema([
                    Textarea::make('operational_notes')
                        ->label('Operationele notities')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pakket')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')->label('Titel'),
                    TextEntry::make('slug')->label('Productcode'),
                    TextEntry::make('description')->label('Beschrijving')->columnSpanFull(),
                    TextEntry::make('features')
                        ->label('Voordelen')
                        ->state(fn (Package $record): string => collect($record->features)->join(' · '))
                        ->columnSpanFull(),
                    TextEntry::make('price_cents')->label('Netto prijs')->money(fn (Package $record): string => $record->currency),
                    TextEntry::make('vat_rate_basis_points')
                        ->label('Btw')
                        ->formatStateUsing(fn (?int $state): string => $state === null ? 'Niet ingesteld' : number_format($state / 100, 2, ',', '.').' %'),
                    TextEntry::make('vacancy_duration_days')->label('Duur')->suffix(' dagen')->placeholder('Per overeenkomst'),
                    TextEntry::make('placement_quantity')->label('Plaatsingen'),
                    TextEntry::make('valid_from')->label('Geldig vanaf')->date(),
                    TextEntry::make('valid_until')->label('Geldig t/m')->date(),
                    TextEntry::make('operational_notes')->label('Operationele notities')->placeholder('-')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Pakket')->searchable()->sortable(),
                TextColumn::make('slug')->label('Productcode')->searchable(),
                TextColumn::make('price_cents')->label('Netto prijs')->money(fn (Package $record): string => $record->currency)->sortable(),
                TextColumn::make('vacancy_duration_days')->label('Duur')->suffix(' dagen')->placeholder('Variabel'),
                TextColumn::make('valid_until')->label('Geldig t/m')->date()->sortable(),
                IconColumn::make('checkout_eligible')->label('Checkout')->boolean(),
                IconColumn::make('is_active')->label('Actief')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Actief'),
                TernaryFilter::make('checkout_eligible')->label('Checkout toegestaan'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPackages::route('/'),
            'view' => ViewPackage::route('/{record}'),
            'edit' => EditPackage::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\PlacementCredits;

use App\Enums\PlacementCreditStatus;
use App\Filament\Resources\PlacementCredits\Pages\ListPlacementCredits;
use App\Filament\Resources\PlacementCredits\Pages\ViewPlacementCredit;
use App\Models\PlacementCredit;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PlacementCreditResource extends Resource
{
    protected static ?string $model = PlacementCredit::class;

    protected static ?string $navigationLabel = 'Plaatsingsrechten';

    protected static ?string $modelLabel = 'plaatsingsrecht';

    protected static ?string $pluralModelLabel = 'plaatsingsrechten';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyEuro;

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Plaatsingsrecht')
                ->columns(2)
                ->schema([
                    TextEntry::make('status_display')
                        ->label('Status')
                        ->state(fn (PlacementCredit $record): PlacementCreditStatus => $record->status())
                        ->badge(),
                    TextEntry::make('company.name')->label('Bedrijf'),
                    TextEntry::make('package_name_snapshot')->label('Pakket'),
                    TextEntry::make('product_code')->label('Productcode'),
                    TextEntry::make('duration_days_snapshot')->label('Duur')->suffix(' dagen'),
                    TextEntry::make('commercial_priority_snapshot')->label('Pakketprioriteit')->numeric(),
                    TextEntry::make('vacancy.title')->label('Vacature')->placeholder('-'),
                    TextEntry::make('order.order_number')->label('Bestelling'),
                    TextEntry::make('orderItem.id')->label('Orderregel'),
                    TextEntry::make('grant_sequence')->label('Grantnummer'),
                    TextEntry::make('granted_at')->label('Toegekend')->dateTime(),
                    TextEntry::make('allocated_at')->label('Toegewezen')->dateTime()->placeholder('-'),
                    TextEntry::make('activated_at')->label('Geactiveerd')->dateTime()->placeholder('-'),
                    TextEntry::make('expires_at')->label('Verloopt')->dateTime()->placeholder('-'),
                    TextEntry::make('reversed_at')->label('Teruggedraaid')->dateTime()->placeholder('-'),
                ]),
            Section::make('Auditgeschiedenis')
                ->schema([
                    TextEntry::make('events')
                        ->label('Gebeurtenissen')
                        ->state(fn (PlacementCredit $record): string => $record->events
                            ->map(fn ($event): string => sprintf(
                                '%s · %s · vacature %s · actor %s',
                                $event->occurred_at->toDateTimeString(),
                                $event->event_type->getLabel(),
                                $event->vacancy_id ?: '-',
                                $event->actor_user_id ?: 'systeem',
                            ))
                            ->join('; '))
                        ->placeholder('-'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['company', 'vacancy', 'order']))
            ->columns([
                TextColumn::make('company.name')->label('Bedrijf')->searchable()->sortable(),
                TextColumn::make('package_name_snapshot')->label('Pakket')->searchable(),
                TextColumn::make('status_display')
                    ->label('Status')
                    ->state(fn (PlacementCredit $record): PlacementCreditStatus => $record->status())
                    ->badge(),
                TextColumn::make('vacancy.title')->label('Vacature')->searchable()->placeholder('-'),
                TextColumn::make('order.order_number')->label('Bestelling')->searchable(),
                TextColumn::make('granted_at')->label('Toegekend')->dateTime()->sortable(),
                TextColumn::make('activated_at')->label('Actief vanaf')->dateTime()->placeholder('-')->sortable(),
                TextColumn::make('expires_at')->label('Verloopt')->dateTime()->placeholder('-')->sortable(),
            ])
            ->filters([
                SelectFilter::make('product_code')
                    ->label('Pakket')
                    ->options(['standard' => 'Standaard', 'superior' => 'Superior']),
                SelectFilter::make('company')->relationship('company', 'name')->searchable()->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlacementCredits::route('/'),
            'view' => ViewPlacementCredit::route('/{record}'),
        ];
    }
}

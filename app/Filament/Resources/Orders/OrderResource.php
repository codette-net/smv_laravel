<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\OrderOperation;
use App\Models\Payment;
use App\Support\Commerce\Money;
use App\Support\Commerce\OrderPresentation;
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

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationLabel = 'Bestellingen';

    protected static ?string $modelLabel = 'bestelling';

    protected static ?string $pluralModelLabel = 'bestellingen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Bestelling')
                ->columns(2)
                ->schema([
                    TextEntry::make('order_number')->label('Referentie'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('user.name')->label('Klant'),
                    TextEntry::make('company.name')->label('Bedrijf'),
                    TextEntry::make('subtotal_cents')->label('Subtotaal')->money(fn (Order $record): string => $record->currency),
                    TextEntry::make('vat_cents')->label('Btw')->money(fn (Order $record): string => $record->currency),
                    TextEntry::make('total_cents')->label('Totaal')->money(fn (Order $record): string => $record->currency),
                    TextEntry::make('currency')->label('Valuta'),
                    TextEntry::make('created_at')->label('Aangemaakt op')->dateTime(),
                ]),
            Section::make('Orderregels')
                ->schema([
                    TextEntry::make('orderItems')
                        ->label('Producten')
                        ->state(fn (Order $record): string => $record->orderItems->pluck('title_snapshot')->filter()->join(', ')),
                    TextEntry::make('vacancies')
                        ->label('Vacatures')
                        ->state(fn (Order $record): string => $record->vacancies->pluck('title')->filter()->join(', '))
                        ->placeholder('-'),
                ]),
            Section::make('Betalingspogingen')
                ->schema([
                    TextEntry::make('financial_status')
                        ->label('Actuele financiële status')
                        ->state(fn (Order $record): string => OrderPresentation::paymentStatus($record)['label'])
                        ->badge()
                        ->color(fn (Order $record): string => match (OrderPresentation::paymentStatus($record)['variant']) {
                            'neutral' => 'gray',
                            default => OrderPresentation::paymentStatus($record)['variant'],
                        }),
                    TextEntry::make('payments')
                        ->label('Betalingen')
                        ->state(fn (Order $record): string => $record->payments
                            ->sortByDesc('id')
                            ->map(fn (Payment $payment): string => sprintf(
                                '%s · %s · %s · Checkout %s · PaymentIntent %s · aangemaakt %s · afgerond %s',
                                $payment->status->getLabel(),
                                $payment->provider,
                                Money::format($payment->amount_cents, $payment->currency),
                                $payment->provider_reference ?: '-',
                                $payment->provider_payment_id ?: '-',
                                $payment->created_at?->toDateTimeString() ?? '-',
                                $payment->completed_at?->toDateTimeString() ?? '-',
                            ))
                            ->join('; '))
                        ->placeholder('-'),
                ]),
            Section::make('Plaatsingsrechten')
                ->schema([
                    TextEntry::make('placementCredits')
                        ->label('Toegekende rechten')
                        ->state(fn (Order $record): string => $record->placementCredits
                            ->map(fn ($credit): string => sprintf(
                                '%s #%d · %s · vacature %s · %s',
                                $credit->package_name_snapshot,
                                $credit->grant_sequence,
                                $credit->status()->getLabel(),
                                $credit->vacancy?->title ?? '-',
                                $credit->expires_at?->toDateTimeString() ?? 'geen einddatum actief',
                            ))
                            ->join('; '))
                        ->placeholder('-'),
                ]),
            Section::make('Annuleringen en terugbetalingen')
                ->schema([
                    TextEntry::make('operations')
                        ->label('Interne auditgeschiedenis')
                        ->state(fn (Order $record): string => $record->operations
                            ->sortByDesc('id')
                            ->map(fn (OrderOperation $operation): string => sprintf(
                                '%s · %s · %s · %s · operator %s · provider %s · referentie %s · %s',
                                $operation->requested_at?->toDateTimeString() ?? '-',
                                $operation->type->getLabel(),
                                $operation->status->getLabel(),
                                Money::format($operation->amount_cents, $operation->currency),
                                $operation->operator?->name ?? 'verwijderde gebruiker',
                                $operation->provider ?: '-',
                                $operation->provider_reference ?: '-',
                                $operation->reason,
                            ))
                            ->join('; '))
                        ->placeholder('-'),
                ]),
            Section::make('Operationele plaatsingscontrole')
                ->description('De financiële terugbetaling is bevestigd. Publicatie, looptijd en handmatige prioriteit zijn bewust niet automatisch gewijzigd.')
                ->visible(fn (Order $record): bool => OrderPresentation::requiresPlacementReview($record))
                ->schema([
                    TextEntry::make('placement_review_action')
                        ->label('Volgende stap')
                        ->state('Beoordeel het gekoppelde plaatsingsrecht en de vacature handmatig. Leg een commercieel besluit vast via de bestaande audit-/beheerprocedure; herstel of verleng niets automatisch.')
                        ->color('warning'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'company'])->withCount('payments'))
            ->columns([
                TextColumn::make('order_number')->label('Referentie')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Klant')->searchable()->sortable(),
                TextColumn::make('company.name')->label('Bedrijf')->searchable()->sortable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('total_cents')->label('Totaal')->money(fn (Order $record): string => $record->currency)->sortable(),
                TextColumn::make('currency')->label('Valuta'),
                TextColumn::make('payments_count')->label('Betalingspogingen')->numeric(),
                TextColumn::make('created_at')->label('Aangemaakt')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(OrderStatus::class),
            ])
            ->recordActions([
                ViewAction::make()->label('Bekijken / acties'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}

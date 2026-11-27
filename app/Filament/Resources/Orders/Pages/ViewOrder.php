<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Orders\CancelUnpaidOrder;
use App\Actions\Orders\RequestFullRefund;
use App\Enums\OrderOperationStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Support\Commerce\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancelUnpaid')
                ->label('Onbetaalde bestelling annuleren')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Onbetaalde bestelling annuleren')
                ->modalDescription('Een open Stripe Checkout-sessie wordt waar mogelijk beëindigd. De bestelling wordt niet geannuleerd als een betaling mogelijk al is geslaagd.')
                ->schema([
                    Textarea::make('reason')
                        ->label('Interne reden')
                        ->required()
                        ->minLength(5)
                        ->maxLength(2000)
                        ->rows(4),
                ])
                ->authorize(fn (): bool => auth()->user()?->can('cancel', $this->record) ?? false)
                ->visible(fn (): bool => in_array($this->record->status, [OrderStatus::Draft, OrderStatus::Pending, OrderStatus::PendingPayment], true))
                ->action(function (array $data): void {
                    $operation = app(CancelUnpaidOrder::class)->execute(auth()->user(), $this->record, $data['reason']);
                    $this->record->refresh();

                    Notification::make()
                        ->title($operation->status === OrderOperationStatus::Succeeded ? 'Bestelling geannuleerd' : 'Handmatige controle vereist')
                        ->body($operation->failure_message)
                        ->color($operation->status->getColor())
                        ->send();
                }),
            Action::make('refundPaid')
                ->label('Volledig terugbetalen')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Volledige terugbetaling aanvragen')
                ->modalDescription(fn (): string => sprintf(
                    'Stripe ontvangt een verzoek voor het volledige betaalde bedrag van %s, inclusief btw. De providerstatus blijft leidend.%s',
                    Money::format($this->record->total_cents, $this->record->currency),
                    $this->record->placementCredits()->whereNotNull('vacancy_id')->exists()
                        ? ' Let op: een plaatsingsrecht is al toegewezen of gebruikt en vereist na bevestiging handmatige controle.'
                        : '',
                ))
                ->schema([
                    Textarea::make('reason')
                        ->label('Interne reden')
                        ->required()
                        ->minLength(5)
                        ->maxLength(2000)
                        ->rows(4),
                ])
                ->authorize(fn (): bool => auth()->user()?->can('refund', $this->record) ?? false)
                ->visible(fn (): bool => $this->record->status === OrderStatus::Paid)
                ->action(function (array $data): void {
                    $operation = app(RequestFullRefund::class)->execute(auth()->user(), $this->record, $data['reason']);
                    $this->record->refresh();

                    Notification::make()
                        ->title(match ($operation->status) {
                            OrderOperationStatus::Succeeded => 'Terugbetaling bevestigd',
                            OrderOperationStatus::Pending => 'Terugbetaling in behandeling',
                            OrderOperationStatus::Failed => 'Terugbetaling mislukt',
                            default => 'Handmatige controle vereist',
                        })
                        ->body($operation->failure_message)
                        ->color($operation->status->getColor())
                        ->send();
                }),
        ];
    }
}

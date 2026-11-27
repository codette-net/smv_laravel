@props(['order'])

@php
    $statusLabel = match ($order->status) {
        \App\Enums\OrderStatus::Draft => 'Concept',
        \App\Enums\OrderStatus::Pending => 'In behandeling',
        \App\Enums\OrderStatus::PendingPayment => 'Wacht op betaling',
        \App\Enums\OrderStatus::Paid => 'Betaald',
        \App\Enums\OrderStatus::Cancelled => 'Geannuleerd',
        \App\Enums\OrderStatus::Failed => 'Mislukt',
        \App\Enums\OrderStatus::Refunded => 'Terugbetaald',
    };
    $money = static fn (int $amount): string => '€ '.number_format($amount / 100, 2, ',', '.');
    $latestPayment = $order->payments->first();
    $latestOperation = $order->operations->first();
    $paymentStatusLabel = match ($latestPayment?->status) {
        \App\Enums\PaymentStatus::Open, \App\Enums\PaymentStatus::Pending => 'In afwachting',
        \App\Enums\PaymentStatus::Paid => 'Betaald',
        \App\Enums\PaymentStatus::Failed => 'Mislukt',
        \App\Enums\PaymentStatus::Cancelled => 'Geannuleerd',
        \App\Enums\PaymentStatus::Expired => 'Verlopen',
        \App\Enums\PaymentStatus::Refunded => 'Terugbetaald',
        null => 'Nog niet gestart',
    };
    $paymentStatusLabel = $latestOperation?->providerSucceeded() ? 'Terugbetaald' : match ($latestOperation?->status) {
        \App\Enums\OrderOperationStatus::Requested, \App\Enums\OrderOperationStatus::Pending => $latestOperation->type === \App\Enums\OrderOperationType::Refund ? 'Terugbetaling in behandeling' : $paymentStatusLabel,
        \App\Enums\OrderOperationStatus::Failed => $latestOperation->type === \App\Enums\OrderOperationType::Refund ? 'Terugbetaling mislukt' : $paymentStatusLabel,
        \App\Enums\OrderOperationStatus::RequiresReview => $latestOperation->type === \App\Enums\OrderOperationType::Refund ? 'Terugbetaling wordt gecontroleerd' : $paymentStatusLabel,
        default => $paymentStatusLabel,
    };
@endphp

<div {{ $attributes->class(['rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8']) }}>
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">Bestelling {{ $order->order_number }}</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">{{ $order->company->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">Aangemaakt op {{ $order->created_at->format('d-m-Y H:i') }}</p>
        </div>
        <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">{{ $statusLabel }}</span>
    </div>

    <div class="mt-5 space-y-4">
        @foreach ($order->orderItems as $item)
            <div class="flex items-start justify-between gap-5">
                <div>
                    <h3 class="font-semibold text-slate-900">{{ $item->title_snapshot }}</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $item->quantity }} × {{ $money($item->price_cents) }} excl. btw
                        @if ($item->vacancy)
                            · {{ $item->vacancy->title }}
                        @endif
                    </p>
                </div>
                <span class="shrink-0 font-medium text-slate-900">{{ $money($item->subtotal_cents) }}</span>
            </div>
        @endforeach
    </div>

    <dl class="mt-6 space-y-2 border-t border-slate-200 pt-5 text-sm">
        <div class="flex justify-between gap-5"><dt class="text-slate-500">Betaalstatus</dt><dd class="font-medium text-slate-900">{{ $paymentStatusLabel }}</dd></div>
        <div class="flex justify-between gap-5"><dt class="text-slate-500">Subtotaal excl. btw</dt><dd class="font-medium text-slate-900">{{ $money($order->subtotal_cents) }}</dd></div>
        <div class="flex justify-between gap-5"><dt class="text-slate-500">Btw{{ $order->vat_rate_basis_points !== null ? ' ('.number_format($order->vat_rate_basis_points / 100, 2, ',', '.').'%)' : '' }}</dt><dd class="font-medium text-slate-900">{{ $money($order->vat_cents) }}</dd></div>
        <div class="flex justify-between gap-5 border-t border-slate-200 pt-3 text-base"><dt class="font-semibold text-slate-900">Totaal incl. btw</dt><dd class="font-bold text-slate-900">{{ $money($order->total_cents) }} {{ $order->currency }}</dd></div>
    </dl>
</div>

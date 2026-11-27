@props(['order'])

@php
    $paymentStatus = \App\Support\Commerce\OrderPresentation::paymentStatus($order);
    $money = static fn (int $amount): string => \App\Support\Commerce\Money::format($amount, $order->currency);
@endphp

<div {{ $attributes->class(['rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8']) }}>
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">Bestelling {{ $order->order_number }}</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">{{ $order->company->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">Aangemaakt op {{ $order->created_at->format('d-m-Y H:i') }}</p>
        </div>
        <x-order.status-badges :order="$order" />
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
        <div class="flex justify-between gap-5"><dt class="text-slate-500">Betaalstatus</dt><dd class="font-medium text-slate-900">{{ $paymentStatus['label'] }}</dd></div>
        <div class="flex justify-between gap-5"><dt class="text-slate-500">Subtotaal excl. btw</dt><dd class="font-medium text-slate-900">{{ $money($order->subtotal_cents) }}</dd></div>
        <div class="flex justify-between gap-5"><dt class="text-slate-500">Btw{{ $order->vat_rate_basis_points !== null ? ' ('.number_format($order->vat_rate_basis_points / 100, 2, ',', '.').'%)' : '' }}</dt><dd class="font-medium text-slate-900">{{ $money($order->vat_cents) }}</dd></div>
        <div class="flex justify-between gap-5 border-t border-slate-200 pt-3 text-base"><dt class="font-semibold text-slate-900">Totaal incl. btw</dt><dd class="font-bold text-slate-900">{{ $money($order->total_cents) }}</dd></div>
    </dl>

    @if ($reviewLabel = \App\Support\Commerce\OrderPresentation::placementReviewLabel($order))
        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
            De financiële terugbetaling is voltooid. {{ $reviewLabel }}. Publicatie, looptijd en handmatige prioriteit zijn niet automatisch gewijzigd.
        </div>
    @endif
</div>

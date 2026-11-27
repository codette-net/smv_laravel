@extends('layouts.public')

@section('title', 'Mijn bestellingen | Sales en Marketing Vacatures')
@section('canonical', route('account.orders.index'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-5xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('account.index') }}">← Mijn account</a>
        <p class="mt-8 text-sm font-semibold uppercase tracking-widest text-blue-700">Mijn account</p>
        <h1 class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">Mijn bestellingen</h1>
        <p class="mt-3 max-w-2xl leading-7 text-slate-600">Bekijk pakket, vacature, bedrag en actuele bestel- en betaalstatus. Betaling publiceert een vacature niet automatisch.</p>

        <div class="mt-8 space-y-5">
            @forelse ($orders as $order)
                <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h2 class="font-semibold text-slate-900">{{ $order->order_number }}</h2>
                            <p class="mt-1 text-sm text-slate-600">Pakket: {{ $order->orderItems->pluck('title_snapshot')->filter()->join(', ') ?: '-' }}</p>
                            <p class="mt-1 text-sm text-slate-600">Vacature: {{ $order->orderItems->pluck('vacancy.title')->filter()->join(', ') ?: 'nog niet gekoppeld' }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ \App\Support\Commerce\Money::format($order->total_cents, $order->currency) }} incl. btw · {{ $order->created_at->format('d-m-Y') }}</p>
                            <x-order.status-badges class="mt-3" :order="$order" />
                            @if ($reviewLabel = \App\Support\Commerce\OrderPresentation::placementReviewLabel($order))
                                <p class="mt-3 text-sm font-medium text-amber-800">De financiële terugbetaling is voltooid. {{ $reviewLabel }}; de vacature is niet automatisch gewijzigd.</p>
                            @endif
                        </div>
                        <a class="inline-flex shrink-0 text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('account.orders.show', $order) }}">Bekijk bestelling →</a>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">U heeft nog geen bestellingen.</div>
            @endforelse
        </div>

        @if ($orders->hasPages())
            <div class="mt-8">{{ $orders->links() }}</div>
        @endif
    </section>
@endsection

@extends('layouts.public')

@section('title', 'Mijn bestellingen | Sales en Marketing Vacatures')
@section('canonical', route('account.orders.index'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-5xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('account.index') }}">← Mijn account</a>
        <p class="mt-8 text-sm font-semibold uppercase tracking-widest text-blue-700">Mijn account</p>
        <h1 class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">Mijn bestellingen</h1>
        <p class="mt-3 max-w-2xl leading-7 text-slate-600">Bekijk de actuele bestel- en betaalstatus. Betaling publiceert een vacature niet automatisch.</p>

        <div class="mt-8 space-y-5">
            @forelse ($orders as $order)
                @php
                    $paymentStatus = match ($order->payments->first()?->status) {
                        \App\Enums\PaymentStatus::Open, \App\Enums\PaymentStatus::Pending => 'betaling in afwachting',
                        \App\Enums\PaymentStatus::Paid => 'betaald',
                        \App\Enums\PaymentStatus::Failed => 'betaling mislukt',
                        \App\Enums\PaymentStatus::Cancelled => 'betaling geannuleerd',
                        \App\Enums\PaymentStatus::Expired => 'betaling verlopen',
                        \App\Enums\PaymentStatus::Refunded => 'terugbetaald',
                        null => 'betaling nog niet gestart',
                    };
                @endphp
                <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:flex sm:items-center sm:justify-between sm:gap-6">
                    <div>
                        <h2 class="font-semibold text-slate-900">{{ $order->order_number }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $order->orderItems->pluck('title_snapshot')->join(', ') }} · € {{ number_format($order->total_cents / 100, 2, ',', '.') }} incl. btw</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $order->created_at->format('d-m-Y') }} · {{ $paymentStatus }}</p>
                    </div>
                    <a class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800 sm:mt-0" href="{{ route('account.orders.show', $order) }}">Bekijk bestelling →</a>
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

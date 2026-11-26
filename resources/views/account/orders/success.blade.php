@extends('layouts.public')

@section('title', 'Betaalstatus | Sales en Marketing Vacatures')
@section('canonical', route('account.orders.success', $order))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-3xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <div class="rounded-xl border border-slate-200 bg-white p-7 text-center shadow-sm sm:p-10">
            @if ($order->status === \App\Enums\OrderStatus::Paid)
                <p class="text-sm font-semibold uppercase tracking-widest text-emerald-700">Betaling bevestigd</p>
                <h1 class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">Uw bestelling is betaald</h1>
                <p class="mt-4 leading-7 text-slate-600">Stripe heeft de betaling bevestigd via de beveiligde webhook en het plaatsingsrecht is aan uw bedrijf toegekend. Een gekoppelde vacature blijft in beoordeling; betaling publiceert haar niet automatisch.</p>
            @else
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Betaling wordt verwerkt</p>
                <h1 class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">We wachten op de betaalbevestiging</h1>
                <p class="mt-4 leading-7 text-slate-600">De terugkeer vanuit Stripe is geen betaalbewijs. Zodra de beveiligde webhook is verwerkt, wordt de actuele status hier zichtbaar.</p>
            @endif
            <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                <a class="btn justify-center bg-blue-600 text-white hover:bg-blue-700" href="{{ route('account.orders.show', $order) }}">Bekijk actuele status</a>
                <a class="btn justify-center border border-slate-300 bg-white text-slate-800 hover:bg-slate-50" href="{{ route('account.orders.index') }}">Mijn bestellingen</a>
            </div>
        </div>
    </section>
@endsection

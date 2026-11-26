@extends('layouts.public')

@section('title', 'Betaling onderbroken | Sales en Marketing Vacatures')
@section('canonical', route('account.orders.cancelled', $order))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-3xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <div class="rounded-xl border border-slate-200 bg-white p-7 text-center shadow-sm sm:p-10">
            <p class="text-sm font-semibold uppercase tracking-widest text-amber-700">Checkout verlaten</p>
            <h1 class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">De betaling is niet via deze pagina bevestigd</h1>
            <p class="mt-4 leading-7 text-slate-600">De bestelling is niet automatisch geannuleerd. Een eventuele webhook blijft leidend; bekijk de actuele status en probeer opnieuw wanneer de bestelling nog openstaat.</p>
            <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                <a class="btn justify-center bg-blue-600 text-white hover:bg-blue-700" href="{{ route('account.orders.show', $order) }}">Bekijk bestelling</a>
                <a class="btn justify-center border border-slate-300 bg-white text-slate-800 hover:bg-slate-50" href="{{ route('account.orders.index') }}">Mijn bestellingen</a>
            </div>
        </div>
    </section>
@endsection

@extends('layouts.public')

@section('title', 'Bestelling ' . $order->order_number . ' | Sales en Marketing Vacatures')
@section('canonical', route('account.orders.show', $order))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-4xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('account.orders.index') }}">← Mijn bestellingen</a>
        <p class="mt-8 text-sm font-semibold uppercase tracking-widest text-blue-700">Bestelling</p>
        <h1 class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">Controleer uw bestelling</h1>

        @if (session('payment_error'))
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">{{ session('payment_error') }}</div>
        @endif
        @if (session('payment_status'))
            <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900" role="status">{{ session('payment_status') }}</div>
        @endif

        <x-order.summary class="mt-8" :order="$order" />

        <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-900">
            Na een bevestigde betaling wordt het plaatsingsrecht veilig aan uw bedrijf toegekend. Een gekoppelde vacature blijft in beoordeling en wordt nooit door de betaling gepubliceerd.
        </div>

        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
            @if (in_array($order->status, [\App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::PendingPayment], true))
                <form action="{{ route('account.orders.pay', $order) }}" method="POST">
                    @csrf
                    <x-ui.button class="w-full justify-center sm:w-auto" type="submit" variant="brand">Veilig betalen via Stripe →</x-ui.button>
                </form>
            @endif
            <a class="btn justify-center border border-slate-300 bg-white text-slate-800 hover:bg-slate-50" href="{{ route('account.orders.index') }}">Terug naar overzicht</a>
        </div>
    </section>
@endsection

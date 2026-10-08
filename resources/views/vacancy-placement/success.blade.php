@extends('layouts.public')

@section('title', 'Vacature ontvangen | Sales en Marketing Vacatures')
@section('canonical', route('vacancy-placement.index'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-4xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <x-vacancy-placement.steps :current="4" />
        <div class="mx-auto mt-10 max-w-2xl rounded-xl border border-emerald-200 bg-white p-7 text-center shadow-sm sm:p-10">
            <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-emerald-100 text-2xl text-emerald-700" aria-hidden="true">✓</span>
            <p class="mt-5 text-sm font-semibold uppercase tracking-widest text-emerald-700">Ontvangen voor controle</p>
            <h1 class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">Uw vacature is veilig ingediend</h1>
            <p class="mt-4 leading-7 text-slate-600">De vacature <strong>{{ $vacancy->title }}</strong> staat nu in afwachting. SMV kan deze in het bestaande beheer controleren en neemt contact op over de verdere plaatsing.</p>
            <div class="mt-6 rounded-lg bg-slate-100 p-4 text-left text-sm leading-6 text-slate-600">
                Er is nog niets betaald en de vacature is niet gepubliceerd of uitgelicht. Bestelling en betaling volgen later via de toekomstige commerciële afhandeling.
            </div>
            <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                <a class="btn justify-center bg-blue-600 text-white hover:bg-blue-700" href="{{ route('home') }}">Naar de homepage</a>
                <a class="btn justify-center border border-slate-300 bg-white text-slate-800 hover:bg-slate-50" href="{{ route('contact', ['reason' => 'advertising']) }}">Vraag stellen</a>
            </div>
        </div>
    </section>
@endsection

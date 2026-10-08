@extends('layouts.public')

@section('title', 'Account voor vacatureplaatsing | Sales en Marketing Vacatures')
@section('meta_description', 'Log in of maak een werkgeversaccount aan om uw vacature als concept op te slaan.')
@section('canonical', route('vacancy-placement.index'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-4xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <x-vacancy-placement.steps :current="1" />
        <div class="mx-auto mt-10 max-w-xl rounded-xl border border-slate-200 bg-white p-7 shadow-sm sm:p-9">
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">{{ $package->label() }} gekozen</p>
            <h1 class="mt-3 font-playfair-display text-3xl font-bold text-slate-900">Sla uw voortgang veilig op</h1>
            <p class="mt-4 leading-7 text-slate-600">Log in met een bestaand account of maak compact een werkgeversaccount aan. Daarna gaat u direct verder met de vacaturegegevens.</p>
            <div class="mt-7 grid gap-3 sm:grid-cols-2">
                <a class="btn justify-center bg-blue-600 text-white hover:bg-blue-700" href="{{ route('register.employer') }}">Werkgeversaccount aanmaken</a>
                <a class="btn justify-center border border-slate-300 bg-white text-slate-800 hover:bg-slate-50" href="{{ route('login') }}">Ik heb al een account</a>
            </div>
            <a class="mt-5 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('vacancy-placement.index') }}">← Pakket wijzigen</a>
        </div>
    </section>
@endsection

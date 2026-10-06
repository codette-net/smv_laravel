@extends('layouts.public')

@section('title', 'Account aanmaken | Sales en Marketing Vacatures')
@section('canonical', route('register'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-3xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <div class="rounded-xl border border-slate-200 bg-white p-7 shadow-sm sm:p-9">
            <h1 class="font-playfair-display text-3xl font-bold text-slate-900">Account aanmaken</h1>
            <p class="mt-3 leading-7 text-slate-600">Waarvoor wil je SMV gebruiken?</p>

            <div class="mt-7 grid gap-4 sm:grid-cols-2">
                <a class="rounded-xl border border-slate-200 p-6 transition hover:border-blue-300 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500" href="{{ route('register.job-seeker') }}">
                    <h2 class="text-xl font-bold text-slate-900">Werkzoekende</h2>
                    <p class="mt-2 leading-6 text-slate-600">Bewaar vacatures en bedrijven en bekijk de status van sollicitaties die je via SMV verstuurt.</p>
                    <span class="mt-5 inline-flex font-semibold text-blue-700">Account voor werkzoekenden →</span>
                </a>
                <a class="rounded-xl border border-slate-200 p-6 transition hover:border-blue-300 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500" href="{{ route('register.employer') }}">
                    <h2 class="text-xl font-bold text-slate-900">Werkgever</h2>
                    <p class="mt-2 leading-6 text-slate-600">Maak je bedrijfsprofiel aan en plaats en beheer vacatures.</p>
                    <span class="mt-5 inline-flex font-semibold text-blue-700">Account voor werkgevers →</span>
                </a>
            </div>

            <p class="mt-7 text-sm text-slate-600">Heb je al een account? <a class="font-semibold text-blue-700 hover:text-blue-800" href="{{ route('login') }}">Inloggen</a></p>
        </div>
    </section>
@endsection

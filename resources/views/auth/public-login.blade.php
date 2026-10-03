@extends('layouts.public')

@section('title', 'Inloggen | Sales en Marketing Vacatures')
@section('canonical', route('login'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <form class="rounded-xl border border-slate-200 bg-white p-7 shadow-sm sm:p-9" action="{{ route('login.store') }}" method="POST">
            @csrf
            <h1 class="font-playfair-display text-3xl font-bold text-slate-900">Inloggen</h1>
            <p class="mt-3 text-slate-600">Na het inloggen gaat u verder waar u gebleven was.</p>
            <div class="mt-7 space-y-5">
                <x-ui.input name="email" label="E-mailadres" type="email" autocomplete="email" required autofocus />
                <x-ui.input name="password" label="Wachtwoord" type="password" autocomplete="current-password" required />
                <label class="flex items-center gap-2 text-sm text-slate-700"><input class="form-checkbox" name="remember" type="checkbox" value="1"> Ingelogd blijven</label>
            </div>
            <div class="mt-7"><x-ui.button class="w-full justify-center" type="submit" variant="brand">Inloggen</x-ui.button></div>
            <p class="mt-6 text-sm text-slate-600">Nog geen account? <a class="font-semibold text-blue-700 hover:text-blue-800" href="{{ route('register') }}">Account aanmaken</a></p>
        </form>
    </section>
@endsection

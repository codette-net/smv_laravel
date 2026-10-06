@extends('layouts.public')

@section('title', ($isEmployerRegistration ? 'Werkgeversaccount' : 'Account') . ' aanmaken | Sales en Marketing Vacatures')
@section('canonical', route('register'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <form class="rounded-xl border border-slate-200 bg-white p-7 shadow-sm sm:p-9" action="{{ route('register.store') }}" method="POST">
            @csrf
            <h1 class="font-playfair-display text-3xl font-bold text-slate-900">{{ $isEmployerRegistration ? 'Werkgeversaccount aanmaken' : 'Account aanmaken' }}</h1>
            <p class="mt-3 leading-7 text-slate-600">
                {{ $isEmployerRegistration
                    ? 'Maak een account en minimaal bedrijfsprofiel aan. Het profiel blijft in afwachting totdat SMV het heeft gecontroleerd.'
                    : 'Maak een account aan om vacatures en bedrijven te bewaren en later eenvoudig terug te vinden.' }}
            </p>
            <div class="mt-7 space-y-5">
                <x-ui.input name="name" label="Naam" autocomplete="name" required />
                @if ($isEmployerRegistration)
                    <x-ui.input name="company_name" label="Bedrijfsnaam" autocomplete="organization" required />
                @endif
                <x-ui.input name="email" label="E-mailadres" type="email" autocomplete="email" required />
                <x-ui.input name="password" label="Wachtwoord" type="password" autocomplete="new-password" required />
                <x-ui.input name="password_confirmation" label="Wachtwoord bevestigen" type="password" autocomplete="new-password" required />
            </div>
            <p class="mt-4 text-xs leading-5 text-slate-500">Gebruik minimaal 8 tekens, waaronder letters en cijfers.</p>
            <div class="mt-7"><x-ui.button class="w-full justify-center" type="submit" variant="brand">Account aanmaken</x-ui.button></div>
            <p class="mt-6 text-sm text-slate-600">Heeft u al een account? <a class="font-semibold text-blue-700 hover:text-blue-800" href="{{ route('login') }}">Inloggen</a></p>
        </form>
    </section>
@endsection

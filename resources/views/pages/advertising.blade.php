@extends('layouts.public')

@section('title', 'Adverteren voor werkgevers | Sales en Marketing Vacatures')
@section('meta_description', 'Plaats uw sales- of marketingvacature gericht bij Sales en Marketing Vacatures en bespreek passende zichtbaarheid en ondersteuning.')
@section('canonical', route('advertising'))
@section('header_theme', 'dark')

@section('content')
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-slate-900 [clip-path:polygon(0_0,_5760px_0,_5760px_calc(100%_-_120px),_0_100%)]" aria-hidden="true"></div>
        <div class="mx-auto max-w-6xl px-4 pb-28 pt-32 sm:px-6 md:pb-36 md:pt-40">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-300">Voor werkgevers</p>
                <h1 class="mt-4 font-playfair-display text-4xl font-bold tracking-tight text-white sm:text-5xl">Uw vacature onder de aandacht bij sales- en marketingprofessionals</h1>
                <p class="mt-6 max-w-2xl text-xl leading-8 text-slate-300">Van vacatureplaatsing tot aanvullende zichtbaarheid: we denken mee over een aanpak die past bij uw functie en wervingsvraag.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a class="btn bg-blue-600 text-white hover:bg-blue-700" href="{{ route('vacancy-placement.index') }}">Vacature plaatsen <span class="ml-1 text-blue-300">→</span></a>
                    <a class="btn border border-slate-600 bg-slate-800 text-white hover:bg-slate-700" href="{{ route('pricing') }}">Bekijk tarieven</a>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16" aria-labelledby="werkgevers-voordelen">
        <div class="mx-auto max-w-3xl text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Gericht adverteren</p>
            <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900" id="werkgevers-voordelen">Meer dan alleen een vacature online zetten</h2>
            <p class="mt-4 text-lg text-slate-600">SMV richt zich op organisaties met een terugkerende of actuele behoefte aan professionals binnen sales en marketing.</p>
        </div>
        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['Zichtbaarheid in het vakgebied', 'Uw vacature staat tussen functies die relevant zijn voor sales- en marketingprofessionals.'],
                ['Controle en redactie', 'We kunnen de aangeleverde tekst controleren en meedenken over een duidelijke, clichéarme presentatie.'],
                ['Ondersteuning en jobmarketing', 'Aanvullende verspreiding en zichtbaarheid worden afgestemd op de vacature en gekozen aanpak.'],
            ] as [$title, $description])
                <article class="relative flex h-full flex-col bg-white p-6 shadow-lg shadow-black/[0.04] before:pointer-events-none before:absolute before:inset-0 before:-z-10 before:border before:border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">{{ $title }}</h3>
                    <p class="mt-3 leading-7 text-slate-600">{{ $description }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="border-y border-slate-200 bg-slate-100" aria-labelledby="werkwijze">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Onze werkwijze</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900" id="werkwijze">Van materiaal tot evaluatie</h2>
                <p class="mt-4 text-slate-600">De precieze invulling hangt af van de gekozen aanpak. Deze stappen geven de gebruikelijke volgorde weer.</p>
            </div>
            <ol class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (['Pakket of aanpak kiezen', 'Materiaal aanleveren', 'Controle en redactie', 'Publicatie', 'Verspreiding', 'Rapportage', 'Evaluatie'] as $step)
                    <li class="flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">{{ $loop->iteration }}</span>
                        <span class="pt-1 font-semibold text-slate-900">{{ $step }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <div class="relative flex flex-col items-start justify-between gap-6 bg-white p-7 shadow-lg shadow-black/[0.04] ring-1 ring-slate-200 md:flex-row md:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Volgende stap</p>
                <h2 class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">Kies een plaatsing of bespreek maatwerk</h2>
                <p class="mt-3 max-w-2xl text-slate-600">Bekijk de eerste pakketmogelijkheden of neem contact op over meerdere vacatures, terugkerende werving of aanvullende zichtbaarheid.</p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-3">
                <a class="btn bg-blue-600 text-white hover:bg-blue-700" href="{{ route('vacancy-placement.index') }}">Vacature plaatsen</a>
                <a class="btn border border-slate-300 bg-white text-slate-800 hover:bg-slate-50" href="{{ route('contact', ['reason' => 'advertising']) }}">Maatwerk bespreken</a>
            </div>
        </div>
    </section>
@endsection

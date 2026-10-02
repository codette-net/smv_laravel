@extends('layouts.public')

@section('title', 'Tarieven voor vacatureplaatsing | Sales en Marketing Vacatures')
@section('meta_description', 'Bekijk Standaard, Superior en maatwerk voor het plaatsen en zichtbaar maken van een sales- of marketingvacature.')
@section('canonical', route('pricing'))
@section('header_theme', 'dark')

@section('content')
    @php
        $plans = [
            [
                'name' => 'Standaard',
                'price' => '€ 189',
                'suffix' => 'excl. btw',
                'intro' => 'Een gerichte plaatsing voor één sales- of marketingvacature.',
                'features' => ['60 dagen zichtbaar', 'Reguliere positionering op SMV', 'Afstemming over aangeleverd materiaal'],
                'featured' => false,
            ],
            [
                'name' => 'Superior',
                'price' => '€ 398',
                'suffix' => 'excl. btw',
                'intro' => 'Voor vacatures die in overleg aanvullende zichtbaarheid krijgen.',
                'features' => ['60 dagen zichtbaar', 'Extra positionering en aandacht in overleg', 'Mogelijke nieuwsbrief- en socialinzet afgestemd op de functie'],
                'featured' => true,
            ],
            [
                'name' => 'Maatwerk',
                'price' => 'Op aanvraag',
                'suffix' => null,
                'intro' => 'Voor meerdere vacatures, terugkerende werving of aanvullende campagneondersteuning.',
                'features' => ['Aanpak afgestemd op uw wervingsvraag', 'Ruimte voor meerdere plaatsingen', 'Aanvullende zichtbaarheid en jobmarketing bespreekbaar'],
                'featured' => false,
            ],
        ];
    @endphp

    <section class="relative">
        <div class="pointer-events-none absolute inset-0 -z-10 h-1/3 bg-slate-900 lg:h-[48rem] [clip-path:polygon(0_0,_5760px_0,_5760px_calc(100%_-_352px),_0_100%)]" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
            <div class="pt-32 md:pt-40">
                <div class="mx-auto max-w-3xl pb-12 text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-blue-300">Vacature adverteren</p>
                    <h1 class="mt-3 font-playfair-display text-4xl font-bold text-slate-100 sm:text-5xl">Kies de zichtbaarheid die bij uw vacature past</h1>
                    <p class="mx-auto mt-5 max-w-2xl text-lg text-slate-300">Een eerste pakket voor een gerichte plaatsing, extra aandacht in overleg of een aanpak op maat.</p>
                </div>
                <div class="mx-auto grid max-w-sm gap-8 pb-16 lg:max-w-none lg:grid-cols-3 lg:gap-6">
                    @foreach ($plans as $plan)
                        <article @class([
                            'relative flex h-full flex-col bg-white px-6 py-6 shadow-lg',
                            'ring-2 ring-blue-500' => $plan['featured'],
                        ])>
                            @if ($plan['featured'])
                                <span class="absolute right-6 -top-4 rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-800">Extra zichtbaarheid</span>
                            @endif
                            <div class="mb-5 border-b border-slate-200 pb-5">
                                <h2 class="text-lg font-semibold text-slate-800">{{ $plan['name'] }}</h2>
                                <p class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">{{ $plan['price'] }}</p>
                                @if ($plan['suffix'])
                                    <p class="mt-1 text-sm text-slate-500">{{ $plan['suffix'] }}</p>
                                @endif
                                <p class="mt-4 leading-6 text-slate-600">{{ $plan['intro'] }}</p>
                            </div>
                            <ul class="grow space-y-3 text-slate-600">
                                @foreach ($plan['features'] as $feature)
                                    <li class="flex items-start gap-3">
                                        <svg class="mt-1 h-3 w-3 shrink-0 fill-current text-emerald-500" viewBox="0 0 12 12" aria-hidden="true"><path d="M10.28 2.28 3.989 8.575 1.695 6.28A1 1 0 0 0 .28 7.695l3 3a1 1 0 0 0 1.414 0l7-7A1 1 0 0 0 10.28 2.28Z" /></svg>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="mt-7 rounded-sm bg-slate-50 p-3">
                                <a class="btn-sm w-full bg-blue-600 text-white hover:bg-blue-700" href="{{ route('contact') }}">{{ $plan['name'] === 'Maatwerk' ? 'Bespreek de mogelijkheden' : 'Vraag dit pakket aan' }} <span class="ml-1 text-blue-300">→</span></a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="bg-slate-100">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
            <div class="relative flex flex-col items-start justify-between gap-6 bg-white p-7 shadow-lg md:flex-row md:items-center">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Meerdere vacatures of vragen?</p>
                    <h2 class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">Bespreek de mogelijkheden</h2>
                    <p class="mt-3 max-w-2xl text-slate-600">We stemmen maatwerk, terugkerende plaatsingen en aanvullende ondersteuning graag rechtstreeks met u af.</p>
                </div>
                <a class="btn shrink-0 bg-blue-600 text-white hover:bg-blue-700" href="{{ route('contact') }}">Neem contact op <span class="ml-1 text-blue-300">→</span></a>
            </div>
        </div>
    </section>
@endsection

@extends('layouts.public')

@section('title', 'Over ons | Sales en Marketing Vacatures')
@section('meta_description', 'Lees meer over Sales en Marketing Vacatures en onze focus op commercieel talent.')
@section('canonical', route('about'))
@section('header_theme', 'dark')

@section('content')
    <section class="relative">
        <div class="absolute inset-0 -z-10 mb-48 bg-slate-900 lg:mb-0 lg:h-[30rem]" aria-hidden="true"><img class="h-full w-full object-cover opacity-10" src="{{ asset('images/about-hero.jpg') }}" width="1440" height="497" alt=""></div>
        <div class="relative mx-auto max-w-6xl px-4 sm:px-6"><div class="pt-32 md:pt-40"><div class="mx-auto max-w-3xl pb-16 text-center"><h1 class="h1 font-playfair-display text-slate-100">Wij brengen commercieel talent en ambitieuze werkgevers samen</h1></div><div class="flex justify-center"><img class="mx-auto" src="{{ asset('images/about-intro.jpg') }}" width="1024" height="576" alt="Samenwerken aan de volgende carrièrestap"></div></div></div>
    </section>
    <section class="-translate-y-1/2"><div class="mx-auto max-w-6xl px-4 sm:px-6"><div class="mx-auto max-w-3xl bg-blue-600 py-4 shadow-xl"><ul class="flex"><li class="w-1/3 px-2 text-center"><div class="font-playfair-display text-3xl font-bold text-white md:text-5xl">Sales</div><div class="mt-2 text-xs font-medium text-blue-200 sm:text-sm">Commercieel talent</div></li><li class="w-1/3 border-x border-blue-500 px-2 text-center"><div class="font-playfair-display text-3xl font-bold text-white md:text-5xl">Marketing</div><div class="mt-2 text-xs font-medium text-blue-200 sm:text-sm">Sterke merken</div></li><li class="w-1/3 px-2 text-center"><div class="font-playfair-display text-3xl font-bold text-white md:text-5xl">Groei</div><div class="mt-2 text-xs font-medium text-blue-200 sm:text-sm">Volgende stap</div></li></ul></div></div></section>
    <section><div class="mx-auto max-w-3xl px-4 pb-16 text-lg leading-8 text-slate-500 sm:px-6 md:pb-24"><h2 class="mb-4 font-playfair-display text-3xl text-slate-800">Onze missie</h2><p class="mb-6">Sales en Marketing Vacatures is het gespecialiseerde platform voor professionals en organisaties die willen groeien. We maken relevante kansen zichtbaar en houden het zoeken naar een nieuwe uitdaging helder en persoonlijk.</p><p>Met actuele vacatures, inspirerende content en een scherp focusgebied helpen we kandidaten en werkgevers elkaar sneller te vinden.</p></div></section>
@endsection

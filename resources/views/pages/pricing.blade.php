@extends('layouts.public')

@section('title', 'Tarieven | Sales en Marketing Vacatures')
@section('meta_description', 'Bekijk de mogelijkheden voor het plaatsen van vacatures bij Sales en Marketing Vacatures.')
@section('canonical', route('pricing'))

@section('content')
    <section class="relative"><div class="pointer-events-none absolute inset-0 -z-10 h-1/3 bg-slate-900 lg:h-[48rem] [clip-path:polygon(0_0,_5760px_0,_5760px_calc(100%_-_352px),_0_100%)]" aria-hidden="true"></div><div class="relative mx-auto max-w-6xl px-4 sm:px-6"><div class="pt-32 md:pt-40"><div class="mx-auto max-w-3xl pb-12 text-center"><h1 class="h1 font-playfair-display text-slate-100">Kies de zichtbaarheid die bij je vacature past</h1></div><div class="mx-auto grid max-w-sm gap-8 pb-16 lg:max-w-none lg:grid-cols-3 lg:gap-6">
        @foreach ([['Start', 'Voor een gerichte start', 'Basisplaatsing van je vacature'], ['Plus', 'Meer bereik voor je vacature', 'Extra zichtbaarheid bij commercieel talent'], ['Premium', 'Maximale aandacht', 'Voor vacatures die niet onopgemerkt mogen blijven']] as $plan)
            <article class="flex h-full flex-col bg-white px-6 py-5 shadow-lg"><div class="mb-4 border-b border-slate-200 pb-4"><h2 class="mb-1 text-lg font-semibold text-slate-800">{{ $plan[0] }}</h2><p class="font-playfair-display text-3xl text-slate-800">Op aanvraag</p><p class="mt-3 text-slate-500">{{ $plan[1] }}</p></div><p class="grow text-slate-500">{{ $plan[2] }}</p><div class="mt-6 rounded-sm bg-slate-50 p-3"><a class="btn-sm w-full bg-blue-600 text-white hover:bg-blue-700" href="{{ route('contact') }}">Neem contact op <span class="ml-1 text-blue-300">→</span></a></div></article>
        @endforeach
    </div></div></div></section>
    <section class="bg-slate-100"><div class="mx-auto max-w-3xl px-4 py-14 text-center sm:px-6"><h2 class="font-playfair-display text-3xl text-slate-800">Liever eerst overleggen?</h2><p class="mt-4 text-slate-500">We denken graag mee over de beste zichtbaarheid voor jouw vacature.</p><a class="btn mt-7 bg-blue-600 text-white hover:bg-blue-700" href="{{ route('contact') }}">Contact opnemen</a></div></section>
@endsection

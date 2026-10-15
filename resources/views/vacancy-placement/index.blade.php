@extends('layouts.public')

@section('title', 'Vacature plaatsen | Sales en Marketing Vacatures')
@section('meta_description', 'Start met het plaatsen van een sales- of marketingvacature.')
@section('canonical', route('vacancy-placement.index'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-6xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <x-vacancy-placement.steps :current="1" />

        <div class="mx-auto mt-10 max-w-3xl text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Vacature plaatsen</p>
            <h1 class="mt-3 font-playfair-display text-4xl font-bold text-slate-900">Kies de plaatsing die bij uw vacature past</h1>
            <p class="mt-4 text-lg leading-8 text-slate-600">U kunt als gast beginnen. Een account is pas nodig wanneer we uw vacature veilig als concept opslaan.</p>
        </div>

        @error('package')
            <p class="mx-auto mt-6 max-w-3xl rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">{{ $message }}</p>
        @enderror

        <div class="mt-10 grid gap-6 lg:grid-cols-3">
            @foreach ($plans as $plan)
                <article @class(['relative flex h-full flex-col rounded-xl border bg-white p-6 shadow-sm', 'border-blue-500 ring-2 ring-blue-100' => $plan['featured'], 'border-slate-200' => ! $plan['featured']])>
                    @if ($plan['featured'])
                        <span class="absolute right-5 -top-3 rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-800">Extra zichtbaarheid</span>
                    @endif
                    <h2 class="text-xl font-semibold text-slate-900">{{ $plan['name'] }}</h2>
                    <p class="mt-3 font-playfair-display text-3xl font-bold text-slate-900">{{ $plan['price'] }}</p>
                    @if ($plan['suffix'])
                        <p class="mt-1 text-sm text-slate-500">{{ $plan['suffix'] }}</p>
                    @endif
                    <p class="mt-4 leading-7 text-slate-600">{{ $plan['intro'] }}</p>
                    <ul class="mt-6 grow space-y-3 text-sm text-slate-600">
                        @foreach ($plan['features'] as $feature)
                            <li class="flex gap-3"><span class="text-emerald-600" aria-hidden="true">✓</span><span>{{ $feature }}</span></li>
                        @endforeach
                    </ul>
                    <form class="mt-7" action="{{ route('vacancy-placement.package') }}" method="POST">
                        @csrf
                        <input name="package" type="hidden" value="{{ $plan['value'] }}">
                        <x-ui.button class="w-full justify-center" type="submit" variant="brand">
                            {{ $plan['enters_flow'] ? 'Kies '.$plan['name'] : 'Bespreek maatwerk' }}
                        </x-ui.button>
                    </form>
                </article>
            @endforeach
        </div>

        <p class="mt-8 text-center text-sm text-slate-500">Uw keuze is nog geen bestelling of betaling. Definitieve pakket- en betaalafhandeling volgt pas na controle.</p>
    </section>
@endsection

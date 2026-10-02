@extends('layouts.public')

@section('title', 'Bedrijven | Sales en Marketing Vacatures')
@section('meta_description', 'Maak kennis met werkgevers en ontdek hun actuele sales- en marketingvacatures.')
@section('canonical', $seoCanonical)

@section('content')
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute left-1/2 top-0 -z-10 -translate-x-1/2" aria-hidden="true">
            <div class="h-80 w-[48rem] bg-[repeating-linear-gradient(135deg,transparent_0,transparent_13px,rgba(86,150,255,.08)_14px,rgba(86,150,255,.08)_15px)]"></div>
        </div>
        <div class="pointer-events-none absolute left-1/2 top-12 -z-10 ml-[23rem] -translate-x-1/2" aria-hidden="true">
            <div class="h-72 w-72 rounded-full bg-linear-to-tr from-blue-500/35 to-slate-900/25 blur-[130px]"></div>
        </div>
        <div class="pointer-events-none absolute left-1/2 top-44 -z-10 -ml-[26rem] -translate-x-1/2" aria-hidden="true">
            <div class="h-72 w-72 rounded-full bg-linear-to-tr from-blue-500/25 to-slate-900/20 blur-[130px]"></div>
        </div>

        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto max-w-3xl pt-28 pb-10 md:pt-36 md:pb-14">
                <div class="text-center">
                    <p class="mb-3 text-sm font-semibold uppercase tracking-widest text-blue-600">Werkgevers</p>
                    <h1 class="border-y py-5 text-4xl font-bold text-slate-900 [border-image:linear-gradient(to_right,transparent,var(--color-slate-300),transparent)_1] sm:text-5xl md:text-6xl">Ontdek bedrijven</h1>
                    <p class="mx-auto mt-6 max-w-2xl text-lg text-slate-600">Maak kennis met werkgevers, lees waar zij voor staan en bekijk hun actuele sales- en marketingvacatures.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 pb-14 sm:px-6 md:pb-20" aria-labelledby="bedrijven-overzicht">
        <div class="mb-5 flex items-center justify-between gap-4">
            <h2 id="bedrijven-overzicht" class="text-xl font-bold text-slate-900">Alle bedrijven</h2>
            <p class="text-sm text-slate-500">{{ $companies->total() }} {{ $companies->total() === 1 ? 'bedrijf' : 'bedrijven' }}</p>
        </div>

        @if ($companies->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2 md:gap-6 lg:grid-cols-3">
                @foreach ($companies as $company)
                    <x-company.card :company="$company" :logo-url="$company->publicLogoUrl()" />
                @endforeach
            </div>

            @if ($companies->hasPages())
                <div class="mt-12">
                    {{ $companies->links('pagination::tailwind') }}
                </div>
            @endif
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-10 text-center text-slate-600 shadow-sm">
                <p class="font-semibold text-slate-900">Geen bedrijven gevonden.</p>
                <p class="mt-2 text-sm">Kom binnenkort terug voor werkgevers met actuele vacatures.</p>
            </div>
        @endif
    </section>
@endsection

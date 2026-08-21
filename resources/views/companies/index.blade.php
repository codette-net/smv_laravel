@extends('layouts.public')

@section('title', 'Bedrijven | Sales en Marketing Vacatures')
@section('meta_description', 'Ontdek bedrijven met actuele sales- en marketingvacatures.')

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Werkgevers</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Bedrijven</h1>
            <p class="mt-4 max-w-2xl text-lg leading-7 text-slate-600">Maak kennis met werkgevers die op zoek zijn naar sales- en marketingtalent.</p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14" aria-labelledby="bedrijven-overzicht">
        <h2 id="bedrijven-overzicht" class="sr-only">Overzicht van bedrijven</h2>

        @if ($companies->isNotEmpty())
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($companies as $company)
                    <x-company.card :company="$company" :logo-url="$company->publicLogoUrl()" />
                @endforeach
            </div>

            @if ($companies->hasPages())
                <div class="mt-10">
                    {{ $companies->links() }}
                </div>
            @endif
        @else
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">
                Geen bedrijven gevonden.
            </div>
        @endif
    </section>
@endsection

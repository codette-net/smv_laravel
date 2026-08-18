@extends('layouts.public')

@section('title', 'Vacatures | Sales en Marketing Vacatures')
@section('meta_description', 'Vind actuele sales- en marketingvacatures bij toonaangevende werkgevers.')

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Vacatures</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Vind jouw volgende vacature</h1>
            <p class="mt-4 max-w-2xl text-lg leading-7 text-slate-600">Ontdek actuele vacatures in sales en marketing.</p>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <div class="grid gap-8 lg:grid-cols-[17rem_minmax(0,1fr)]">
            <aside class="self-start rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                <details class="group" open>
                    <summary class="flex cursor-pointer list-none items-center justify-between text-lg font-bold text-slate-900">
                        Verfijn je zoekopdracht
                        <span class="text-blue-700 group-open:rotate-180" aria-hidden="true">⌄</span>
                    </summary>
                    <div class="mt-5"><x-vacancy.filter-form :filters="$filters" :sort="$sort" :sort-options="$sortOptions" :locations="$locations" :categories="$categories" :companies="$companies" /></div>
                </details>
            </aside>

            <section aria-labelledby="vacature-resultaten">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900" id="vacature-resultaten">{{ $vacancies->total() }} {{ Str::plural('vacature', $vacancies->total()) }} gevonden</h2>
                        <p class="mt-1 text-sm text-slate-500">Sorteer en verfijn de actuele vacatures.</p>
                    </div>
                    <x-vacancy.active-filters :filters="$activeFilters" />
                </div>

                @if ($vacancies->isNotEmpty())
                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        @foreach ($vacancies as $vacancy)
                            <x-vacancy.card :vacancy="$vacancy" />
                        @endforeach
                    </div>

                    @if ($vacancies->hasPages())
                        <div class="mt-10">{{ $vacancies->links() }}</div>
                    @endif
                @else
                    <div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center">
                        <h2 class="text-lg font-bold text-slate-900">Geen vacatures gevonden</h2>
                        <p class="mt-2 text-slate-600">Probeer je zoekopdracht aan te passen of verwijder je filters.</p>
                        <a class="mt-5 inline-flex rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" href="{{ route('vacancies.index') }}">Wis filters</a>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection

@extends('layouts.public')

@section('title', 'Sales en Marketing Vacatures | Vind jouw volgende commerciële baan')
@section('meta_description', 'Vind actuele sales- en marketingvacatures en werkgevers op Sales en Marketing Vacatures.')
@section('canonical', route('home'))

@section('content')
    <section class="bg-slate-950 text-white">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-300">Sales &amp; Marketing Vacatures</p>
                <h1 class="mt-4 font-playfair-display text-4xl font-bold tracking-tight sm:text-5xl">Vind jouw volgende commerciële uitdaging</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">Ontdek actuele vacatures en werkgevers die passen bij jouw ervaring in sales, marketing en commercie.</p>
                <a class="btn mt-8 bg-blue-600 text-white hover:bg-blue-700" href="{{ route('vacancies.index') }}">Bekijk alle vacatures</a>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14" aria-labelledby="vacature-zoeker">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Zoek vacatures</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900" id="vacature-zoeker">Waar ben je naar op zoek?</h2>
                <p class="mt-2 text-slate-600">Verfijn je zoekopdracht en bekijk de actuele resultaten.</p>
            </div>
            <div class="mt-7">
                <x-vacancy.filter-form :filters="$filters" :sort="$sort" :sort-options="$sortOptions" :locations="$locations" :taxonomy-options="$taxonomyOptions" :companies="$companies" variant="homepage" />
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14" aria-labelledby="recente-vacatures">
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Actueel aanbod</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900" id="recente-vacatures">Recente vacatures</h2>
            </div>
            <a class="text-sm font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('vacancies.index') }}">Bekijk alle vacatures <span aria-hidden="true">→</span></a>
        </div>

        @if ($vacancies->isNotEmpty())
            <div class="mt-8 flex flex-col gap-3">
                @foreach ($vacancies->take(3) as $vacancy)
                    <x-vacancy.card :vacancy="$vacancy" :detail-url="route('vacancies.show', $vacancy)" />
                @endforeach
            </div>
        @else
            <div class="mt-8 rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">Er zijn op dit moment geen actuele vacatures.</div>
        @endif
    </section>

    @if ($latestBlogPost)
        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Uit de blog</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Nieuwste inzichten</h2>
                    </div>
                    <a class="text-sm font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('blog.index') }}">Bekijk alle artikelen <span aria-hidden="true">→</span></a>
                </div>
                <div class="mt-8 max-w-md"><x-blog.card :post="$latestBlogPost" /></div>
            </div>
        </section>
    @endif
@endsection

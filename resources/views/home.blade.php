@extends('layouts.public')

@section('title', 'Sales en Marketing Vacatures | Vind jouw volgende commerciële baan')
@section('meta_description', 'Vind actuele sales- en marketingvacatures en werkgevers op Sales en Marketing Vacatures.')
@section('canonical', route('home'))
@section('header_theme', 'dark')

@section('content')
    <section class="relative">
        <div
            class="pointer-events-none absolute inset-0 -z-10 bg-slate-900 [clip-path:polygon(0_0,_5760px_0,_5760px_calc(100%_-_160px),_0_100%)]"
            aria-hidden="true"></div>
        <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
            <div class="pt-32 pb-28 md:pt-40 md:pb-44">
                <div class="mx-auto max-w-xl text-center md:mx-0 md:text-left"><p
                        class="text-sm font-semibold uppercase tracking-widest text-blue-300">Sales &amp; Marketing
                        Vacatures</p>
                    <h1 class="mt-4 font-playfair-display text-4xl font-bold tracking-tight text-slate-100 sm:text-5xl">
                        Vind jouw volgende commerciële uitdaging</h1>
                    <p class="mt-6 text-xl text-slate-400">Ontdek actuele vacatures en werkgevers die passen bij jouw
                        ervaring in sales, marketing en commercie.</p><a
                        class="btn mt-8 bg-blue-600 text-white hover:bg-blue-700" href="{{ route('vacancies.index') }}">Bekijk
                        alle vacatures <span class="ml-1 text-blue-300">→</span></a></div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-8 2xl:px-16 py-10 sm:px-6 lg:py-14" aria-labelledby="vacature-zoeker">
        <div class="lg:flex lg:items-start lg:gap-10">
            <aside
                class="mb-8 lg:sticky lg:top-24 lg:mb-0 lg:w-72 lg:shrink-0 shadow-lg shadow-blue-200/50 rounded-xl ">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Zoek vacatures</p>
                        <h2 class="mt-2 text-lg font-bold tracking-tight text-gray-800" id="vacature-zoeker">Verfijn je
                            zoekopdracht</h2>
                    </div>
                    <div class="mt-5">
                        <x-home.vacancy-search :filters="$filters" :sort="$sort" :sort-options="$sortOptions"
                                               :locations="$locations" :taxonomy-options="$taxonomyOptions"
                                               :companies="$companies" :has-filters="$hasFilters"
                                               :has-additional-filters="$hasAdditionalFilters"/>
                    </div>
                </div>
            </aside>
            <section class="min-w-0 lg:grow" aria-labelledby="recente-vacatures">
                <div
                    class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Actueel aanbod</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900"
                            id="recente-vacatures">{{ $hasFilters ? 'Gevonden vacatures' : 'Recente vacatures' }}</h2>
                    </div>
                    <a class="text-sm font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600"
                       href="{{ route('vacancies.index') }}">Bekijk alle vacatures <span aria-hidden="true">→</span></a>
                </div>

                @if ($vacancies->isNotEmpty())
                    <div class="mt-8 grid gap-4 grid-cols-[repeat(auto-fill,minmax(16rem,1fr))]">
                        @foreach ($vacancies as $vacancy)
                            <x-vacancy.card :vacancy="$vacancy" :detail-url="route('vacancies.show', $vacancy)"/>
                        @endforeach
                    </div>
                    @if ($vacancies->hasPages())
                        <div class="mt-10">{{ $vacancies->links('pagination::tailwind') }}</div>
                    @endif
                @else
                    <div
                        class="mt-8 rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">
                        Er zijn op dit moment geen actuele vacatures.
                    </div>
                @endif
            </section>
        </div>
    </section>

    @if ($latestBlogPost)
        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Uit de blog</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Nieuwste inzichten</h2>
                    </div>
                    <a class="text-sm font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600"
                       href="{{ route('blog.index') }}">Bekijk alle artikelen <span aria-hidden="true">→</span></a>
                </div>
                <div class="mt-8 max-w-md">
                    <x-blog.card :post="$latestBlogPost"/>
                </div>
            </div>
        </section>
    @endif
@endsection

@extends('layouts.public')

@section('title', 'Bedrijven | Sales en Marketing Vacatures')
@section('meta_description', 'Maak kennis met werkgevers en ontdek hun actuele sales- en marketingvacatures.')
@section('canonical', $seoCanonical)

@section('content')
    <section class="smv-hero-light relative overflow-hidden">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto max-w-3xl pt-28 pb-10 md:pt-36 md:pb-14">
                <div class="text-center">
                    <p class="mb-3 text-sm font-semibold uppercase tracking-widest text-blue-600">Werkgevers</p>
                    <h1 class="border-y py-5 text-4xl font-bold text-slate-900 [border-image:linear-gradient(to_right,transparent,var(--color-slate-300),transparent)_1] sm:text-5xl md:text-6xl">Ontdek bedrijven</h1>
                    <p class="mx-auto mt-6 max-w-2xl text-lg text-slate-600">Maak kennis met werkgevers, lees waar zij voor staan en bekijk hun actuele sales- en marketingvacatures.</p>
                    <a class="mt-5 inline-flex text-sm font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="#bedrijfscategorieen">Zoeken op categorie <span class="ml-1" aria-hidden="true">↓</span></a>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 pb-14 sm:px-6 md:pb-20" aria-labelledby="bedrijven-overzicht">
        <div class="rounded-xl border border-slate-200 bg-white/85 p-4 shadow-sm backdrop-blur-sm sm:p-5">
            <form class="flex flex-col gap-3 sm:flex-row sm:items-end" action="{{ route('companies.index') }}" method="GET" x-data>
                <div class="grow">
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="company-search">Zoek een bedrijf</label>
                    <div class="relative">
                        <input class="form-input w-full pl-10" id="company-search" name="q" type="search" value="{{ $search }}" placeholder="Naam, locatie of omschrijving" x-on:input.debounce.450ms="$el.form.requestSubmit()">
                        <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 fill-current text-slate-400" viewBox="0 0 16 16" aria-hidden="true"><path d="M7 14c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7Zm0-12C4.243 2 2 4.243 2 7s2.243 5 5 5 5-2.243 5-5-2.243-5-5-5Z" /><path d="m15.707 14.293-2.393-2.393a8.019 8.019 0 0 1-1.414 1.414l2.393 2.393a.997.997 0 0 0 1.414 0 .999.999 0 0 0 0-1.414Z" /></svg>
                    </div>
                </div>

                @if ($category)
                    <input name="category" type="hidden" value="{{ $category }}">
                @endif

                <x-ui.button class="justify-center" type="submit">Zoeken</x-ui.button>

                @if ($search || $category)
                    <a class="btn justify-center border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:text-slate-900" href="{{ route('companies.index') }}">Wis filters</a>
                @endif
            </form>
        </div>

        @if ($companyCategories->isNotEmpty())
            <nav class="mt-8" id="bedrijfscategorieen" aria-label="Bedrijfscategorieën">
                <div class="mb-3 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Categorieën</p>
                        <h2 class="mt-1 text-lg font-bold text-slate-900">Verken werkgevers per categorie</h2>
                    </div>
                    <span class="hidden text-sm text-slate-500 sm:block">Eén categorie tegelijk</span>
                </div>
                <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-3 sm:mx-0 sm:px-0">
                    <a @class([
                        'shrink-0 rounded-lg border px-4 py-3 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600',
                        'border-blue-600 bg-blue-600 text-white' => ! $category,
                        'border-slate-200 bg-white text-slate-700 hover:border-blue-300 hover:text-blue-700' => $category,
                    ]) href="{{ route('companies.index', array_filter(['q' => $search])) }}" @if (! $category) aria-current="page" @endif>
                        Alle bedrijven
                    </a>
                    @foreach ($companyCategories as $companyCategory)
                        <a @class([
                            'shrink-0 rounded-lg border px-4 py-3 text-sm transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600',
                            'border-blue-600 bg-blue-600 text-white' => $category === $companyCategory->slug,
                            'border-slate-200 bg-white text-slate-700 hover:border-blue-300 hover:text-blue-700' => $category !== $companyCategory->slug,
                        ]) href="{{ route('companies.index', array_filter(['q' => $search, 'category' => $companyCategory->slug])) }}" @if ($category === $companyCategory->slug) aria-current="page" @endif>
                            <span class="font-semibold">{{ $companyCategory->name }}</span>
                            <span @class(['ml-1', 'text-blue-100' => $category === $companyCategory->slug, 'text-slate-400' => $category !== $companyCategory->slug])>({{ $companyCategory->public_companies_count }})</span>
                        </a>
                    @endforeach
                </div>
            </nav>
        @endif

        @php($activeCategory = $companyCategories->firstWhere('slug', $category))
        <div class="mt-8 mb-5 flex items-center justify-between gap-4">
            <h2 id="bedrijven-overzicht" class="text-xl font-bold text-slate-900">
                {{ $activeCategory ? 'Bedrijven in '.$activeCategory->name : ($search ? 'Gevonden bedrijven' : 'Alle bedrijven') }}
            </h2>
            <p class="text-sm text-slate-500" aria-live="polite">{{ $companies->total() }} {{ $companies->total() === 1 ? 'bedrijf' : 'bedrijven' }}</p>
        </div>

        @if ($companies->isNotEmpty())
            <div class="grid gap-4 lg:grid-cols-2 md:gap-6">
                @foreach ($companies as $company)
                    <x-company.card :company="$company" :logo-url="$company->publicLogoUrl()" :show-vacancy-titles="true" />
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
                <p class="mt-2 text-sm">Pas je zoekopdracht aan of wis de gekozen filters.</p>
                @if ($search || $category)
                    <a class="mt-5 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('companies.index') }}">Alle bedrijven bekijken</a>
                @endif
            </div>
        @endif
    </section>
@endsection

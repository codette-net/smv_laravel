@extends('layouts.public')

@section('title', $vacancy->title.' | Sales en Marketing Vacatures')
@section('meta_description', Str::limit(Str::squish(strip_tags($vacancy->description)), 155))

@section('content')
    <div class="mx-auto flex max-w-5xl flex-col gap-8 px-4 py-10 sm:px-6 lg:flex-row lg:gap-12 lg:py-14">
        <article class="min-w-0 grow">
            <a class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-blue-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('vacancies.index') }}">
                <span aria-hidden="true" class="mr-2">←</span> Terug naar vacatures
            </a>

            <div class="mt-7 text-sm text-slate-500">
                @if ($vacancy->published_at)
                    Gepubliceerd {{ $vacancy->published_at->translatedFormat('j F Y') }}
                @endif
            </div>
            <header class="mt-2 border-b border-slate-200 pb-6">
                @if ($vacancy->is_featured)
                    <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Uitgelichte vacature</span>
                @endif
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $vacancy->title }}</h1>
                <a class="mt-3 inline-flex text-base font-semibold text-blue-700 hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('bedrijven.show', $vacancy->company) }}">{{ $vacancy->company->name }}</a>
                @if ($vacancy->location)
                    <p class="mt-2 text-slate-600">{{ $vacancy->location }}</p>
                @endif
            </header>

            @if (collect($taxonomy)->flatten()->isNotEmpty() || $vacancy->tags->isNotEmpty())
                <section class="border-b border-slate-200 py-6" aria-labelledby="vacaturekenmerken">
                    <h2 id="vacaturekenmerken" class="text-lg font-bold text-slate-900">Vacaturekenmerken</h2>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach (['dienstverband' => 'Dienstverband', 'werklocatie' => 'Werklocatie', 'sector' => 'Sector', 'functiegebied' => 'Functiegebied', 'ervaring' => 'Ervaring'] as $key => $label)
                            @if ($taxonomy[$key]->isNotEmpty())
                                <div>
                                    <dt class="text-sm font-semibold text-slate-700">{{ $label }}</dt>
                                    <dd class="mt-1 text-sm text-slate-600">{{ $taxonomy[$key]->map(fn ($category) => $category->parent ? $category->parent->name.' — '.$category->name : $category->name)->join(', ') }}</dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                    @if ($vacancy->tags->isNotEmpty())
                        <div class="mt-5">
                            <h3 class="text-sm font-semibold text-slate-700">Tags</h3>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($vacancy->tags as $tag)
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $tag->name }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
            @endif

            <section class="py-8" aria-labelledby="over-deze-vacature">
                <h2 id="over-deze-vacature" class="text-2xl font-bold text-slate-900">Over deze vacature</h2>
                <div class="prose prose-slate mt-5 max-w-none leading-7">{!! $vacancy->description !!}</div>
            </section>

            @if ($relatedVacancies->isNotEmpty())
                <section class="border-t border-slate-200 pt-8" aria-labelledby="gerelateerde-vacatures">
                    <h2 id="gerelateerde-vacatures" class="text-2xl font-bold text-slate-900">Gerelateerde vacatures</h2>
                    <div class="mt-5 space-y-3">
                        @foreach ($relatedVacancies as $relatedVacancy)
                            <x-company.vacancy-card :vacancy="$relatedVacancy" :logo-url="$relatedVacancy->company->publicLogoUrl()" />
                        @endforeach
                    </div>
                </section>
            @endif
        </article>

        <aside class="w-full shrink-0 space-y-4 lg:w-80">
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-blue-50 text-xl font-bold text-blue-700">
                        @if ($logoUrl)
                            <img class="h-full w-full object-cover" src="{{ $logoUrl }}" alt="Logo van {{ $vacancy->company->name }}">
                        @else
                            {{ Str::upper(Str::substr($vacancy->company->name, 0, 1)) }}
                        @endif
                    </div>
                    <h2 class="mt-3 text-lg font-bold text-slate-900">{{ $vacancy->company->name }}</h2>
                    @if ($vacancy->company->tagline)
                        <p class="mt-1 text-sm text-slate-500">{{ $vacancy->company->tagline }}</p>
                    @endif
                </div>

                @if ($vacancy->deadline_at || $vacancy->compensationLabel())
                    <div class="mt-5 space-y-2 border-t border-slate-200 pt-5 text-sm text-slate-600">
                        @if ($vacancy->deadline_at)
                            <p><span class="font-semibold text-slate-800">Solliciteren vóór:</span> {{ $vacancy->deadline_at->translatedFormat('j F Y') }}</p>
                        @endif
                        @if ($vacancy->compensationLabel())
                            <p>{{ $vacancy->compensationLabel() }}</p>
                        @endif
                    </div>
                @endif

                <div class="mt-6 space-y-2">
                    @if ($vacancy->application_url)
                        <a class="btn flex w-full justify-center bg-slate-900 text-white hover:bg-slate-800" href="{{ $vacancy->application_url }}" target="_blank" rel="noopener noreferrer">Solliciteer nu</a>
                    @elseif ($vacancy->application_email)
                        <a class="btn flex w-full justify-center bg-slate-900 text-white hover:bg-slate-800" href="mailto:{{ $vacancy->application_email }}?subject={{ rawurlencode('Sollicitatie: '.$vacancy->title) }}">Solliciteer nu</a>
                    @endif
                    <a class="btn flex w-full justify-center border border-slate-300 bg-white text-slate-700 hover:border-slate-400" href="{{ route('bedrijven.show', $vacancy->company) }}">Bekijk bedrijfsprofiel</a>
                </div>
            </section>

            @if ($vacancy->company->description || $vacancy->company->location)
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="over-het-bedrijf">
                    <h2 id="over-het-bedrijf" class="text-lg font-bold text-slate-900">Over het bedrijf</h2>
                    @if ($vacancy->company->location)
                        <p class="mt-3 text-sm text-slate-500">{{ $vacancy->company->location }}</p>
                    @endif
                    @if ($vacancy->company->description)
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ Str::limit(Str::squish(strip_tags($vacancy->company->description)), 240) }}</p>
                    @endif
                </section>
            @endif
        </aside>
    </div>
@endsection

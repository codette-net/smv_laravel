@extends('layouts.public')

@section('title', 'Mijn account | Sales en Marketing Vacatures')
@section('canonical', route('account.index'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-6xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Mijn account</p>
                <h1 class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">Welkom, {{ auth()->user()->name }}</h1>
                <p class="mt-3 max-w-2xl leading-7 text-slate-600">Beheer uw account, bewaarde vacatures en eventuele werkgeversactiviteiten.</p>
            </div>
            <a class="btn justify-center bg-blue-600 text-white hover:bg-blue-700" href="{{ route('vacancy-placement.index') }}">Vacature plaatsen</a>
        </div>

        @if (session('account_status'))
            <div class="mt-7 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900" role="status">{{ session('account_status') }}</div>
        @endif

        <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="space-y-8">
                <section aria-labelledby="applications-heading">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:flex sm:items-center sm:justify-between sm:gap-6">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900" id="applications-heading">Mijn sollicitaties</h2>
                            <p class="mt-2 text-slate-600">{{ $applicationsCount }} {{ $applicationsCount === 1 ? 'sollicitatie' : 'sollicitaties' }} rechtstreeks via SMV.</p>
                        </div>
                        <a class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800 sm:mt-0" href="{{ route('account.applications') }}">Bekijk mijn sollicitaties →</a>
                    </div>
                </section>

                <section aria-labelledby="saved-vacancies-heading">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:flex sm:items-center sm:justify-between sm:gap-6">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900" id="saved-vacancies-heading">Bewaarde vacatures</h2>
                            <p class="mt-2 text-slate-600">{{ $savedVacanciesCount }} {{ Str::plural('vacature', $savedVacanciesCount) }} bewaard om later terug te bekijken.</p>
                        </div>
                        <a class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800 sm:mt-0" href="{{ route('account.saved-vacancies') }}">Bekijk bewaarde vacatures →</a>
                    </div>
                </section>

                <section aria-labelledby="saved-companies-heading">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:flex sm:items-center sm:justify-between sm:gap-6">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900" id="saved-companies-heading">Bewaarde bedrijven</h2>
                            <p class="mt-2 text-slate-600">{{ $savedCompaniesCount }} {{ $savedCompaniesCount === 1 ? 'bedrijf' : 'bedrijven' }} bewaard om later terug te bekijken.</p>
                        </div>
                        <a class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800 sm:mt-0" href="{{ route('account.saved-companies') }}">Bekijk bewaarde bedrijven →</a>
                    </div>
                </section>

                @if ($companies->isNotEmpty())
                <section aria-labelledby="companies-heading">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-xl font-bold text-slate-900" id="companies-heading">Mijn bedrijven</h2>
                    </div>
                    <div class="mt-4 space-y-4">
                        @foreach ($companies as $company)
                            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:flex sm:items-center sm:justify-between sm:gap-5">
                                <div class="flex min-w-0 items-center gap-4">
                                    <div class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white">
                                        @if ($company->publicLogoUrl())
                                            <img class="h-full w-full object-contain p-1.5" src="{{ $company->publicLogoUrl() }}" alt="Logo van {{ $company->name }}">
                                        @else
                                            <span class="text-xl font-bold text-blue-700">{{ Str::upper(Str::substr($company->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="truncate font-semibold text-slate-900">{{ $company->name }}</h3>
                                        <p class="mt-1 text-sm text-slate-500">{{ $company->status->getLabel() }} · {{ $company->vacancies_count }} {{ Str::plural('vacature', $company->vacancies_count) }}</p>
                                        @unless ($company->hasCompletePublicProfile())
                                            <p class="mt-1 text-sm text-amber-700">Uw bedrijfsprofiel kan nog worden aangevuld.</p>
                                        @endunless
                                    </div>
                                </div>
                                <a class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800 sm:mt-0" href="{{ route('account.companies.edit', $company) }}">Bedrijfsprofiel aanvullen →</a>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section aria-labelledby="vacancies-heading">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-xl font-bold text-slate-900" id="vacancies-heading">Recente vacatures</h2>
                        <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('account.vacancies') }}">Mijn vacatures →</a>
                    </div>
                    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        @forelse ($vacancies as $vacancy)
                            <article class="flex flex-col gap-3 border-b border-slate-100 p-5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-semibold text-slate-900">{{ $vacancy->title }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $vacancy->company->name }} · {{ $vacancy->status->getLabel() }}</p>
                                </div>
                                @if ($vacancy->status === \App\Enums\VacancyStatus::Draft)
                                    <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('vacancy-placement.edit', $vacancy) }}">Bewerken</a>
                                @elseif (in_array($vacancy->id, $publicVacancyIds, true))
                                    <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('vacancies.show', $vacancy) }}">Bekijk vacature</a>
                                @else
                                    <span class="text-sm text-slate-500">Alleen bekijken</span>
                                @endif
                            </article>
                        @empty
                            <p class="p-6 text-slate-600">U heeft nog geen vacatures geplaatst.</p>
                        @endforelse
                    </div>
                </section>
                @endif
            </div>

            <aside class="h-fit rounded-xl border border-slate-200 bg-slate-100 p-5">
                <h2 class="font-semibold text-slate-900">{{ $companies->isNotEmpty() ? 'Wat kunt u nu doen?' : 'Vacature plaatsen?' }}</h2>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    @if ($companies->isNotEmpty())
                        <li>Vul uw bedrijfsprofiel aan voor een sterke publieke presentatie.</li>
                        <li>Plaats een nieuwe vacature of werk een concept verder uit.</li>
                        <li>Ingediende vacatures blijven in beoordeling totdat een beheerder ze publiceert.</li>
                    @else
                        <li>Wilt u namens een bedrijf een vacature plaatsen? Start de plaatsingsflow en maak daar een werkgeversprofiel aan.</li>
                    @endif
                </ul>
            </aside>
        </div>
    </section>
@endsection

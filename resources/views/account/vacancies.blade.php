@extends('layouts.public')

@section('title', 'Mijn vacatures | Sales en Marketing Vacatures')
@section('canonical', route('account.vacancies'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-5xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('account.index') }}">← Mijn account</a>
                <h1 class="mt-3 font-playfair-display text-3xl font-bold text-slate-900">Mijn vacatures</h1>
                <p class="mt-3 text-slate-600">Concepten kunt u bewerken. Ingediende vacatures blijven alleen-lezen tijdens de beoordeling.</p>
            </div>
            <a class="btn justify-center bg-blue-600 text-white hover:bg-blue-700" href="{{ route('vacancy-placement.index') }}">Vacature plaatsen</a>
        </div>

        <div class="mt-8 space-y-3">
            @forelse ($vacancies as $vacancy)
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:flex sm:items-center sm:justify-between sm:gap-5">
                    <div>
                        <h2 class="font-semibold text-slate-900">{{ $vacancy->title }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $vacancy->company->name }} · {{ $vacancy->status->getLabel() }} · bijgewerkt {{ $vacancy->updated_at->translatedFormat('j F Y') }}</p>
                    </div>
                    <div class="mt-4 sm:mt-0">
                        @if ($vacancy->status === \App\Enums\VacancyStatus::Draft)
                            <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('vacancy-placement.edit', $vacancy) }}">Bewerken</a>
                        @elseif (in_array($vacancy->id, $publicVacancyIds, true))
                            <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('vacancies.show', $vacancy) }}">Bekijk vacature</a>
                        @else
                            <span class="text-sm text-slate-500">Geen bewerkactie beschikbaar</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-600">U heeft nog geen vacatures.</div>
            @endforelse
        </div>

        @if ($vacancies->hasPages())
            <div class="mt-8">{{ $vacancies->links() }}</div>
        @endif
    </section>
@endsection

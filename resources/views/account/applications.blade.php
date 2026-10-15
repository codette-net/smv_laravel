@extends('layouts.public')

@section('title', 'Mijn sollicitaties | Sales en Marketing Vacatures')
@section('canonical', route('account.applications'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-5xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Mijn account</p>
        <h1 class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">Mijn sollicitaties</h1>
        <p class="mt-3 max-w-2xl leading-7 text-slate-600">Hier ziet u sollicitaties die u rechtstreeks via SMV heeft ingediend.</p>

        <div class="mt-10 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            @forelse ($applications as $application)
                @php($isPublic = in_array($application->vacancy_id, $publicVacancyIds, true))
                <article class="flex flex-col gap-4 border-b border-slate-100 p-5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        @if ($isPublic && $application->vacancy)
                            <h2 class="font-semibold text-slate-900">
                                <a class="hover:text-blue-700" href="{{ route('vacancies.show', $application->vacancy) }}">{{ $application->vacancy->title }}</a>
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $application->vacancy->company->name }} · Sollicitatie van {{ $application->created_at->translatedFormat('j F Y') }}</p>
                        @else
                            <h2 class="font-semibold text-slate-900">Vacature niet meer beschikbaar</h2>
                            <p class="mt-1 text-sm text-slate-500">Sollicitatie van {{ $application->created_at->translatedFormat('j F Y') }}</p>
                        @endif
                    </div>

                    <div class="shrink-0">
                        <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</span>
                        <x-ui.badge :variant="$application->status->candidateBadgeVariant()">{{ $application->status->candidateLabel() }}</x-ui.badge>
                    </div>
                </article>
            @empty
                <div class="px-6 py-12 text-center">
                    <h2 class="text-lg font-bold text-slate-900">U heeft nog geen sollicitaties via SMV</h2>
                    <p class="mt-2 text-slate-600">Alleen sollicitaties die rechtstreeks via SMV zijn ingediend verschijnen hier.</p>
                    <a class="btn mt-6 inline-flex bg-blue-600 text-white hover:bg-blue-700" href="{{ route('vacancies.index') }}">Vacatures bekijken</a>
                </div>
            @endforelse
        </div>

        @if ($applications->hasPages())
            <div class="mt-8">{{ $applications->links() }}</div>
        @endif
    </section>
@endsection

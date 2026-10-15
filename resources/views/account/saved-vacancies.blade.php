@extends('layouts.public')

@section('title', 'Bewaarde vacatures | Sales en Marketing Vacatures')
@section('canonical', route('account.saved-vacancies'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-6xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('account.index') }}">← Mijn account</a>
        <h1 class="mt-4 font-playfair-display text-3xl font-bold text-slate-900">Bewaarde vacatures</h1>
        <p class="mt-3 max-w-2xl leading-7 text-slate-600">Bekijk vacatures die u wilt bewaren voor later.</p>

        @if (session('account_status'))
            <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900" role="status">{{ session('account_status') }}</div>
        @endif

        <div class="mt-8 grid gap-6 md:grid-cols-2">
            @forelse ($vacancies as $vacancy)
                @if (in_array($vacancy->id, $publicVacancyIds, true))
                    @php($vacancy->setAttribute('is_saved', true))
                    <x-vacancy.card :vacancy="$vacancy" :detail-url="route('vacancies.show', $vacancy)" />
                @else
                    <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="font-semibold text-slate-900">Bewaarde vacature</h2>
                        <p class="mt-2 text-sm text-slate-600">Niet meer beschikbaar</p>
                        <div class="mt-5">
                            <x-vacancy.save-button :vacancy="$vacancy" :saved="true" />
                        </div>
                    </article>
                @endif
            @empty
                <div class="rounded-xl border border-slate-200 bg-white p-8 md:col-span-2">
                    <h2 class="text-lg font-semibold text-slate-900">Nog geen vacatures bewaard</h2>
                    <p class="mt-2 text-slate-600">Bekijk het vacatureaanbod en bewaar interessante functies voor later.</p>
                    <a class="btn mt-5 bg-blue-600 text-white hover:bg-blue-700" href="{{ route('vacancies.index') }}">Bekijk vacatures</a>
                </div>
            @endforelse
        </div>

        @if ($vacancies->hasPages())
            <div class="mt-8">{{ $vacancies->links() }}</div>
        @endif
    </section>
@endsection

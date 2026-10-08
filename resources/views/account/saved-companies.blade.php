@extends('layouts.public')

@section('title', 'Bewaarde bedrijven | Sales en Marketing Vacatures')
@section('canonical', route('account.saved-companies'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-6xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('account.index') }}">← Mijn account</a>
        <h1 class="mt-4 font-playfair-display text-3xl font-bold text-slate-900">Bewaarde bedrijven</h1>
        <p class="mt-3 max-w-2xl leading-7 text-slate-600">Bewaar interessante werkgevers om hun profiel en vacatures later terug te vinden.</p>

        @if (session('account_status'))
            <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900" role="status">{{ session('account_status') }}</div>
        @endif

        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($companies as $company)
                @if (in_array($company->id, $publicCompanyIds, true))
                    @php($company->setAttribute('is_saved', true))
                    <x-company.card :company="$company" :logo-url="$company->publicLogoUrl()" />
                @else
                    <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="font-semibold text-slate-900">Bewaard bedrijf</h2>
                        <p class="mt-2 text-sm text-slate-600">Niet meer beschikbaar</p>
                        <div class="mt-5 w-fit">
                            <x-company.save-button :company="$company" :saved="true" />
                        </div>
                    </article>
                @endif
            @empty
                <div class="rounded-xl border border-slate-200 bg-white p-8 sm:col-span-2 lg:col-span-3">
                    <h2 class="text-lg font-semibold text-slate-900">Nog geen bedrijven bewaard</h2>
                    <p class="mt-2 text-slate-600">Bekijk de werkgevers op SMV en bewaar interessante bedrijfsprofielen.</p>
                    <a class="btn mt-5 bg-blue-600 text-white hover:bg-blue-700" href="{{ route('companies.index') }}">Bekijk bedrijven</a>
                </div>
            @endforelse
        </div>

        @if ($companies->hasPages())
            <div class="mt-8">{{ $companies->links() }}</div>
        @endif
    </section>
@endsection

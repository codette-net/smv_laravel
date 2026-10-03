@extends('layouts.public')

@section('title', 'Voorbeeld: ' . $vacancy->title . ' | Sales en Marketing Vacatures')
@section('canonical', route('vacancy-placement.index'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-5xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <x-vacancy-placement.steps :current="3" />

        <div class="mt-8 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900" role="status">
            <strong>Privévoorbeeld.</strong> Deze vacature is nog niet ingediend, gepubliceerd of vindbaar voor bezoekers.
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-9">
                <p class="text-sm font-semibold text-blue-700">{{ $vacancy->company->name }}</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $vacancy->title }}</h1>

                <div class="mt-5 flex flex-wrap gap-2">
                    @if ($vacancy->location)<x-ui.badge variant="dark">{{ $vacancy->location }}</x-ui.badge>@endif
                    @if ($vacancy->compensationLabel())<x-ui.badge variant="primary">{{ $vacancy->compensationLabel() }} per maand</x-ui.badge>@endif
                    @foreach ($vacancy->categories as $category)
                        <x-ui.badge size="xs" variant="info">{{ $category->name }}</x-ui.badge>
                    @endforeach
                </div>

                <hr class="my-7 border-slate-200">
                <section aria-labelledby="preview-description">
                    <h2 class="text-xl font-bold text-slate-900" id="preview-description">Over deze vacature</h2>
                    <div class="prose prose-slate mt-5 max-w-none leading-7">{!! $vacancy->description !!}</div>
                </section>
            </article>

            <aside class="h-fit space-y-5 lg:sticky lg:top-24">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-semibold text-slate-900">Controle</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-slate-500">Pakketintentie</dt><dd class="font-medium text-slate-900">{{ $package?->label() ?? 'Niet vastgelegd' }}</dd></div>
                        <div><dt class="text-slate-500">Status</dt><dd class="font-medium text-slate-900">Concept</dd></div>
                        <div><dt class="text-slate-500">Solliciteren</dt><dd class="font-medium text-slate-900">{{ $vacancy->application_mode->getLabel() }}</dd></div>
                    </dl>
                    <p class="mt-4 text-xs leading-5 text-slate-500">Indienen activeert geen betaling, publicatie of Superior-uitlichting.</p>
                </div>

                <a class="btn w-full justify-center border border-slate-300 bg-white text-slate-800 hover:bg-slate-50" href="{{ route('vacancy-placement.edit', $vacancy) }}">Gegevens bewerken</a>
                <form action="{{ route('vacancy-placement.submit', $vacancy) }}" method="POST">
                    @csrf
                    <x-ui.button class="w-full justify-center" type="submit" variant="brand">Indienen voor controle →</x-ui.button>
                </form>
            </aside>
        </div>
    </section>
@endsection

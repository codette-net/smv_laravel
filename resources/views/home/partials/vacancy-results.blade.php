<section class="min-w-0 lg:grow" aria-labelledby="recente-vacatures" data-vacancy-results data-result-count="{{ $vacancies->total() }}">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Actueel aanbod</p>
            <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900" id="recente-vacatures">{{ $hasFilters ? 'Gevonden vacatures' : 'Recente vacatures' }}</h2>
        </div>
        <a class="text-sm font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600"
           href="{{ route('vacancies.index') }}">Bekijk alle vacatures <span aria-hidden="true">→</span></a>
    </div>

    @if ($vacancies->isNotEmpty())
        <div class="mt-8 grid gap-4 grid-cols-[repeat(auto-fill,minmax(17.5rem,1fr))]">
            @foreach ($vacancies as $vacancy)
                <x-vacancy.card :vacancy="$vacancy" :detail-url="route('vacancies.show', $vacancy)"/>
            @endforeach
        </div>
        @if ($vacancies->hasPages())
            <div class="mt-10">{{ $vacancies->links('pagination::tailwind') }}</div>
        @endif
    @else
        <div class="mt-8 rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">
            Er zijn op dit moment geen actuele vacatures.
        </div>
    @endif
</section>

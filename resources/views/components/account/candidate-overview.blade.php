@props([
    'applicationsCount',
    'savedVacanciesCount',
    'savedCompaniesCount',
    'heading' => 'Mijn werkzoekende activiteiten',
    'headingId' => 'candidate-overview-heading',
])

<section aria-labelledby="{{ $headingId }}">
    <div>
        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Werkzoekende</p>
        <h2 class="mt-2 text-xl font-bold text-slate-900" id="{{ $headingId }}">{{ $heading }}</h2>
    </div>
    <div class="mt-4 grid gap-4 md:grid-cols-3">
        <a class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300" href="{{ route('account.applications') }}">
            <span class="text-2xl font-bold text-slate-900">{{ $applicationsCount }}</span>
            <span class="mt-2 block font-semibold text-slate-900">Mijn sollicitaties</span>
            <span class="mt-1 block text-sm text-slate-600">Bekijk uw rechtstreeks via SMV verstuurde sollicitaties.</span>
        </a>
        <a class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300" href="{{ route('account.saved-vacancies') }}">
            <span class="text-2xl font-bold text-slate-900">{{ $savedVacanciesCount }}</span>
            <span class="mt-2 block font-semibold text-slate-900">Bewaarde vacatures</span>
            <span class="mt-1 block text-sm text-slate-600">Ga verder met vacatures die u wilt terugbekijken.</span>
        </a>
        <a class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300" href="{{ route('account.saved-companies') }}">
            <span class="text-2xl font-bold text-slate-900">{{ $savedCompaniesCount }}</span>
            <span class="mt-2 block font-semibold text-slate-900">Bewaarde bedrijven</span>
            <span class="mt-1 block text-sm text-slate-600">Bekijk de werkgevers die u heeft bewaard.</span>
        </a>
    </div>
</section>

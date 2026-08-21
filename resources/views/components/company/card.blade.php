@props(['company', 'logoUrl' => null])

<article class="flex h-full flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-xs transition hover:border-blue-200 hover:shadow-sm">
    <div class="flex items-start gap-4">
        <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-blue-50 text-xl font-bold text-blue-700">
            @if ($logoUrl)
                <img class="h-full w-full object-cover" src="{{ $logoUrl }}" alt="Logo van {{ $company->name }}">
            @else
                {{ Str::upper(Str::substr($company->name, 0, 1)) }}
            @endif
        </div>
        <div class="min-w-0 grow">
            @if ($company->is_featured)
                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Uitgelicht</span>
            @endif
            <h2 class="mt-2 text-lg font-bold text-slate-900">
                <a class="transition hover:text-blue-600 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('bedrijven.show', $company) }}">
                    {{ $company->name }}
                </a>
            </h2>
        </div>
    </div>

    @if ($company->tagline)
        <p class="mt-5 leading-6 text-slate-600">{{ $company->tagline }}</p>
    @endif

    @if ($company->location || $company->public_vacancies_count !== null)
        <div class="mt-5 flex flex-wrap gap-x-4 gap-y-2 text-sm text-slate-500">
            @if ($company->location)
                <span>{{ $company->location }}</span>
            @endif
            @if ($company->public_vacancies_count !== null)
                <span>{{ $company->public_vacancies_count }} {{ Str::plural('vacature', $company->public_vacancies_count) }}</span>
            @endif
        </div>
    @endif

    <a class="mt-6 inline-flex w-fit font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('bedrijven.show', $company) }}">
        Bekijk bedrijf
    </a>
</article>

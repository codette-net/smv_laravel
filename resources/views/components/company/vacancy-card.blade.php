@props(['vacancy', 'logoUrl' => null])

<article class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-xs transition hover:border-blue-200 hover:shadow-sm">
    <div class="flex items-start gap-4">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-50 text-sm font-bold text-blue-700">
            @if ($logoUrl)
                <img class="h-full w-full object-cover" src="{{ $logoUrl }}" alt="Logo van {{ $vacancy->company->name }}">
            @else
                {{ Str::upper(Str::substr($vacancy->company->name, 0, 1)) }}
            @endif
        </div>
        <div class="min-w-0 grow">
            <h3 class="text-base font-semibold text-slate-900">
                <a class="transition hover:text-blue-600 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('vacancies.show', $vacancy) }}">
                    {{ $vacancy->title }}
                </a>
            </h3>
            @if ($vacancy->location)
                <p class="mt-1 text-sm text-slate-500">{{ $vacancy->location }}</p>
            @endif
        </div>
        <a class="hidden shrink-0 rounded-lg border border-blue-600 px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-600 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600 sm:inline-flex" href="{{ route('vacancies.show', $vacancy) }}">
            Bekijk vacature
        </a>
    </div>
</article>

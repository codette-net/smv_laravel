@props(['company', 'logoUrl' => null, 'showVacancyTitles' => false])

@php
    $coverUrl = $company->is_featured ? $company->publicCoverUrl() : null;
    $introduction = $company->publicIntroduction();
    $vacancyCount = (int) ($company->public_vacancies_count ?? 0);
    $vacancyPreviews = $company->relationLoaded('publicVacanciesPreview') ? $company->publicVacanciesPreview : collect();
    $remainingVacancyCount = max(0, $vacancyCount - $vacancyPreviews->count());
    $fallbackLetter = Str::upper(Str::substr(trim($company->name), 0, 1));
@endphp

<article class="group relative flex h-full flex-col rounded-xl border border-slate-200 bg-white/95 shadow-sm backdrop-blur-sm transition duration-200 hover:border-blue-300 hover:shadow-md" data-demo="company-card">
    @if ($coverUrl)
        <img class="h-24 w-full rounded-t-xl object-cover" src="{{ $coverUrl }}" alt="" loading="lazy">
    @endif

    <div class="absolute right-4 top-4 z-30">
        <x-company.save-button :company="$company" />
    </div>

    <div class="flex grow flex-col gap-4 p-5 pr-14 sm:flex-row sm:items-start">
        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-2 text-2xl font-bold text-blue-700 shadow-sm">
            @if ($logoUrl)
                <img class="h-full w-full object-contain" src="{{ $logoUrl }}" alt="Logo van {{ $company->name }}" loading="lazy">
            @else
                <span aria-hidden="true">{{ $fallbackLetter }}</span>
            @endif
        </div>

        <div class="min-w-0 grow">
            @if ($company->is_featured)
                <x-ui.badge class="mb-2 w-fit" variant="info">Uitgelicht</x-ui.badge>
            @endif

            <h2 class="text-lg font-bold text-slate-900">
                <a class="before:absolute before:inset-0 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('bedrijven.show', $company) }}">
                    {{ $company->name }}
                </a>
            </h2>

            @if ($company->categories->first() || $company->location)
                <p class="mt-1 text-sm font-medium text-slate-500">
                    {{ collect([$company->categories->first()?->name, $company->location])->filter()->join(' · ') }}
                </p>
            @endif

            @if ($introduction)
                <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">{{ $introduction }}</p>
            @endif

            @if ($company->categories->isNotEmpty())
                <div class="mt-4 flex flex-wrap items-center gap-2">
                @foreach ($company->categories->take(2) as $category)
                    <x-ui.badge size="xs">{{ $category->name }}</x-ui.badge>
                @endforeach
                </div>
            @endif

            @if ($showVacancyTitles && $vacancyPreviews->isNotEmpty())
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Open vacatures</p>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($vacancyPreviews as $vacancy)
                            <a class="relative z-20 max-w-full truncate rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" href="{{ route('vacancies.show', $vacancy) }}" title="{{ $vacancy->title }}">
                                {{ $vacancy->title }}
                            </a>
                        @endforeach

                        @if ($remainingVacancyCount > 0)
                            <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white" aria-label="Nog {{ $remainingVacancyCount }} {{ Str::plural('vacature', $remainingVacancyCount) }} bij {{ $company->name }}">
                                +{{ $remainingVacancyCount }}
                            </span>
                        @endif
                    </div>
                </div>
            @elseif (! $showVacancyTitles)
                <p class="mt-4 text-sm font-semibold text-blue-700">
                    {{ $vacancyCount }} open {{ Str::plural('vacature', $vacancyCount) }}
                </p>
            @endif
        </div>
    </div>
</article>

@props([
    'vacancy',
    'detailUrl' => null,
])

@php
    /*
     * Temporary fallback until listing_tier exists.
     *
     * standard | featured | premium
     */
    $tier = $vacancy->listing_tier
        ?? ($vacancy->is_featured ? 'featured' : 'standard');

    $isFeatured = $tier === 'featured';
    $isPremium = $tier === 'premium';


    $imageUrl = $vacancy->company->publicCoverUrl() ?? $vacancy->company->publicLogoUrl();

    $salary = null;

    if ($vacancy->salary_min && $vacancy->salary_max) {
        $salary = '€' . number_format($vacancy->salary_min, 0, ',', '.')
            . ' – €' . number_format($vacancy->salary_max, 0, ',', '.');
    }
@endphp

<article
    @class([
        'group relative flex h-full overflow-hidden rounded-2xl border transition-all duration-200',

        // Standard + featured = vertical card
        'flex-col' => !$isPremium,

        // Premium = wide banner on desktop
        'flex-col md:grid md:grid-cols-[minmax(220px,0.8fr)_minmax(0,1.6fr)] lg:col-span-2'
            => $isPremium,

        // Standard
        'border-gray-200 bg-white shadow-sm hover:-translate-y-1 hover:shadow-md'
            => $tier === 'standard',

        // Featured
        'border-indigo-200 bg-gradient-to-b from-indigo-50/70 via-white to-white shadow-md hover:-translate-y-1 hover:shadow-xl'
            => $isFeatured,

        // Premium
        'border-indigo-200 bg-gradient-to-br from-indigo-50/80 via-white to-violet-50/70 shadow-lg hover:shadow-xl'
            => $isPremium,
    ])
>

    {{-- Badge --}}
    @if ($isFeatured || $isPremium)
        <div class="absolute left-4 top-4 z-20">
            <span
                @class([
                    'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold shadow-sm ring-1 ring-inset',
                    'bg-white/95 text-indigo-600 ring-indigo-100'
                        => $isFeatured,
                    'bg-amber-50/95 text-amber-700 ring-amber-200'
                        => $isPremium,
                ])
            >
                @if ($isPremium)
                    <span aria-hidden="true">★</span>
                    Premium vacature
                @else
                    <span aria-hidden="true">✦</span>
                    Uitgelicht
                @endif
            </span>
        </div>
    @endif


    {{-- Bookmark --}}
    <button
        type="button"
        class="absolute right-4 top-4 z-20 flex size-9 items-center justify-center rounded-full bg-white/95 text-gray-700 shadow-sm ring-1 ring-gray-200 transition hover:text-indigo-600 hover:shadow-md"
        aria-label="Vacature opslaan"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M17 3a2 2 0 0 1 2 2v15a1 1 0 0 1-1.496.868l-4.512-2.578a2 2 0 0 0-1.984 0l-4.512 2.578A1 1 0 0 1 5 20V5a2 2 0 0 1 2-2z"/>
        </svg>
    </button>


    {{-- Media --}}
    <a
        href="{{ $detailUrl ?? '#' }}"
        @class([
            'relative flex items-center justify-center overflow-hidden bg-gray-50',
            'aspect-[4/3] w-full' => !$isPremium,
            'min-h-64 md:h-full' => $isPremium,
        ])
    >
        @if ($imageUrl)

            <img
                src="{{ $imageUrl }}"
                alt="Logo van {{ $vacancy->company->name }}"
                @class([
                    'h-full w-full object-contain',
                    'p-7' => !$isPremium,
                    'p-8 lg:p-10' => $isPremium,
                ])
                loading="lazy"
            >

        @else

            <span
                @class([
                    'font-bold text-gray-300',
                    'text-6xl' => !$isPremium,
                    'text-7xl' => $isPremium,
                ])
            >
                {{ Str::upper(Str::substr($vacancy->company->name, 0, 1)) }}
            </span>

        @endif
    </a>


    {{-- Content --}}
    <div
        @class([
            'flex min-w-0 grow flex-col',
            'p-5' => !$isPremium,
            'p-6 md:p-7' => $isPremium,
        ])
    >

        {{-- Main content --}}
        <div class="grow">

            {{-- Company --}}
            <a
                href="{{ route('bedrijven.show', $vacancy->company) }}"
                class="text-sm font-medium text-gray-600 transition hover:text-indigo-600"
            >
                {{ $vacancy->company->name }}
            </a>


            {{-- Title --}}
            <h2
                @class([
                    'mt-1 font-bold leading-tight tracking-tight text-gray-900',
                    'text-xl' => !$isPremium,
                    'text-2xl lg:text-3xl' => $isPremium,
                ])
            >
                <a
                    href="{{ $detailUrl ?? '#' }}"
                    class="transition hover:text-indigo-600"
                >
                    {{ $vacancy->title }}
                </a>
            </h2>


            {{-- Salary --}}
            @if ($salary)
                <div class="mt-4">
                    <span class="inline-flex rounded-lg bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-600">
                        {{ $salary }}
                    </span>
                </div>
            @endif


            {{-- Metadata --}}
            <div class="mt-4 flex flex-wrap gap-2">

                @if ($vacancy->location)
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-sm text-gray-600">
                        {{ $vacancy->location }}
                    </span>
                @endif

                @foreach ($vacancy->categories->take($isPremium ? 4 : 3) as $category)
                    <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 text-sm text-gray-600">
                        {{ $category->name }}
                    </span>
                @endforeach

            </div>


            {{-- Premium-only content --}}
            @if ($isPremium)

                @if ($vacancy->excerpt)
                    <p class="mt-5 max-w-2xl text-sm leading-6 text-gray-600 lg:text-base">
                        {{ Str::limit($vacancy->excerpt, 180) }}
                    </p>
                @endif

                {{-- This can later contain actual package benefits / vacancy highlights --}}
                <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">

                    <div class="rounded-xl bg-white/70 p-3 ring-1 ring-gray-100">
                        <div class="text-sm font-semibold text-gray-800">
                            Direct solliciteren
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                            Bekijk de volledige functie
                        </div>
                    </div>

                    @if ($vacancy->location)
                        <div class="rounded-xl bg-white/70 p-3 ring-1 ring-gray-100">
                            <div class="text-sm font-semibold text-gray-800">
                                {{ $vacancy->location }}
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                Werklocatie
                            </div>
                        </div>
                    @endif

                    @if ($vacancy->deadline_at)
                        <div class="rounded-xl bg-white/70 p-3 ring-1 ring-gray-100">
                            <div class="text-sm font-semibold text-gray-800">
                                {{ $vacancy->deadline_at->translatedFormat('j F') }}
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                Sollicitatiedeadline
                            </div>
                        </div>
                    @endif

                </div>

            @endif

        </div>


        {{-- Footer --}}
        <div
            @class([
                'mt-6 border-t border-gray-100 pt-4',
                'flex flex-col gap-4' => !$isPremium,
                'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between'
                    => $isPremium,
            ])
        >

            <div class="space-y-1 text-xs text-gray-500">

                @if ($vacancy->published_at)
                    <p>
                        Geplaatst
                        <span class="font-medium text-gray-700">
                            {{ $vacancy->published_at->diffForHumans() }}
                        </span>
                    </p>
                @endif

                @if ($vacancy->deadline_at)
                    <p>
                        Deadline
                        <span class="font-medium text-gray-700">
                            {{ $vacancy->deadline_at->translatedFormat('j F Y') }}
                        </span>
                    </p>
                @endif

            </div>


            @if ($detailUrl)
                <a
                    href="{{ $detailUrl }}"
                    @class([
                        'inline-flex items-center justify-center rounded-lg bg-indigo-500 font-semibold text-white shadow-sm transition hover:bg-indigo-600',
                        'w-full px-4 py-2.5 text-sm' => !$isPremium,
                        'px-5 py-2.5 text-sm' => $isPremium,
                    ])
                >
                    Bekijk vacature

                    <span class="ml-2 transition-transform duration-150 group-hover:translate-x-1">
                        →
                    </span>
                </a>
            @endif

        </div>

    </div>
</article>

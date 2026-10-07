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
        'group relative flex h-full overflow-hidden rounded-2xl border transition-all duration-200
        bg-white/20 p-2 shadow-lg shadow-blue-700/50 transition hover:bg-white/90
        before:pointer-events-none before:absolute before:inset-0 before:-z-10
        before:rounded-[inherit] before:border before:border-transparent
        before:[background:linear-gradient(var(--color-gray-100),var(--color-gray-200))_border-box]
         before:[mask:linear-gradient(white_0_0)_padding-box,linear-gradient(white_0_0)]
          before:[mask-composite:exclude_!important]',

        // Standard + featured = vertical card
        'flex-col' => !$isPremium,

        // Premium = wide banner on desktop
        'flex-col md:grid md:grid-cols-[minmax(220px,0.8fr)_minmax(0,1.6fr)] lg:col-span-2'
            => $isPremium,

        // Standard
        'border-gray-200 bg-white shadow-sm hover:shadow-md'
            => $tier === 'standard',

        // Featured
        'border-blue-200 bg-gradient-to-b from-blue-50/70 via-white to-white shadow-md hover:shadow-xl'
            => $isFeatured,

        // Premium
        'border-blue-200 bg-gradient-to-br from-blue-50/80 via-white to-blue-100/60 shadow-lg hover:shadow-xl'
            => $isPremium,
    ])
>
    <div class="absolute right-4 top-4 z-30">
        <x-vacancy.save-button :vacancy="$vacancy" icon-only />
    </div>

    {{-- Badge --}}
    @if ($isFeatured || $isPremium)
        <div class="absolute left-4 top-4 z-20">
            @if ($isPremium)
                <x-ui.badge icon="bolt" variant="dark">Premium vacature</x-ui.badge>
            @else
                <x-ui.badge variant="primary">Uitgelicht</x-ui.badge>
            @endif
        </div>
    @endif
    {{-- Media --}}
    <a
        href="{{ $detailUrl ?? '#' }}"
        @class([
            'relative flex items-center justify-center overflow-hidden bg-gray-50 rounded-t-lg',
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
            'p-4' => !$isPremium,
            'p-5 md:p-6' => $isPremium,
        ])
    >

        {{-- Main content --}}
        <div class="grow">

            {{-- Company --}}
            <a
                href="{{ route('bedrijven.show', $vacancy->company) }}"
                class="text-sm font-medium text-gray-600 transition hover:text-blue-700 text-balance mb-2"
            >
                {{ $vacancy->company->name }}
            </a>


            {{-- Title --}}
            <h2
                @class([
                    'my-2 font-bold leading-tight tracking-tight text-gray-900 text-balance',
                    'text-xl' => !$isPremium,
                    'text-2xl lg:text-3xl' => $isPremium,
                ])
            >
                <a
                    href="{{ $detailUrl ?? '#' }}"
                    class="transition hover:text-blue-700"
                >
                    {{ $vacancy->title }}
                </a>
            </h2>

            @if ($vacancy->location)
                <x-ui.badge variant="dark">{{ $vacancy->location }}</x-ui.badge>
            @endif


            {{-- Salary --}}
            @if ($salary)
                <div class="mt-4">
                    <x-ui.badge variant="primary">{{ $salary }}</x-ui.badge>
                </div>
            @endif


            {{-- Metadata --}}
            <div class="mt-4 flex flex-wrap gap-2">


                @foreach ($vacancy->categories->take($isPremium ? 4 : 2) as $category)
                    <x-ui.badge size="xs" variant="info">{{ $category->name }}</x-ui.badge>
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


            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @if ($detailUrl)
                    <a
                        href="{{ $detailUrl }}"
                        @class([
                            'inline-flex items-center justify-center rounded-lg bg-blue-600 font-semibold text-white shadow-sm transition hover:bg-blue-700',
                            'w-full px-4 py-2.5 text-sm' => !$isPremium,
                            'px-5 py-2.5 text-sm' => $isPremium,
                        ])
                    >
                        Bekijk vacature

                        <span class="ml-2 transition-transform duration-150 group-hover:translate-x-1">→</span>
                    </a>
                @endif
            </div>

        </div>

    </div>
</article>

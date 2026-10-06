@props([
    'vacancy',
    'logoUrl' => null,
    'detailUrl' => null,
])

@php
    $tier = $vacancy->listing_tier
        ?? ($vacancy->is_featured ? 'featured' : 'standard');

    $isFeatured = $tier === 'featured';
    $isPremium = $tier === 'premium';

    $detailUrl ??= route('vacancies.show', $vacancy);

    $salary = null;

    if ($vacancy->salary_min && $vacancy->salary_max) {
        $salary = '€' . number_format($vacancy->salary_min, 0, ',', '.')
            . ' – €' . number_format($vacancy->salary_max, 0, ',', '.');
    }

    $isNew = $vacancy->published_at
        && $vacancy->published_at->gte(now()->subDays(7));
@endphp

<article
    @class([
        'relative rounded-xl border px-5 py-4 transition duration-150 ease-in-out',

        // Standard
        'border-transparent bg-white shadow-xs hover:shadow-md'
            => $tier === 'standard',

        // Featured
        'border-indigo-200 bg-indigo-50/60 shadow-md hover:shadow-lg'
            => $isFeatured,

        // Premium
        'border-indigo-300 bg-gradient-to-r from-indigo-50 via-white to-violet-50 shadow-lg ring-1 ring-indigo-100 hover:shadow-xl'
            => $isPremium,
    ])
>
    <div class="absolute right-3 top-3 z-20">
        <x-vacancy.save-button :vacancy="$vacancy" icon-only />
    </div>

    {{-- Premium accent --}}
    @if ($isPremium)
        <div class="absolute inset-y-3 left-0 w-1 rounded-r-full bg-indigo-500"></div>
    @endif


    <div class="space-y-4 md:flex md:items-center md:justify-between md:space-x-2 md:space-y-0">

        {{-- Left side --}}
        <div class="flex min-w-0 items-start space-x-3 md:space-x-4">

            {{-- Logo --}}
            <div
                @class([
                    'mt-1 flex shrink-0 items-center justify-center overflow-hidden bg-white',
                    'h-9 w-9 rounded-full' => !$isPremium,
                    'h-11 w-11 rounded-lg shadow-xs' => $isPremium,
                ])
            >

                @if ($logoUrl)

                    <img
                        class="h-full w-full object-contain p-0.5"
                        src="{{ $logoUrl }}"
                        alt=""
                    >

                @else

                    <span class="text-sm font-bold text-gray-300">
                        {{ Str::upper(Str::substr($vacancy->company->name, 0, 1)) }}
                    </span>

                @endif

            </div>


            <div class="min-w-0">

                {{-- Tier --}}
                @if ($isFeatured || $isPremium)

                    <div class="mb-1">

                        @if ($isPremium)
                            <x-ui.badge icon="bolt" size="xs" variant="dark">Premium vacature</x-ui.badge>

                        @else
                            <x-ui.badge size="xs" variant="primary">Uitgelicht</x-ui.badge>

                        @endif

                    </div>

                @endif


                {{-- Title --}}
                <a
                    class="inline-flex font-semibold text-gray-800 transition hover:text-indigo-600"
                    href="{{ $detailUrl }}"
                >
                    {{ $vacancy->title }}
                </a>


                {{-- Meta --}}
                <div class="mt-1 flex flex-wrap items-center gap-x-1 text-sm text-gray-500">

                    @foreach ($vacancy->categories->take(2) as $category)

                        @if (!$loop->first)
                            <span>/</span>
                        @endif

                        <span>{{ $category->name }}</span>

                    @endforeach


                    @if ($vacancy->location)

                        @if ($vacancy->categories->isNotEmpty())
                            <span>/</span>
                        @endif

                        <span>{{ $vacancy->location }}</span>

                    @endif

                </div>


                {{-- Premium extra information --}}
                @if ($isPremium)

                    <div class="mt-2 flex flex-wrap gap-2">

                        @if ($salary)
                            <span class="rounded-md bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                {{ $salary }}
                            </span>
                        @endif

                        @foreach ($vacancy->categories->slice(2, 2) as $category)
                            <span class="rounded-md bg-white px-2 py-0.5 text-xs font-medium text-gray-500 shadow-xs">
                                {{ $category->name }}
                            </span>
                        @endforeach

                    </div>

                @endif

            </div>

        </div>


        {{-- Right side --}}
        <div class="flex items-center space-x-4 pl-12 md:pl-0">

            {{-- Published --}}
            @if ($vacancy->published_at)
                <div class="whitespace-nowrap text-sm italic text-gray-500">
                    {{ $vacancy->published_at->translatedFormat('j M') }}
                </div>
            @endif


            {{-- New --}}
            @if ($isNew)
                <x-ui.badge size="xs" variant="success">Nieuw</x-ui.badge>
            @endif


            {{-- Featured --}}
            @if ($isFeatured && !$isNew)
                <x-ui.badge size="xs" variant="primary">Uitgelicht</x-ui.badge>
            @endif


            {{-- Premium --}}
            @if ($isPremium)
                <a
                    href="{{ $detailUrl }}"
                    class="hidden whitespace-nowrap rounded-lg bg-indigo-500 px-3 py-1.5 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-600 sm:inline-flex"
                >
                    Bekijk →
                </a>
            @endif


        </div>

    </div>


    {{-- Premium excerpt --}}
    @if ($isPremium && $vacancy->excerpt)

        <p class="mt-3 border-t border-indigo-100 pt-3 pl-14 text-sm leading-5 text-gray-600">
            {{ Str::limit($vacancy->excerpt, 140) }}
        </p>

    @endif

</article>

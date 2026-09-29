@props(['filters', 'sort', 'sortOptions', 'locations', 'taxonomyOptions', 'companies', 'hasFilters' => false, 'hasAdditionalFilters' => false])

<form action="{{ route('home') }}" class="grid grid-cols-1 gap-6" method="GET" x-data="{ filtersOpen: @js($hasAdditionalFilters), loading: false }" x-on:submit="loading = true">
    <div class="col-span-full">
        <label class="mb-3 block text-sm font-semibold text-gray-800" for="zoek">Zoeken</label>
        <div class="relative">
            <input class="form-input w-full py-2.5 pl-3 pr-10 text-sm" data-last-submitted-value="{{ $filters['zoek'] }}" id="zoek" name="zoek" type="search" value="{{ $filters['zoek'] }}" placeholder="Functie of bedrijf" x-on:input.debounce.500ms="if ($el.value !== $el.dataset.lastSubmittedValue) { loading = true; $el.dataset.lastSubmittedValue = $el.value; $el.form.requestSubmit() }">
            <button class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 transition hover:text-indigo-500" type="submit">
                <span class="sr-only">Zoeken</span>
                <svg aria-hidden="true" class="h-5 w-5 fill-current" viewBox="0 0 20 20"><path d="M8.5 3a5.5 5.5 0 1 0 3.44 9.79l4.13 4.12 1.06-1.06-4.12-4.13A5.5 5.5 0 0 0 8.5 3Zm0 1.5a4 4 0 1 1 0 8 4 4 0 0 1 0-8Z" /></svg>
            </button>
        </div>
        <p class="mt-2 flex items-center gap-2 text-sm text-gray-500" aria-live="polite" x-cloak x-show="loading"><svg aria-hidden="true" class="size-3.5 animate-spin fill-current" viewBox="0 0 16 16"><path d="M8 16a7.928 7.928 0 0 1-3.428-.77l.857-1.807A6.006 6.006 0 0 0 14 8c0-3.309-2.691-6-6-6a6.006 6.006 0 0 0-5.422 8.572l-1.806.859A7.929 7.929 0 0 1 0 8c0-4.411 3.589-8 8-8s8 3.589 8 8-3.589 8-8 8z" /></svg>Vacatures bijwerken…</p>
    </div>

    <div class="col-span-full flex items-center gap-4">
        <button class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-500 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500" type="button" aria-controls="homepage-aanvullende-filters" :aria-expanded="filtersOpen" x-on:click="filtersOpen = ! filtersOpen">
            <span>Meer filters</span>
            <svg aria-hidden="true" class="h-4 w-4 transition-transform" :class="{ 'rotate-180': filtersOpen }" viewBox="0 0 16 16"><path d="m4 6 4 4 4-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" /></svg>
        </button>

        @if ($hasFilters)
            <a class="inline-flex text-sm font-semibold text-indigo-500 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500" href="{{ route('home') }}">Wis filters</a>
        @endif
    </div>

    <div class="col-span-full grid grid-cols-1 gap-6" id="homepage-aanvullende-filters" x-cloak x-show="filtersOpen" x-transition.opacity>
        <div>
            <label class="mb-3 block text-sm font-semibold text-gray-800" for="locatie">Locatie</label>
            <select class="form-select w-full text-sm" id="locatie" name="locatie" x-on:change="loading = true; $el.form.requestSubmit()">
                <option value="">Alle locaties</option>
                @foreach ($locations as $location)
                    <option value="{{ $location }}" @selected($filters['locatie'] === $location)>{{ $location }}</option>
                @endforeach
            </select>
        </div>

        @foreach (['dienstverband' => 'Dienstverband', 'werklocatie' => 'Werklocatie', 'sector' => 'Sector', 'functiegebied' => 'Functiegebied', 'ervaring' => 'Ervaring'] as $filter => $label)
            <div>
                <label class="mb-3 block text-sm font-semibold text-gray-800" for="{{ $filter }}">{{ $label }}</label>
                <select class="form-select w-full text-sm" id="{{ $filter }}" name="{{ $filter }}" x-on:change="loading = true; $el.form.requestSubmit()">
                    <option value="">Alle opties</option>
                    @foreach ($taxonomyOptions[$filter] as $category)
                        <option value="{{ $category->slug }}" @selected($filters[$filter] === $category->slug)>{{ $category->parent ? $category->parent->name.' — '.$category->name : $category->name }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach

        <div>
            <label class="mb-3 block text-sm font-semibold text-gray-800" for="bedrijf">Bedrijf</label>
            <select class="form-select w-full text-sm" id="bedrijf" name="bedrijf" x-on:change="loading = true; $el.form.requestSubmit()">
                <option value="">Alle bedrijven</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->slug }}" @selected($filters['bedrijf'] === $company->slug)>{{ $company->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-3 block text-sm font-semibold text-gray-800" for="sort">Sorteren</label>
            <select class="form-select w-full text-sm" id="sort" name="sort" x-on:change="loading = true; $el.form.requestSubmit()">
                @foreach ($sortOptions as $value => $label)
                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <button class="btn col-span-full justify-center bg-indigo-500 text-white hover:bg-indigo-600" type="submit">Vacatures tonen</button>
    </div>
</form>

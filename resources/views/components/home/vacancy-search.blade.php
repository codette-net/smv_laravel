@props(['filters', 'sort', 'sortOptions', 'locations', 'taxonomyOptions', 'companies', 'hasFilters' => false, 'hasAdditionalFilters' => false])

<form action="{{ route('home') }}" class="grid grid-cols-1 gap-6" method="GET" x-data="{ filtersOpen: @js($hasAdditionalFilters), loading: false }" x-on:pageshow.window="loading = false" x-on:submit="loading = true">
    <div class="col-span-full">
        <label class="block text-sm font-medium mb-1" for="zoek">Zoeken</label>
        <div class="relative">
            <input class="form-input w-full pl-9" data-last-submitted-value="{{ $filters['zoek'] }}" id="zoek" name="zoek" type="search" value="{{ $filters['zoek'] }}" placeholder="Functie of bedrijf" x-on:input="loading = true" x-on:input.debounce.500ms="if ($el.value !== $el.dataset.lastSubmittedValue) { $el.dataset.lastSubmittedValue = $el.value; $el.form.requestSubmit() } else { loading = false }">
            <button class="absolute inset-0 right-auto group" type="submit" aria-label="Zoeken">
                <span class="sr-only">Zoeken</span>
                <svg class="shrink-0 fill-current text-gray-400 group-hover:text-gray-500 ml-3 mr-2" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M7 14c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7Zm0-12C4.243 2 2 4.243 2 7s2.243 5 5 5 5-2.243 5-5-2.243-5-5-5Z" /><path d="m15.707 14.293-2.393-2.393a8.019 8.019 0 0 1-1.414 1.414l2.393 2.393a.997.997 0 0 0 1.414 0 .999.999 0 0 0 0-1.414Z" /></svg>
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
        <x-ui.select-dropdown name="locatie" label="Locatie" :options="collect($locations)->mapWithKeys(fn ($location) => [$location => $location])" :value="$filters['locatie']" placeholder="Alle locaties" x-on:change="loading = true; $el.form.requestSubmit()" />

        @foreach (['dienstverband' => 'Dienstverband', 'werklocatie' => 'Werklocatie', 'sector' => 'Sector', 'functiegebied' => 'Functiegebied', 'ervaring' => 'Ervaring'] as $filter => $label)
            <x-ui.select-dropdown
                :name="$filter"
                :label="$label"
                :options="collect($taxonomyOptions[$filter])->mapWithKeys(fn ($category) => [$category->slug => $category->parent ? $category->parent->name.' — '.$category->name : $category->name])"
                :value="$filters[$filter]"
                placeholder="Alle opties"
                x-on:change="loading = true; $el.form.requestSubmit()"
            />
        @endforeach

        <x-ui.select-dropdown name="bedrijf" label="Bedrijf" :options="collect($companies)->mapWithKeys(fn ($company) => [$company->slug => $company->name])" :value="$filters['bedrijf']" placeholder="Alle bedrijven" x-on:change="loading = true; $el.form.requestSubmit()" />

        <x-ui.select-dropdown name="sort" label="Sorteren" :options="$sortOptions" :value="$sort" placeholder="Sorteren" x-on:change="loading = true; $el.form.requestSubmit()" />

        <x-ui.button class="col-span-full justify-center" type="submit">Vacatures tonen</x-ui.button>
    </div>
</form>

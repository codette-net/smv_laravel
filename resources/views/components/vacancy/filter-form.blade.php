@props([
    'filters', 'sort', 'sortOptions', 'locations', 'taxonomyOptions', 'companies',
    'action' => null, 'autoSubmit' => true, 'hasFilters' => false,
    'hasAdditionalFilters' => false, 'secondaryFilterCount' => 0, 'filterErrors' => [],
])

@php($formAction = $action ?? route('vacancies.index'))

<form action="{{ $formAction }}" class="grid grid-cols-1 gap-5" method="GET"
      x-data="{ filtersOpen: @js($hasAdditionalFilters), loading: false }"
      x-on:pageshow.window="loading = false" x-on:submit="loading = true">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium" for="zoek">Zoeken</label>
            <div class="relative">
                <input class="form-input w-full pl-9" data-last-submitted-value="{{ $filters['zoek'] }}" id="zoek" name="zoek" type="search" value="{{ $filters['zoek'] }}" placeholder="Functie of bedrijf"
                       @if ($autoSubmit) x-on:input="loading = true" x-on:input.debounce.500ms="if ($el.value !== $el.dataset.lastSubmittedValue) { $el.dataset.lastSubmittedValue = $el.value; $el.form.requestSubmit() } else { loading = false }" @endif>
                <button class="absolute inset-0 right-auto group" type="submit" aria-label="Zoeken">
                    <span class="sr-only">Zoeken</span>
                    <svg class="ml-3 mr-2 shrink-0 fill-current text-gray-400 group-hover:text-gray-500" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M7 14c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7Zm0-12C4.243 2 2 4.243 2 7s2.243 5 5 5 5-2.243 5-5-2.243-5-5-5Z" /><path d="m15.707 14.293-2.393-2.393a8.019 8.019 0 0 1-1.414 1.414l2.393 2.393a.997.997 0 0 0 1.414 0 .999.999 0 0 0 0-1.414Z" /></svg>
                </button>
            </div>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="locatie">Plaats</label>
            <select class="form-select w-full" id="locatie" name="locatie" @if ($autoSubmit) x-on:change="loading = true; $el.form.requestSubmit()" @endif>
                <option value="">Alle plaatsen</option>
                @foreach ($locations as $location)<option value="{{ $location }}" @selected($filters['locatie'] === $location)>{{ $location }}</option>@endforeach
            </select>
        </div>
    </div>

    <p class="flex items-center gap-2 text-sm text-gray-500" aria-live="polite" x-cloak x-show="loading">
        <svg aria-hidden="true" class="size-3.5 animate-spin fill-current" viewBox="0 0 16 16"><path d="M8 16a7.928 7.928 0 0 1-3.428-.77l.857-1.807A6.006 6.006 0 0 0 14 8c0-3.309-2.691-6-6-6a6.006 6.006 0 0 0-5.422 8.572l-1.806.859A7.929 7.929 0 0 1 0 8c0-4.411 3.589-8 8-8s8 3.589 8 8-3.589 8-8 8z" /></svg>
        Vacatures bijwerken…
    </p>

    <div class="flex items-start gap-4">
        <details class="min-w-0 grow" {{ $hasAdditionalFilters ? 'open' : '' }} x-bind:open="filtersOpen" x-on:toggle="filtersOpen = $el.open">
            <summary class="inline-flex cursor-pointer list-none items-center gap-2 text-sm font-semibold text-indigo-600 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500" role="button" aria-controls="aanvullende-filters" x-bind:aria-expanded="filtersOpen.toString()" x-on:keydown.enter.prevent="filtersOpen = ! filtersOpen" x-on:keydown.space.prevent="filtersOpen = ! filtersOpen">
                <span>Meer filters</span>
                @if ($secondaryFilterCount > 0)<span class="inline-flex min-w-5 items-center justify-center rounded-full bg-indigo-100 px-1.5 py-0.5 text-xs text-indigo-700" aria-label="{{ $secondaryFilterCount }} actieve aanvullende filtergroepen">{{ $secondaryFilterCount }}</span>@endif
                <svg aria-hidden="true" class="h-4 w-4 transition-transform" x-bind:class="{ 'rotate-180': filtersOpen }" viewBox="0 0 16 16"><path d="m4 6 4 4 4-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" /></svg>
            </summary>

            <input type="hidden" name="meer_filters" value="1" x-bind:disabled="! filtersOpen" @disabled(! $hasAdditionalFilters)>

            <div class="mt-5 grid grid-cols-1 gap-5" id="aanvullende-filters">
        @foreach (['dienstverband' => 'Dienstverband', 'sector' => 'Sector', 'functiegebied' => 'Functiegebied', 'ervaring' => 'Ervaring', 'opleiding' => 'Opleidingsniveau', 'werklocatie' => 'Werklocatie'] as $filter => $label)
            <div>
                <label class="mb-1 block text-sm font-medium" for="{{ $filter }}">{{ $label }}</label>
                <select class="form-select w-full" id="{{ $filter }}" name="{{ $filter }}" @if ($autoSubmit) x-on:change="loading = true; $el.form.requestSubmit()" @endif>
                    <option value="">Alle opties</option>
                    @foreach ($taxonomyOptions[$filter] as $category)<option value="{{ $category->slug }}" @selected($filters[$filter] === $category->slug)>{{ $category->parent ? $category->parent->name.' — '.$category->name : $category->name }}</option>@endforeach
                </select>
            </div>
        @endforeach

        <fieldset class="grid grid-cols-2 gap-3 rounded-lg border border-gray-200 p-4">
            <legend class="px-1 text-sm font-semibold text-gray-800">Salaris of tarief</legend>
            <div class="col-span-full">
                <label class="mb-1 block text-sm font-medium" for="vergoeding">Type vergoeding</label>
                <select class="form-select w-full" id="vergoeding" name="vergoeding" @if ($autoSubmit) x-on:change="loading = true; $el.form.requestSubmit()" @endif aria-describedby="vergoeding-uitleg @if(isset($filterErrors['vergoeding'])) vergoeding-error @endif">
                    <option value="">Kies salaris of tarief</option>
                    <option value="maand" @selected($filters['vergoeding'] === 'maand')>Bruto maandsalaris (EUR, FTE)</option>
                    <option value="uur" @selected($filters['vergoeding'] === 'uur')>Uurtarief (EUR)</option>
                </select>
                <p class="mt-1 text-xs text-gray-500" id="vergoeding-uitleg">Maandsalaris en uurtarief worden nooit naar elkaar omgerekend.</p>
                @if (isset($filterErrors['vergoeding']))<p class="mt-1 text-xs text-red-600" id="vergoeding-error" role="alert">{{ $filterErrors['vergoeding'] }}</p>@endif
            </div>
            @foreach (['bedrag_van' => 'Vanaf', 'bedrag_tot' => 'Tot en met'] as $field => $label)
                <div>
                    <label class="mb-1 block text-sm font-medium" for="{{ $field }}">{{ $label }}</label>
                    <input class="form-input w-full" id="{{ $field }}" name="{{ $field }}" type="number" min="1" step="1" inputmode="numeric" value="{{ $filters[$field] }}" @if ($autoSubmit) x-on:change="loading = true; $el.form.requestSubmit()" @endif @if(isset($filterErrors[$field])) aria-invalid="true" aria-describedby="{{ $field }}-error" @endif>
                    @if (isset($filterErrors[$field]))<p class="mt-1 text-xs text-red-600" id="{{ $field }}-error" role="alert">{{ $filterErrors[$field] }}</p>@endif
                </div>
            @endforeach
        </fieldset>

        <div>
            <label class="mb-1 block text-sm font-medium" for="bedrijf">Bedrijf</label>
            <select class="form-select w-full" id="bedrijf" name="bedrijf" @if ($autoSubmit) x-on:change="loading = true; $el.form.requestSubmit()" @endif>
                <option value="">Alle bedrijven</option>
                @foreach ($companies as $company)<option value="{{ $company->slug }}" @selected($filters['bedrijf'] === $company->slug)>{{ $company->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="sort">Sorteren</label>
            <select class="form-select w-full" id="sort" name="sort" @if ($autoSubmit) x-on:change="loading = true; $el.form.requestSubmit()" @endif>
                @foreach ($sortOptions as $value => $label)<option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>@endforeach
            </select>
        </div>
                <x-ui.button class="justify-center" type="submit">Vacatures tonen</x-ui.button>
            </div>
        </details>
        <a class="shrink-0 text-sm font-semibold text-indigo-600 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500" href="{{ $formAction }}">Wis filters</a>
    </div>
</form>

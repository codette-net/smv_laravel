@props(['filters', 'sort', 'sortOptions', 'locations', 'taxonomyOptions', 'companies', 'variant' => 'sidebar', 'action' => null, 'autoSubmit' => true])

<form action="{{ $action ?? route('vacancies.index') }}" @class([
    'grid gap-6',
    'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3' => $variant === 'homepage',
    'grid-cols-2 md:grid-cols-1' => $variant !== 'homepage',
]) method="GET" x-data>
    <div class="col-span-full">
        <label class="block text-sm font-medium mb-1" for="zoek">Zoeken</label>
        <div class="relative">
            <input class="form-input w-full pl-9" id="zoek" name="zoek" type="search" value="{{ $filters['zoek'] }}" placeholder="Functie of bedrijf" @if ($autoSubmit) x-on:input.debounce.450ms="$el.form.requestSubmit()" @endif>
            <button class="absolute inset-0 right-auto group" type="submit" aria-label="Zoeken">
                <span class="sr-only">Zoeken</span>
                <svg class="shrink-0 fill-current text-gray-400 group-hover:text-gray-500 ml-3 mr-2" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M7 14c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7Zm0-12C4.243 2 2 4.243 2 7s2.243 5 5 5 5-2.243 5-5-2.243-5-5-5Z" /><path d="m15.707 14.293-2.393-2.393a8.019 8.019 0 0 1-1.414 1.414l2.393 2.393a.997.997 0 0 0 1.414 0 .999.999 0 0 0 0-1.414Z" /></svg>
            </button>
        </div>
    </div>

    <x-ui.select-dropdown name="locatie" label="Locatie" :options="collect($locations)->mapWithKeys(fn ($location) => [$location => $location])" :value="$filters['locatie']" placeholder="Alle locaties" x-on:change="{{ $autoSubmit ? '$el.form.requestSubmit()' : '' }}" />

    @foreach (['dienstverband' => 'Dienstverband', 'werklocatie' => 'Werklocatie', 'sector' => 'Sector', 'functiegebied' => 'Functiegebied', 'ervaring' => 'Ervaring'] as $filter => $label)
        <x-ui.select-dropdown
            :name="$filter"
            :label="$label"
            :options="collect($taxonomyOptions[$filter])->mapWithKeys(fn ($category) => [$category->slug => $category->parent ? $category->parent->name.' — '.$category->name : $category->name])"
            :value="$filters[$filter]"
            placeholder="Alle opties"
            x-on:change="{{ $autoSubmit ? '$el.form.requestSubmit()' : '' }}"
        />
    @endforeach

    <x-ui.select-dropdown name="bedrijf" label="Bedrijf" :options="collect($companies)->mapWithKeys(fn ($company) => [$company->slug => $company->name])" :value="$filters['bedrijf']" placeholder="Alle bedrijven" x-on:change="{{ $autoSubmit ? '$el.form.requestSubmit()' : '' }}" />

    <x-ui.select-dropdown name="sort" label="Sorteren" :options="$sortOptions" :value="$sort" placeholder="Sorteren" x-on:change="{{ $autoSubmit ? '$el.form.requestSubmit()' : '' }}" />

    <x-ui.button class="col-span-full justify-center" type="submit">Vacatures tonen</x-ui.button>
</form>

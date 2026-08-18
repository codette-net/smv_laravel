@props(['filters', 'sort', 'sortOptions', 'locations', 'categories', 'companies'])

<form action="{{ route('vacancies.index') }}" class="grid grid-cols-2 gap-6 md:grid-cols-1" method="GET" x-data>
    <div class="col-span-full">
        <label class="mb-3 block text-sm font-semibold text-gray-800" for="zoek">Zoeken</label>
        <div class="relative">
            <input class="form-input w-full py-2.5 pl-3 pr-10 text-sm" id="zoek" name="zoek" type="search" value="{{ $filters['zoek'] }}" placeholder="Functie of bedrijf" @input.debounce.450ms="$el.form.requestSubmit()">
            <button class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 transition hover:text-indigo-500" type="submit">
                <span class="sr-only">Zoeken</span>
                <svg aria-hidden="true" class="h-5 w-5 fill-current" viewBox="0 0 20 20"><path d="M8.5 3a5.5 5.5 0 1 0 3.44 9.79l4.13 4.12 1.06-1.06-4.12-4.13A5.5 5.5 0 0 0 8.5 3Zm0 1.5a4 4 0 1 1 0 8 4 4 0 0 1 0-8Z" /></svg>
            </button>
        </div>
    </div>

    <div>
        <label class="mb-3 block text-sm font-semibold text-gray-800" for="locatie">Locatie</label>
        <select class="form-select w-full text-sm" id="locatie" name="locatie" @change="$el.form.requestSubmit()">
            <option value="">Alle locaties</option>
            @foreach ($locations as $location)
                <option value="{{ $location }}" @selected($filters['locatie'] === $location)>{{ $location }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-3 block text-sm font-semibold text-gray-800" for="categorie">Categorie</label>
        <select class="form-select w-full text-sm" id="categorie" name="categorie" @change="$el.form.requestSubmit()">
            <option value="">Alle categorieën</option>
            @foreach ($categories as $category)
                <option value="{{ $category->slug }}" @selected($filters['categorie'] === $category->slug)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-3 block text-sm font-semibold text-gray-800" for="bedrijf">Bedrijf</label>
        <select class="form-select w-full text-sm" id="bedrijf" name="bedrijf" @change="$el.form.requestSubmit()">
            <option value="">Alle bedrijven</option>
            @foreach ($companies as $company)
                <option value="{{ $company->slug }}" @selected($filters['bedrijf'] === $company->slug)>{{ $company->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-3 block text-sm font-semibold text-gray-800" for="sort">Sorteren</label>
        <select class="form-select w-full text-sm" id="sort" name="sort" @change="$el.form.requestSubmit()">
            @foreach ($sortOptions as $value => $label)
                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <button class="btn col-span-full justify-center bg-indigo-500 text-white hover:bg-indigo-600" type="submit">Vacatures tonen</button>
</form>

<section class="min-w-0 lg:grow" aria-labelledby="vacature-resultaten" data-vacancy-results data-result-count="{{ $vacancies->total() }}">
    <div class="mb-8 flex flex-col gap-4 border-b border-gray-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="font-inter text-3xl font-bold text-gray-800" id="vacature-resultaten">{{ $vacancies->total() }} {{ Str::plural('vacature', $vacancies->total()) }} gevonden</h2>
            <p class="mt-2 text-sm text-gray-500">Gebruik de filters om de resultaten te verfijnen.</p>
        </div>
        <x-vacancy.active-filters :filters="$activeFilters" />
    </div>

    @if ($vacancies->isNotEmpty())
        <div class="grid gap-4 grid-cols-[repeat(auto-fill,minmax(16rem,1fr))]">
            @foreach ($vacancies as $vacancy)
                <x-vacancy.card :vacancy="$vacancy" :detail-url="route('vacancies.show', $vacancy)" />
            @endforeach
        </div>

        @if ($vacancies->hasPages())
            <div class="mt-10">{{ $vacancies->links('pagination::tailwind') }}</div>
        @endif
    @else
        <div class="relative rounded-xl border border-gray-200 bg-gray-50 px-6 py-10 text-center">
            <h2 class="text-xl font-bold text-gray-800 text-pretty">Geen vacatures gevonden</h2>
            <p class="mt-2 text-gray-500">Probeer je zoekopdracht aan te passen of verwijder je filters.</p>
            <a class="btn mt-5 bg-blue-600 text-white hover:bg-blue-700" href="{{ route('vacancies.index') }}">Wis filters</a>
        </div>
    @endif
</section>

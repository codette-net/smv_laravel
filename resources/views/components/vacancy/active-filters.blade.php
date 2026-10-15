@props(['filters'])

@if (count($filters))
    <div class="flex flex-wrap items-center gap-2" aria-label="Actieve filters">
        <span class="text-sm font-medium text-gray-600">Actief:</span>
        @foreach ($filters as $filter)
            <a class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-gray-600 transition hover:text-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" href="{{ $filter['url'] }}">
                {{ $filter['label'] }} <span class="ml-1" aria-hidden="true">×</span><span class="sr-only"> verwijderen</span>
            </a>
        @endforeach
        <a class="text-sm font-semibold text-blue-700 hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" href="{{ route('vacancies.index') }}">Wis filters</a>
    </div>
@endif

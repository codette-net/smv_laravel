@props(['vacancy', 'detailUrl' => null])

<article class="flex h-full flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-xs transition hover:border-blue-200 hover:shadow-sm">
    <div class="flex items-start gap-4">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-blue-50 text-lg font-bold text-blue-700">
            @if ($vacancy->company->publicLogoUrl())
                <img class="h-full w-full object-cover" src="{{ $vacancy->company->publicLogoUrl() }}" alt="Logo van {{ $vacancy->company->name }}">
            @else
                {{ Str::upper(Str::substr($vacancy->company->name, 0, 1)) }}
            @endif
        </div>
        <div class="min-w-0 grow">
            @if ($vacancy->is_featured)
                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Uitgelicht</span>
            @endif
            <h2 class="mt-2 text-lg font-bold leading-6 text-slate-900">{{ $vacancy->title }}</h2>
            <a class="mt-1 inline-block text-sm font-medium text-blue-700 hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" href="{{ route('bedrijven.show', $vacancy->company) }}">{{ $vacancy->company->name }}</a>
        </div>
    </div>

    @if ($vacancy->location || $vacancy->categories->isNotEmpty())
        <div class="mt-5 flex flex-wrap gap-2 text-sm text-slate-600">
            @if ($vacancy->location)
                <span>{{ $vacancy->location }}</span>
            @endif
            @foreach ($vacancy->categories->take(2) as $category)
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $category->name }}</span>
            @endforeach
        </div>
    @endif

    <div class="mt-5 flex items-center justify-between gap-3 border-t border-slate-100 pt-4 text-sm">
        @if ($vacancy->deadline_at)
            <span class="text-slate-500">Deadline {{ $vacancy->deadline_at->translatedFormat('j F') }}</span>
        @elseif ($vacancy->published_at)
            <span class="text-slate-500">Geplaatst {{ $vacancy->published_at->translatedFormat('j F') }}</span>
        @endif
        @if ($detailUrl)
            <a class="ml-auto font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" href="{{ $detailUrl }}">Bekijk vacature</a>
        @endif
    </div>
</article>

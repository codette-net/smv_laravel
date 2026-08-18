@props(['vacancy', 'detailUrl' => null])

<article class="group">
    <div @class(['rounded-xl px-4 py-6 transition duration-150 ease-in-out sm:px-5', 'bg-indigo-100' => $vacancy->is_featured])>
        <div class="sm:flex sm:items-center sm:space-x-5">
            <div class="mb-3 h-12 w-12 shrink-0 overflow-hidden rounded-full bg-gray-100 sm:mb-0">
                @if ($vacancy->company->publicLogoUrl())
                    <img class="h-full w-full object-cover" src="{{ $vacancy->company->publicLogoUrl() }}" alt="Logo van {{ $vacancy->company->name }}">
                @else
                    <span class="flex h-full w-full items-center justify-center text-lg font-bold text-indigo-500">{{ Str::upper(Str::substr($vacancy->company->name, 0, 1)) }}</span>
                @endif
            </div>

            <div class="grow lg:flex lg:items-center lg:justify-between lg:space-x-4">
                <div>
                    <div class="mb-1 flex items-start space-x-2">
                        <a class="text-sm font-semibold text-gray-800 hover:text-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500" href="{{ route('bedrijven.show', $vacancy->company) }}">{{ $vacancy->company->name }}</a>
                        @if ($vacancy->is_featured)
                            <svg aria-label="Uitgelichte vacature" class="mt-1 h-3 w-3 shrink-0 fill-amber-400" viewBox="0 0 12 12"><path d="M11.143 5.143A4.29 4.29 0 0 1 6.857.857a.857.857 0 0 0-1.714 0A4.29 4.29 0 0 1 .857 5.143a.857.857 0 0 0 0 1.714 4.29 4.29 0 0 1 4.286 4.286.857.857 0 0 0 1.714 0 4.29 4.29 0 0 1 4.286-4.286.857.857 0 0 0 0-1.714Z" /></svg>
                        @endif
                    </div>
                    <h2 class="mb-2 text-lg font-bold text-gray-800">{{ $vacancy->title }}</h2>
                    <div class="-m-1 flex flex-wrap">
                        @if ($vacancy->salary_min && $vacancy->salary_max)
                            <span class="m-1 inline-flex whitespace-nowrap rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-medium text-gray-500">€{{ number_format($vacancy->salary_min, 0, ',', '.') }} – €{{ number_format($vacancy->salary_max, 0, ',', '.') }}</span>
                        @endif
                        @if ($vacancy->location)
                            <span class="m-1 inline-flex whitespace-nowrap rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-medium text-gray-500">{{ $vacancy->location }}</span>
                        @endif
                        @foreach ($vacancy->categories->take(2) as $category)
                            <span class="m-1 inline-flex whitespace-nowrap rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">{{ $category->name }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 flex min-w-[120px] items-center justify-between lg:mt-0 lg:justify-end">
                    @if ($detailUrl)
                        <a class="btn-sm bg-indigo-500 px-3 py-1.5 text-white shadow-xs hover:bg-indigo-600" href="{{ $detailUrl }}">Bekijk vacature <span class="ml-1 text-indigo-200">-&gt;</span></a>
                    @elseif ($vacancy->deadline_at)
                        <span class="text-sm italic text-gray-500">Deadline {{ $vacancy->deadline_at->translatedFormat('j F') }}</span>
                    @elseif ($vacancy->published_at)
                        <span class="text-sm italic text-gray-500">Geplaatst {{ $vacancy->published_at->translatedFormat('j F') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</article>

@props(['company', 'logoUrl' => null])

<article class="group relative flex h-full flex-col rounded-2xl bg-white/20 p-5 shadow-lg shadow-black/3 transition hover:bg-white/90 before:pointer-events-none before:absolute before:inset-0 before:-z-10 before:rounded-[inherit] before:border before:border-transparent before:[background:linear-gradient(var(--color-gray-100),var(--color-gray-200))_border-box] before:[mask:linear-gradient(white_0_0)_padding-box,linear-gradient(white_0_0)] before:[mask-composite:exclude_!important]">
    <svg class="absolute top-5 right-5 transition-transform group-hover:rotate-45" xmlns="http://www.w3.org/2000/svg" width="9" height="9" aria-hidden="true">
        <path class="fill-slate-400" d="M1.065 9 0 7.93l6.456-6.46H1.508L1.519 0H9v7.477H7.516l.011-4.942L1.065 9Z" />
    </svg>

    <div class="mb-3 inline-flex">
        <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-blue-50 text-lg font-bold text-blue-700 shadow-lg shadow-black/[0.03]">
            @if ($logoUrl)
                <img class="h-full w-full object-cover" src="{{ $logoUrl }}" alt="Logo van {{ $company->name }}">
            @else
                {{ Str::upper(Str::substr($company->name, 0, 1)) }}
            @endif
        </div>
    </div>

    @if ($company->is_featured)
        <span class="mb-2 inline-flex w-fit rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700">Uitgelicht</span>
    @endif

    <h2 class="mb-1 text-lg font-bold text-slate-900">
        <a class="before:absolute before:inset-0 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('bedrijven.show', $company) }}">
            {{ $company->name }}
        </a>
    </h2>

    @if ($company->tagline)
        <p class="text-sm leading-6 text-slate-600">{{ $company->tagline }}</p>
    @endif

    @if ($company->categories->isNotEmpty())
        <div class="mt-4 flex flex-wrap gap-1.5 text-xs font-medium text-slate-600">
            @foreach ($company->categories->take(2) as $category)
                <span class="rounded-full bg-slate-100 px-2.5 py-1">{{ $category->name }}</span>
            @endforeach
        </div>
    @endif

    @if ($company->location || $company->public_vacancies_count !== null)
        <div class="mt-auto flex flex-wrap gap-x-3 gap-y-1 pt-5 text-sm text-slate-700">
            @if ($company->location)
                <span class="inline-flex items-center gap-1.5">
                    <svg class="h-4 w-4 shrink-0 fill-none stroke-current text-slate-400" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 8.992a2 2 0 1 1-.002-3.998A2 2 0 0 1 8 8.992Zm-.7 6.694c-.1-.1-4.2-3.696-4.2-3.796C1.7 10.69 1 8.892 1 6.994 1 3.097 4.1 0 8 0s7 3.097 7 6.994c0 1.898-.7 3.697-2.1 4.996-.1.1-4.1 3.696-4.2 3.796-.4.3-1 .3-1.4-.1Z" /></svg>
                    {{ $company->location }}
                </span>
            @endif
            @if ($company->public_vacancies_count !== null)
                <span>{{ $company->public_vacancies_count }} {{ Str::plural('vacature', $company->public_vacancies_count) }}</span>
            @endif
        </div>
    @endif
</article>

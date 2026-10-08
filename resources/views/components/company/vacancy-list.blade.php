@props([
    'company',
    'vacancies',
    'logoUrl' => null,
])

<section aria-labelledby="company-vacancies">

    <div class="flex items-end justify-between gap-4">

        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-700">
                Werken bij
            </p>

            <h2
                id="company-vacancies"
                class="mt-2 text-xl font-bold text-gray-900"
            >
                Openstaande vacatures
            </h2>
        </div>


        @if ($vacancies->isNotEmpty())
            <span class="shrink-0 text-sm text-gray-500">
                {{ $vacancies->count() }}
                {{ Str::plural('vacature', $vacancies->count()) }}
            </span>
        @endif

    </div>


    @if ($vacancies->isNotEmpty())

        <div class="mt-6 space-y-3">

            @foreach ($vacancies as $vacancy)
                <x-company.vacancy-card
                    :vacancy="$vacancy"
                    :logo-url="$logoUrl"
                    :detail-url="route('vacancies.show', $vacancy)"
                />
            @endforeach

        </div>

    @else

        <div class="mt-6 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6">

            <h3 class="font-semibold text-gray-900">
                Momenteel geen openstaande vacatures
            </h3>

            <p class="mt-1 text-sm leading-6 text-gray-500">
                Er zijn op dit moment geen actieve vacatures bij
                {{ $company->name }}.
            </p>

            <a
                href="{{ route('vacancies.index') }}"
                class="mt-4 inline-flex items-center font-semibold text-blue-700 hover:text-blue-800"
            >
                Bekijk alle vacatures
                <span class="ml-1">→</span>
            </a>

        </div>

    @endif

</section>

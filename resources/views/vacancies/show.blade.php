@extends('layouts.public')

@section(
    'title',
    $vacancy->title
    . ($vacancy->location ? ' vacature in ' . $vacancy->location : ' vacature')
    . ' | Sales en Marketing Vacatures'
)

@section(
    'meta_description',
    Str::limit(
        \App\Support\Seo\StructuredData::plainText($vacancy->description),
        155
    )
)

@section('canonical', route('vacancies.show', $vacancy))
@section('og_type', 'article')

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(
            $structuredData,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        ) !!}
    </script>
@endpush


@section('content')

    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">

        <div class="mx-auto flex max-w-5xl flex-col lg:flex-row lg:space-x-8 xl:space-x-16">

            {{-- Content --}}
            <article class="min-w-0 grow">

                {{-- Back --}}
                <div class="mb-6">

                    <a
                        class="btn-sm border-gray-200 bg-white px-3 text-gray-800 hover:border-gray-300"
                        href="{{ route('vacancies.index') }}"
                    >
                        <svg
                            class="mr-2 fill-current text-gray-400"
                            width="7"
                            height="12"
                            viewBox="0 0 7 12"
                            aria-hidden="true"
                        >
                            <path d="M5.4.6 6.8 2l-4 4 4 4-1.4 1.4L0 6z"/>
                        </svg>

                        <span>Terug naar vacatures</span>
                    </a>

                </div>


                {{-- Published --}}
                @if ($vacancy->published_at)

                    <div class="mb-2 text-sm italic text-gray-500">
                        Gepubliceerd
                        {{ $vacancy->published_at->translatedFormat('j F Y') }}
                    </div>

                @endif


                {{-- Header --}}
                <header class="mb-4">

                    @if ($vacancy->is_featured)
                        <div class="mb-2">
                            <x-ui.badge variant="warning">Uitgelichte vacature</x-ui.badge>
                        </div>
                    @endif

                    <h1 class="text-2xl font-bold text-gray-800 md:text-3xl">
                        {{ $vacancy->title }}
                    </h1>

                </header>


                {{-- Company information - mobile --}}
                <div class="mb-6 rounded-xl bg-white p-5 shadow-lg lg:hidden">

                    <div class="mb-6 text-center">

                        <div class="mb-3 inline-flex">

                            <div class="flex size-16 items-center justify-center overflow-hidden rounded-full bg-gray-50">

                                @if ($logoUrl)

                                    <img
                                        class="h-full w-full object-contain p-1"
                                        src="{{ $logoUrl }}"
                                        width="64"
                                        height="64"
                                        alt="Logo van {{ $vacancy->company->name }}"
                                    >

                                @else

                                    <span class="text-xl font-bold text-indigo-500">
                                        {{ Str::upper(Str::substr($vacancy->company->name, 0, 1)) }}
                                    </span>

                                @endif

                            </div>

                        </div>

                        <div class="mb-1 text-lg font-bold text-gray-800">
                            {{ $vacancy->company->name }}
                        </div>

                        @if ($vacancy->company->tagline)
                            <div class="text-sm italic text-gray-500">
                                {{ $vacancy->company->tagline }}
                            </div>
                        @endif

                    </div>


                    {{-- Vacancy info --}}
                    @if (
                        $vacancy->location
                        || $vacancy->deadline_at
                        || $vacancy->compensationLabel()
                    )

                        <div class="mb-5 space-y-2 border-t border-gray-100 pt-4 text-sm text-gray-600">

                            @if ($vacancy->location)
                                <div>
                                    {{ $vacancy->location }}
                                </div>
                            @endif

                            @if ($vacancy->compensationLabel())
                                <div>
                                    {{ $vacancy->compensationLabel() }}
                                </div>
                            @endif

                            @if ($vacancy->deadline_at)
                                <div>
                                    Solliciteren vóór:
                                    <span class="font-medium text-gray-800">
                                        {{ $vacancy->deadline_at->translatedFormat('j F Y') }}
                                    </span>
                                </div>
                            @endif

                        </div>

                    @endif


                    <div class="space-y-2 sm:flex sm:space-x-2 sm:space-y-0">

                        @if (
                            $vacancy->application_mode === \App\Enums\ApplicationMode::External
                            && $vacancy->application_url
                        )

                            <a
                                class="btn w-full bg-gray-900 text-center text-gray-100 hover:bg-gray-800"
                                href="{{ $vacancy->application_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Solliciteer nu →
                            </a>

                        @elseif (
                            $vacancy->application_mode === \App\Enums\ApplicationMode::Email
                            && $vacancy->application_email
                        )

                            <a
                                class="btn w-full bg-gray-900 text-center text-gray-100 hover:bg-gray-800"
                                href="mailto:{{ $vacancy->application_email }}?subject={{ rawurlencode('Sollicitatie: ' . $vacancy->title) }}"
                            >
                                Solliciteer via e-mail →
                            </a>

                        @elseif (
                            $vacancy->application_mode === \App\Enums\ApplicationMode::Internal
                        )

                            <a
                                class="btn w-full bg-gray-900 text-center text-gray-100 hover:bg-gray-800"
                                href="{{ route('applications.create', $vacancy) }}"
                            >
                                Solliciteer nu →
                            </a>

                        @endif


                        <a
                            class="btn w-full border-gray-200 text-center text-gray-800 hover:border-gray-300"
                            href="{{ route('bedrijven.show', $vacancy->company) }}"
                        >
                            Bedrijfsprofiel
                        </a>

                    </div>

                </div>


                {{-- Vacancy taxonomy / tags --}}
                @if (
                    collect($taxonomy)->flatten()->isNotEmpty()
                    || $vacancy->tags->isNotEmpty()
                )

                    <div class="mb-6">

                        <div class="-m-1 flex flex-wrap items-center">

                            @foreach (
                                [
                                    'dienstverband',
                                    'werklocatie',
                                    'sector',
                                    'functiegebied',
                                    'ervaring'
                                ] as $key
                            )

                                @foreach ($taxonomy[$key] as $category)

                                    <div class="m-1">
                                        <span class="btn-xs rounded-full border-gray-200 px-2.5 py-1 text-xs text-gray-800 shadow-none">
                                            <span class="sr-only">{{ ucfirst($key) }}:</span>
                                            {{ $category->parent
                                                ? $category->parent->name . ' — ' . $category->name
                                                : $category->name }}
                                        </span>
                                    </div>

                                @endforeach

                            @endforeach


                            @foreach ($vacancy->tags as $tag)

                                <div class="m-1">
                                    <span class="btn-xs rounded-full border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs text-indigo-700 shadow-none">
                                        {{ $tag->name }}
                                    </span>
                                </div>

                            @endforeach

                        </div>

                    </div>

                @endif


                <hr class="my-6 border-t border-gray-100">


                {{-- Vacancy description --}}
                <section aria-labelledby="over-deze-vacature">

                    <h2
                        id="over-deze-vacature"
                        class="mb-4 text-xl font-bold leading-snug text-gray-800"
                    >
                        Over deze vacature
                    </h2>

                    <div class="prose prose-slate max-w-none leading-7">
                        {!! $vacancy->description !!}
                    </div>

                </section>


                {{-- Apply section --}}
                <div class="mt-8">

                    <p class="mb-6 font-medium italic">
                        Interesse in deze vacature?
                    </p>


                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">

                        {{-- Apply button --}}
                        <div>

                            @if (
                                $vacancy->application_mode === \App\Enums\ApplicationMode::External
                                && $vacancy->application_url
                            )

                                <a
                                    class="btn whitespace-nowrap bg-gray-900 text-gray-100 hover:bg-gray-800"
                                    href="{{ $vacancy->application_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Solliciteer nu →
                                </a>

                            @elseif (
                                $vacancy->application_mode === \App\Enums\ApplicationMode::Email
                                && $vacancy->application_email
                            )

                                <a
                                    class="btn whitespace-nowrap bg-gray-900 text-gray-100 hover:bg-gray-800"
                                    href="mailto:{{ $vacancy->application_email }}?subject={{ rawurlencode('Sollicitatie: ' . $vacancy->title) }}"
                                >
                                    Solliciteer via e-mail →
                                </a>

                            @elseif (
                                $vacancy->application_mode === \App\Enums\ApplicationMode::Internal
                            )

                                <a
                                    class="btn whitespace-nowrap bg-gray-900 text-gray-100 hover:bg-gray-800"
                                    href="{{ route('applications.create', $vacancy) }}"
                                >
                                    Solliciteer nu →
                                </a>

                            @endif

                        </div>


                        {{-- Share --}}
                        <div
                            class="flex items-center"
                            x-data="{
                                copied: false,
                                copy() {
                                    navigator.clipboard.writeText(window.location.href);
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 1500);
                                }
                            }"
                        >

                            <div class="mr-4 text-sm italic text-gray-500">
                                Delen:
                            </div>


                            <div class="flex items-center space-x-3">

                                {{-- LinkedIn --}}
                                <a
                                    class="text-gray-400 hover:text-indigo-500"
                                    href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(route('vacancies.show', $vacancy)) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <span class="sr-only">Delen op LinkedIn</span>

                                    <svg
                                        class="fill-current"
                                        width="16"
                                        height="16"
                                        viewBox="0 0 16 16"
                                        aria-hidden="true"
                                    >
                                        <path d="M0 1.146C0 .514.53 0 1.182 0h13.635C15.471 0 16 .513 16 1.146v13.708c0 .633-.53 1.146-1.183 1.146H1.182C.53 16 0 15.487 0 14.854V1.146ZM4.862 13.39V6.187H2.468v7.203h2.394ZM3.666 5.203c.834 0 1.354-.553 1.354-1.244-.016-.707-.52-1.245-1.338-1.245-.82 0-1.355.538-1.355 1.245 0 .691.52 1.244 1.323 1.244h.015Zm2.522 8.187h2.394V9.368c0-.215.015-.43.078-.584.173-.43.567-.876 1.229-.876.866 0 1.213.66 1.213 1.629v3.853h2.394V9.26c0-2.213-1.181-3.242-2.756-3.242-1.292 0-1.86.722-2.174 1.213h.016V6.187H6.188c.03.676 0 7.203 0 7.203Z"/>
                                    </svg>
                                </a>


                                {{-- Copy link --}}
                                <button
                                    type="button"
                                    class="relative text-gray-400 hover:text-indigo-500"
                                    @click="copy()"
                                >
                                    <span class="sr-only">
                                        Link kopiëren
                                    </span>

                                    <svg
                                        width="16"
                                        height="16"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        aria-hidden="true"
                                    >
                                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                                    </svg>

                                    <span
                                        x-cloak
                                        x-show="copied"
                                        class="absolute right-0 top-full mt-2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white"
                                    >
                                        Gekopieerd
                                    </span>

                                </button>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- Related vacancies --}}
                @if ($relatedVacancies->isNotEmpty())

                    <hr class="my-8 border-t border-gray-100">


                    <section aria-labelledby="gerelateerde-vacatures">

                        <h2
                            id="gerelateerde-vacatures"
                            class="mb-6 text-xl font-bold leading-snug text-gray-800"
                        >
                            Gerelateerde vacatures
                        </h2>


                        <div class="mt-6 space-y-2">

                            @foreach ($relatedVacancies as $relatedVacancy)

                                <x-company.vacancy-card
                                    :vacancy="$relatedVacancy"
                                    :logo-url="$relatedVacancy->company->publicLogoUrl()"
                                    :detail-url="route('vacancies.show', $relatedVacancy)"
                                />

                            @endforeach

                        </div>

                    </section>

                @endif

            </article>


            {{-- Desktop sidebar --}}
            <aside class="hidden shrink-0 space-y-4 lg:block">

                <div class="sticky top-24">

                    <div class="w-72 rounded-xl bg-white p-5 shadow-xs xl:w-80">

                        {{-- Company --}}
                        <div class="mb-6 text-center">

                            <div class="mb-3 inline-flex">

                                <div class="flex size-16 items-center justify-center overflow-hidden rounded-full bg-gray-50">

                                    @if ($logoUrl)

                                        <img
                                            class="h-full w-full object-contain p-1"
                                            src="{{ $logoUrl }}"
                                            width="64"
                                            height="64"
                                            alt="Logo van {{ $vacancy->company->name }}"
                                        >

                                    @else

                                        <span class="text-xl font-bold text-indigo-500">
                                            {{ Str::upper(Str::substr($vacancy->company->name, 0, 1)) }}
                                        </span>

                                    @endif

                                </div>

                            </div>

                            <div class="mb-1 text-lg font-bold text-gray-800">
                                {{ $vacancy->company->name }}
                            </div>

                            @if ($vacancy->company->tagline)
                                <div class="text-sm italic text-gray-500">
                                    {{ $vacancy->company->tagline }}
                                </div>
                            @endif

                        </div>


                        {{-- Vacancy information --}}
                        @if (
                            $vacancy->location
                            || $vacancy->deadline_at
                            || $vacancy->compensationLabel()
                        )

                            <div class="mb-5 space-y-3 border-t border-gray-100 pt-5 text-sm text-gray-600">

                                @if ($vacancy->location)

                                    <div class="flex items-start">

                                        <svg
                                            class="mr-3 mt-0.5 shrink-0 fill-gray-400"
                                            width="14"
                                            height="16"
                                            viewBox="0 0 14 16"
                                            aria-hidden="true"
                                        >
                                            <circle cx="7" cy="7" r="2"/>
                                            <path d="M6.3 15.7c-.1-.1-4.2-3.7-4.2-3.8C.7 10.7 0 8.9 0 7c0-3.9 3.1-7 7-7s7 3.1 7 7c0 1.9-.7 3.7-2.1 5-.1.1-4.1 3.7-4.2 3.8-.4.3-1 .3-1.4-.1Zm-2.7-5 3.4 3 3.4-3c1-1 1.6-2.2 1.6-3.6 0-2.8-2.2-5-5-5S2 4.2 2 7c0 1.4.6 2.7 1.6 3.7Z"/>
                                        </svg>

                                        <span>
                                            {{ $vacancy->location }}
                                        </span>

                                    </div>

                                @endif


                                @if ($vacancy->compensationLabel())

                                    <div class="flex items-start">

                                        <svg
                                            class="mr-3 mt-0.5 shrink-0 fill-gray-400"
                                            width="16"
                                            height="12"
                                            viewBox="0 0 16 12"
                                            aria-hidden="true"
                                        >
                                            <path d="M15 0H1C.4 0 0 .4 0 1v10c0 .6.4 1 1 1h14c.6 0 1-.4 1-1V1c0-.6-.4-1-1-1Zm-1 10H2V2h12v8Z"/>
                                            <circle cx="8" cy="6" r="2"/>
                                        </svg>

                                        <span>
                                            {{ $vacancy->compensationLabel() }}
                                        </span>

                                    </div>

                                @endif


                                @if ($vacancy->deadline_at)

                                    <div class="flex items-start">

                                        <svg
                                            class="mr-3 mt-0.5 shrink-0 fill-gray-400"
                                            width="14"
                                            height="14"
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path d="M19 4h-1V2h-2v2H8V2H6v2H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3Zm1 15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8h16v8ZM4 9V7a1 1 0 0 1 1-1h1v2h2V6h8v2h2V6h1a1 1 0 0 1 1 1v2H4Z"/>
                                        </svg>

                                        <span>
                                            Solliciteren vóór<br>
                                            <strong class="font-medium text-gray-800">
                                                {{ $vacancy->deadline_at->translatedFormat('j F Y') }}
                                            </strong>
                                        </span>

                                    </div>

                                @endif

                            </div>

                        @endif


                        {{-- Actions --}}
                        <div class="space-y-2">

                            @if (
                                $vacancy->application_mode === \App\Enums\ApplicationMode::External
                                && $vacancy->application_url
                            )

                                <a
                                    class="btn w-full bg-gray-900 text-center text-gray-100 hover:bg-gray-800"
                                    href="{{ $vacancy->application_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Solliciteer nu →
                                </a>

                            @elseif (
                                $vacancy->application_mode === \App\Enums\ApplicationMode::Email
                                && $vacancy->application_email
                            )

                                <a
                                    class="btn w-full bg-gray-900 text-center text-gray-100 hover:bg-gray-800"
                                    href="mailto:{{ $vacancy->application_email }}?subject={{ rawurlencode('Sollicitatie: ' . $vacancy->title) }}"
                                >
                                    Solliciteer via e-mail →
                                </a>

                            @elseif (
                                $vacancy->application_mode === \App\Enums\ApplicationMode::Internal
                            )

                                <a
                                    class="btn w-full bg-gray-900 text-center text-gray-100 hover:bg-gray-800"
                                    href="{{ route('applications.create', $vacancy) }}"
                                >
                                    Solliciteer nu →
                                </a>

                            @endif


                            <a
                                class="btn w-full border-gray-200 text-center text-gray-800 hover:border-gray-300"
                                href="{{ route('bedrijven.show', $vacancy->company) }}"
                            >
                                Bedrijfsprofiel
                            </a>

                        </div>

                    </div>

                </div>

            </aside>

        </div>

    </div>

@endsection

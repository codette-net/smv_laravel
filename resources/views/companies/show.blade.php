@extends('layouts.public')

@section('title', $company->name . ' | Sales en Marketing Vacatures')
@section('meta_description', $metaDescription)
@section('canonical', route('bedrijven.show', $company))

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

    {{-- Profile background --}}
    <div class="h-56 bg-gray-200">
        @if ($coverUrl)
            <img
                class="h-full w-full object-cover"
                src="{{ $coverUrl }}"
                alt=""
                width="2560"
                height="440"
            >
        @else
            <div class="smv-hero-light h-full w-full"></div>
        @endif
    </div>


    {{-- Header --}}
    <header class="border-b border-gray-200 bg-white/70 pb-6 text-center backdrop-blur-sm">

        <div class="w-full px-4 sm:px-6 lg:px-8">

            <div class="mx-auto max-w-3xl">

                {{-- Logo --}}
                <div class="-mt-12 mb-2">

                    <div class="inline-flex">

                        <div class="flex size-[104px] -mt-12 items-center justify-center overflow-hidden rounded-full border-4 border-blue-400/50 bg-white shadow-sm">

                            @if ($logoUrl)
                                <img
                                    class="h-full w-full object-contain p-2"
                                    src="{{ $logoUrl }}"
                                    width="104"
                                    height="104"
                                    alt="Logo van {{ $company->name }}"
                                >
                            @else
                                <span class="text-3xl font-bold text-blue-700">
                                    {{ Str::upper(Str::substr($company->name, 0, 1)) }}
                                </span>
                            @endif

                        </div>

                    </div>

                </div>


                {{-- Company name and info --}}
                <div class="mb-4">

                    @if ($company->is_featured)
                        <div class="mb-2">
                            <x-ui.badge variant="info">Uitgelicht bedrijf</x-ui.badge>
                        </div>
                    @endif

                    <h1 class="mb-2 text-2xl font-bold text-gray-800">
                        {{ $company->name }}
                    </h1>

                    @if ($company->tagline)
                        <p class="text-gray-600">
                            {{ $company->tagline }}
                        </p>
                    @endif

                </div>


                {{-- Meta --}}
                <div class="inline-flex flex-wrap justify-center gap-x-5 gap-y-2">

                    @if ($company->location)
                        <div class="flex items-center">

                            <svg
                                class="shrink-0 fill-current text-gray-400"
                                width="16"
                                height="16"
                                viewBox="0 0 16 16"
                                aria-hidden="true"
                            >
                                <path d="M8 8.992a2 2 0 1 1-.002-3.998A2 2 0 0 1 8 8.992Zm-.7 6.694c-.1-.1-4.2-3.696-4.2-3.796C1.7 10.69 1 8.892 1 6.994 1 3.097 4.1 0 8 0s7 3.097 7 6.994c0 1.898-.7 3.697-2.1 4.996-.1.1-4.1 3.696-4.2 3.796-.4.3-1 .3-1.4-.1Zm-2.7-4.995L8 13.688l3.4-2.997c1-1 1.6-2.198 1.6-3.597 0-2.798-2.2-4.996-5-4.996S3 4.196 3 6.994c0 1.399.6 2.698 1.6 3.697Z"/>
                            </svg>

                            <span class="ml-2 whitespace-nowrap text-sm font-medium text-gray-500">
                                {{ $company->location }}
                            </span>

                        </div>
                    @endif


                    @if ($company->website)
                        <div class="flex items-center">

                            <svg
                                class="shrink-0 fill-current text-gray-400"
                                width="16"
                                height="16"
                                viewBox="0 0 16 16"
                                aria-hidden="true"
                            >
                                <path d="M11 0c1.3 0 2.6.5 3.5 1.5 1 .9 1.5 2.2 1.5 3.5 0 1.3-.5 2.6-1.4 3.5l-1.2 1.2c-.2.2-.5.3-.7.3-.2 0-.5-.1-.7-.3-.4-.4-.4-1 0-1.4l1.1-1.2c.6-.5.9-1.3.9-2.1s-.3-1.6-.9-2.2C12 1.7 10 1.7 8.9 2.8L7.7 4c-.4.4-1 .4-1.4 0-.4-.4-.4-1 0-1.4l1.2-1.1C8.4.5 9.7 0 11 0ZM8.3 12c.4-.4 1-.5 1.4-.1.4.4.4 1 0 1.4l-1.2 1.2C7.6 15.5 6.3 16 5 16c-1.3 0-2.6-.5-3.5-1.5C.5 13.6 0 12.3 0 11c0-1.3.5-2.6 1.5-3.5l1.1-1.2c.4-.4 1-.4 1.4 0 .4.4.4 1 0 1.4L2.9 8.9c-.6.5-.9 1.3-.9 2.1s.3 1.6.9 2.2c1.1 1.1 3.1 1.1 4.2 0L8.3 12Zm1.1-6.8c.4-.4 1-.4 1.4 0 .4.4.4 1 0 1.4l-4.2 4.2c-.2.2-.5.3-.7.3-.2 0-.5-.1-.7-.3-.4-.4-.4-1 0-1.4l4.2-4.2Z"/>
                            </svg>

                            <a
                                class="ml-2 whitespace-nowrap text-sm font-medium text-blue-700 hover:text-blue-800"
                                href="{{ $company->website }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Website
                            </a>

                        </div>
                    @endif

                </div>

            </div>

        </div>

    </header>


    {{-- Page content --}}
    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">

        <div class="mx-auto max-w-5xl">

            <div class="md:flex md:items-start">

                {{-- Main content --}}
                <main class="min-w-0 grow">

                    {{-- About --}}
                    @if ($descriptionHtml)

                        <section class="mb-10">

                            <h2 class="mb-4 text-xl font-bold leading-snug text-gray-800">
                                Over {{ $company->name }}
                            </h2>

                            <x-ui.rich-content class="text-gray-600" :html="$descriptionHtml" />

                        </section>

                    @endif


                    {{-- Vacancies --}}
                    <section>

                        <div class="mb-6 flex items-baseline justify-between gap-4">

                            <h2 class="text-xl font-bold leading-snug text-gray-800">
                                Openstaande vacatures bij {{ $company->name }}
                            </h2>

                            @if ($vacancies->isNotEmpty())
                                <span class="shrink-0 text-sm text-gray-500">
                                    {{ $vacancies->count() }}
                                    {{ Str::plural('vacature', $vacancies->count()) }}
                                </span>
                            @endif

                        </div>


                        @if ($vacancies->isNotEmpty())

                            <div class="space-y-2">

                                @foreach ($vacancies as $vacancy)

                                    <x-company.vacancy-card
                                        :vacancy="$vacancy"
                                        :logo-url="$logoUrl"
                                        :detail-url="route('vacancies.show', $vacancy)"
                                    />

                                @endforeach

                            </div>

                        @else

                            <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 text-sm text-gray-500 shadow-xs">
                                Momenteel geen openstaande vacatures.
                            </div>

                        @endif

                    </section>

                </main>


                {{-- Sidebar --}}
                <x-company.sidebar
                    :company="$company"
                    :logo-url="$logoUrl"
                />

            </div>

        </div>

    </div>

@endsection

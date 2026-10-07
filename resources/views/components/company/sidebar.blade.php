@props([
    'company',
    'logoUrl' => null,
])

@php
    $hasSocials = $company->linkedin_url
        || $company->facebook_url
        || $company->instagram_url
        || $company->video_url;
@endphp

<aside class="mb-8 md:order-1 md:mb-0 md:ml-12 md:w-64 md:shrink-0 lg:ml-20 lg:w-72">

    <div class="sticky top-24">

        <div class="relative rounded-xl border border-gray-200 bg-gray-50 p-5">

            {{-- Company --}}
            <div class="mb-6 text-center">

                <div class="mb-2 inline-flex size-[72px] items-center justify-center overflow-hidden rounded-xl bg-white">

                    @if ($logoUrl)

                        <img
                            class="h-full w-full object-contain p-1"
                            src="{{ $logoUrl }}"
                            width="72"
                            height="72"
                            alt="Logo van {{ $company->name }}"
                        >

                    @else

                        <span class="text-2xl font-bold text-blue-700">
                            {{ Str::upper(Str::substr($company->name, 0, 1)) }}
                        </span>

                    @endif

                </div>

                <h2 class="text-lg font-bold text-gray-800">
                    {{ $company->name }}
                </h2>

            </div>


            {{-- Company information --}}
            <div class="mb-5 flex justify-center md:justify-start">

                <ul class="inline-flex min-w-0 flex-col space-y-3">

                    @if ($company->location)

                        <li class="flex items-start">

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

                            <span class="text-sm text-gray-600">
                                {{ $company->location }}
                            </span>

                        </li>

                    @endif


                    @if ($company->email)

                        <li class="flex items-start">

                            <svg
                                class="mr-3 mt-0.5 shrink-0 fill-none stroke-gray-400"
                                width="16"
                                height="14"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <rect x="3" y="5" width="18" height="14" rx="2"/>
                                <path d="m3 7 9 6 9-6"/>
                            </svg>

                            <a
                                class="min-w-0 break-all text-sm text-gray-600 hover:text-blue-700"
                                href="mailto:{{ $company->email }}"
                            >
                                {{ $company->email }}
                            </a>

                        </li>

                    @endif


                    @if ($company->phone)

                        <li class="flex items-start">

                            <svg
                                class="mr-3 mt-0.5 shrink-0 fill-none stroke-gray-400"
                                width="15"
                                height="15"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z"/>
                            </svg>

                            <a
                                class="text-sm text-gray-600 hover:text-blue-700"
                                href="tel:{{ $company->phone }}"
                            >
                                {{ $company->phone }}
                            </a>

                        </li>

                    @endif


                    @if ($company->categories->isNotEmpty())

                        <li class="flex items-start">

                            <svg
                                class="mr-3 mt-0.5 shrink-0 fill-none stroke-gray-400"
                                width="15"
                                height="15"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42Z"/>
                                <circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>
                            </svg>

                            <span class="text-sm leading-5 text-gray-600">
                                {{ $company->categories->pluck('name')->join(', ') }}
                            </span>

                        </li>

                    @endif

                </ul>

            </div>


            {{-- Primary CTA --}}
            @if ($company->website)

                <div class="mx-auto mb-5 max-w-xs">

                    <a
                        class="btn group w-full bg-blue-600 text-white shadow-xs hover:bg-blue-700"
                        href="{{ $company->website }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Bezoek website

                        <span class="ml-1 tracking-normal text-blue-200 transition-transform duration-150 ease-in-out group-hover:translate-x-0.5">
                            →
                        </span>
                    </a>

                </div>

            @endif


            {{-- Social links --}}
            @if ($hasSocials)

                <div class="border-t border-gray-200 pt-4 text-center">

                    <div class="flex flex-wrap justify-center gap-x-4 gap-y-2">

                        @if ($company->linkedin_url)
                            <a
                                class="text-sm font-medium text-blue-700 hover:underline"
                                href="{{ $company->linkedin_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                LinkedIn
                            </a>
                        @endif

                        @if ($company->facebook_url)
                            <a
                                class="text-sm font-medium text-blue-700 hover:underline"
                                href="{{ $company->facebook_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Facebook
                            </a>
                        @endif

                        @if ($company->instagram_url)
                            <a
                                class="text-sm font-medium text-blue-700 hover:underline"
                                href="{{ $company->instagram_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Instagram
                            </a>
                        @endif

                        @if ($company->video_url)
                            <a
                                class="text-sm font-medium text-blue-700 hover:underline"
                                href="{{ $company->video_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Video
                            </a>
                        @endif

                    </div>

                </div>

            @endif

        </div>

    </div>

</aside>

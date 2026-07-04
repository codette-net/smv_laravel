@extends('layouts.public')

@section('title')
    home
@endsection

@section('content')

    <main class="grow">

        <!-- Hero -->
        <section class="relative">

            <!-- Dark background -->
            <div
                class="absolute inset-0 bg-slate-900 pointer-events-none -z-10 [clip-path:polygon(0_0,_5760px_0,_5760px_calc(100%_-_352px),_0_100%)]"
                aria-hidden="true"></div>

            <div class="relative max-w-6xl mx-auto px-4 sm:px-6">
                <div class="pt-32 pb-20 md:pt-40 md:pb-44">

                    <!-- Hero content -->
                    <div
                        class="max-w-xl mx-auto md:max-w-none md:flex md:items-center md:space-x-8 lg:space-x-16 xl:space-x-20 space-y-16 md:space-y-0">

                        <!-- Content -->
                        <div class="text-center md:text-left md:min-w-[30rem]" data-aos="fade-right">
                            <p class="text-sm font-semibold uppercase tracking-widest text-blue-400 mb-3">
                                Sales & Marketing Vacatures
                            </p>

                            <h1 class="h1 font-playfair-display text-slate-100 mb-4">
                                Vind sales- en marketingtalent dat écht past
                            </h1>

                            <p class="text-xl text-slate-400 mb-8">
                                Salesenmarketingvacatures.nl brengt werkgevers, recruiters en ambitieuze kandidaten samen
                                binnen één gespecialiseerd recruitment platform voor sales, marketing en commerciële functies.
                            </p>

                            <div
                                class="max-w-xs mx-auto sm:max-w-none sm:flex sm:justify-center md:justify-start space-y-4 sm:space-y-0 sm:space-x-4">
                                <div>
                                    <a class="btn text-white bg-blue-600 hover:bg-blue-700 w-full group"
                                       href="/vacature-plaatsen">
                                        Plaats een vacature
                                        <span
                                            class="tracking-normal text-blue-300 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>
                                    </a>
                                </div>
                                <div>
                                    <a class="btn text-white bg-slate-700 hover:bg-slate-800 w-full"
                                       href="/vacatures">
                                        Bekijk vacatures
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Hero image -->
                        <div data-aos="fade-left">
                            <div class="flex justify-center items-center">
                                <div class="relative">
                                    <div
                                        class="absolute inset-0 pointer-events-none border-2 border-slate-700 mt-3 ml-3 translate-x-4 translate-y-4 -z-10"
                                        aria-hidden="true"></div>

                                    <img class="mx-auto md:max-w-none"
                                         src="./images/hero-image-01.jpg"
                                         width="540"
                                         height="405"
                                         alt="Werkgevers en kandidaten binnen sales en marketing"/>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- job listings and sidbar -->

        <section>
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <div class="py-8 md:py-16">

                    <div class="md:flex md:justify-between">

                        <!-- Sidebar -->
                        <aside class="mb-8 md:mb-0 md:w-64 lg:w-72 md:ml-12 lg:ml-20 md:shrink-0 md:order-1">
                            <div class="sticky top-8">

                                <div class="relative bg-gray-50 rounded-xl border border-gray-200 p-5">

                                    <div class="absolute top-5 right-5 leading-none">
                                        <button class="text-sm font-medium text-indigo-500 hover:underline">Clear
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-2 md:grid-cols-1 gap-6">
                                        <!-- Group 1 -->
                                        <div>
                                            <div class="text-sm text-gray-800 font-semibold mb-3">Job Type</div>
                                            <ul class="space-y-2">
                                                <li>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" class="form-checkbox"/>
                                                        <span class="text-sm text-gray-600 ml-2">Full-time</span>
                                                    </label>
                                                </li>
                                                <li>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" class="form-checkbox"/>
                                                        <span class="text-sm text-gray-600 ml-2">Part-time</span>
                                                    </label>
                                                </li>
                                                <li>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" class="form-checkbox"/>
                                                        <span class="text-sm text-gray-600 ml-2">Intership</span>
                                                    </label>
                                                </li>
                                                <li>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" class="form-checkbox"/>
                                                        <span
                                                            class="text-sm text-gray-600 ml-2">Contract / Freelance</span>
                                                    </label>
                                                </li>
                                                <li>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" class="form-checkbox"/>
                                                        <span class="text-sm text-gray-600 ml-2">Co-founder</span>
                                                    </label>
                                                </li>
                                            </ul>
                                        </div>
                                        <!-- Group 2 -->
                                        <div>
                                            <div class="text-sm text-gray-800 font-semibold mb-3">Job Roles</div>
                                            <ul class="space-y-2">
                                                <li>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" class="form-checkbox" checked/>
                                                        <span class="text-sm text-gray-600 ml-2">Programming</span>
                                                    </label>
                                                </li>
                                                <li>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" class="form-checkbox"/>
                                                        <span class="text-sm text-gray-600 ml-2">Design</span>
                                                    </label>
                                                </li>
                                                <li>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" class="form-checkbox"/>
                                                        <span
                                                            class="text-sm text-gray-600 ml-2">Management / Finance</span>
                                                    </label>
                                                </li>
                                                <li>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" class="form-checkbox"/>
                                                        <span class="text-sm text-gray-600 ml-2">Customer Support</span>
                                                    </label>
                                                </li>
                                                <li>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" class="form-checkbox"/>
                                                        <span
                                                            class="text-sm text-gray-600 ml-2">Sales / Marketing</span>
                                                    </label>
                                                </li>
                                            </ul>
                                        </div>
                                        <!-- Group 3 -->
                                        <div>
                                            <div class="text-sm text-gray-800 font-semibold mb-3">Remote Only</div>
                                            <div class="flex items-center" x-data="{ checked: false }">
                                                <div class="form-switch">
                                                    <input type="checkbox" id="remote-toggle" class="sr-only"
                                                           x-model="checked"/>
                                                    <label for="remote-toggle">
                                                        <span class="bg-white shadow-xs" aria-hidden="true"></span>
                                                        <span class="sr-only">Remote Only</span>
                                                    </label>
                                                </div>
                                                <div class="text-sm text-gray-400 italic ml-2"
                                                     x-text="checked ? 'On' : 'Off'"></div>
                                            </div>
                                        </div>
                                        <!-- Group 3 -->
                                        <div>
                                            <div class="text-sm text-gray-800 font-semibold mb-3">Salary Range</div>
                                            <ul class="space-y-2">
                                                <li>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" class="form-checkbox"/>
                                                        <span class="text-sm text-gray-600 ml-2">$20K - $50K</span>
                                                    </label>
                                                </li>
                                                <li>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" class="form-checkbox"/>
                                                        <span class="text-sm text-gray-600 ml-2">$50K - $100K</span>
                                                    </label>
                                                </li>
                                                <li>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" class="form-checkbox"/>
                                                        <span class="text-sm text-gray-600 ml-2">&gt; $100K</span>
                                                    </label>
                                                </li>
                                                <li>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" class="form-checkbox"/>
                                                        <span
                                                            class="text-sm text-gray-600 ml-2">Drawing / Painting</span>
                                                    </label>
                                                </li>
                                            </ul>
                                        </div>
                                        <!-- Group 4 -->
                                        <div>
                                            <div class="text-sm text-gray-800 font-semibold mb-3">Location</div>
                                            <label class="sr-only">Location</label>
                                            <select class="form-select w-full">
                                                <option>Anywhere</option>
                                                <option>London</option>
                                                <option>San Francisco</option>
                                                <option>New York</option>
                                                <option>Berlin</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </aside>

                        <!-- Main content -->
                        <div class="md:grow">

                            <!-- Job list -->
                            <div class="pb-8 md:pb-16">
                                <h2 class="text-3xl font-bold font-inter mb-10">Latest jobs</h2>
                                <!-- List container -->
                                <div class="flex flex-col">
                                    lala
                                    @foreach($vacancies as $vac)
                                        :

                                        <!-- Item -->
                                        <div class="nth-[-n+12]:-order-1 group">
                                            <div @class(['bg-indigo-100' => $vac->is_featured,'px-4 py-6 rounded-xl']) >
                                                <div class="sm:flex items-center space-y-3 sm:space-y-0 sm:space-x-5">
                                                    <div
                                                        class="shrink-1 w-12 h-12 rounded-full object-cover object-center">
                                                        <img src="{{ $vac->company->logo }}" width="56" height="56"
                                                             alt="{{ $vac->company->name }}"
                                                             class="h-[56px] max-h-12 max-w-12 rounded-full object-cover object-center"/>
                                                    </div>
                                                    <div
                                                        class="grow lg:flex items-center justify-between space-y-5 lg:space-x-2 lg:space-y-0">
                                                        <div>
                                                            <div class="flex items-start space-x-2">
                                                                <div
                                                                    class="text-sm text-gray-800 font-semibold mb-1">{{ $vac->company->name }}</div>
                                                                @if($vac->is_featured)
                                                                    <svg class="w-3 h-3 shrink-0 fill-amber-400"
                                                                         viewBox="0 0 12 12"
                                                                         xmlns="http://www.w3.org/2000/svg">
                                                                        <path
                                                                            d="M11.143 5.143A4.29 4.29 0 0 1 6.857.857a.857.857 0 0 0-1.714 0A4.29 4.29 0 0 1 .857 5.143a.857.857 0 0 0 0 1.714 4.29 4.29 0 0 1 4.286 4.286.857.857 0 0 0 1.714 0 4.29 4.29 0 0 1 4.286-4.286.857.857 0 0 0 0-1.714Z"/>
                                                                    </svg>
                                                                @endif
                                                            </div>
                                                            <div class="mb-2">
                                                                <a class="text-lg text-gray-800 font-bold"
                                                                   href="/vacature/{{ $vac->slug }}">{{ $vac->title }}</a>
                                                            </div>
                                                            <div class="-m-1">
                                                                <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-indigo-50 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out"
                                                                   href="#0">
                                                                    &euro;{{ $vac->salary_min . ' - €' . $vac->salary_max }}
                                                                </a>
                                                                <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-indigo-50 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out"
                                                                   href="#0">{{ $vac->location }}</a>
                                                                <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out"
                                                                   href="#0">Full time</a>
                                                                <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out"
                                                                   href="#0">🌎 Remote</a>
                                                            </div>
                                                        </div>
                                                        <div
                                                            class="min-w-[120px] flex items-center lg:justify-end space-x-3 lg:space-x-0">
                                                            <div class="lg:hidden lg:group-hover:block">
                                                                <a class="btn-sm py-1.5 px-3 text-white bg-indigo-500 hover:bg-indigo-600 group shadow-xs"
                                                                   href="{{ route('vacatures.show', $vac) }}">
                                                                    Lees meer <span
                                                                        class="tracking-normal text-indigo-200 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>
                                                                </a>
                                                            </div>
                                                            <div
                                                                class="lg:group-hover:hidden text-sm italic text-gray-500">{{ \Carbon\Carbon::parse($vac->created_at)->diffForHumans() }}

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    @endforeach
                                    <!-- Item -->
                                    {{--                                    <div class="nth-[-n+12]:-order-1 border-b border-gray-200 group">--}}
                                    {{--                                        <div class="px-4 py-6">--}}
                                    {{--                                            <div class="sm:flex items-center space-y-3 sm:space-y-0 sm:space-x-5">--}}
                                    {{--                                                <div class="shrink-0">--}}
                                    {{--                                                    <img src="./images/company-icon-02.svg" width="56" height="56" alt="Company 02" />--}}
                                    {{--                                                </div>--}}
                                    {{--                                                <div class="grow lg:flex items-center justify-between space-y-5 lg:space-x-2 lg:space-y-0">--}}
                                    {{--                                                    <div>--}}
                                    {{--                                                        <div class="flex items-start space-x-2">--}}
                                    {{--                                                            <div class="text-sm text-gray-800 font-semibold mb-1">Vimeo</div>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="mb-2">--}}
                                    {{--                                                            <a class="text-lg text-gray-800 font-bold" href="job-post.html">Software Engineer Backend</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="-m-1">--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">Full Time</a>--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">🌎 Remote</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                    <div class="min-w-[120px] flex items-center lg:justify-end space-x-3 lg:space-x-0">--}}
                                    {{--                                                        <div class="lg:hidden lg:group-hover:block">--}}
                                    {{--                                                            <a class="btn-sm py-1.5 px-3 text-white bg-indigo-500 hover:bg-indigo-600 group shadow-xs" href="job-post.html">--}}
                                    {{--                                                                Apply Now <span class="tracking-normal text-indigo-200 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>--}}
                                    {{--                                                            </a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="lg:group-hover:hidden text-sm italic text-gray-500">2h</div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                </div>--}}
                                    {{--                                            </div>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}
                                    {{--                                    <!-- Item -->--}}
                                    {{--                                    <div class="nth-[-n+12]:-order-1 border-b border-gray-200 group">--}}
                                    {{--                                        <div class="px-4 py-6">--}}
                                    {{--                                            <div class="sm:flex items-center space-y-3 sm:space-y-0 sm:space-x-5">--}}
                                    {{--                                                <div class="shrink-0">--}}
                                    {{--                                                    <img src="./images/company-icon-03.svg" width="56" height="56" alt="Company 03" />--}}
                                    {{--                                                </div>--}}
                                    {{--                                                <div class="grow lg:flex items-center justify-between space-y-5 lg:space-x-2 lg:space-y-0">--}}
                                    {{--                                                    <div>--}}
                                    {{--                                                        <div class="flex items-start space-x-2">--}}
                                    {{--                                                            <div class="text-sm text-gray-800 font-semibold mb-1">Robinhood</div>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="mb-2">--}}
                                    {{--                                                            <a class="text-lg text-gray-800 font-bold" href="job-post.html">Senior Site Reliability Engineer</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="-m-1">--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">Full Time</a>--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">🌎 Remote</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                    <div class="min-w-[120px] flex items-center lg:justify-end space-x-3 lg:space-x-0">--}}
                                    {{--                                                        <div class="lg:hidden lg:group-hover:block">--}}
                                    {{--                                                            <a class="btn-sm py-1.5 px-3 text-white bg-indigo-500 hover:bg-indigo-600 group shadow-xs" href="job-post.html">--}}
                                    {{--                                                                Apply Now <span class="tracking-normal text-indigo-200 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>--}}
                                    {{--                                                            </a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="lg:group-hover:hidden text-sm italic text-gray-500">3h</div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                </div>--}}
                                    {{--                                            </div>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}
                                    {{--                                    <!-- Item -->--}}
                                    {{--                                    <div class="nth-[-n+12]:-order-1 border-b border-gray-200 group">--}}
                                    {{--                                        <div class="px-4 py-6">--}}
                                    {{--                                            <div class="sm:flex items-center space-y-3 sm:space-y-0 sm:space-x-5">--}}
                                    {{--                                                <div class="shrink-0">--}}
                                    {{--                                                    <img src="./images/company-icon-04.svg" width="56" height="56" alt="Company 04" />--}}
                                    {{--                                                </div>--}}
                                    {{--                                                <div class="grow lg:flex items-center justify-between space-y-5 lg:space-x-2 lg:space-y-0">--}}
                                    {{--                                                    <div>--}}
                                    {{--                                                        <div class="flex items-start space-x-2">--}}
                                    {{--                                                            <div class="text-sm text-gray-800 font-semibold mb-1">GitHub</div>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="mb-2">--}}
                                    {{--                                                            <a class="text-lg text-gray-800 font-bold" href="job-post.html">Remote Shopify Website Tester</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="-m-1">--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">$100K - $170K</a>--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">🇺🇸 NYC</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                    <div class="min-w-[120px] flex items-center lg:justify-end space-x-3 lg:space-x-0">--}}
                                    {{--                                                        <div class="lg:hidden lg:group-hover:block">--}}
                                    {{--                                                            <a class="btn-sm py-1.5 px-3 text-white bg-indigo-500 hover:bg-indigo-600 group shadow-xs" href="job-post.html">--}}
                                    {{--                                                                Apply Now <span class="tracking-normal text-indigo-200 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>--}}
                                    {{--                                                            </a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="lg:group-hover:hidden text-sm italic text-gray-500">4h</div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                </div>--}}
                                    {{--                                            </div>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}
                                    {{--                                    <!-- Item -->--}}
                                    {{--                                    <div class="nth-[-n+12]:-order-1 border-b border-gray-200 group">--}}
                                    {{--                                        <div class="px-4 py-6">--}}
                                    {{--                                            <div class="sm:flex items-center space-y-3 sm:space-y-0 sm:space-x-5">--}}
                                    {{--                                                <div class="shrink-0">--}}
                                    {{--                                                    <img src="./images/company-icon-04.svg" width="56" height="56" alt="Company 04" />--}}
                                    {{--                                                </div>--}}
                                    {{--                                                <div class="grow lg:flex items-center justify-between space-y-5 lg:space-x-2 lg:space-y-0">--}}
                                    {{--                                                    <div>--}}
                                    {{--                                                        <div class="flex items-start space-x-2">--}}
                                    {{--                                                            <div class="text-sm text-gray-800 font-semibold mb-1">GitHub</div>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="mb-2">--}}
                                    {{--                                                            <a class="text-lg text-gray-800 font-bold" href="job-post.html">Remote Senior Software Engineer</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="-m-1">--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">$100K - $170K</a>--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">🇺🇸 NYC</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                    <div class="min-w-[120px] flex items-center lg:justify-end space-x-3 lg:space-x-0">--}}
                                    {{--                                                        <div class="lg:hidden lg:group-hover:block">--}}
                                    {{--                                                            <a class="btn-sm py-1.5 px-3 text-white bg-indigo-500 hover:bg-indigo-600 group shadow-xs" href="job-post.html">--}}
                                    {{--                                                                Apply Now <span class="tracking-normal text-indigo-200 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>--}}
                                    {{--                                                            </a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="lg:group-hover:hidden text-sm italic text-gray-500">7h</div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                </div>--}}
                                    {{--                                            </div>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}
                                    {{--                                    <!-- Item -->--}}
                                    {{--                                    <div class="nth-[-n+12]:-order-1 border-b border-gray-200 group">--}}
                                    {{--                                        <div class="px-4 py-6">--}}
                                    {{--                                            <div class="sm:flex items-center space-y-3 sm:space-y-0 sm:space-x-5">--}}
                                    {{--                                                <div class="shrink-0">--}}
                                    {{--                                                    <img src="./images/company-icon-01.svg" width="56" height="56" alt="Company 01" />--}}
                                    {{--                                                </div>--}}
                                    {{--                                                <div class="grow lg:flex items-center justify-between space-y-5 lg:space-x-2 lg:space-y-0">--}}
                                    {{--                                                    <div>--}}
                                    {{--                                                        <div class="flex items-start space-x-2">--}}
                                    {{--                                                            <div class="text-sm text-gray-800 font-semibold mb-1">Qonto</div>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="mb-2">--}}
                                    {{--                                                            <a class="text-lg text-gray-800 font-bold" href="job-post.html">Senior Web App Designer</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="-m-1">--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">Contract</a>--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">🇬🇧 London, UK</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                    <div class="min-w-[120px] flex items-center lg:justify-end space-x-3 lg:space-x-0">--}}
                                    {{--                                                        <div class="lg:hidden lg:group-hover:block">--}}
                                    {{--                                                            <a class="btn-sm py-1.5 px-3 text-white bg-indigo-500 hover:bg-indigo-600 group shadow-xs" href="job-post.html">--}}
                                    {{--                                                                Apply Now <span class="tracking-normal text-indigo-200 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>--}}
                                    {{--                                                            </a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="lg:group-hover:hidden text-sm italic text-gray-500">12h</div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                </div>--}}
                                    {{--                                            </div>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}
                                    {{--                                    <!-- Item -->--}}
                                    {{--                                    <div class="nth-[-n+12]:-order-1 border-b border-gray-200 group">--}}
                                    {{--                                        <div class="px-4 py-6">--}}
                                    {{--                                            <div class="sm:flex items-center space-y-3 sm:space-y-0 sm:space-x-5">--}}
                                    {{--                                                <div class="shrink-0">--}}
                                    {{--                                                    <img src="./images/company-icon-05.svg" width="56" height="56" alt="Company 05" />--}}
                                    {{--                                                </div>--}}
                                    {{--                                                <div class="grow lg:flex items-center justify-between space-y-5 lg:space-x-2 lg:space-y-0">--}}
                                    {{--                                                    <div>--}}
                                    {{--                                                        <div class="flex items-start space-x-2">--}}
                                    {{--                                                            <div class="text-sm text-gray-800 font-semibold mb-1">Revolut</div>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="mb-2">--}}
                                    {{--                                                            <a class="text-lg text-gray-800 font-bold" href="job-post.html">Ruby on Rails Engineer</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="-m-1">--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">Full Time</a>--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">🌎 Remote</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                    <div class="min-w-[120px] flex items-center lg:justify-end space-x-3 lg:space-x-0">--}}
                                    {{--                                                        <div class="lg:hidden lg:group-hover:block">--}}
                                    {{--                                                            <a class="btn-sm py-1.5 px-3 text-white bg-indigo-500 hover:bg-indigo-600 group shadow-xs" href="job-post.html">--}}
                                    {{--                                                                Apply Now <span class="tracking-normal text-indigo-200 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>--}}
                                    {{--                                                            </a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="lg:group-hover:hidden text-sm italic text-gray-500">12h</div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                </div>--}}
                                    {{--                                            </div>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}
                                    {{--                                    <!-- Item -->--}}
                                    {{--                                    <div class="nth-[-n+12]:-order-1 border-b border-gray-200 group">--}}
                                    {{--                                        <div class="px-4 py-6">--}}
                                    {{--                                            <div class="sm:flex items-center space-y-3 sm:space-y-0 sm:space-x-5">--}}
                                    {{--                                                <div class="shrink-0">--}}
                                    {{--                                                    <img src="./images/company-icon-06.svg" width="56" height="56" alt="Company 06" />--}}
                                    {{--                                                </div>--}}
                                    {{--                                                <div class="grow lg:flex items-center justify-between space-y-5 lg:space-x-2 lg:space-y-0">--}}
                                    {{--                                                    <div>--}}
                                    {{--                                                        <div class="flex items-start space-x-2">--}}
                                    {{--                                                            <div class="text-sm text-gray-800 font-semibold mb-1">HSBC</div>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="mb-2">--}}
                                    {{--                                                            <a class="text-lg text-gray-800 font-bold" href="job-post.html">Senior Software Engineer Backend</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="-m-1">--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">Full Time</a>--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">🇮🇹 Milan, IT</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                    <div class="min-w-[120px] flex items-center lg:justify-end space-x-3 lg:space-x-0">--}}
                                    {{--                                                        <div class="lg:hidden lg:group-hover:block">--}}
                                    {{--                                                            <a class="btn-sm py-1.5 px-3 text-white bg-indigo-500 hover:bg-indigo-600 group shadow-xs" href="job-post.html">--}}
                                    {{--                                                                Apply Now <span class="tracking-normal text-indigo-200 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>--}}
                                    {{--                                                            </a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="lg:group-hover:hidden text-sm italic text-gray-500">20h</div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                </div>--}}
                                    {{--                                            </div>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}
                                    {{--                                    <!-- Item -->--}}
                                    {{--                                    <div class="nth-[-n+12]:-order-1 border-b border-gray-200 group">--}}
                                    {{--                                        <div class="px-4 py-6">--}}
                                    {{--                                            <div class="sm:flex items-center space-y-3 sm:space-y-0 sm:space-x-5">--}}
                                    {{--                                                <div class="shrink-0">--}}
                                    {{--                                                    <img src="./images/company-icon-07.svg" width="56" height="56" alt="Company 07" />--}}
                                    {{--                                                </div>--}}
                                    {{--                                                <div class="grow lg:flex items-center justify-between space-y-5 lg:space-x-2 lg:space-y-0">--}}
                                    {{--                                                    <div>--}}
                                    {{--                                                        <div class="flex items-start space-x-2">--}}
                                    {{--                                                            <div class="text-sm text-gray-800 font-semibold mb-1">Twitter</div>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="mb-2">--}}
                                    {{--                                                            <a class="text-lg text-gray-800 font-bold" href="job-post.html">React.js Software Developer</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="-m-1">--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">Full Time</a>--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">🌎 Remote</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                    <div class="min-w-[120px] flex items-center lg:justify-end space-x-3 lg:space-x-0">--}}
                                    {{--                                                        <div class="lg:hidden lg:group-hover:block">--}}
                                    {{--                                                            <a class="btn-sm py-1.5 px-3 text-white bg-indigo-500 hover:bg-indigo-600 group shadow-xs" href="job-post.html">--}}
                                    {{--                                                                Apply Now <span class="tracking-normal text-indigo-200 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>--}}
                                    {{--                                                            </a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="lg:group-hover:hidden text-sm italic text-gray-500">1d</div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                </div>--}}
                                    {{--                                            </div>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}
                                    {{--                                    <!-- Item -->--}}
                                    {{--                                    <div class="nth-[-n+12]:-order-1 border-b border-gray-200 group">--}}
                                    {{--                                        <div class="px-4 py-6">--}}
                                    {{--                                            <div class="sm:flex items-center space-y-3 sm:space-y-0 sm:space-x-5">--}}
                                    {{--                                                <div class="shrink-0">--}}
                                    {{--                                                    <img src="./images/company-icon-08.svg" width="56" height="56" alt="Company 08" />--}}
                                    {{--                                                </div>--}}
                                    {{--                                                <div class="grow lg:flex items-center justify-between space-y-5 lg:space-x-2 lg:space-y-0">--}}
                                    {{--                                                    <div>--}}
                                    {{--                                                        <div class="flex items-start space-x-2">--}}
                                    {{--                                                            <div class="text-sm text-gray-800 font-semibold mb-1">Medium</div>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="mb-2">--}}
                                    {{--                                                            <a class="text-lg text-gray-800 font-bold" href="job-post.html">Senior Client Engineer (React & React Native)</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="-m-1">--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">$55K - $100K</a>--}}
                                    {{--                                                            <a class="text-xs text-gray-500 font-medium inline-flex px-2 py-0.5 bg-gray-100 hover:text-gray-600 rounded-md m-1 whitespace-nowrap transition duration-150 ease-in-out" href="#0">🌎 Remote</a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                    <div class="min-w-[120px] flex items-center lg:justify-end space-x-3 lg:space-x-0">--}}
                                    {{--                                                        <div class="lg:hidden lg:group-hover:block">--}}
                                    {{--                                                            <a class="btn-sm py-1.5 px-3 text-white bg-indigo-500 hover:bg-indigo-600 group shadow-xs" href="job-post.html">--}}
                                    {{--                                                                Apply Now <span class="tracking-normal text-indigo-200 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>--}}
                                    {{--                                                            </a>--}}
                                    {{--                                                        </div>--}}
                                    {{--                                                        <div class="lg:group-hover:hidden text-sm italic text-gray-500">1d</div>--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                </div>--}}
                                    {{--                                            </div>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}

                                    <!-- Newletter CTA -->
                                    <div class="py-8 border-b border-gray-200 -order-1">
                                        <div class="relative text-center px-4 py-6 group">
                                            <div
                                                class="absolute inset-0 rounded-xl bg-gray-50 border border-gray-200 -rotate-1 group-hover:rotate-0 transition duration-150 ease-in-out -z-10"
                                                aria-hidden="true"></div>
                                            <div class="font-nycd text-xl text-indigo-500 mb-1">Land your dream job
                                            </div>
                                            <div class="text-2xl font-bold mb-5">Get a weekly email with the latest
                                                startup jobs.
                                            </div>
                                            <form class="inline-flex max-w-sm">
                                                <div
                                                    class="flex flex-col sm:flex-row justify-center max-w-xs mx-auto sm:max-w-none">
                                                    <input type="email"
                                                           class="form-input py-1.5 w-full mb-2 sm:mb-0 sm:mr-2"
                                                           placeholder="Your email" aria-label="Your email"/>
                                                    <button
                                                        class="btn-sm text-white bg-indigo-500 hover:bg-indigo-600 shadow-xs whitespace-nowrap"
                                                        type="submit">Join Newsletter
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Testimonials -->
                            <div>
                                <h2 class="text-3xl font-bold font-inter mb-10">Our customers love us</h2>
                                <!-- Testimonials container -->
                                <div class="space-y-10">
                                    <!-- Item -->
                                    <div
                                        class="p-5 rounded-xl bg-teal-50 border border-teal-200 odd:rotate-1 even:-rotate-1 hover:rotate-0 transition duration-150 ease-in-out">
                                        <div class="flex items-center space-x-5">
                                            <div class="relative shrink-0">
                                                <img class="rounded-full" src="./images/testimonial-01.jpg" width="102"
                                                     height="102" alt="Testimonial 01"/>
                                                <svg class="absolute top-0 right-0 fill-indigo-400" width="26"
                                                     height="17" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M0 16.026h8.092l6.888-16h-4.592L0 16.026Zm11.02 0h8.092L26 .026h-4.65l-10.33 16Z"/>
                                                </svg>
                                            </div>
                                            <figure>
                                                <blockquote class="text-lg font-bold m-0 pb-1">
                                                    <p>Hiring a Senior Laravel engineer through JobBoard has been
                                                        incredible. The best job board experience we've ever had.</p>
                                                </blockquote>
                                                <figcaption class="text-sm font-medium">Patrick Metzger, CEO <a
                                                        class="text-teal-500 hover:underline" href="#0">App.com</a>
                                                </figcaption>
                                            </figure>
                                        </div>
                                    </div>
                                    <!-- Item -->
                                    <div
                                        class="p-5 rounded-xl bg-sky-50 border border-sky-200 odd:rotate-1 even:-rotate-1 hover:rotate-0 transition duration-150 ease-in-out">
                                        <div class="flex items-center space-x-5">
                                            <div class="relative shrink-0">
                                                <img class="rounded-full" src="./images/testimonial-02.jpg" width="102"
                                                     height="102" alt="Testimonial 02"/>
                                                <svg class="absolute top-0 right-0 fill-indigo-400" width="26"
                                                     height="17" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M0 16.026h8.092l6.888-16h-4.592L0 16.026Zm11.02 0h8.092L26 .026h-4.65l-10.33 16Z"/>
                                                </svg>
                                            </div>
                                            <figure>
                                                <blockquote class="text-lg font-bold m-0 pb-1">
                                                    <p>Hiring a Senior Laravel engineer through JobBoard has been
                                                        incredible. The best job board experience we've ever had.</p>
                                                </blockquote>
                                                <figcaption class="text-sm font-medium">Annie Patrick, CEO <a
                                                        class="text-sky-500 hover:underline" href="#0">TrueThing</a>
                                                </figcaption>
                                            </figure>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>
            </div>
        </section>


        <!-- Features blocks -->
        <section>
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <div class="py-12 md:py-20">

                    <!-- Items -->
                    <div
                        class="relative max-w-sm mx-auto grid gap-16 md:grid-cols-2 lg:grid-cols-3 lg:gap-y-20 items-start md:max-w-2xl lg:max-w-none"
                        data-aos-id-blocks>

                        <!-- Lines decoration -->
                        <div class="absolute inset-0 -my-8 md:-my-12 pointer-events-none hidden md:flex"
                             aria-hidden="true">
                            <div
                                class="h-full w-full border-l last:border-r odd:hidden lg:odd:block border-slate-100"></div>
                            <div
                                class="h-full w-full border-l last:border-r odd:hidden lg:odd:block border-slate-100"></div>
                            <div
                                class="h-full w-full border-l last:border-r odd:hidden lg:odd:block border-slate-100"></div>
                            <div
                                class="h-full w-full border-l last:border-r odd:hidden lg:odd:block border-slate-100"></div>
                        </div>

                        <!-- 1st item -->
                        <div class="relative" data-aos="fade-up" data-aos-anchor="[data-aos-id-blocks]">
                            <svg class="w-16 h-16 mb-4" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink">
                                <defs>
                                    <filter x="-17.5%" y="-10.4%" width="135%" height="129.2%"
                                            filterUnits="objectBoundingBox" id="fb1-a">
                                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/>
                                        <feGaussianBlur stdDeviation="1" in="shadowOffsetOuter1"
                                                        result="shadowBlurOuter1"/>
                                        <feColorMatrix
                                            values="0 0 0 0 0.062745098 0 0 0 0 0.11372549 0 0 0 0 0.176470588 0 0 0 0.12 0"
                                            in="shadowBlurOuter1"/>
                                    </filter>
                                    <filter x="-43.8%" y="-31.2%" width="187.5%" height="187.5%"
                                            filterUnits="objectBoundingBox" id="fb1-d">
                                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/>
                                        <feGaussianBlur stdDeviation="1" in="shadowOffsetOuter1"
                                                        result="shadowBlurOuter1"/>
                                        <feComposite in="shadowBlurOuter1" in2="SourceAlpha" operator="out"
                                                     result="shadowBlurOuter1"/>
                                        <feColorMatrix
                                            values="0 0 0 0 0.062745098 0 0 0 0 0.11372549 0 0 0 0 0.176470588 0 0 0 0.12 0"
                                            in="shadowBlurOuter1"/>
                                    </filter>
                                    <path d="M35 20H24a1 1 0 00-1 1v22a1 1 0 001 1h18a1 1 0 001-1V28h-8v-8z"
                                          id="fb1-b"/>
                                    <path id="fb1-e" d="M35 20v8h8z"/>
                                    <linearGradient x1="50%" y1="0%" x2="50%" y2="100%" id="fb1-c">
                                        <stop stop-color="#FFF" offset="0%"/>
                                        <stop stop-color="#E2EEFF" offset="100%"/>
                                    </linearGradient>
                                </defs>
                                <rect class="fill-current text-blue-600" width="64" height="64" rx="32"/>
                                <use fill="#000" filter="url(#fb1-a)" xlink:href="#fb1-b"/>
                                <use fill="url(#fb1-c)" xlink:href="#fb1-b"/>
                                <use fill="#000" filter="url(#fb1-d)" xlink:href="#fb1-e"/>
                                <use fill-opacity=".64" fill="#E2EEFF" xlink:href="#fb1-e"/>
                            </svg>
                            <h3 class="h4 font-playfair-display mb-2">Robust Workflow</h3>
                            <p class="text-lg text-slate-500">Duis aute irure dolor in reprehenderit in voluptate velit
                                esse cillum dolore eu fugiat nulla pariatur.</p>
                        </div>

                        <!-- 2nd item -->
                        <div class="relative" data-aos="fade-up" data-aos-anchor="[data-aos-id-blocks]"
                             data-aos-delay="100">
                            <svg class="w-16 h-16 mb-4" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink">
                                <defs>
                                    <filter x="-12.1%" y="-13.2%" width="124.1%" height="136.8%"
                                            filterUnits="objectBoundingBox" id="fb2-a">
                                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/>
                                        <feGaussianBlur stdDeviation="1" in="shadowOffsetOuter1"
                                                        result="shadowBlurOuter1"/>
                                        <feComposite in="shadowBlurOuter1" in2="SourceAlpha" operator="out"
                                                     result="shadowBlurOuter1"/>
                                        <feColorMatrix
                                            values="0 0 0 0 0.062745098 0 0 0 0 0.11372549 0 0 0 0 0.176470588 0 0 0 0.12 0"
                                            in="shadowBlurOuter1"/>
                                    </filter>
                                    <filter x="-12.1%" y="-13.2%" width="124.1%" height="136.8%"
                                            filterUnits="objectBoundingBox" id="fb2-c">
                                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/>
                                        <feGaussianBlur stdDeviation="1" in="shadowOffsetOuter1"
                                                        result="shadowBlurOuter1"/>
                                        <feColorMatrix
                                            values="0 0 0 0 0.062745098 0 0 0 0 0.11372549 0 0 0 0 0.176470588 0 0 0 0.12 0"
                                            in="shadowBlurOuter1"/>
                                    </filter>
                                    <path
                                        d="M46 23H19c-.552 0-1 .424-1 .947v17.106c0 .523.448.947 1 .947h27c.552 0 1-.424 1-.947V23.947c0-.523-.448-.947-1-.947z"
                                        id="fb2-b"/>
                                    <path
                                        d="M46 23H19c-.552 0-1 .424-1 .947v17.106c0 .523.448.947 1 .947h27c.552 0 1-.424 1-.947V23.947c0-.523-.448-.947-1-.947z"
                                        id="fb2-d"/>
                                    <linearGradient x1="50%" y1="0%" x2="50%" y2="100%" id="fb2-e">
                                        <stop stop-color="#FFF" offset="0%"/>
                                        <stop stop-color="#E2EEFF" offset="100%"/>
                                    </linearGradient>
                                </defs>
                                <rect class="fill-current text-blue-600" width="64" height="64" rx="32"/>
                                <g transform="rotate(25 32.5 32.5)">
                                    <use fill="#000" filter="url(#fb2-a)" xlink:href="#fb2-b"/>
                                    <use fill-opacity=".64" fill="#E2EEFF" xlink:href="#fb2-b"/>
                                </g>
                                <g>
                                    <use fill="#000" filter="url(#fb2-c)" xlink:href="#fb2-d"/>
                                    <use fill="url(#fb2-e)" xlink:href="#fb2-d"/>
                                </g>
                                <path d="M32.5 36a3.5 3.5 0 110-7 3.5 3.5 0 010 7z" fill-opacity=".64" fill="#5091EE"/>
                            </svg>
                            <h3 class="h4 font-playfair-display mb-2">Robust Workflow</h3>
                            <p class="text-lg text-slate-500">Duis aute irure dolor in reprehenderit in voluptate velit
                                esse cillum dolore eu fugiat nulla pariatur.</p>
                        </div>

                        <!-- 3rd item -->
                        <div class="relative" data-aos="fade-up" data-aos-anchor="[data-aos-id-blocks]"
                             data-aos-delay="200">
                            <svg class="w-16 h-16 mb-4" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink">
                                <defs>
                                    <filter x="-58.3%" y="-17.9%" width="216.7%" height="150%"
                                            filterUnits="objectBoundingBox" id="fb3-a">
                                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/>
                                        <feGaussianBlur stdDeviation="1" in="shadowOffsetOuter1"
                                                        result="shadowBlurOuter1"/>
                                        <feComposite in="shadowBlurOuter1" in2="SourceAlpha" operator="out"
                                                     result="shadowBlurOuter1"/>
                                        <feColorMatrix
                                            values="0 0 0 0 0.062745098 0 0 0 0 0.11372549 0 0 0 0 0.176470588 0 0 0 0.12 0"
                                            in="shadowBlurOuter1"/>
                                    </filter>
                                    <filter x="-19.4%" y="-10.9%" width="138.9%" height="130.4%"
                                            filterUnits="objectBoundingBox" id="fb3-c">
                                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/>
                                        <feGaussianBlur stdDeviation="1" in="shadowOffsetOuter1"
                                                        result="shadowBlurOuter1"/>
                                        <feColorMatrix
                                            values="0 0 0 0 0.062745098 0 0 0 0 0.11372549 0 0 0 0 0.176470588 0 0 0 0.12 0"
                                            in="shadowBlurOuter1"/>
                                    </filter>
                                    <path d="M27 29h-4a1 1 0 00-1 1v12a1 1 0 001 1h5V30a1 1 0 00-1-1z" id="fb3-b"/>
                                    <path
                                        d="M43.882 28.133A2.986 2.986 0 0043 28h-6v-3c0-3.824-2.589-4.942-3.958-5A1 1 0 0032 21v4.638l-4 4.8V43h12.23a2.985 2.985 0 002.87-2.118l2.769-9a3 3 0 00-1.987-3.749z"
                                        id="fb3-d"/>
                                    <linearGradient x1="50%" y1="0%" x2="50%" y2="100%" id="fb3-e">
                                        <stop stop-color="#FFF" offset="0%"/>
                                        <stop stop-color="#E2EEFF" offset="100%"/>
                                    </linearGradient>
                                </defs>
                                <rect class="fill-current text-blue-600" width="64" height="64" rx="32"/>
                                <use fill="#000" filter="url(#fb3-a)" xlink:href="#fb3-b"/>
                                <use fill-opacity=".64" fill="#E2EEFF" xlink:href="#fb3-b"/>
                                <use fill="#000" filter="url(#fb3-c)" xlink:href="#fb3-d"/>
                                <use fill="url(#fb3-e)" xlink:href="#fb3-d"/>
                            </svg>
                            <h3 class="h4 font-playfair-display mb-2">Robust Workflow</h3>
                            <p class="text-lg text-slate-500">Duis aute irure dolor in reprehenderit in voluptate velit
                                esse cillum dolore eu fugiat nulla pariatur.</p>
                        </div>

                        <!-- 4th item -->
                        <div class="relative" data-aos="fade-up" data-aos-anchor="[data-aos-id-blocks]"
                             data-aos-delay="300">
                            <svg class="w-16 h-16 mb-4" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink">
                                <defs>
                                    <filter x="-29.2%" y="-11.5%" width="158.3%" height="132.2%"
                                            filterUnits="objectBoundingBox" id="fb4-a">
                                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/>
                                        <feGaussianBlur stdDeviation="1" in="shadowOffsetOuter1"
                                                        result="shadowBlurOuter1"/>
                                        <feColorMatrix
                                            values="0 0 0 0 0.062745098 0 0 0 0 0.11372549 0 0 0 0 0.176470588 0 0 0 0.12 0"
                                            in="shadowBlurOuter1"/>
                                    </filter>
                                    <filter x="-29.2%" y="-11.5%" width="158.3%" height="132.2%"
                                            filterUnits="objectBoundingBox" id="fb4-d">
                                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/>
                                        <feGaussianBlur stdDeviation="1" in="shadowOffsetOuter1"
                                                        result="shadowBlurOuter1"/>
                                        <feComposite in="shadowBlurOuter1" in2="SourceAlpha" operator="out"
                                                     result="shadowBlurOuter1"/>
                                        <feColorMatrix
                                            values="0 0 0 0 0.062745098 0 0 0 0 0.11372549 0 0 0 0 0.176470588 0 0 0 0.12 0"
                                            in="shadowBlurOuter1"/>
                                    </filter>
                                    <path
                                        d="M32 24.691l-10.737-2.656a1.007 1.007 0 00-.87.17c-.247.19-.393.483-.393.795v17a1 1 0 00.737.965L32 43.764V24.691z"
                                        id="fb4-b"/>
                                    <path
                                        d="M43.607 22.205a1.012 1.012 0 00-.87-.17L32 24.691v19.073l11.263-2.799A1 1 0 0044 40V23c0-.312-.146-.605-.393-.795z"
                                        id="fb4-e"/>
                                    <linearGradient x1="50%" y1="0%" x2="50%" y2="100%" id="fb4-c">
                                        <stop stop-color="#FFF" offset="0%"/>
                                        <stop stop-color="#E2EEFF" offset="100%"/>
                                    </linearGradient>
                                </defs>
                                <rect class="fill-current text-blue-600" width="64" height="64" rx="32"/>
                                <use fill="#000" filter="url(#fb4-a)" xlink:href="#fb4-b"/>
                                <use fill="url(#fb4-c)" xlink:href="#fb4-b"/>
                                <use fill="#000" filter="url(#fb4-d)" xlink:href="#fb4-e"/>
                                <use fill-opacity=".64" fill="#E2EEFF" xlink:href="#fb4-e"/>
                            </svg>
                            <h3 class="h4 font-playfair-display mb-2">Robust Workflow</h3>
                            <p class="text-lg text-slate-500">Duis aute irure dolor in reprehenderit in voluptate velit
                                esse cillum dolore eu fugiat nulla pariatur.</p>
                        </div>

                        <!-- 5th item -->
                        <div class="relative" data-aos="fade-up" data-aos-anchor="[data-aos-id-blocks]"
                             data-aos-delay="400">
                            <svg class="w-16 h-16 mb-4" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink">
                                <defs>
                                    <filter x="-29.2%" y="-22.7%" width="158.3%" height="163.6%"
                                            filterUnits="objectBoundingBox" id="fb5-a">
                                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/>
                                        <feGaussianBlur stdDeviation="1" in="shadowOffsetOuter1"
                                                        result="shadowBlurOuter1"/>
                                        <feComposite in="shadowBlurOuter1" in2="SourceAlpha" operator="out"
                                                     result="shadowBlurOuter1"/>
                                        <feColorMatrix
                                            values="0 0 0 0 0.062745098 0 0 0 0 0.11372549 0 0 0 0 0.176470588 0 0 0 0.12 0"
                                            in="shadowBlurOuter1"/>
                                    </filter>
                                    <filter x="-15.9%" y="-11.4%" width="131.8%" height="131.8%"
                                            filterUnits="objectBoundingBox" id="fb5-c">
                                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/>
                                        <feGaussianBlur stdDeviation="1" in="shadowOffsetOuter1"
                                                        result="shadowBlurOuter1"/>
                                        <feColorMatrix
                                            values="0 0 0 0 0.062745098 0 0 0 0 0.11372549 0 0 0 0 0.176470588 0 0 0 0.12 0"
                                            in="shadowBlurOuter1"/>
                                    </filter>
                                    <path id="fb5-b" d="M26 21h12v11H26z"/>
                                    <path
                                        d="M40 21h-4v10l-4-3-4 3V21h-4a3 3 0 00-3 3v16a3 3 0 003 3h16a3 3 0 003-3V24a3 3 0 00-3-3z"
                                        id="fb5-d"/>
                                    <linearGradient x1="50%" y1="0%" x2="50%" y2="100%" id="fb5-e">
                                        <stop stop-color="#FFF" offset="0%"/>
                                        <stop stop-color="#E2EEFF" offset="100%"/>
                                    </linearGradient>
                                </defs>
                                <rect class="fill-current text-blue-600" width="64" height="64" rx="32"/>
                                <use fill="#000" filter="url(#fb5-a)" xlink:href="#fb5-b"/>
                                <use fill-opacity=".64" fill="#E2EEFF" xlink:href="#fb5-b"/>
                                <use fill="#000" filter="url(#fb5-c)" xlink:href="#fb5-d"/>
                                <use fill="url(#fb5-e)" xlink:href="#fb5-d"/>
                            </svg>
                            <h3 class="h4 font-playfair-display mb-2">Robust Workflow</h3>
                            <p class="text-lg text-slate-500">Duis aute irure dolor in reprehenderit in voluptate velit
                                esse cillum dolore eu fugiat nulla pariatur.</p>
                        </div>

                        <!-- 6th item -->
                        <div class="relative" data-aos="fade-up" data-aos-anchor="[data-aos-id-blocks]"
                             data-aos-delay="500">
                            <svg class="w-16 h-16 mb-4" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink">
                                <defs>
                                    <filter x="-14.6%" y="-11.4%" width="129.2%" height="132%"
                                            filterUnits="objectBoundingBox" id="fb6-a">
                                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/>
                                        <feGaussianBlur stdDeviation="1" in="shadowOffsetOuter1"
                                                        result="shadowBlurOuter1"/>
                                        <feColorMatrix
                                            values="0 0 0 0 0.062745098 0 0 0 0 0.11372549 0 0 0 0 0.176470588 0 0 0 0.12 0"
                                            in="shadowBlurOuter1"/>
                                    </filter>
                                    <filter x="-29.2%" y="-20.8%" width="158.3%" height="158.3%"
                                            filterUnits="objectBoundingBox" id="fb6-d">
                                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/>
                                        <feGaussianBlur stdDeviation="1" in="shadowOffsetOuter1"
                                                        result="shadowBlurOuter1"/>
                                        <feComposite in="shadowBlurOuter1" in2="SourceAlpha" operator="out"
                                                     result="shadowBlurOuter1"/>
                                        <feColorMatrix
                                            values="0 0 0 0 0.062745098 0 0 0 0 0.11372549 0 0 0 0 0.176470588 0 0 0 0.12 0"
                                            in="shadowBlurOuter1"/>
                                    </filter>
                                    <path
                                        d="M41.95 24.051A6.957 6.957 0 0037 22a6.956 6.956 0 00-5 2.102l-.05-.051A6.957 6.957 0 0027 22c-1.87 0-3.627.729-4.95 2.051A6.948 6.948 0 0020 29c0 1.87.728 3.627 2.05 4.949l9.95 9.95 9.95-9.95A6.952 6.952 0 0044 29a6.954 6.954 0 00-2.05-4.949z"
                                        id="fb6-b"/>
                                    <path d="M36 33a6 6 0 100 12 6 6 0 000-12z" id="fb6-e"/>
                                    <linearGradient x1="50%" y1="0%" x2="50%" y2="100%" id="fb6-c">
                                        <stop stop-color="#FFF" offset="0%"/>
                                        <stop stop-color="#E2EEFF" offset="100%"/>
                                    </linearGradient>
                                </defs>
                                <rect class="fill-current text-blue-600" width="64" height="64" rx="32"/>
                                <use fill="#000" filter="url(#fb6-a)" xlink:href="#fb6-b"/>
                                <use fill="url(#fb6-c)" xlink:href="#fb6-b"/>
                                <use fill="#000" filter="url(#fb6-d)" xlink:href="#fb6-e"/>
                                <use fill-opacity=".64" fill="#E2EEFF" xlink:href="#fb6-e"/>
                            </svg>
                            <h3 class="h4 font-playfair-display mb-2">Robust Workflow</h3>
                            <p class="text-lg text-slate-500">Duis aute irure dolor in reprehenderit in voluptate velit
                                esse cillum dolore eu fugiat nulla pariatur.</p>
                        </div>

                    </div>

                </div>
            </div>
        </section>

        <!-- Features 01 -->
        <section class="relative">

            <div class="absolute inset-0 bg-slate-100 pointer-events-none mb-64 md:mb-80" aria-hidden="true"></div>

            <div class="relative max-w-6xl mx-auto px-4 sm:px-6">
                <div class="py-12 md:py-20">

                    <!-- Section header -->
                    <div class="max-w-3xl mx-auto text-center pb-12">
                        <h2 class="h2 font-playfair-display text-slate-800 mb-4">Built exclusively for you</h2>
                        <p class="text-xl text-slate-500">Excepteur sint occaecat cupidatat non proident, sunt in culpa
                            qui officia deserunt mollit anim id est laborum — semper quis lectus nulla at volutpat diam
                            ut venenatis.</p>
                    </div>

                    <!-- Section content -->
                    <div class="max-w-3xl mx-auto" x-data="{ tab: '1' }">

                        <!-- Tabs buttons -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 pb-12">
                            <button
                                class="text-center transition-opacity"
                                :class="tab === '1' || 'opacity-50 hover:opacity-75'"
                                @click="tab = '1'"
                            >
                                <div class="inline-flex bg-white rounded-full shadow-md mb-3">
                                    <svg width="56" height="56" xmlns="http://www.w3.org/2000/svg">
                                        <path fill="#2174EA" d="M20 20h6v16h-6z"/>
                                        <path fill-opacity=".64" fill="#5091EE" d="M29 20h3v16h-3zM35 20h1v16h-1z"/>
                                    </svg>
                                </div>
                                <div class="md:text-lg leading-tight font-semibold text-slate-800">Internal Feedback
                                </div>
                            </button>
                            <button
                                class="text-center transition-opacity"
                                :class="tab === '2' || 'opacity-50 hover:opacity-75'"
                                @click="tab = '2'"
                            >
                                <div class="inline-flex bg-white rounded-full shadow-md mb-3">
                                    <svg width="56" height="56" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-opacity=".64" fill="#5091EE" d="M33 23v8h3V20H25v3z"/>
                                        <path fill="#2174EA" d="M20 25h11v11H20z"/>
                                    </svg>
                                </div>
                                <div class="md:text-lg leading-tight font-semibold text-slate-800">Internal Feedback
                                </div>
                            </button>
                            <button
                                class="text-center transition-opacity"
                                :class="tab === '3' || 'opacity-50 hover:opacity-75'"
                                @click="tab = '3'"
                            >
                                <div class="inline-flex bg-white rounded-full shadow-md mb-3">
                                    <svg width="56" height="56" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-opacity=".64" fill="#5091EE" d="M20 27l7-7h-7z"/>
                                        <path fill="#2174EA" d="M29 20l7 7v-7z"/>
                                        <path fill-opacity=".64" fill="#5091EE" d="M36 29l-7 7h7z"/>
                                        <path fill="#2174EA" d="M27 36l-7-7v7z"/>
                                    </svg>
                                </div>
                                <div class="md:text-lg leading-tight font-semibold text-slate-800">Internal Feedback
                                </div>
                            </button>
                            <button
                                class="text-center transition-opacity"
                                :class="tab === '4' || 'opacity-50 hover:opacity-75'"
                                @click="tab = '4'"
                            >
                                <div class="inline-flex bg-white rounded-full shadow-md mb-3">
                                    <svg width="56" height="56" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M24 28h-4v4.9c0 1 .7 1.9 1.7 2.1 1.2.2 2.3-.8 2.3-2v-5z"
                                              fill-opacity=".64" fill="#5091EE"/>
                                        <path
                                            d="M35 21h-8c-.6 0-1 .4-1 1v11c0 .7-.2 1.4-.6 2H33c1.7 0 3-1.3 3-3V22c0-.6-.4-1-1-1z"
                                            fill="#2174EA"/>
                                    </svg>
                                </div>
                                <div class="md:text-lg leading-tight font-semibold text-slate-800">Internal Feedback
                                </div>
                            </button>
                        </div>

                        <!-- Tab items -->
                        <div class="relative flex flex-col" data-aos="fade-up">
                            <div
                                x-show="tab === '1'"
                                x-transition:enter="transition ease-in-out duration-700 order-first"
                                x-transition:enter-start="opacity-0 -translate-y-16"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in-out duration-300 absolute"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-16"
                            >
                                <img class="mx-auto shadow-2xl" src="./images/features-home-01.jpg" width="768"
                                     height="474" alt="Features home 01"/>
                            </div>
                            <div
                                x-show="tab === '2'"
                                x-transition:enter="transition ease-in-out duration-700 order-first"
                                x-transition:enter-start="opacity-0 -translate-y-16"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in-out duration-300 absolute"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-16"
                            >
                                <img class="mx-auto shadow-2xl" src="./images/features-home-01.jpg" width="768"
                                     height="474" alt="Features home 02"/>
                            </div>
                            <div
                                x-show="tab === '3'"
                                x-transition:enter="transition ease-in-out duration-700 order-first"
                                x-transition:enter-start="opacity-0 -translate-y-16"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in-out duration-300 absolute"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-16"
                            >
                                <img class="mx-auto shadow-2xl" src="./images/features-home-01.jpg" width="768"
                                     height="474" alt="Features home 03"/>
                            </div>
                            <div
                                x-show="tab === '4'"
                                x-transition:enter="transition ease-in-out duration-700 order-first"
                                x-transition:enter-start="opacity-0 -translate-y-16"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in-out duration-300 absolute"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-16"
                            >
                                <img class="mx-auto shadow-2xl" src="./images/features-home-01.jpg" width="768"
                                     height="474" alt="Features home 04"/>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </section>

        <!-- Features 02 -->
        <section>
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <div class="py-12 md:py-20 border-t border-slate-200">

                    <!-- Section header -->
                    <div class="max-w-3xl mx-auto text-center pb-12 md:pb-20">
                        <h2 class="h2 font-playfair-display text-slate-800">The quick brown fox jumped over the lazy
                            dog</h2>
                    </div>

                    <!-- Section content -->
                    <div
                        class="max-w-xl mx-auto md:max-w-none flex flex-col md:flex-row md:items-start md:space-x-8 lg:space-x-16 xl:space-x-18 space-y-8 space-y-reverse md:space-y-0"
                        x-data="{ tab: '1' }">

                        <!-- Tabs items (images) -->
                        <div class="md:rtl md:w-5/12 lg:w-1/2 order-1 md:order-none" data-aos="fade-down">
                            <div class="relative flex flex-col">
                                <!-- Item 1 -->
                                <div
                                    class="w-full"
                                    x-show="tab === '1'"
                                    x-transition:enter="transition ease-in-out duration-700 transform order-first"
                                    x-transition:enter-start="opacity-0 -translate-y-16"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in-out duration-300 transform absolute"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 translate-y-16"
                                >
                                    <img class="md:max-w-none mx-auto rounded-sm" src="./images/features-home-02.png"
                                         width="540" height="620" alt="Features home 2 01"/>
                                </div>
                                <!-- Item 2 -->
                                <div
                                    class="w-full"
                                    x-show="tab === '2'"
                                    x-transition:enter="transition ease-in-out duration-700 transform order-first"
                                    x-transition:enter-start="opacity-0 -translate-y-16"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in-out duration-300 transform absolute"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 translate-y-16"
                                >
                                    <img class="md:max-w-none mx-auto rounded-sm" src="./images/features-home-02.png"
                                         width="540" height="620" alt="Features home 2 02"/>
                                </div>
                                <!-- Item 3 -->
                                <div
                                    class="w-full"
                                    x-show="tab === '3'"
                                    x-transition:enter="transition ease-in-out duration-700 transform order-first"
                                    x-transition:enter-start="opacity-0 -translate-y-16"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in-out duration-300 transform absolute"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 translate-y-16"
                                >
                                    <img class="md:max-w-none mx-auto rounded-sm" src="./images/features-home-02.png"
                                         width="540" height="620" alt="Features home 2 03"/>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="md:w-7/12 lg:w-1/2" data-aos="fade-up">
                            <div class="mb-8 text-center md:text-left">
                                <h3 class="h3 text-slate-800 font-playfair-display mb-3">Built exclusively for you</h3>
                                <p class="text-xl text-slate-500">Excepteur sint occaecat cupidatat non proident, sunt
                                    in culpa qui officia deserunt mollit anim id est laborum — semper quis lectus nulla
                                    at volutpat diam ut venenatis.</p>
                            </div>
                            <!-- Tabs buttons -->
                            <div class="mb-8 md:mb-0">
                                <button
                                    :class="tab !== '1' ? 'border-transparent opacity-50 hover:opacity-75' : 'border-2 border-blue-500 opacity-100'"
                                    class="flex items-start text-left bg-white border-2 px-5 py-3 rounded-sm shadow-md transition duration-300 ease-in-out mb-3"
                                    @click="tab = '1'"
                                >
                                    <svg class="w-4 h-4 fill-current text-blue-600 shrink-0 mt-1 mr-4"
                                         viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M9.4 6.6c.8.8.8 2 0 2.8-.8.8-2 .8-2.8 0-.8-.8-5-7.8-5-7.8s7 4.2 7.8 5Z"/>
                                        <path
                                            d="M8 16c-4.4 0-8-3.6-8-8 0-.6.4-1 1-1s1 .4 1 1c0 3.3 2.7 6 6 6s6-2.7 6-6-2.7-6-6-6c-.6 0-1-.4-1-1s.4-1 1-1c4.4 0 8 3.6 8 8s-3.6 8-8 8Z"/>
                                    </svg>
                                    <div>
                                        <div class="text-slate-800 font-medium mb-1">Internal Feedback</div>
                                        <div class="text-slate-500">Duis aute irure dolor in reprehenderit in voluptate
                                            velit esse cillum dolore eu fugiat nulla pariatur velit.
                                        </div>
                                    </div>
                                </button>
                                <button
                                    :class="tab !== '2' ? 'border-transparent opacity-50 hover:opacity-75' : 'border-2 border-blue-500 opacity-100'"
                                    class="flex items-start text-left bg-white border-2 px-5 py-3 rounded-sm shadow-md transition duration-300 ease-in-out mb-3"
                                    @click="tab = '2'"
                                >
                                    <svg class="w-4 h-4 fill-current text-blue-600 shrink-0 mt-1 mr-4"
                                         viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M4.019 15.276.034 1.329A1.058 1.058 0 0 1 1.33.034L15.276 4.02c.896.299.996 1.494.1 1.893L8.8 8.8l-2.79 6.574c-.498.897-1.693.797-1.992-.1ZM2.525 2.525l2.69 9.463 1.892-4.383c.1-.199.299-.398.498-.498l4.383-1.893-9.463-2.69Z"/>
                                    </svg>
                                    <div>
                                        <div class="text-slate-800 font-medium mb-1">Internal Feedback</div>
                                        <div class="text-slate-500">Duis aute irure dolor in reprehenderit in voluptate
                                            velit esse cillum dolore eu fugiat nulla pariatur velit.
                                        </div>
                                    </div>
                                </button>
                                <button
                                    :class="tab !== '3' ? 'border-transparent opacity-50 hover:opacity-75' : 'border-2 border-blue-500 opacity-100'"
                                    class="flex items-start text-left bg-white border-2 px-5 py-3 rounded-sm shadow-md transition duration-300 ease-in-out"
                                    @click="tab = '3'"
                                >
                                    <svg class="w-4 h-4 fill-current text-blue-600 shrink-0 mt-1 mr-4"
                                         viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M15.686 5.71 10.291.3c-.4-.4-.999-.4-1.399 0a.97.97 0 0 0 0 1.403l.6.6L2.698 6.01l-1-1.002c-.4-.4-.999-.4-1.398 0a.97.97 0 0 0 0 1.403l1.498 1.502 2.398 2.404L.6 14.023 2 15.425l3.696-3.706 3.997 4.007c.5.5 1.199.2 1.398 0a.97.97 0 0 0 0-1.402l-.999-1.002 3.697-6.711.6.6c.599.602 1.199.201 1.398 0 .3-.4.3-1.1-.1-1.502Zm-7.193 6.11L4.196 7.511l6.695-3.706 1.298 1.302-3.696 6.711Z"/>
                                    </svg>
                                    <div>
                                        <div class="text-slate-800 font-medium mb-1">Internal Feedback</div>
                                        <div class="text-slate-500">Duis aute irure dolor in reprehenderit in voluptate
                                            velit esse cillum dolore eu fugiat nulla pariatur velit.
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </section>

        <!-- Features 03 -->
        <section class="relative">

            <!-- Dark background -->
            <div
                class="absolute inset-0 bg-slate-900 pointer-events-none -z-10 [clip-path:polygon(0_0,_5760px_0,_5760px_calc(100%_-_352px),_0_100%)] h-96 md:h-auto md:mb-64"
                aria-hidden="true"></div>

            <div class="relative max-w-6xl mx-auto px-4 sm:px-6">
                <div class="py-12 md:py-20">

                    <!-- Section header -->
                    <div class="max-w-3xl mx-auto text-center pb-12 md:pb-20">
                        <h2 class="h2 font-playfair-display text-slate-100">Simplify operating and manage with
                            transparency</h2>
                    </div>

                    <!-- Section content -->
                    <div
                        class="max-w-sm mx-auto md:max-w-none grid gap-12 md:grid-cols-3 md:gap-x-10 md:gap-y-10 items-start">

                        <!-- 1st article -->
                        <article data-aos="fade-up">
                            <a class="relative block group mt-8 mb-4" href="#0">
                                <div
                                    class="absolute inset-0 pointer-events-none border-2 border-slate-500 opacity-20 translate-x-4 -translate-y-4 group-hover:translate-x-0 group-hover:translate-y-0 transition duration-300 ease-out -z-10"
                                    aria-hidden="true"></div>
                                <div class="overflow-hidden">
                                    <img
                                        class="w-full aspect-square object-cover group-hover:scale-105 transition duration-700 ease-out"
                                        src="./images/features-home-3-01.jpg" width="342" height="342" alt="News 01"/>
                                </div>
                                <div
                                    class="w-16 h-16 absolute bg-linear-to-b from-blue-500 to-blue-600 rounded-full -top-8 left-8">
                                    <svg class="w-16 h-16 fill-current" viewBox="0 0 64 64"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path class="text-white"
                                              d="m30.152 39.848.672-.408C32.24 33.632 33.92 32 35.96 26.768a10.714 10.714 0 0 0-.432-2.28c-.288-.096-.888-.336-1.44-.336-1.776.48-3.48 1.632-5.208 2.088-1.272.336-.576 2.352.36 3.312.672-.384 1.872-1.008 2.28-.96-1.248 2.112-3 6.12-3 7.704 0 .528-.48.816-.48 1.104 0 .288.12.6.144.936.384-.24.48-.12.48.264.264.192.672.504 1.032.816.144-.744.384-1.56.888-1.464l-.6 1.704c.072.072.12.144.168.192Z"/>
                                    </svg>
                                </div>
                            </a>
                            <h3 class="h4 font-playfair-display mb-2">
                                <a class="text-slate-800 hover:underline hover:decoration-blue-100" href="#0">Advanced
                                    Features</a>
                            </h3>
                            <p class="text-lg text-slate-500">Lorem ipsum is placeholder text used in the graphic,
                                print, and publishing for previewing layouts.</p>
                        </article>

                        <!-- 2nd article -->
                        <article data-aos="fade-up" data-aos-delay="100">
                            <a class="relative block group mt-8 mb-4" href="#0">
                                <div
                                    class="absolute inset-0 pointer-events-none border-2 border-slate-500 opacity-20 translate-x-4 -translate-y-4 group-hover:translate-x-0 group-hover:translate-y-0 transition duration-300 ease-out -z-10"
                                    aria-hidden="true"></div>
                                <div class="overflow-hidden">
                                    <img
                                        class="w-full aspect-square object-cover group-hover:scale-105 transition duration-700 ease-out"
                                        src="./images/features-home-3-02.jpg" width="342" height="342" alt="News 02"/>
                                </div>
                                <div
                                    class="w-16 h-16 absolute bg-linear-to-b from-blue-500 to-blue-600 rounded-full -top-8 left-8">
                                    <svg class="w-16 h-16 fill-current" viewBox="0 0 64 64"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path class="text-white"
                                              d="M25.508 39.044c.312 0 .672-.024 1.008-.048-.096-.432.384-.528.816-.096 1.488-.216 3.12-.624 4.416-.888l.6-.624c1.104 0 .456.624 1.44.432.168-1.008.552-.456 1.92-1.176l.072.168c-.264.192-.72.408-1.152.576v.216c1.584-.528 1.848-1.32 2.928-1.464 1.224-.168 2.184-.048 2.52-.792l-1.728-.096-.168-.24h1.008l-.096-.216c-1.032 0-1.44-.168-2.4-.576-2.016.192-5.328.912-7.632.768 1.296-1.512 3.72-2.592 5.712-3.648.024.36-.384.6-.744.816l.072.216 1.344-.744c.048-.72.552-1.344 1.272-1.44.6-.744.864-1.776.864-2.76 0-1.08-1.2-2.208-3.024-2.208l.096-.264c-1.968.192-5.4.36-6.792 1.176-.504.288-.456.696-.792.984.48.36.024.528.504 1.2.624.888.648 1.488 1.152 1.2 1.056-.576 2.4-1.176 3.384-1.176.48 0 .864.168 1.104.504 0 1.488-6.744 3.792-9.288 7.632.48.096.312.48.072.84.504.768.84 1.728 1.512 1.728Zm7.344-5.976.912-.48-.048-.264-.912.6.048.144Zm7.08 3.216-.096-.216-1.128.216c.144.168.72.048 1.224 0Z"/>
                                    </svg>
                                </div>
                            </a>
                            <h3 class="h4 font-playfair-display mb-2">
                                <a class="text-slate-800 hover:underline hover:decoration-blue-100" href="#0">Advanced
                                    Features</a>
                            </h3>
                            <p class="text-lg text-slate-500">Lorem ipsum is placeholder text used in the graphic,
                                print, and publishing for previewing layouts.</p>
                        </article>

                        <!-- 3rd article -->
                        <article data-aos="fade-up" data-aos-delay="200">
                            <a class="relative block group mt-8 mb-4" href="#0">
                                <div
                                    class="absolute inset-0 pointer-events-none border-2 border-slate-500 opacity-20 translate-x-4 -translate-y-4 group-hover:translate-x-0 group-hover:translate-y-0 transition duration-300 ease-out -z-10"
                                    aria-hidden="true"></div>
                                <div class="overflow-hidden">
                                    <img
                                        class="w-full aspect-square object-cover group-hover:scale-105 transition duration-700 ease-out"
                                        src="./images/features-home-3-03.jpg" width="342" height="342" alt="News 03"/>
                                </div>
                                <div
                                    class="w-16 h-16 absolute bg-linear-to-b from-blue-500 to-blue-600 rounded-full -top-8 left-8">
                                    <svg class="w-16 h-16 fill-current" viewBox="0 0 64 64"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path class="text-white"
                                              d="M25.53 40.038c1.607-.216 3.071-.576 4.655-1.08.048-.36.288-.6.552-.624l.12.408a37.15 37.15 0 0 0 2.352-.912l.12.264c1.416-.72 2.64-1.2 3.984-2.376l.312.144c1.392-1.248 1.632-2.904 1.632-4.728-.624-.912-1.416-1.656-2.784-2.112.36-.72.768-1.752.984-2.784-.12-.648-.408-1.224-.864-1.776-.84-.072-2.088-.24-3.264-.24l-.192-.216a18.485 18.485 0 0 0-1.152-.048c-1.776 0-4.056 1.032-4.056 1.944 0 .624.048 1.248.192 1.824l.268-.172c.578-.368 1.127-.671 1.46-.38l-1.656.792c.048.144.12.336.192.48 1.296-.672 3.96-1.608 5.64-1.608-.456.984-2.376 2.496-3.672 2.832l-.24 1.056c.456 1.248.864 1.032 2.16 1.68l.12-.288c1.8.216 3.336 0 3.96.72-.312 1.464-3.984 2.712-5.4 3.12-.504.144-.84.144-.936-.096-1.416.744-3.36.696-5.064.696-.24.768-.336 1.608.048 2.544l.72-.312c.144.288-.432.696-.456.96-.024.24.12.312.264.288Zm7.703-2.352-.144-.264.744-.312.072.216-.672.36Zm-4.344 1.248-.048-.264c.216-.048.384-.096.552-.12a.588.588 0 0 1 .48.12l-.984.264Z"/>
                                    </svg>
                                </div>
                            </a>
                            <h3 class="h4 font-playfair-display mb-2">
                                <a class="text-slate-800 hover:underline hover:decoration-blue-100" href="#0">Advanced
                                    Features</a>
                            </h3>
                            <p class="text-lg text-slate-500">Lorem ipsum is placeholder text used in the graphic,
                                print, and publishing for previewing layouts.</p>
                        </article>

                    </div>

                </div>
            </div>
        </section>

        <!-- Target -->
        <section>
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <div class="py-12 md:py-20 border-t border-slate-200">

                    <!-- Section header -->
                    <div class="max-w-3xl mx-auto text-center pb-12 md:pb-20">
                        <h2 class="h2 font-playfair-display text-slate-800 mb-3">We're experts and brand creators</h2>
                        <p class="text-xl text-slate-500">Excepteur sint occaecat cupidatat non proident, sunt in culpa
                            qui officia deserunt mollit anim id est laborum — semper quis lectus nulla at volutpat diam
                            ut venenatis.</p>
                    </div>

                    <!-- Section content -->
                    <div
                        class="max-w-xl mx-auto md:max-w-none flex flex-col md:flex-row md:items-center md:space-x-8 lg:space-x-16 xl:space-x-18 space-y-8 space-y-reverse md:space-y-0">

                        <!-- Content -->
                        <div class="md:w-7/12 lg:w-1/2 order-1 md:order-none" data-aos="fade-right">
                            <ul class="space-y-6">
                                <li>
                                    <div class="flex items-center mb-4">
                                        <svg class="h-4 w-4 shrink-0 fill-current text-blue-500 mr-3">
                                            <path
                                                d="M15.722 4.008C14.408 1.214 10.954-.635 7.318.203 5.6.596 4.072 1.561 2.919 2.757A10.57 10.57 0 0 0 .484 6.93C.03 8.458-.173 10.035.18 11.764c.191.862.518 1.683 1.146 2.479a4.876 4.876 0 0 0 2.256 1.522c1.635.469 3.156.192 4.41-.439 1.242-.615 2.298-1.769 2.494-3.094.094-.656-.537-.657-.69-.18-.781 2.126-3.715 2.534-5.265 1.579-1.568-.922-1.185-3.068-.294-4.801.89-1.729 2.454-3.02 3.92-3.338.376-.098.714-.121 1.026-.098.324.018.658.074.98.188.65.2 1.23.591 1.618 1 .27.3.575.386 1.002.461.436.061.95.117 1.499.045.535-.073 1.06-.287 1.41-.807.345-.504.462-1.348.03-2.273"/>
                                        </svg>
                                        <div class="h2 font-playfair-display text-slate-800">79%</div>
                                    </div>
                                    <div class="text-slate-500 text-lg">Lorem ipsum is placeholder text used in the
                                        graphic, print, and publishing for previewing layouts.
                                    </div>
                                </li>
                                <li>
                                    <div class="flex items-center mb-4">
                                        <svg class="h-4 w-4 shrink-0 fill-current text-rose-400 mr-3">
                                            <path
                                                d="M15.722 4.008C14.408 1.214 10.954-.635 7.318.203 5.6.596 4.072 1.561 2.919 2.757A10.57 10.57 0 0 0 .484 6.93C.03 8.458-.173 10.035.18 11.764c.191.862.518 1.683 1.146 2.479a4.876 4.876 0 0 0 2.256 1.522c1.635.469 3.156.192 4.41-.439 1.242-.615 2.298-1.769 2.494-3.094.094-.656-.537-.657-.69-.18-.781 2.126-3.715 2.534-5.265 1.579-1.568-.922-1.185-3.068-.294-4.801.89-1.729 2.454-3.02 3.92-3.338.376-.098.714-.121 1.026-.098.324.018.658.074.98.188.65.2 1.23.591 1.618 1 .27.3.575.386 1.002.461.436.061.95.117 1.499.045.535-.073 1.06-.287 1.41-.807.345-.504.462-1.348.03-2.273"/>
                                        </svg>
                                        <div class="h2 font-playfair-display text-slate-800">1M+</div>
                                    </div>
                                    <div class="text-slate-500 text-lg">Lorem ipsum is placeholder text used in the
                                        graphic, print, and publishing for previewing layouts.
                                    </div>
                                </li>
                                <li>
                                    <div class="flex items-center mb-4">
                                        <svg class="h-4 w-4 shrink-0 fill-current text-yellow-400 mr-3">
                                            <path
                                                d="M15.722 4.008C14.408 1.214 10.954-.635 7.318.203 5.6.596 4.072 1.561 2.919 2.757A10.57 10.57 0 0 0 .484 6.93C.03 8.458-.173 10.035.18 11.764c.191.862.518 1.683 1.146 2.479a4.876 4.876 0 0 0 2.256 1.522c1.635.469 3.156.192 4.41-.439 1.242-.615 2.298-1.769 2.494-3.094.094-.656-.537-.657-.69-.18-.781 2.126-3.715 2.534-5.265 1.579-1.568-.922-1.185-3.068-.294-4.801.89-1.729 2.454-3.02 3.92-3.338.376-.098.714-.121 1.026-.098.324.018.658.074.98.188.65.2 1.23.591 1.618 1 .27.3.575.386 1.002.461.436.061.95.117 1.499.045.535-.073 1.06-.287 1.41-.807.345-.504.462-1.348.03-2.273"/>
                                        </svg>
                                        <div class="h2 font-playfair-display text-slate-800">500K</div>
                                    </div>
                                    <div class="text-slate-500 text-lg">Lorem ipsum is placeholder text used in the
                                        graphic, print, and publishing for previewing layouts.
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- Image -->
                        <div class="md:w-5/12 lg:w-1/2" data-aos="fade-left">
                            <img class="mx-auto md:max-w-none" src="./images/target.png" width="540" height="540"
                                 alt="Target"/>
                        </div>

                    </div>

                </div>
            </div>
        </section>

        <!-- Pricing -->
        <section class="relative">

            <!-- Dark background -->
            <div class="absolute inset-0 bg-slate-900 pointer-events-none -z-10 h-1/3 lg:h-2/3"
                 aria-hidden="true"></div>

            <div class="relative max-w-6xl mx-auto px-4 sm:px-6">
                <div class="py-12 md:py-20">

                    <!-- Section header -->
                    <div class="max-w-3xl mx-auto text-center pb-12">
                        <h2 class="h2 font-playfair-display text-slate-100">Find the right plan for your business</h2>
                    </div>

                    <!-- Pricing tables -->
                    <div x-data="{ annual: true }">

                        <!-- Pricing toggle -->
                        <div class="flex justify-center items-center space-x-4 sm:space-x-7 mb-16">
                            <div class="text-sm text-slate-500 font-medium text-right min-w-[8rem]">Pay Monthly</div>
                            <div class="form-switch shrink-0">
                                <input type="checkbox" id="toggle" class="sr-only" x-model="annual"/>
                                <label class="bg-slate-700" for="toggle">
                                    <span aria-hidden="true"></span>
                                    <span class="sr-only">Pay annually</span>
                                </label>
                            </div>
                            <div class="text-sm text-slate-500 font-medium min-w-[8rem]">Pay Yearly <span
                                    class="text-emerald-500">(-20%)</span></div>
                        </div>

                        <div class="max-w-sm mx-auto grid gap-8 lg:grid-cols-3 lg:gap-6 items-start lg:max-w-none pt-4">

                            <!-- Pricing table 1 -->
                            <div class="relative flex flex-col h-full px-6 py-5 bg-white shadow-lg" data-aos="fade-up">
                                <div class="mb-4 pb-4 border-b border-slate-200">
                                    <div class="text-lg font-semibold text-slate-800 mb-1">Essential</div>
                                    <div class="inline-flex items-baseline mb-3">
                                        <span class="h3 font-medium text-slate-500">$</span>
                                        <span x-text="annual ? '49' : '55'"
                                              class="h2 leading-7 font-playfair-display text-slate-800"></span>
                                        <span class="font-medium text-slate-400">/mo</span>
                                    </div>
                                    <div class="text-slate-500">Better insights for growing businesses that want more
                                        customers.
                                    </div>
                                </div>
                                <div class="font-medium mb-3">Features include:</div>
                                <ul class="text-slate-500 space-y-3 grow mb-6">
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>50 Placeholder text commonly</span>
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>Consectetur adipiscing elit</span>
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>Excepteur sint occaecat cupidatat</span>
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>Officia deserunt mollit anim</span>
                                    </li>
                                </ul>
                                <div class="p-3 rounded-sm bg-slate-50">
                                    <a class="btn-sm text-white bg-blue-600 hover:bg-blue-700 w-full group" href="#0">
                                        Start free trial <span
                                            class="tracking-normal text-blue-300 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Pricing table 2 -->
                            <div class="relative flex flex-col h-full px-6 py-5 bg-white shadow-lg" data-aos="fade-up"
                                 data-aos-delay="100">
                                <div class="absolute top-0 right-0 mr-6 -mt-4">
                                    <div
                                        class="inline-flex text-sm font-semibold py-1 px-3 text-emerald-700 bg-emerald-200 rounded-full">
                                        Most Popular
                                    </div>
                                </div>
                                <div class="mb-4 pb-4 border-b border-slate-200">
                                    <div class="text-lg font-semibold text-slate-800 mb-1">Premium</div>
                                    <div class="inline-flex items-baseline mb-3">
                                        <span class="h3 font-medium text-slate-500">$</span>
                                        <span x-text="annual ? '79' : '85'"
                                              class="h2 leading-7 font-playfair-display text-slate-800"></span>
                                        <span class="font-medium text-slate-400">/mo</span>
                                    </div>
                                    <div class="text-slate-500">Better insights for growing businesses that want more
                                        customers.
                                    </div>
                                </div>
                                <div class="font-medium mb-3">All features of Essential plus:</div>
                                <ul class="text-slate-500 space-y-3 grow mb-6">
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>100 placeholder text commonly</span>
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>Consectetur adipiscing elit</span>
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>Excepteur sint occaecat cupidatat</span>
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>Officia deserunt mollit anim</span>
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>Placeholder text commonly used</span>
                                    </li>
                                </ul>
                                <div class="p-3 rounded-sm bg-slate-50">
                                    <a class="btn-sm text-white bg-blue-600 hover:bg-blue-700 w-full group" href="#0">
                                        Start free trial <span
                                            class="tracking-normal text-blue-300 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Pricing table 3 -->
                            <div class="relative flex flex-col h-full px-6 py-5 bg-white shadow-lg" data-aos="fade-up"
                                 data-aos-delay="200">
                                <div class="mb-4 pb-4 border-b border-slate-200">
                                    <div class="text-lg font-semibold text-slate-800 mb-1">Advanced</div>
                                    <div class="inline-flex items-baseline mb-3">
                                        <span class="h3 font-medium text-slate-500">$</span>
                                        <span x-text="annual ? '129' : '135'"
                                              class="h2 leading-7 font-playfair-display text-slate-800"></span>
                                        <span class="font-medium text-slate-400">/mo</span>
                                    </div>
                                    <div class="text-slate-500">Better insights for growing businesses that want more
                                        customers.
                                    </div>
                                </div>
                                <div class="font-medium mb-3">All features of Essential plus:</div>
                                <ul class="text-slate-500 space-y-3 grow mb-6">
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>200 placeholder text commonly</span>
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>Consectetur adipiscing elit</span>
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>Excepteur sint occaecat cupidatat</span>
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>Officia deserunt mollit anim</span>
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>Voluptate velit esse cillum</span>
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-3 h-3 fill-current text-emerald-500 mr-3 shrink-0"
                                             viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z"/>
                                        </svg>
                                        <span>Placeholder text commonly used</span>
                                    </li>
                                </ul>
                                <div class="p-3 rounded-sm bg-slate-50">
                                    <a class="btn-sm text-white bg-blue-600 hover:bg-blue-700 w-full group" href="#0">
                                        Start free trial <span
                                            class="tracking-normal text-blue-300 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>
                                    </a>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>
            </div>
        </section>

        <!-- Cta -->
        <section class="bg-slate-100">
            <div class="relative max-w-6xl mx-auto px-4 sm:px-6">
                <div class="py-12 md:py-20">

                    <div class="relative max-w-3xl mx-auto text-center">

                        <div class="absolute right-0 -mt-4 -mr-1 fill-slate-300 hidden lg:block" aria-hidden="true">
                            <svg class="fill-slate-300" width="56" height="43">
                                <path
                                    d="M4.532 30.45C15.785 23.25 24.457 12.204 29.766.199c.034-.074-.246-.247-.3-.186-4.227 5.033-9.298 9.282-14.372 13.162C10 17.07 4.919 20.61.21 24.639c-1.173 1.005 2.889 6.733 4.322 5.81M18.96 42.198c12.145-4.05 24.12-8.556 36.631-12.365.076-.024.025-.349-.055-.347-6.542.087-13.277.083-19.982.827-6.69.74-13.349 2.24-19.373 5.197-1.53.75 1.252 7.196 2.778 6.688"/>
                            </svg>
                        </div>

                        <div class="relative">
                            <h2 class="h2 font-playfair-display text-slate-800 mb-4">Say goodbye to long queues, big
                                updates, and <span class="text-emerald-500">confusion</span>.</h2>
                            <p class="text-xl text-slate-500 mb-8">Excepteur sint occaecat cupidatat non proident, sunt
                                in culpa qui officia deserunt mollit anim id est laborum — semper quis lectus nulla at
                                volutpat diam ut venenatis.</p>
                            <div>
                                <a class="btn text-white bg-blue-600 hover:bg-blue-700 group" href="request-demo.html">
                                    Request Demo <span
                                        class="tracking-normal text-blue-300 group-hover:translate-x-0.5 transition-transform duration-150 ease-in-out ml-1">-&gt;</span>
                                </a>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </section>

    </main>
@endsection

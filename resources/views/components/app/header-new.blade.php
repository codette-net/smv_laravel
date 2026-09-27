@php
    $navigation = [
        ['label' => 'Home', 'route' => 'home', 'active' => ['home']],
        ['label' => 'Vacatures', 'route' => 'vacancies.index', 'active' => ['vacancies.*']],
        ['label' => 'Bedrijven', 'route' => 'companies.index', 'active' => ['companies.*', 'bedrijven.*']],
        ['label' => 'Blog', 'route' => 'blog.index', 'active' => ['blog.*']],
    ];
    $loginRoute = 'filament.dashboard.auth.login';
@endphp

<header class="fixed top-2 md:top-6 w-full z-30">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div
            class="relative flex items-center justify-between gap-3 h-14 rounded-2xl px-3 backdrop-blur-xs bg-white/90 shadow-lg shadow-black/[0.03] before:absolute before:inset-0 before:rounded-[inherit] before:border before:border-transparent before:[background:linear-gradient(var(--color-gray-100),var(--color-gray-200))_border-box] before:[mask:linear-gradient(white_0_0)_padding-box,_linear-gradient(white_0_0)] before:[mask-composite:exclude_!important] before:pointer-events-none">

            <!-- Site branding -->
            <div class="flex-1 flex items-center">
                <!-- Logo -->
                <a class="inline-flex" href="{{ route('home') }}" aria-label="Sales en Marketing Vacatures, home">
                    <img class="object-cover z-40" src="{{ Vite::asset('resources/images/smv_profile.png') }}"
                         width="40" height="40" alt="Sales en Marketing Vacatures">
                </a>
            </div>

            <!-- Desktop navigation -->
            <nav class="hidden md:flex md:grow z-40" aria-label="Hoofdnavigatie">

                <ul class="text-sm flex grow justify-center flex-wrap items-center gap-4 lg:gap-8">
                    @foreach ($navigation as $item)
                        @php($isCurrent = request()->routeIs(...$item['active']))
                        <li class="px-3 py-1">
                            <a @class([
                                    'text-gray-800 hover:text-gray-900 text-str flex items-center transition underline'
                                    => $isCurrent,
                                    'text-gray-700 hover:text-gray-900 flex items-center transition'
                                    => ! $isCurrent,
                                ])
                               href="{{ route($item['route']) }}"
                               @if ($isCurrent) aria-current="page" @endif>{{ $item['label'] }}</a>
                        </li>
                    @endforeach

                </ul>
                <!-- Desktop sign in links -->
                <ul class="flex-1 flex justify-end items-center gap-3 z-40">

                    @auth
                        <li>
                            <a class="btn-sm text-gray-800 bg-white hover:bg-gray-50 shadow-sm" href="{{ route('filament.dashboard.pages.dashboard') }}">{{ auth()->user()->name}}</a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('filament.dashboard.auth.logout') }}">
                                @csrf
                                <button type="submit" class="btn-sm text-gray-200 bg-gray-800 hover:bg-gray-900 shadow-sm">Logout</button>
                            </form>
                        </li>
                    @else
                        <li>
                            <a class="btn-sm text-gray-800 bg-white hover:bg-gray-50 shadow-sm" href="{{ route($loginRoute) }}">Login</a>
                        </li>
                        <li>
                            <a class="btn-sm text-gray-200 bg-gray-800 hover:bg-gray-900 shadow-sm"
                               href="signup.html">Register</a>
                        </li>
                    @endauth
                </ul>
            </nav>



            <!-- Mobile menu -->
            <div class="flex md:hidden z-50" x-data="{ expanded: false }">

                <!-- Hamburger button -->
                <button
                    class="group inline-flex w-8 h-8 text-gray-800 text-center items-center justify-center transition"
                    aria-controls="mobile-nav" :aria-expanded="expanded" @click.stop="expanded = !expanded">
                    <span class="sr-only">Menu</span>
                    <svg class="w-4 h-4 fill-current stroke-current pointer-events-none" viewBox="0 0 16 16"
                         xmlns="http://www.w3.org/2000/svg">
                        <rect
                            class="origin-center transition-all duration-300 ease-[cubic-bezier(.5,.85,.25,1.1)] -translate-y-[5px] translate-x-[7px] group-aria-expanded:rotate-[315deg] group-aria-expanded:translate-y-0 group-aria-expanded:translate-x-0"
                            y="7" width="9" height="2" rx="1"></rect>
                        <rect
                            class="origin-center group-aria-expanded:rotate-45 transition-all duration-300 ease-[cubic-bezier(.5,.85,.25,1.8)]"
                            y="7" width="16" height="2" rx="1"></rect>
                        <rect
                            class="origin-center transition-all duration-300 ease-[cubic-bezier(.5,.85,.25,1.1)] translate-y-[5px] group-aria-expanded:rotate-[135deg] group-aria-expanded:translate-y-0"
                            y="7" width="9" height="2" rx="1"></rect>
                    </svg>
                </button>

                <!-- Mobile navigation -->
                <nav
                    id="mobile-nav"
                    class="absolute top-full z-50 left-0 w-full bg-white rounded-xl shadow-lg shadow-black/[0.03] before:absolute before:inset-0 before:rounded-[inherit] before:border before:border-transparent before:[background:linear-gradient(var(--color-gray-100),var(--color-gray-200))_border-box] before:[mask:linear-gradient(white_0_0)_padding-box,_linear-gradient(white_0_0)] before:[mask-composite:exclude_!important] before:pointer-events-none"
                    @click.outside="expanded = false"
                    @keydown.escape.window="expanded = false"
                    x-show="expanded"
                    x-transition:enter="transition ease-out duration-200 transform"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-out duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    x-cloak
                >

                    <ul class="text-sm p-2">
                        <li>
                            <a class="flex text-gray-700 hover:bg-gray-100 rounded-lg py-1.5 px-2" href="pricing.html">Pricing</a>
                        </li>
                        <li>
                            <a class="flex text-gray-700 hover:bg-gray-100 rounded-lg py-1.5 px-2"
                               href="customers.html">Customers</a>
                        </li>
                        <li>
                            <a class="flex text-gray-700 hover:bg-gray-100 rounded-lg py-1.5 px-2"
                               href="blog.html">Blog</a>
                        </li>
                        <li>
                            <a class="flex text-gray-700 hover:bg-gray-100 rounded-lg py-1.5 px-2"
                               href="documentation.html">Docs</a>
                        </li>
                        <li>
                            <a class="flex text-gray-700 hover:bg-gray-100 rounded-lg py-1.5 px-2" href="support.html">Support
                                center</a>
                        </li>
                        <li>
                            <a class="flex text-gray-700 hover:bg-gray-100 rounded-lg py-1.5 px-2"
                               href="apps.html">Apps</a>
                        </li>
                    </ul>
                </nav>

            </div>

        </div>
    </div>
</header>

@php
    $navigation = [
        ['label' => 'Home', 'route' => 'home', 'active' => ['home']],
        ['label' => 'Vacatures', 'route' => 'vacancies.index', 'active' => ['vacancies.*']],
        ['label' => 'Bedrijven', 'route' => 'companies.index', 'active' => ['companies.*', 'bedrijven.*']],
        ['label' => 'Blog', 'route' => 'blog.index', 'active' => ['blog.*']],
        ['label' => 'Over ons', 'route' => 'about', 'active' => ['about']],
        ['label' => 'Tarieven', 'route' => 'pricing', 'active' => ['pricing']],
        ['label' => 'Contact', 'route' => 'contact', 'active' => ['contact']],
    ];
    $loginRoute = 'filament.dashboard.auth.login';
@endphp

<header class="z-30 transition-[background-color,border-color,box-shadow,top] duration-200 motion-reduce:transition-none" x-data="{ stuck: false }" x-init="new IntersectionObserver(([entry]) => stuck = ! entry.isIntersecting, { threshold: 0 }).observe(document.getElementById('public-nav-sentinel'))" :class="stuck ? 'fixed inset-x-0 top-0 border-b border-gray-200 bg-white/95 shadow-sm backdrop-blur' : 'absolute inset-x-0 top-2 md:top-6'">
    <div class="mx-auto transition-[max-width,padding] duration-200 motion-reduce:transition-none" :class="stuck ? 'max-w-7xl px-4 sm:px-6' : 'max-w-6xl px-4 sm:px-6'">
        <div
            class="relative flex h-14 items-center justify-between gap-3 px-3 transition-[border-radius,box-shadow,background-color] duration-200 motion-reduce:transition-none"
            :class="stuck ? 'bg-transparent' : 'rounded-2xl bg-white/90 shadow-lg shadow-black/[0.03] backdrop-blur-xs'">

            <!-- Site branding -->
            <div class="flex-1 flex items-center">
                <!-- Logo -->
                <a class="inline-flex" href="{{ route('home') }}" aria-label="Sales en Marketing Vacatures, home">
                    <img class="object-cover z-40" src="{{ Vite::asset('resources/images/smv_profile.png') }}"
                         width="40" height="40" alt="Sales en Marketing Vacatures">
                </a>
            </div>

            <!-- Desktop navigation -->
            <nav class="hidden lg:flex lg:grow z-40" aria-label="Hoofdnavigatie">

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
                <div class="relative flex flex-1 justify-end z-40" x-data="{ open: false }">
                    <button class="inline-flex size-9 items-center justify-center rounded-full text-gray-700 transition hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" type="button" aria-controls="account-menu" :aria-expanded="open" x-on:click="open = ! open">
                        <span class="sr-only">Accountmenu</span>
                        <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.118a7.5 7.5 0 0 1 15 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.5-1.632Z" /></svg>
                    </button>
                    <div class="absolute right-0 top-full mt-3 w-48 rounded-xl bg-white p-2 shadow-lg shadow-black/[0.08] ring-1 ring-gray-200" id="account-menu" x-cloak x-show="open" x-transition @click.outside="open = false" @keydown.escape.window="open = false">
                        @auth
                            <a class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100" href="{{ route('filament.dashboard.pages.dashboard') }}">Dashboard</a>
                            <form method="POST" action="{{ route('filament.dashboard.auth.logout') }}">@csrf<button class="block w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-100" type="submit">Uitloggen</button></form>
                        @else
                            <a class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100" href="{{ route($loginRoute) }}">Inloggen</a>
                        @endauth
                    </div>
                </div>
            </nav>



            <!-- Mobile menu -->
            <div class="flex lg:hidden z-50" x-data="{ expanded: false }">

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
                        @foreach ($navigation as $item)
                            <li><a class="flex rounded-lg px-2 py-1.5 text-gray-700 hover:bg-gray-100" href="{{ route($item['route']) }}" @click="expanded = false">{{ $item['label'] }}</a></li>
                        @endforeach
                        @guest
                            <li class="mt-2 border-t border-gray-100 pt-2"><a class="flex rounded-lg px-2 py-1.5 text-gray-700 hover:bg-gray-100" href="{{ route($loginRoute) }}">Inloggen</a></li>
                        @endguest
                    </ul>
                </nav>

            </div>

        </div>
    </div>
</header>

@props(['darkAtTop' => false])

@php
    $navigation = [
        ['label' => 'Home', 'route' => 'home', 'active' => ['home']],
        ['label' => 'Vacatures', 'route' => 'vacancies.index', 'active' => ['vacancies.*']],
        ['label' => 'Bedrijven', 'route' => 'companies.index', 'active' => ['companies.*', 'bedrijven.*']],
        ['label' => 'Blog', 'route' => 'blog.index', 'active' => ['blog.*']],
        ['label' => 'Adverteren', 'route' => 'advertising', 'active' => ['advertising']],
    ];
    $loginRoute = 'filament.dashboard.auth.login';
    $user = auth()->user();
    $canAccessDashboard = $user?->canAccessPanel(\Filament\Facades\Filament::getPanel('dashboard')) ?? false;
@endphp

<header class="z-30 transition-[background-color,border-color,box-shadow,top] duration-200 motion-reduce:transition-none" x-data="{ stuck: false }" x-init="new IntersectionObserver(([entry]) => stuck = ! entry.isIntersecting, { threshold: 0 }).observe(document.getElementById('public-nav-sentinel'))" :class="stuck ? 'fixed inset-x-0 top-2 md:top-6' : @js($darkAtTop ? 'absolute inset-x-0 top-0 border-b border-white/10' : 'absolute inset-x-0 top-0 border-b border-gray-200')">
    <div class="mx-auto transition-[max-width,padding] duration-200 motion-reduce:transition-none" :class="stuck ? 'max-w-6xl px-4 sm:px-6' : 'max-w-7xl px-4 sm:px-6'">
        <div
            class="relative flex h-14 items-center justify-between gap-3 px-3 transition-[border-radius,box-shadow,background-color] duration-200 motion-reduce:transition-none"
            :class="stuck ? 'rounded-2xl bg-white/90 shadow-lg shadow-black/[0.03] backdrop-blur-xs' : 'bg-transparent'">

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
                                'flex items-center transition decoration-2 underline-offset-8',
                                'font-semibold underline' => $isCurrent,
                            ])
                               :class="stuck ? 'text-gray-700 hover:text-gray-900' : @js($darkAtTop ? 'text-gray-200 hover:text-white' : 'text-gray-700 hover:text-gray-900')"
                               href="{{ route($item['route']) }}"
                               @if ($isCurrent) aria-current="page" @endif>{{ $item['label'] }}</a>
                        </li>
                    @endforeach

                </ul>
                <div class="relative flex flex-1 justify-end z-40" x-data="{ open: false }">
                    <button class="grow flex max-w-44 items-center justify-end truncate" type="button" aria-controls="account-menu" aria-haspopup="true" :aria-expanded="open" x-on:click.prevent="open = ! open" x-on:keydown.escape="open = false">
                        <span class="mr-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-full" :class="stuck ? 'bg-gray-100 text-gray-600' : @js($darkAtTop ? 'bg-white/10 text-gray-100' : 'bg-gray-100 text-gray-600')">
                            <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.118a7.5 7.5 0 0 1 15 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.5-1.632Z" /></svg>
                        </span>
                        <span class="truncate text-sm font-medium" :class="stuck ? 'text-gray-700' : @js($darkAtTop ? 'text-gray-200' : 'text-gray-700')">{{ auth()->user()?->name ?? 'Account' }}</span>
                        <svg class="ml-1 h-3 w-3 shrink-0 fill-current" :class="stuck ? 'text-gray-400' : @js($darkAtTop ? 'text-gray-300' : 'text-gray-400')" viewBox="0 0 12 12" aria-hidden="true"><path d="M5.9 11.4.5 6l1.4-1.4 4 4 4-4L11.3 6z" /></svg>
                    </button>
                    <div class="origin-top-right z-50 absolute top-full right-0 min-w-60 bg-white border border-gray-200 py-1.5 rounded-lg shadow-lg overflow-hidden mt-1" id="account-menu" x-cloak x-show="open" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false" x-transition:enter="transition ease-out duration-200 transform" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-out duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        @auth
                            <div class="border-b border-gray-200 px-3 py-2">
                                <p class="truncate text-sm font-medium text-gray-800">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                            </div>
                            @if ($canAccessDashboard)
                                <a class="font-medium text-sm text-gray-600 hover:text-gray-800 block py-1.5 px-3 hover:bg-gray-50" href="{{ route('filament.dashboard.pages.dashboard') }}" x-on:click="open = false" x-on:focus="open = true">Dashboard</a>
                            @endif
                            <form method="POST" action="{{ route('filament.dashboard.auth.logout') }}">@csrf<button class="font-medium text-sm text-gray-600 hover:text-gray-800 block w-full py-1.5 px-3 text-left hover:bg-gray-50" type="submit">Uitloggen</button></form>
                        @else
                            <a class="font-medium text-sm text-gray-600 hover:text-gray-800 block py-1.5 px-3 hover:bg-gray-50" href="{{ route($loginRoute) }}" x-on:click="open = false" x-on:focus="open = true">Inloggen</a>
                            @if (Route::has('register'))
                                <a class="font-medium text-sm text-gray-600 hover:text-gray-800 block py-1.5 px-3 hover:bg-gray-50" href="{{ route('register') }}" x-on:click="open = false" x-on:focus="open = true">Registreren</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </nav>



            <!-- Mobile menu -->
            <div class="flex lg:hidden z-50" x-data="{ expanded: false }">

                <!-- Hamburger button -->
                <button
                    class="group inline-flex w-8 h-8 text-center items-center justify-center transition"
                    :class="stuck ? 'text-gray-800' : @js($darkAtTop ? 'text-gray-100' : 'text-gray-800')"
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
                    class="fixed inset-x-4 top-20 z-60 max-h-[calc(100vh-6rem)] overflow-y-auto rounded-xl bg-white shadow-lg shadow-black/[0.08] ring-1 ring-gray-200"
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
                            @php($isCurrent = request()->routeIs(...$item['active']))
                            <li><a @class([
                                'flex rounded-lg px-2 py-1.5 text-gray-700 hover:bg-gray-100 decoration-2 underline-offset-4',
                                'font-semibold underline' => $isCurrent,
                            ]) href="{{ route($item['route']) }}" @if ($isCurrent) aria-current="page" @endif @click="expanded = false">{{ $item['label'] }}</a></li>
                        @endforeach
                        <li class="mt-2 border-t border-gray-100 pt-2">
                            @auth
                                <p class="px-2 py-1.5 text-xs font-medium text-gray-500">{{ auth()->user()->name }}</p>
                                @if ($canAccessDashboard)
                                    <a class="flex rounded-lg px-2 py-1.5 text-gray-700 hover:bg-gray-100" href="{{ route('filament.dashboard.pages.dashboard') }}">Dashboard</a>
                                @endif
                                <form method="POST" action="{{ route('filament.dashboard.auth.logout') }}">@csrf<button class="flex w-full rounded-lg px-2 py-1.5 text-left text-gray-700 hover:bg-gray-100" type="submit">Uitloggen</button></form>
                            @else
                                <a class="flex rounded-lg px-2 py-1.5 text-gray-700 hover:bg-gray-100" href="{{ route($loginRoute) }}">Inloggen</a>
                                @if (Route::has('register'))
                                    <a class="flex rounded-lg px-2 py-1.5 text-gray-700 hover:bg-gray-100" href="{{ route('register') }}">Registreren</a>
                                @endif
                            @endauth
                        </li>
                    </ul>
                </nav>

            </div>

        </div>
    </div>
</header>

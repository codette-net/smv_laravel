@php
    $navigation = [
        ['label' => 'Home', 'route' => 'home', 'active' => ['home']],
        ['label' => 'Vacatures', 'route' => 'vacancies.index', 'active' => ['vacancies.*']],
        ['label' => 'Bedrijven', 'route' => 'companies.index', 'active' => ['companies.*', 'bedrijven.*']],
        ['label' => 'Blog', 'route' => 'blog.index', 'active' => ['blog.*']],
    ];
    $loginRoute = 'filament.dashboard.auth.login';
@endphp

<header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
    <div class="mx-auto flex min-h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <a class="shrink-0 rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('home') }}" aria-label="Sales en Marketing Vacatures, home">
            <img class="h-10 w-10 rounded-full object-cover" src="{{ Vite::asset('resources/images/smv_profile.png') }}" width="40" height="40" alt="Sales en Marketing Vacatures">
        </a>

        <nav class="hidden md:block" aria-label="Hoofdnavigatie">
            <ul class="flex items-center gap-1">
                @foreach ($navigation as $item)
                    @php($isCurrent = request()->routeIs(...$item['active']))
                    <li>
                        <a @class([
                            'inline-flex rounded-md px-3 py-2 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600',
                            'bg-blue-50 text-blue-700' => $isCurrent,
                            'text-slate-700 hover:bg-slate-100 hover:text-blue-700' => ! $isCurrent,
                        ]) href="{{ route($item['route']) }}" @if ($isCurrent) aria-current="page" @endif>{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="hidden md:flex md:items-center">
            @guest
                <a class="inline-flex rounded-md px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" href="{{ route($loginRoute) }}">Inloggen</a>
            @endguest
        </div>

        <div class="md:hidden" x-data="{ open: false }">
            <button class="inline-flex h-10 w-10 items-center justify-center rounded-md text-slate-700 transition hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" type="button" aria-controls="mobile-navigation" :aria-expanded="open" @click="open = ! open">
                <span class="sr-only">Menu openen</span>
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <nav class="absolute inset-x-0 top-full border-b border-slate-200 bg-white p-4 shadow-lg" id="mobile-navigation" aria-label="Mobiele hoofdnavigatie" x-cloak x-show="open" @keydown.escape.window="open = false" @click.outside="open = false">
                <ul class="space-y-1">
                    @foreach ($navigation as $item)
                        @php($isCurrent = request()->routeIs(...$item['active']))
                        <li>
                            <a @class([
                                'block rounded-md px-3 py-2 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600',
                                'bg-blue-50 text-blue-700' => $isCurrent,
                                'text-slate-700 hover:bg-slate-100 hover:text-blue-700' => ! $isCurrent,
                            ]) href="{{ route($item['route']) }}" @if ($isCurrent) aria-current="page" @endif @click="open = false">{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                    @guest
                        <li class="border-t border-slate-200 pt-2">
                            <a class="block rounded-md px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" href="{{ route($loginRoute) }}">Inloggen</a>
                        </li>
                    @endguest
                </ul>
            </nav>
        </div>
    </div>
</header>

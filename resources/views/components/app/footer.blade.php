@php
    $navigation = [
        ['label' => 'Vacatures', 'route' => 'vacancies.index'],
        ['label' => 'Bedrijven', 'route' => 'companies.index'],
        ['label' => 'Blog', 'route' => 'blog.index'],
    ];
    $loginRoute = 'filament.dashboard.auth.login';
@endphp

<footer class="mt-12 border-t border-slate-800 bg-slate-950 text-slate-300">
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 md:grid-cols-[minmax(0,1fr)_auto] md:items-start">
            <div class="max-w-md">
                <a class="inline-block rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-300" href="{{ route('home') }}" aria-label="Sales en Marketing Vacatures, home">
                    <img class="h-auto w-64 max-w-full" src="{{ Vite::asset('resources/images/smv-logo.svg') }}" width="320" height="64" alt="Sales en Marketing Vacatures">
                </a>
                <p class="mt-5 text-sm leading-6 text-slate-400">Het gespecialiseerde vacatureplatform voor sales, marketing en commerciële professionals.</p>
            </div>

            <nav aria-label="Footer navigatie">
                <h2 class="text-sm font-semibold text-white">Ontdek SMV</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($navigation as $item)
                        <li><a class="rounded-sm text-slate-300 transition hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-300" href="{{ route($item['route']) }}">{{ $item['label'] }}</a></li>
                    @endforeach
                    @guest
                        <li><a class="rounded-sm text-slate-300 transition hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-300" href="{{ route($loginRoute) }}">Inloggen</a></li>
                    @endguest
                </ul>
            </nav>
        </div>

        <div class="mt-10 border-t border-slate-800 pt-6 text-sm text-slate-500">© {{ now()->year }} Sales en Marketing Vacatures</div>
    </div>
</footer>

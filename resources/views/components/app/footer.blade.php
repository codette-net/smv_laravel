@php
    $groups = [
        'Voor werkzoekenden' => [
            ['label' => 'Vacatures', 'route' => 'vacancies.index'],
            ['label' => 'Bedrijven', 'route' => 'companies.index'],
            ['label' => 'Blog', 'route' => 'blog.index'],
        ],
        'Voor werkgevers' => [
            ['label' => 'Adverteren', 'route' => 'advertising'],
            ['label' => 'Tarieven', 'route' => 'pricing'],
            ['label' => 'Vacature plaatsen', 'route' => 'vacancy-placement.index'],
        ],
        'Over SMV' => [
            ['label' => 'Over ons', 'route' => 'about'],
            ['label' => 'Contact', 'route' => 'contact'],
        ],
    ];
    $loginRoute = 'login';
@endphp

<footer class="mt-12 border-t border-slate-200 bg-white text-slate-600">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="grid gap-10 py-10 sm:grid-cols-2 md:py-14 lg:grid-cols-12">
            <div class="sm:col-span-2 lg:col-span-5 lg:max-w-sm">
                <a class="inline-block rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('home') }}" aria-label="Sales en Marketing Vacatures, home">
                    <img class="h-auto w-64 max-w-full" src="{{ Vite::asset('resources/images/smv-logo.svg') }}" width="320" height="64" alt="Sales en Marketing Vacatures">
                </a>
                <p class="mt-5 text-sm leading-6 text-slate-500">Dé plek voor sales- en marketingprofessionals: ontdek vacatures, werkgevers en vakinhoud zonder onnodige ruis.</p>
            </div>

            <nav class="contents" aria-label="Footer navigatie">
                @foreach ($groups as $heading => $items)
                    <div class="lg:col-span-2">
                        <h2 class="text-sm font-semibold text-slate-900">{{ $heading }}</h2>
                        <ul class="mt-4 space-y-3 text-sm font-medium">
                            @foreach ($items as $item)
                                <li><a class="rounded-sm text-slate-500 transition hover:text-blue-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route($item['route']) }}">{{ $item['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <span>© {{ now()->year }} Sales en Marketing Vacatures</span>
            @guest
                <a class="w-fit rounded-sm font-medium transition hover:text-blue-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route($loginRoute) }}">Inloggen</a>
            @endguest
        </div>
    </div>
</footer>

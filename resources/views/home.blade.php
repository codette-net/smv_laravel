@extends('layouts.public')

@section('title', 'Sales- en marketingvacatures zonder de ruis | SMV')
@section('meta_description', 'Ontdek actuele sales- en marketingvacatures, werkgevers en vakinhoud voor jouw volgende stap.')
@section('canonical', route('home'))
@section('header_theme', 'dark')

@section('content')
    <section class="relative">
        <div
            class="pointer-events-none absolute inset-0 -z-10 bg-slate-900 [clip-path:polygon(0_0,_5760px_0,_5760px_calc(100%_-_160px),_0_100%)]"
            aria-hidden="true"></div>
        <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
            <div class="pt-32 pb-28 md:pt-40 md:pb-44">
                <div class="mx-auto max-w-xl text-center md:mx-0 md:text-left"><p
                        class="text-sm font-semibold uppercase tracking-widest text-blue-300">Sales &amp; Marketing
                        Vacatures</p>
                    <h1 class="mt-4 font-playfair-display text-4xl font-bold tracking-tight text-slate-100 sm:text-5xl">
                        Sales- en marketingvacatures zonder de ruis</h1>
                    <p class="mt-6 text-xl text-slate-400">Ontdek relevante vacatures, werkgevers en vakinhoud voor elke volgende stap in je commerciële loopbaan.</p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3 md:justify-start">
                        <a class="btn bg-blue-600 text-white hover:bg-blue-700" href="{{ route('vacancies.index') }}">Bekijk vacatures <span class="ml-1 text-blue-300">→</span></a>
                        <a class="btn border border-slate-600 bg-slate-800 text-white hover:bg-slate-700" href="{{ route('advertising') }}">Voor werkgevers</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-8 2xl:px-16 py-10 sm:px-6 lg:py-14" aria-labelledby="vacature-zoeker">
        <div class="lg:flex lg:items-start lg:gap-10">
            <aside
                class="mb-8 lg:sticky lg:top-24 lg:mb-0 lg:w-72 lg:shrink-0 shadow-lg shadow-blue-200/50 rounded-xl ">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Zoek vacatures</p>
                        <h2 class="mt-2 text-lg font-bold tracking-tight text-gray-800" id="vacature-zoeker">Verfijn je
                            zoekopdracht</h2>
                    </div>
                    <div class="mt-5">
                        <x-home.vacancy-search :filters="$filters" :sort="$sort" :sort-options="$sortOptions"
                                               :locations="$locations" :taxonomy-options="$taxonomyOptions"
                                               :companies="$companies" :has-filters="$hasFilters"
                                               :has-additional-filters="$hasAdditionalFilters"/>
                    </div>
                </div>
            </aside>
            <section class="min-w-0 lg:grow" aria-labelledby="recente-vacatures">
                <div
                    class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Actueel aanbod</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900"
                            id="recente-vacatures">{{ $hasFilters ? 'Gevonden vacatures' : 'Recente vacatures' }}</h2>
                    </div>
                    <a class="text-sm font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600"
                       href="{{ route('vacancies.index') }}">Bekijk alle vacatures <span aria-hidden="true">→</span></a>
                </div>

                @if ($vacancies->isNotEmpty())
                    <div class="mt-8 grid gap-4 grid-cols-[repeat(auto-fill,minmax(16rem,1fr))]">
                        @foreach ($vacancies as $vacancy)
                            <x-vacancy.card :vacancy="$vacancy" :detail-url="route('vacancies.show', $vacancy)"/>
                        @endforeach
                    </div>
                    @if ($vacancies->hasPages())
                        <div class="mt-10">{{ $vacancies->links('pagination::tailwind') }}</div>
                    @endif
                @else
                    <div
                        class="mt-8 rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">
                        Er zijn op dit moment geen actuele vacatures.
                    </div>
                @endif
            </section>
        </div>
    </section>

    <section class="border-y border-slate-200 bg-white" aria-labelledby="kandidaat-voordelen">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Gericht op jouw vak</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900" id="kandidaat-voordelen">Meer relevante kansen, minder afleiding</h2>
                <p class="mt-4 text-lg text-slate-600">Of je nu een stage, vaste baan, hybride functie of freelance-opdracht zoekt: je begint bij functies binnen sales en marketing.</p>
            </div>
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['Relevanter zoeken', 'Vacatures en werkgevers binnen sales en marketing staan bij elkaar, zodat je niet eerst door andere vakgebieden hoeft.'],
                    ['Snel vergelijken', 'Bekijk in één overzicht wat een functie inhoudt, waar je werkt en welke arbeidsvoorwaarden bekend zijn.'],
                    ['Blijf geïnspireerd', 'Verdiep je met artikelen over salescarrière, marketingcarrière, solliciteren en ontwikkelingen in het vak.'],
                ] as [$title, $description])
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                        <h3 class="text-lg font-bold text-slate-900">{{ $title }}</h3>
                        <p class="mt-3 leading-7 text-slate-600">{{ $description }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @if ($featuredCompanies->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16" aria-labelledby="werkgevers-ontdekken">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Werkgevers ontdekken</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900" id="werkgevers-ontdekken">Maak kennis met bedrijven</h2>
                    <p class="mt-3 max-w-2xl text-slate-600">Bekijk werkgevers, hun verhaal en de vacatures die zij nu hebben.</p>
                </div>
                <a class="text-sm font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('companies.index') }}">Bekijk alle bedrijven <span aria-hidden="true">→</span></a>
            </div>
            <div class="mt-8 grid gap-4 sm:grid-cols-2 md:gap-6 lg:grid-cols-3">
                @foreach ($featuredCompanies as $company)
                    <x-company.card :company="$company" :logo-url="$company->publicLogoUrl()" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="relative overflow-hidden bg-slate-900 text-white" aria-labelledby="voor-werkgevers">
        <div class="pointer-events-none absolute inset-0 bg-[repeating-linear-gradient(135deg,transparent_0,transparent_13px,rgba(96,165,250,.06)_14px,rgba(96,165,250,.06)_15px)]" aria-hidden="true"></div>
        <div class="relative mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] lg:items-center lg:px-8 lg:py-20">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-300">Voor werkgevers</p>
                <h2 class="mt-3 font-playfair-display text-3xl font-bold sm:text-4xl" id="voor-werkgevers">Bereik professionals die bewust voor sales of marketing kiezen</h2>
                <p class="mt-5 text-lg leading-8 text-slate-300">SMV combineert vacatureplaatsing met redactionele ondersteuning, zichtbaarheid en jobmarketing die passen bij uw vacature.</p>
                <a class="btn mt-7 bg-blue-600 text-white hover:bg-blue-700" href="{{ route('advertising') }}">Bekijk de mogelijkheden <span class="ml-1 text-blue-300">→</span></a>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['Gespecialiseerd bereik', 'Zichtbaar binnen een omgeving voor sales- en marketingprofessionals.'],
                    ['Scherpere vacaturetekst', 'We kunnen meedenken over duidelijkheid en clichéarme presentatie.'],
                    ['Evalueren en bijsturen', 'Tijdens de looptijd bespreken we waar aanvullende aandacht zinvol kan zijn.'],
                ] as [$title, $description])
                    <article class="rounded-xl border border-white/10 bg-white/5 p-5 backdrop-blur-sm">
                        <h3 class="font-semibold text-white">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-300">{{ $description }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @if ($latestBlogPosts->isNotEmpty())
        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Uit de blog</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Nieuwste inzichten</h2>
                    </div>
                    <a class="text-sm font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600"
                       href="{{ route('blog.index') }}">Bekijk alle artikelen <span aria-hidden="true">→</span></a>
                </div>
                <p class="mt-4 max-w-2xl text-slate-600">Lees over salescarrière, marketingcarrière, sollicitatieadvies, recruitment en ontwikkelingen in het vak.</p>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latestBlogPosts as $post)
                        <x-blog.card :post="$post" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <div class="rounded-2xl bg-blue-600 px-6 py-10 text-center text-white shadow-xl shadow-blue-900/10 sm:px-10">
            <h2 class="font-playfair-display text-3xl font-bold">Klaar voor een volgende stap?</h2>
            <p class="mx-auto mt-4 max-w-2xl text-blue-100">Ontdek een vacature die bij je past, of bespreek hoe uw organisatie gericht zichtbaar kan worden.</p>
            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <a class="btn bg-white text-blue-700 hover:bg-blue-50" href="{{ route('vacancies.index') }}">Bekijk vacatures</a>
                <a class="btn border border-blue-300 bg-blue-700 text-white hover:bg-blue-800" href="{{ route('advertising') }}">Adverteren op SMV</a>
            </div>
        </div>
    </section>
@endsection

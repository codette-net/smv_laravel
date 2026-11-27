@extends('layouts.public')

@section('title', 'Mijn account | Sales en Marketing Vacatures')
@section('canonical', route('account.index'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-6xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Mijn account</p>
                <h1 class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">Welkom, {{ auth()->user()->name }}</h1>
                <p class="mt-3 max-w-2xl leading-7 text-slate-600">
                    {{ $hasEmployerContext ? 'Beheer uw bedrijven, vacatures, plaatsingsrechten en bestellingen.' : 'Bekijk uw sollicitaties en bewaarde vacatures en bedrijven.' }}
                </p>
            </div>
            <a class="btn justify-center bg-blue-600 text-white hover:bg-blue-700" href="{{ route('vacancy-placement.index') }}">Vacature plaatsen</a>
        </div>

        @if (session('account_status'))
            <div class="mt-7 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900" role="status">{{ session('account_status') }}</div>
        @endif

        <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="space-y-10">
                @if ($hasEmployerContext)
                    <section aria-labelledby="employer-overview-heading">
                        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Werkgever</p>
                        <h2 class="mt-2 text-xl font-bold text-slate-900" id="employer-overview-heading">Werkgeversoverzicht</h2>
                        <dl class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach ([
                                ['label' => 'Actieve vacatures', 'value' => $employerStats['activeVacancies']],
                                ['label' => 'In beoordeling', 'value' => $employerStats['awaitingApproval']],
                                ['label' => 'Beschikbare rechten', 'value' => $employerStats['availableCredits']],
                                ['label' => 'Open aandachtspunten', 'value' => $employerStats['attention']],
                            ] as $stat)
                                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <dt class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</dt>
                                    <dd class="mt-2 text-3xl font-bold text-slate-900">{{ $stat['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <a class="btn justify-center bg-blue-600 text-white hover:bg-blue-700" href="{{ route('vacancy-placement.index') }}">Nieuwe vacature</a>
                            <a class="btn justify-center border border-slate-300 bg-white text-slate-800 hover:bg-slate-50" href="{{ route('account.vacancies') }}">Mijn vacatures</a>
                            <a class="btn justify-center border border-slate-300 bg-white text-slate-800 hover:bg-slate-50" href="{{ route('account.placements') }}">Plaatsingsrechten</a>
                            <a class="btn justify-center border border-slate-300 bg-white text-slate-800 hover:bg-slate-50" href="{{ route('account.orders.index') }}">Bestellingen</a>
                        </div>
                    </section>

                    <section aria-labelledby="companies-heading">
                        <h2 class="text-xl font-bold text-slate-900" id="companies-heading">Mijn bedrijven</h2>
                        <div class="mt-4 space-y-4">
                            @foreach ($companies as $company)
                                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:flex sm:items-center sm:justify-between sm:gap-5">
                                    <div class="flex min-w-0 items-center gap-4">
                                        <div class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white">
                                            @if ($company->publicLogoUrl())
                                                <img class="h-full w-full object-contain p-1.5" src="{{ $company->publicLogoUrl() }}" alt="Logo van {{ $company->name }}">
                                            @else
                                                <span class="text-xl font-bold text-blue-700">{{ Str::upper(Str::substr($company->name, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="truncate font-semibold text-slate-900">{{ $company->name }}</h3>
                                                <x-ui.badge size="xs" :variant="$company->status === \App\Enums\CompanyStatus::Active ? 'success' : ($company->status === \App\Enums\CompanyStatus::Pending ? 'warning' : 'neutral')">{{ $company->status->getLabel() }}</x-ui.badge>
                                            </div>
                                            <p class="mt-1 text-sm text-slate-500">{{ $company->vacancies_count }} {{ Str::plural('vacature', $company->vacancies_count) }}</p>
                                            @if ($company->status === \App\Enums\CompanyStatus::Pending)
                                                <p class="mt-1 text-sm text-amber-700">Uw bedrijf wacht op goedkeuring; vacatures kunnen nog niet openbaar worden.</p>
                                            @elseif (! $company->hasCompletePublicProfile())
                                                <p class="mt-1 text-sm text-amber-700">Uw bedrijfsprofiel kan nog worden aangevuld.</p>
                                            @endif
                                        </div>
                                    </div>
                                    <a class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800 sm:mt-0" href="{{ route('account.companies.edit', $company) }}">Bedrijfsprofiel beheren →</a>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <section aria-labelledby="vacancies-heading">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="text-xl font-bold text-slate-900" id="vacancies-heading">Recente vacatures</h2>
                            <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('account.vacancies') }}">Alle vacatures →</a>
                        </div>
                        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            @forelse ($vacancies as $vacancy)
                                <article class="flex flex-col gap-3 border-b border-slate-100 p-5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h3 class="font-semibold text-slate-900">{{ $vacancy->title }}</h3>
                                        <p class="mt-1 text-sm text-slate-500">{{ $vacancy->company->name }} · {{ $vacancy->status->getLabel() }}</p>
                                        @if ($vacancy->status === \App\Enums\VacancyStatus::Pending)
                                            <p class="mt-1 text-sm text-amber-700">Deze vacature wacht op goedkeuring door SMV.</p>
                                        @elseif ($vacancy->status === \App\Enums\VacancyStatus::Active && $vacancy->published_at?->isFuture())
                                            <p class="mt-1 text-sm text-blue-700">Publicatie gepland voor {{ $vacancy->published_at->translatedFormat('j F Y H:i') }}.</p>
                                        @endif
                                    </div>
                                    @if ($vacancy->status === \App\Enums\VacancyStatus::Draft)
                                        <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('vacancy-placement.edit', $vacancy) }}">Concept bewerken</a>
                                    @elseif (in_array($vacancy->id, $publicVacancyIds, true))
                                        <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('vacancies.show', $vacancy) }}">Bekijk vacature</a>
                                    @else
                                        <span class="text-sm text-slate-500">Alleen bekijken</span>
                                    @endif
                                </article>
                            @empty
                                <p class="p-6 text-slate-600">U heeft nog geen vacatures geplaatst.</p>
                            @endforelse
                        </div>
                    </section>

                    <section aria-labelledby="placements-heading">
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h2 class="text-xl font-bold text-slate-900" id="placements-heading">Plaatsingsrechten</h2>
                                    <p class="mt-2 text-slate-600">{{ $creditCounts['available'] }} beschikbaar · {{ $creditCounts['assigned'] }} toegewezen · {{ $creditCounts['active'] }} actief · {{ $creditCounts['review'] }} vereist controle.</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">Na betaling ontvangt uw bedrijf een recht. Toewijzing publiceert niets; de looptijd start pas wanneer bedrijf en vacature openbaar zijn.</p>
                                </div>
                                <a class="inline-flex shrink-0 text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('account.placements') }}">Bekijk rechten →</a>
                            </div>
                        </div>
                    </section>

                    <section aria-labelledby="orders-heading">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="text-xl font-bold text-slate-900" id="orders-heading">Recente bestellingen</h2>
                            <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('account.orders.index') }}">Alle {{ $ordersCount }} bekijken →</a>
                        </div>
                        <div class="mt-4 space-y-4">
                            @forelse ($recentOrders as $order)
                                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:flex sm:items-center sm:justify-between sm:gap-5">
                                    <div>
                                        <h3 class="font-semibold text-slate-900">{{ $order->order_number }}</h3>
                                        <p class="mt-1 text-sm text-slate-500">Pakket: {{ $order->orderItems->pluck('title_snapshot')->filter()->join(', ') ?: '-' }}</p>
                                        <p class="mt-1 text-sm text-slate-500">Vacature: {{ $order->orderItems->pluck('vacancy.title')->filter()->join(', ') ?: 'nog niet gekoppeld' }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ \App\Support\Commerce\Money::format($order->total_cents, $order->currency) }} incl. btw · {{ $order->created_at->format('d-m-Y') }}</p>
                                        <x-order.status-badges class="mt-3" :order="$order" />
                                    </div>
                                    <a class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800 sm:mt-0" href="{{ route('account.orders.show', $order) }}">Details →</a>
                                </article>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-slate-600">Er zijn nog geen bestellingen.</div>
                            @endforelse
                        </div>
                    </section>

                    @if ($hasCandidateContext)
                        <x-account.candidate-overview
                            :applications-count="$applicationsCount"
                            :saved-vacancies-count="$savedVacanciesCount"
                            :saved-companies-count="$savedCompaniesCount"
                            heading="Ook in dit account"
                        />
                    @endif
                @else
                    <x-account.candidate-overview
                        :applications-count="$applicationsCount"
                        :saved-vacancies-count="$savedVacanciesCount"
                        :saved-companies-count="$savedCompaniesCount"
                        heading="Mijn overzicht"
                    />

                    <section class="rounded-xl border border-blue-200 bg-blue-50 p-6" aria-labelledby="employer-start-heading">
                        <h2 class="text-xl font-bold text-slate-900" id="employer-start-heading">Wilt u namens een bedrijf een vacature plaatsen?</h2>
                        <p class="mt-2 leading-7 text-slate-700">Start de plaatsingsflow. U kunt met hetzelfde account een bedrijfsprofiel aanmaken; uw werkzoekende activiteiten blijven gewoon beschikbaar.</p>
                        <a class="mt-4 inline-flex font-semibold text-blue-700 hover:text-blue-800" href="{{ route('vacancy-placement.index') }}">Start met vacature plaatsen →</a>
                    </section>
                @endif
            </div>

            <aside class="h-fit rounded-xl border border-slate-200 bg-slate-100 p-5">
                <h2 class="font-semibold text-slate-900">{{ $hasEmployerContext ? 'Wat vraagt aandacht?' : 'Verder zoeken?' }}</h2>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    @if ($hasEmployerContext)
                        <li>Maak conceptvacatures af en dien ze in voor beoordeling.</li>
                        <li>Een pending bedrijf of vacature blokkeert publicatie en de start van een betaalde looptijd.</li>
                        <li>Controleer open betalingen en plaatsingsrechten met de status ‘Controle vereist’.</li>
                    @else
                        <li><a class="font-semibold text-blue-700 hover:text-blue-800" href="{{ route('vacancies.index') }}">Bekijk alle vacatures →</a></li>
                        <li>Bewaar interessante vacatures en bedrijven om ze hier terug te vinden.</li>
                    @endif
                </ul>
            </aside>
        </div>
    </section>
@endsection

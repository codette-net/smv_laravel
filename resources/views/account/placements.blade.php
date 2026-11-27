@extends('layouts.public')

@section('title', 'Mijn plaatsingsrechten | Sales en Marketing Vacatures')
@section('canonical', route('account.placements'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-5xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('account.index') }}">← Mijn account</a>
        <p class="mt-8 text-sm font-semibold uppercase tracking-widest text-blue-700">Werkgever</p>
        <h1 class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">Mijn plaatsingsrechten</h1>
        <p class="mt-3 max-w-3xl leading-7 text-slate-600">Bekijk beschikbare en gebruikte plaatsingen. Betaling kent een recht toe aan uw bedrijf; toewijzing publiceert een vacature nooit automatisch. De betaalde looptijd start pas wanneer bedrijf en vacature openbaar zijn.</p>

        @if (session('account_status'))
            <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900" role="status">{{ session('account_status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">{{ $errors->first() }}</div>
        @endif

        <dl class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Beschikbaar', 'value' => $creditCounts['available']],
                ['label' => 'Toegewezen / wacht', 'value' => $creditCounts['assigned']],
                ['label' => 'Actief', 'value' => $creditCounts['active']],
                ['label' => 'Controle vereist', 'value' => $creditCounts['review']],
            ] as $stat)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <dt class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</dt>
                    <dd class="mt-2 text-3xl font-bold text-slate-900">{{ $stat['value'] }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-5 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-900">
            <strong>Van betaling naar publicatie:</strong> bevestigde betaling → recht toegekend → recht aan een conceptvacature toegewezen → bedrijf en vacature goedgekeurd → looptijd geactiveerd. Goedkeuring en publicatie blijven altijd aparte stappen.
        </div>

        <div class="mt-8 space-y-5">
            @forelse ($credits as $credit)
                @php($status = $credit->status())
                <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold text-slate-900">{{ $credit->package_name_snapshot }}</h2>
                                <x-ui.badge size="xs" :variant="$status->badgeVariant()">{{ $status->getLabel() }}</x-ui.badge>
                            </div>
                            <p class="mt-2 text-sm text-slate-500">{{ $credit->company->name }} · {{ $credit->duration_days_snapshot }} dagen · bestelling {{ $credit->order->order_number }}</p>
                            @if ($credit->vacancy)
                                <p class="mt-2 text-sm text-slate-700">Vacature: {{ $credit->vacancy->title }}</p>
                            @endif
                            @if ($credit->expires_at)
                                <p class="mt-1 text-sm text-slate-500">Plaatsing geldig tot {{ $credit->expires_at->translatedFormat('j F Y H:i') }}</p>
                            @endif
                            @if ($explanation = \App\Support\Commerce\PlacementCreditPresentation::explanation($credit))
                                <p class="mt-3 text-sm font-medium {{ $status === \App\Enums\PlacementCreditStatus::RequiresReview ? 'text-amber-800' : 'text-slate-700' }}">{{ $explanation }}</p>
                            @endif
                        </div>
                        <a class="text-sm font-semibold text-blue-700 hover:text-blue-800" href="{{ route('account.orders.show', $credit->order) }}">Bestelling bekijken →</a>
                    </div>

                    @if ($status === \App\Enums\PlacementCreditStatus::Available)
                        @php($eligible = ($vacancies[$credit->company_id] ?? collect())->filter(fn ($vacancy) => $vacancy->placement_package?->value === $credit->product_code))
                        @if ($eligible->isNotEmpty())
                            <form class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-end" action="{{ route('account.placements.redeem', $credit) }}" method="POST">
                                @csrf
                                <label class="flex-1 text-sm font-medium text-slate-700">
                                    Gebruik voor conceptvacature
                                    <select class="form-select mt-2 w-full" name="vacancy_id" required>
                                        <option value="">Kies een vacature</option>
                                        @foreach ($eligible as $vacancy)
                                            <option value="{{ $vacancy->id }}">{{ $vacancy->title }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <x-ui.button type="submit" variant="brand">Recht gebruiken</x-ui.button>
                            </form>
                        @else
                            <p class="mt-5 border-t border-slate-100 pt-5 text-sm text-slate-600">Maak eerst een concept met hetzelfde pakket om dit recht te gebruiken. <a class="font-semibold text-blue-700 hover:text-blue-800" href="{{ route('vacancy-placement.index') }}">Vacature plaatsen →</a></p>
                        @endif
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">Er zijn nog geen betaalde plaatsingsrechten.</div>
            @endforelse
        </div>
    </section>
@endsection

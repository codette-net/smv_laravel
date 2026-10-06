@props(['companies'])

@if ($companies->isNotEmpty())
    @php
        $repeatCount = (int) ceil(10 / $companies->count());
        $trackCompanies = collect(range(1, max(1, $repeatCount)))
            ->flatMap(fn () => $companies)
            ->values();
    @endphp

    <section class="bg-slate-900 text-white" aria-labelledby="company-logo-banner-title" data-company-logo-banner>
        <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-widest text-blue-300">Werkgevers</p>
                    <h2 class="mt-1 text-2xl font-bold text-white" id="company-logo-banner-title">Ontdek werkgevers</h2>
                </div>
                <a class="text-sm font-semibold text-blue-200 transition hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white" href="{{ route('companies.index') }}">Bekijk alle bedrijven <span aria-hidden="true">→</span></a>
            </div>

            <div class="company-logo-marquee-window relative -mx-4 mt-6 overflow-hidden px-4 py-2 sm:mx-0 sm:px-0" role="list" aria-label="Publieke werkgevers">
                <div class="company-logo-marquee flex w-max">
                    @foreach ([false, true] as $duplicateTrack)
                        <div class="company-logo-marquee-group flex shrink-0 gap-3 pr-3" @if ($duplicateTrack) aria-hidden="true" @endif>
                            @foreach ($trackCompanies as $company)
                                @php
                                    $logoUrl = $company->publicLogoUrl();
                                    $duplicateItem = $duplicateTrack || $loop->index >= $companies->count();
                                @endphp
                                <a class="group flex h-28 w-36 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white p-4 shadow-sm transition hover:border-blue-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" href="{{ route('bedrijven.show', $company) }}" role="listitem" aria-label="Bekijk {{ $company->name }}" @if ($duplicateItem) aria-hidden="true" tabindex="-1" @endif>
                                    @if ($logoUrl)
                                        <img class="max-h-full max-w-full object-contain" src="{{ $logoUrl }}" alt="Logo van {{ $company->name }}" loading="lazy">
                                    @else
                                        <span class="text-2xl font-bold text-blue-700" aria-hidden="true">{{ Str::upper(Str::substr(trim($company->name), 0, 1)) }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif

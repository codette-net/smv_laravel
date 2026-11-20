<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Dit rapport telt <strong>raw outbound apply clicks</strong> van {{ $periodFrom }} tot en met {{ $periodTo }} (UTC).
                Elke succesvolle redirect is één event. De cijfers zijn geen unieke kandidaten, sollicitaties,
                conversies, partnerbevestigde clicks of billable CPC-clicks; dubbelklikken en bots kunnen meetellen.
            </p>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Totaal raw outbound apply clicks</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ number_format($totalClicks, 0, ',', '.') }}</div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Clicks per bedrijf</h2>
                <div class="mt-3 max-h-64 space-y-2 overflow-y-auto text-sm">
                    @forelse ($companyTotals as $company)
                        <div class="flex justify-between gap-4">
                            <span class="truncate text-gray-600 dark:text-gray-300">{{ $company->name }}</span>
                            <span class="font-semibold text-gray-950 dark:text-white">{{ $company->click_count }}</span>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400">Geen resultaten.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Clicks per importbron / partner</h2>
                <div class="mt-3 max-h-64 space-y-2 overflow-y-auto text-sm">
                    @forelse ($sourceTotals as $source)
                        <div class="flex justify-between gap-4">
                            <span class="truncate text-gray-600 dark:text-gray-300">{{ $source->name ?? 'Geen importbron' }}</span>
                            <span class="font-semibold text-gray-950 dark:text-white">{{ $source->click_count }}</span>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400">Geen resultaten.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>

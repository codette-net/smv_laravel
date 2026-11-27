<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Dit rapport vergelijkt <strong>ruwe openbare vacature-detailweergaven</strong> met
                <strong>raw outbound apply clicks</strong> van {{ $periodFrom }} tot en met {{ $periodTo }} (UTC).
                Elke geldige GET-weergave en succesvolle externe redirect is een afzonderlijk event. De cijfers zijn geen
                unieke bezoekers of kandidaten, sollicitaties, conversies, hires, partnerbevestigde clicks of billable CPC-clicks;
                verversen, dubbelklikken, prefetches en bots kunnen meetellen.
                Raw events worden maximaal 24 maanden bewaard; oudere perioden leveren daarom geen historische waarden op.
            </p>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Detailweergaven (ruw)</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ number_format($totalViews, 0, ',', '.') }}</div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Externe clicks (ruw)</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ number_format($totalClicks, 0, ',', '.') }}</div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Externe sollicitatie-CTR (ruw)</div>
                <div class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $totalCtr }}</div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Per bedrijf</h2>
                <div class="mt-3 max-h-64 space-y-2 overflow-y-auto text-sm">
                    @forelse ($companyTotals as $company)
                        <div class="grid grid-cols-[minmax(0,1fr)_auto_auto_auto] gap-3">
                            <span class="truncate text-gray-600 dark:text-gray-300">{{ $company->name }}</span>
                            <span class="text-gray-500">{{ $company->view_count }} views</span>
                            <span class="text-gray-500">{{ $company->click_count }} clicks</span>
                            <span class="font-semibold text-gray-950 dark:text-white">{{ $this->formatCtr((int) $company->click_count, (int) $company->view_count) }}</span>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400">Geen resultaten.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Per importbron / partner</h2>
                <div class="mt-3 max-h-64 space-y-2 overflow-y-auto text-sm">
                    @forelse ($sourceTotals as $source)
                        <div class="grid grid-cols-[minmax(0,1fr)_auto_auto_auto] gap-3">
                            <span class="truncate text-gray-600 dark:text-gray-300">{{ $source->name ?? 'Geen importbron' }}</span>
                            <span class="text-gray-500">{{ $source->view_count }} views</span>
                            <span class="text-gray-500">{{ $source->click_count }} clicks</span>
                            <span class="font-semibold text-gray-950 dark:text-white">{{ $this->formatCtr((int) $source->click_count, (int) $source->view_count) }}</span>
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

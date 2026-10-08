<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Actuele bewaarstatistieken</x-slot>
        <x-slot name="description">Huidige saves van bestaande vacatures en bedrijven. Dit zijn geen historische totalen.</x-slot>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                <p class="text-sm text-gray-500 dark:text-gray-400">Bewaarde vacatures</p>
                <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $currentVacancySaves }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                <p class="text-sm text-gray-500 dark:text-gray-400">Bewaarde bedrijven</p>
                <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $currentCompanySaves }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div>
                <h3 class="font-semibold text-gray-950 dark:text-white">Meest bewaarde vacatures</h3>
                <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                    @forelse ($topVacancies as $vacancy)
                        <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-4 py-3 last:border-b-0 dark:border-white/10">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $vacancy->title }}</p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $vacancy->company?->name ?? 'Bedrijf niet beschikbaar' }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $vacancy->saved_by_users_count }}</span>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400">Nog geen bewaarde vacatures.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h3 class="font-semibold text-gray-950 dark:text-white">Meest bewaarde bedrijven</h3>
                <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                    @forelse ($topCompanies as $company)
                        <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-4 py-3 last:border-b-0 dark:border-white/10">
                            <p class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $company->name }}</p>
                            <span class="shrink-0 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $company->saved_by_users_count }}</span>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400">Nog geen bewaarde bedrijven.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

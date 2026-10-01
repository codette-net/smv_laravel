<x-filament-panels::page>
    @if ($error)
        <x-filament::section heading="Preview niet beschikbaar">
            <p>{{ $error }}</p>
        </x-filament::section>
    @endif

    <x-filament::section heading="Samenvatting importpreview">
        @php
            $summaries = [
                ['key' => 'previewed', 'label' => 'bekeken'],
                ['key' => 'ready', 'label' => 'klaar voor import'],
                ['key' => 'warnings', 'label' => 'waarschuwingen'],
                ['key' => 'needs_resolution', 'label' => 'actie vereist'],
                ['key' => 'errors', 'label' => 'fouten'],
            ];
        @endphp
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($summaries as $summary)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <div class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $preview['counts'][$summary['key']] ?? 0 }}</div>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $summary['label'] }}</div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section heading="Preview filteren" description="Toon records met een bepaalde importstatus.">
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter op status">
            <span class="mr-1 text-sm font-medium text-gray-700 dark:text-gray-200">Filter op status:</span>
            @foreach (['Alles', 'Klaar voor import', 'Waarschuwing', 'Actie vereist', 'Fout'] as $state)
                <button
                    type="button"
                    wire:click="$set('filter', '{{ $state }}')"
                    @class([
                        'rounded-lg border px-3 py-2 text-sm font-medium',
                        'border-primary-600 bg-primary-600 text-white' => $filter === $state,
                        'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-white/20 dark:bg-white/5 dark:text-gray-200' => $filter !== $state,
                    ])
                >
                    {{ match ($state) {
                        'Klaar voor import' => 'Klaar',
                        'Waarschuwing' => 'Waarschuwingen',
                        'Actie vereist' => 'Actie vereist',
                        'Fout' => 'Fouten',
                        default => 'Alles',
                    } }}
                </button>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section heading="Genormaliseerde records" description="Bekijk per record de genormaliseerde waarden en eventuele meldingen.">
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
            <table class="min-w-[960px] w-full table-fixed text-left text-sm">
                <thead class="bg-gray-50 text-gray-700 dark:bg-white/5 dark:text-gray-200">
                    <tr>
                        <th class="w-40 px-4 py-3 font-semibold">Status</th>
                        <th class="w-44 px-4 py-3 font-semibold">Referentie</th>
                        <th class="w-72 px-4 py-3 font-semibold">Titel</th>
                        <th class="w-52 px-4 py-3 font-semibold">Locatie</th>
                        <th class="w-28 px-4 py-3 font-semibold">Meldingen</th>
                        <th class="w-32 px-4 py-3 font-semibold">Acties</th>
                    </tr>
                </thead>
                <tbody x-data="{ openRow: null }" class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-transparent">
                    @foreach ($preview['records'] as $row)
                        @if ($filter === 'Alles' || $filter === $row['status'])
                            @php
                                $messageCount = count($row['warnings']) + count($row['errors']) + count($row['validation_warnings']) + count($row['validation_errors']) + count($row['unresolved']);
                                $detailId = 'record-detail-'.md5((string) $row['position']);
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-4">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-400' => $row['status'] === 'Klaar voor import',
                                        'bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-400' => $row['status'] === 'Waarschuwing',
                                        'bg-info-50 text-info-700 dark:bg-info-400/10 dark:text-info-400' => $row['status'] === 'Actie vereist',
                                        'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400' => $row['status'] === 'Fout',
                                    ])>{{ $row['status'] }}</span>
                                </td>
                                <td class="break-words px-4 py-4 text-gray-700 dark:text-gray-200">{{ data_get($row['data'], 'source_reference', '-') }}</td>
                                <td class="px-4 py-4">
                                    <span class="block truncate font-medium text-gray-950 dark:text-white" title="{{ data_get($row['data'], 'vacancy.title', '-') }}">{{ data_get($row['data'], 'vacancy.title', '-') }}</span>
                                </td>
                                <td class="px-4 py-4"><span class="block truncate" title="{{ data_get($row['data'], 'vacancy.location', '-') }}">{{ data_get($row['data'], 'vacancy.location', '-') }}</span></td>
                                <td class="px-4 py-4">{{ $messageCount }}</td>
                                <td class="px-4 py-4">
                                    <button type="button" class="font-medium text-primary-600 hover:underline" x-on:click="openRow = openRow === @js($detailId) ? null : @js($detailId)" :aria-expanded="openRow === @js($detailId)" aria-controls="{{ $detailId }}">
                                        <span x-text="openRow === @js($detailId) ? 'Sluiten' : 'Details'"></span>
                                    </button>
                                </td>
                            </tr>
                            <tr x-cloak x-show="openRow === @js($detailId)" id="{{ $detailId }}" class="bg-gray-50/70 dark:bg-white/[0.03]">
                                <td colspan="6" class="px-4 py-5">
                                    <div class="grid gap-5 lg:grid-cols-2">
                                        <div>
                                            <h3 class="font-semibold text-gray-950 dark:text-white">Waarom heeft dit record deze status?</h3>
                                            @if ($messageCount === 0)
                                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Er zijn geen waarschuwingen, acties of fouten.</p>
                                            @else
                                                <ul class="mt-2 space-y-2 text-sm">
                                                    @foreach ($row['warnings'] as $message)<li><strong>Waarschuwing:</strong> {{ $message }}</li>@endforeach
                                                    @foreach ($row['validation_warnings'] as $message)<li><strong>Waarschuwing:</strong> {{ $message['message'] ?? 'Controleer dit veld.' }}</li>@endforeach
                                                    @foreach ($row['errors'] as $message)<li><strong>Fout:</strong> {{ $message }}</li>@endforeach
                                                    @foreach ($row['validation_errors'] as $message)<li><strong>Fout:</strong> {{ $message['message'] ?? 'Dit record is ongeldig.' }}</li>@endforeach
                                                    @foreach ($row['unresolved'] as $message)
                                                        <li>
                                                            <strong>Actie vereist:</strong> {{ $message['message'] ?? 'Taxonomiewaarde is nog niet gekoppeld.' }}
                                                            @if (filled($message['source_value'] ?? null))
                                                                <span>Bronwaarde: {{ $message['source_value'] }}.</span>
                                                                <button type="button" class="ml-2 font-medium text-primary-600 hover:underline" x-on:click="$wire.prepareTaxonomyResolution(@js(str($message['field'] ?? '')->after('taxonomy.')->toString()), @js((string) $message['source_value'])); document.getElementById('taxonomy-resolution').scrollIntoView({ behavior: 'smooth' })">Waarde koppelen</button>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="font-semibold text-gray-950 dark:text-white">Genormaliseerde gegevens</h3>
                                            <div class="mt-2 max-h-80 max-w-full overflow-auto rounded-lg bg-white p-3 dark:bg-gray-950">
                                                <pre class="max-w-full whitespace-pre-wrap break-all text-xs">{{ json_encode($row['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        </div>
                                        <details class="min-w-0">
                                            <summary class="cursor-pointer font-semibold">Herkomst van mapping</summary>
                                            <pre class="mt-2 max-h-64 max-w-full overflow-auto whitespace-pre-wrap break-all rounded-lg bg-white p-3 text-xs dark:bg-gray-950">{{ json_encode($row['provenance'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </details>
                                        <details class="min-w-0">
                                            <summary class="cursor-pointer font-semibold">Bronrecord, ingekort</summary>
                                            <pre class="mt-2 max-h-64 max-w-full overflow-auto whitespace-pre-wrap break-all rounded-lg bg-white p-3 text-xs dark:bg-gray-950">{{ json_encode($row['source'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section id="taxonomy-resolution" heading="Taxonomiewaarde koppelen" description="Koppel een niet-herkende bronwaarde aan een bestaande SMV-categorie.">
        <form wire:submit="saveTaxonomyMapping" class="grid gap-4 md:grid-cols-3">
            <label class="block text-sm font-medium">
                <span>Type</span>
                <select wire:model.live="resolutionType" class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-900">
                    <option value="employment_type">Dienstverband</option>
                    <option value="workplace">Werklocatie</option>
                    <option value="sector">Sector</option>
                    <option value="function_area">Functiegebied</option>
                    <option value="experience">Ervaring</option>
                </select>
            </label>
            <label class="block text-sm font-medium">
                <span>Bronwaarde</span>
                <input wire:model="resolutionValue" type="text" class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-900" placeholder="Waarde uit de bron">
            </label>
            <label class="block text-sm font-medium">
                <span>Koppelen aan</span>
                <select wire:model="resolutionCategoryId" class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-900">
                    <option value="">Kies een categorie</option>
                    @foreach ($this->categoriesForResolution() as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                </select>
                @error('resolutionCategoryId')<span class="mt-1 block text-sm text-danger-600">{{ $message }}</span>@enderror
            </label>
            <div class="md:col-span-3">
                <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">Koppeling opslaan</button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>

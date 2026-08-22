<x-filament-panels::page>
    @if ($error)<x-filament::section heading="Preview niet beschikbaar"><p>{{ $error }}</p></x-filament::section>@endif
    <x-filament::section heading="Importpreview">
        @foreach ($preview['counts'] as $label => $count)<span class="mr-6"><strong>{{ ucfirst($label) }}</strong>: {{ $count }}</span>@endforeach
    </x-filament::section>
    <x-filament::section heading="Genormaliseerde records">
        <div class="mb-4">@foreach (['Alles','Klaar','Waarschuwing','Fout'] as $state)<button wire:click="$set('filter', '{{ $state }}')" class="mr-2">{{ $state === 'Waarschuwing' ? 'Waarschuwingen' : ($state === 'Fout' ? 'Fouten' : $state) }}</button>@endforeach</div>
        <table class="w-full text-sm"><thead><tr><th>Status</th><th>Referentie</th><th>Titel</th><th>Locatie</th><th>Meldingen</th></tr></thead><tbody>@foreach ($preview['records'] as $row) @if($filter === 'Alles' || $filter === $row['status'])<tr x-data="{open:false}"><td>{{ $row['status'] }}</td><td>{{ data_get($row['data'], 'source_reference', '-') }}</td><td><button @click="open=!open">{{ data_get($row['data'], 'vacancy.title', '-') }}</button><div x-show="open"><p class="mt-2 font-medium">Genormaliseerde gegevens</p><pre>{{ json_encode($row['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre><p>{{ implode(' · ', $row['warnings']) }} {{ implode(' · ', $row['errors']) }}</p><p class="mt-2 font-medium">Herkomst van mapping</p><pre>{{ json_encode($row['provenance'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre><p class="mt-2 font-medium">Bronrecord (ingekort)</p><pre>{{ json_encode($row['source'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div></td><td>{{ data_get($row['data'], 'vacancy.location', '-') }}</td><td>{{ count($row['warnings']) + count($row['errors']) }}</td></tr>@endif @endforeach</tbody></table>
    </x-filament::section>
</x-filament-panels::page>

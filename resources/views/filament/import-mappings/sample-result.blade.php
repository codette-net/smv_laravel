@if ($result === null)
    <p>Voor deze importbron is nog geen lokaal voorbeeldbestand geconfigureerd.</p>
@else
    <pre class="max-h-96 overflow-auto text-xs">{{ json_encode($result->data->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    @if ($result->warnings !== []) <p><strong>Waarschuwingen:</strong> {{ implode(' · ', $result->warnings) }}</p> @endif
    @if ($result->errors !== []) <p><strong>Fouten:</strong> {{ implode(' · ', $result->errors) }}</p> @endif
@endif

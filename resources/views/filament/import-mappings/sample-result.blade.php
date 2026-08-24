@if ($result === null)
    <p>Voor deze importbron is nog geen lokaal voorbeeldbestand geconfigureerd.</p>
@else
    <div class="max-h-[70vh] max-w-full overflow-auto rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
        <pre class="max-w-full whitespace-pre-wrap break-all text-xs">{{ json_encode($result->data->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
    @if ($result->warnings !== []) <p><strong>Waarschuwingen:</strong> {{ implode(' · ', $result->warnings) }}</p> @endif
    @if ($result->errors !== []) <p><strong>Fouten:</strong> {{ implode(' · ', $result->errors) }}</p> @endif
@endif

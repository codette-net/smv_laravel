<?php

namespace App\Imports\Preview;

use App\Enums\ImportTransport;
use App\Imports\Data\SourcePayload;
use App\Imports\ImportReaderResolver;
use App\Imports\Mapping\ImportMapper;
use App\Imports\Mapping\MappingCompletion;
use App\Imports\RecordSelector;
use App\Imports\SourceFetcher;
use App\Imports\Validation\ImportRecordValidator;
use App\Models\ImportMapping;
use App\Models\ImportSource;
use RuntimeException;

class ImportPreview
{
    public function build(ImportSource $source, ImportMapping $mapping, int $limit = 25): array
    {
        if (app(MappingCompletion::class)->for($mapping) !== 'Klaar voor preview') {
            throw new RuntimeException('De importmapping is onvolledig.');
        }

        $path = data_get($source->configuration, 'sample_path');
        $payload = in_array($source->transport, [ImportTransport::Http, ImportTransport::Api], true) ? app(SourceFetcher::class)->fetch($source) : (is_string($path) && is_file($path) ? SourcePayload::fromPath($path) : throw new RuntimeException('Er is geen leesbaar lokaal voorbeeldbestand geconfigureerd.'));
        $records = [];
        foreach (app(RecordSelector::class)->filter(app(ImportReaderResolver::class)->for($source)->records($source, $payload), $source->selection_rules) as $record) {
            if (count($records) >= $limit) {
                break;
            }
            $result = app(ImportMapper::class)->map($record, $mapping, $source);
            $outcome = app(ImportRecordValidator::class)->validate($result->data, $source);
            $records[] = new PreviewRecord(
                $record->position,
                $result,
                $outcome,
                $mapping->fields->map(fn ($field) => ['destination' => $field->destination_key, 'paths' => $field->source_paths, 'operation' => $field->operation])->all(),
                $this->boundedSource($record->data),
            );
        }
        $counts = ['previewed' => count($records), 'ready' => 0, 'warnings' => 0, 'needs_resolution' => 0, 'errors' => 0];
        foreach ($records as $record) {
            match ($record->status()) {
                'Klaar voor import' => $counts['ready']++, 'Waarschuwing' => $counts['warnings']++, 'Actie vereist' => $counts['needs_resolution']++, default => $counts['errors']++
            };
        }

        return compact('counts', 'records');
    }

    /** @param array<string, mixed> $source */
    private function boundedSource(array $source, int $depth = 0): array
    {
        if ($depth >= 3) {
            return ['…' => 'Dieper geneste brondata is ingekort.'];
        }

        return collect($source)->take(20)->map(function (mixed $value) use ($depth): mixed {
            if (is_array($value)) {
                return $this->boundedSource($value, $depth + 1);
            }

            if (is_string($value) && mb_strlen($value) > 500) {
                return mb_substr($value, 0, 500).'…';
            }

            return $value;
        })->all();
    }
}

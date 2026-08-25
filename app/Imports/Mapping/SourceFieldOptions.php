<?php

namespace App\Imports\Mapping;

use App\Enums\ImportTransport;
use App\Imports\Data\SourceRecord;
use App\Imports\FieldDiscovery;
use App\Imports\ImportReaderResolver;
use App\Imports\LocalSourceLoader;
use App\Imports\SourceFetcher;
use App\Models\ImportSource;
use Illuminate\Support\Facades\Cache;

class SourceFieldOptions
{
    public function firstRecordFor(ImportSource $source): ?SourceRecord
    {
        $discovery = $this->isRemote($source) ? $this->cachedDiscovery($source) : $this->discoverSafely($source);
        $record = $discovery['records'][0] ?? null;

        return is_array($record) ? new SourceRecord($record['position'], $record['data'], $record['record_path']) : null;
    }

    /** @return array<string, string> */
    public function for(ImportSource $source): array
    {
        $discovery = $this->isRemote($source) ? $this->cachedDiscovery($source) : $this->discoverSafely($source);

        return collect($discovery['metadata'] ?? [])->mapWithKeys(fn (array $field, string $path) => [$path => "{$path} ({$field['type']}, {$field['present']}×)"])->all();
    }

    /** @return array<string, array{type: string, present: int, samples: array<int, mixed>}> */
    public function metadataFor(ImportSource $source): array
    {
        $discovery = $this->isRemote($source) ? $this->cachedDiscovery($source) : $this->discoverSafely($source);

        return $discovery['metadata'] ?? [];
    }

    /** @return array<string, array{type: string, present: int, samples: array<int, mixed>}> */
    public function refresh(ImportSource $source): array
    {
        $discovery = $this->discover($source);
        if ($this->isRemote($source)) {
            $discovery['fingerprint'] = $this->fingerprint($source);
            $discovery['analyzed_at'] = now()->toIso8601String();
            Cache::put($this->cacheKey($source), $discovery, now()->addDay());
        }

        return $discovery['metadata'];
    }

    public function stateFor(ImportSource $source): string
    {
        if (! $this->isRemote($source)) {
            return 'Lokale bronvelden beschikbaar';
        }

        $cached = Cache::get($this->cacheKey($source));
        if (! is_array($cached)) {
            return 'Nog niet geanalyseerd';
        }
        if (($cached['fingerprint'] ?? null) !== $this->fingerprint($source)) {
            return 'Bron gewijzigd, opnieuw analyseren';
        }

        $analyzedAt = isset($cached['analyzed_at']) ? date_create_immutable($cached['analyzed_at']) : null;

        return $analyzedAt
            ? 'Geanalyseerd: '.$analyzedAt->setTimezone(now()->timezone)->format('d-m-Y H:i')
            : 'Geanalyseerd';
    }

    /** @return array{records: list<array{position: int|string, data: array<string, mixed>, record_path: ?string}>, metadata: array<string, array{type: string, present: int, samples: array<int, mixed>}>} */
    private function discoverSafely(ImportSource $source): array
    {
        try {
            return $this->discover($source);
        } catch (\Throwable) {
            return ['records' => [], 'metadata' => []];
        }
    }

    /** @return array{records: list<array{position: int|string, data: array<string, mixed>, record_path: ?string}>, metadata: array<string, array{type: string, present: int, samples: array<int, mixed>}>} */
    private function discover(ImportSource $source): array
    {
        $payload = $this->isRemote($source)
            ? app(SourceFetcher::class)->fetch($source)
            : app(LocalSourceLoader::class)->forSource($source);
        $records = [];
        foreach (app(ImportReaderResolver::class)->for($source)->records($source, $payload) as $record) {
            $records[] = $record;
            if (count($records) === 5) {
                break;
            }
        }
        $metadata = collect(app(FieldDiscovery::class)->discover($records))->mapWithKeys(fn ($field) => [$field->path => ['type' => $field->type, 'present' => $field->present, 'samples' => array_map(fn ($sample) => str($sample)->limit(120)->toString(), $field->samples)]])->all();

        return [
            'records' => array_map(fn (SourceRecord $record): array => ['position' => $record->position, 'data' => $record->data, 'record_path' => $record->recordPath], $records),
            'metadata' => $metadata,
        ];
    }

    private function isRemote(ImportSource $source): bool
    {
        return ! $this->hasControlledLocalSource($source)
            && in_array($source->transport, [ImportTransport::Http, ImportTransport::Api], true);
    }

    private function hasControlledLocalSource(ImportSource $source): bool
    {
        return filled(data_get($source->configuration, 'source_path'))
            || filled(data_get($source->configuration, 'sample_path'));
    }

    private function cacheKey(ImportSource $source): string
    {
        return 'import-source-fields:'.$source->getKey();
    }

    private function fingerprint(ImportSource $source): string
    {
        return hash('sha256', implode('|', [
            (string) $source->getKey(),
            $source->transport->value,
            $source->format->value,
            (string) $source->endpoint_url,
            (string) $source->record_path,
        ]));
    }

    private function cachedDiscovery(ImportSource $source): ?array
    {
        $cached = Cache::get($this->cacheKey($source));

        return is_array($cached) && ($cached['fingerprint'] ?? null) === $this->fingerprint($source)
            ? $cached
            : null;
    }
}

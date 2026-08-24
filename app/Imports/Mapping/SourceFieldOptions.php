<?php

namespace App\Imports\Mapping;

use App\Imports\Data\SourceRecord;
use App\Imports\FieldDiscovery;
use App\Imports\ImportReaderResolver;
use App\Imports\LocalSourceLoader;
use App\Models\ImportSource;

class SourceFieldOptions
{
    public function firstRecordFor(ImportSource $source): ?SourceRecord
    {
        try {
            $payload = app(LocalSourceLoader::class)->forSource($source);
        } catch (\Throwable) {
            return null;
        }
        foreach (app(ImportReaderResolver::class)->for($source)->records($source, $payload) as $record) {
            return $record;
        }

        return null;
    }

    /** @return array<string, string> */
    public function for(ImportSource $source): array
    {
        try {
            $payload = app(LocalSourceLoader::class)->forSource($source);
        } catch (\Throwable) {
            return [];
        }
        $records = [];
        foreach (app(ImportReaderResolver::class)->for($source)->records($source, $payload) as $record) {
            $records[] = $record;
            if (count($records) === 5) {
                break;
            }
        }

        return collect(app(FieldDiscovery::class)->discover($records))->mapWithKeys(fn ($field) => [$field->path => "{$field->path} ({$field->type}, {$field->present}×)"])->all();
    }

    /** @return array<string, array{type: string, present: int, samples: array<int, mixed>}> */
    public function metadataFor(ImportSource $source): array
    {
        try {
            $payload = app(LocalSourceLoader::class)->forSource($source);
        } catch (\Throwable) {
            return [];
        }
        $records = [];
        foreach (app(ImportReaderResolver::class)->for($source)->records($source, $payload) as $record) {
            $records[] = $record;
            if (count($records) === 5) {
                break;
            }
        }

        return collect(app(FieldDiscovery::class)->discover($records))->mapWithKeys(fn ($field) => [$field->path => ['type' => $field->type, 'present' => $field->present, 'samples' => array_map(fn ($sample) => str($sample)->limit(120)->toString(), $field->samples)]])->all();
    }
}

<?php

namespace App\Imports\Readers;

use App\Imports\Concerns\ResolvesRecordPaths;
use App\Imports\Contracts\ImportReader;
use App\Imports\Data\SourcePayload;
use App\Imports\Data\SourceRecord;
use App\Imports\Exceptions\InvalidSourceException;
use App\Models\ImportSource;
use JsonException;

class JsonReader implements ImportReader
{
    use ResolvesRecordPaths;

    public function records(ImportSource $source, SourcePayload $payload): iterable
    {
        try {
            $data = json_decode($payload->contents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidSourceException('The JSON import source is malformed.', previous: $exception);
        }

        if (! is_array($data)) {
            throw new InvalidSourceException('The JSON import source must contain an object or array of records.');
        }

        foreach ($this->extractRecords($data, $source->record_path) as $position => $record) {
            yield new SourceRecord($position + 1, $record, $source->record_path);
        }
    }
}

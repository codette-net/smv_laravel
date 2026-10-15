<?php

namespace App\Imports\Readers;

use App\Imports\Contracts\ImportReader;
use App\Imports\Data\SourcePayload;
use App\Imports\Data\SourceRecord;
use App\Imports\Exceptions\InvalidSourceException;
use App\Models\ImportSource;
use League\Csv\Reader;
use League\Csv\SyntaxError;

class CsvReader implements ImportReader
{
    public function records(ImportSource $source, SourcePayload $payload): iterable
    {
        try {
            $reader = Reader::createFromPath($payload->path());
            $reader->setHeaderOffset(0);
            $reader->setDelimiter($this->delimiter($source));
            $header = $reader->getHeader();
        } catch (SyntaxError $exception) {
            throw new InvalidSourceException('The CSV import source has an invalid or duplicate header row.', previous: $exception);
        }

        if ($header === []) {
            throw new InvalidSourceException('The CSV import source requires a header row.');
        }

        foreach ($reader->getRecords() as $position => $record) {
            yield new SourceRecord($position + 1, $record, $source->record_path);
        }
    }

    private function delimiter(ImportSource $source): string
    {
        $delimiter = data_get($source->configuration, 'delimiter', ',');

        if (! is_string($delimiter) || mb_strlen($delimiter) !== 1) {
            throw new InvalidSourceException('The configured CSV delimiter must be a single character.');
        }

        return $delimiter;
    }
}

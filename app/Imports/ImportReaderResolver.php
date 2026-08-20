<?php

namespace App\Imports;

use App\Enums\ImportFormat;
use App\Imports\Contracts\ImportReader;
use App\Imports\Exceptions\UnsupportedImportFormatException;
use App\Imports\Readers\CsvReader;
use App\Imports\Readers\JsonReader;
use App\Imports\Readers\SpreadsheetReader;
use App\Imports\Readers\XmlReader;
use App\Models\ImportSource;

class ImportReaderResolver
{
    public function for(ImportSource|ImportFormat|string $source): ImportReader
    {
        $format = $source instanceof ImportSource ? $source->format : ($source instanceof ImportFormat ? $source : ImportFormat::tryFrom($source));

        if ($format === null) {
            throw new UnsupportedImportFormatException('The configured import format is not supported.');
        }

        return match ($format) {
            ImportFormat::Json => app(JsonReader::class),
            ImportFormat::Xml => app(XmlReader::class),
            ImportFormat::Csv => app(CsvReader::class),
            ImportFormat::Xlsx => app(SpreadsheetReader::class),
        };
    }
}

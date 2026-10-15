<?php

namespace App\Imports\Readers;

use App\Imports\Contracts\ImportReader;
use App\Imports\Data\SourcePayload;
use App\Imports\Data\SourceRecord;
use App\Imports\Exceptions\InvalidSourceException;
use App\Models\ImportSource;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;

class SpreadsheetReader implements ImportReader
{
    public function records(ImportSource $source, SourcePayload $payload): iterable
    {
        $reader = new Reader;

        try {
            $reader->open($payload->path());
            $sheets = $reader->getSheetIterator();
            $sheet = null;
            foreach ($sheets as $firstSheet) {
                $sheet = $firstSheet;
                break;
            }

            if ($sheet === null) {
                throw new InvalidSourceException('The XLSX import source has no worksheet.');
            }

            $headers = null;
            $position = 0;
            foreach ($sheet->getRowIterator() as $row) {
                if ($headers === null) {
                    $headers = array_map(fn (mixed $value): string => trim((string) $value), $row->toArray());

                    if ($headers === [] || in_array('', $headers, true) || count($headers) !== count(array_unique($headers))) {
                        throw new InvalidSourceException('The XLSX import source has an invalid or duplicate header row.');
                    }

                    continue;
                }

                $position++;
                $values = $row->toArray();
                $record = [];
                foreach ($headers as $index => $header) {
                    $record[$header] = $values[$index] ?? null;
                }

                yield new SourceRecord($position, $record, $source->record_path);
            }

            if ($headers === null) {
                throw new InvalidSourceException('The XLSX import source requires a header row.');
            }
        } catch (InvalidSourceException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidSourceException('The XLSX import source could not be read.', previous: $exception);
        } finally {
            $reader->close();
        }
    }
}

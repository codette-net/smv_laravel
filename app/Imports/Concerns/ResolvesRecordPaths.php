<?php

namespace App\Imports\Concerns;

use App\Imports\Exceptions\RecordPathNotFoundException;

trait ResolvesRecordPaths
{
    /** @return list<array<string, mixed>> */
    protected function extractRecords(array $data, ?string $recordPath): array
    {
        if (blank($recordPath)) {
            return array_is_list($data) ? $data : [$data];
        }

        $nodes = [$data];

        foreach (array_filter(explode('.', $recordPath)) as $segment) {
            $next = [];

            foreach ($nodes as $node) {
                if (! is_array($node)) {
                    continue;
                }

                if ($segment === '*') {
                    foreach ($node as $value) {
                        $next[] = $value;
                    }
                } elseif (array_key_exists($segment, $node)) {
                    $next[] = $node[$segment];
                }
            }

            $nodes = $next;
        }

        $records = [];

        foreach ($nodes as $node) {
            if (is_array($node) && array_is_list($node)) {
                foreach ($node as $record) {
                    if (is_array($record)) {
                        $records[] = $record;
                    }
                }
            } elseif (is_array($node)) {
                $records[] = $node;
            }
        }

        if ($records === []) {
            throw new RecordPathNotFoundException("The configured record path [{$recordPath}] did not produce any records.");
        }

        return $records;
    }
}
